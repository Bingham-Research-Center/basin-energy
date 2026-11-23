<?php

namespace App\Domains\Auth\Http\Controllers\Frontend\Auth;

use App\Domains\Auth\Events\User\UserLoggedIn;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialController
{
    private const PENDING_SIGNUP = 'auth.google.pending_signup';
    private const SIGNUP_LIFETIME = 600;
    private const POPUP_SESSION_KEY = 'auth.google.popup_started_at';
    private const POPUP_LIFETIME = 900;

    private function googleAvailable(): bool
    {
        return config('services.google.active')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    public function redirect(string $provider, Request $request)
    {
        abort_unless($provider === 'google' && $this->googleAvailable(), 404);

        // Retain this marker through OAuth, Terms acceptance and optional 2FA.
        // The session cookie is shared by the opener tab and the popup window.
        if ($request->query('popup') === '1') {
            $request->session()->put(self::POPUP_SESSION_KEY, time());
        } else {
            $request->session()->forget(self::POPUP_SESSION_KEY);
        }

        // Keep the flow in the current browser session; Socialite verifies OAuth state.
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(string $provider, Request $request, UserService $userService)
    {
        abort_unless($provider === 'google' && $this->googleAvailable(), 404);

        // Google reports declined access as ?error=access_denied (no code).
        // Do not attempt a token exchange for a declined authorization.
        if ($request->query('error') === 'access_denied') {
            return $this->failedLogin($request, __('Google authorization was cancelled. Please try again.'));
        }

        try {
            $profile = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return $this->failedLogin($request, __('Google sign-in could not be completed. Please try again.'));
        }

        // Google's userinfo typically returns a JSON boolean, but some
        // Socialite-compatible responses represent it as "true" or "1".
        // Keep requiring an affirmative claim; never infer it from an email.
        $verifiedClaim = $profile->user['email_verified'] ?? $profile->user['verified_email'] ?? false;
        $verified = filter_var($verifiedClaim, FILTER_VALIDATE_BOOLEAN);
        $email = mb_strtolower(trim((string) $profile->getEmail()));
        $providerId = (string) $profile->getId();

        if (! $verified || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $providerId === '') {
            return $this->failedLogin($request, __('Google did not provide a verified email address.'));
        }

        // Match by both provider AND immutable provider user ID, never by email alone.
        $user = User::where('provider', 'google')->where('provider_id', $providerId)->first();

        if ($user) {
            if (! $user->isActive()) {
                return $this->failedLogin($request, __('Your account has been deactivated.'));
            }

            return $this->completeLogin($request, $user);
        }

        // A previous local or other-provider account must not be silently
        // attached to Google based solely on an email claim.
        if (User::where('email', $email)->exists()) {
            return $this->failedLogin($request, __('This email is already registered. Sign in using your existing method.'));
        }

        abort_unless(config('boilerplate.access.user.registration'), 404);

        $request->session()->put(self::PENDING_SIGNUP, [
            'id' => $providerId,
            'name' => trim((string) $profile->getName()) ?: $email,
            'email' => $email,
            'expires_at' => time() + self::SIGNUP_LIFETIME,
        ]);

        return redirect()->route('frontend.auth.social.signup');
    }

    public function showSignup(Request $request)
    {
        abort_unless(config('boilerplate.access.user.registration') && $this->googleAvailable(), 404);

        $pending = $this->pendingSignup($request);
        if (! $pending) {
            return $this->failedLogin($request, __('Your Google sign-up session has expired. Please try again.'));
        }

        return view('frontend.auth.social-google-signup', ['pending' => $pending]);
    }

    public function completeSignup(Request $request, UserService $userService)
    {
        abort_unless(config('boilerplate.access.user.registration') && $this->googleAvailable(), 404);

        $pending = $this->pendingSignup($request);
        if (! $pending) {
            return $this->failedLogin($request, __('Your Google sign-up session has expired. Please try again.'));
        }

        $request->validate([
            'terms' => ['required', 'accepted'],
        ], [
            'terms.accepted' => __('You must accept the Terms & Conditions.'),
        ]);

        // Check again on submission; another registration may have taken place.
        if (User::where('email', $pending['email'])->exists()
            || User::where('provider', 'google')->where('provider_id', $pending['id'])->exists()) {
            $request->session()->forget(self::PENDING_SIGNUP);

            return $this->failedLogin($request, __('This account already exists. Please sign in.'));
        }

        try {
            $profile = (object) [
                'id' => $pending['id'],
                'name' => $pending['name'],
                'email' => $pending['email'],
            ];
            $user = $userService->registerProvider($profile, 'google');
        } catch (Throwable $e) {
            report($e);

            return $this->failedLogin($request, __('Unable to create your account. Please try again or contact support.'));
        }

        $request->session()->forget(self::PENDING_SIGNUP);

        return $this->completeLogin($request, $user);
    }

    private function pendingSignup(Request $request): ?array
    {
        $pending = $request->session()->get(self::PENDING_SIGNUP);
        if (! is_array($pending)
            || empty($pending['id'])
            || empty($pending['email'])
            || empty($pending['name'])
            || ($pending['expires_at'] ?? 0) < time()) {
            $request->session()->forget(self::PENDING_SIGNUP);
            return null;
        }

        return $pending;
    }

    private function completeLogin(Request $request, User $user)
    {
        // Re-check here as the pending Terms flow is separate from the
        // callback and the account may have been disabled in the meantime.
        if (! $user->isActive()) {
            return $this->failedLogin($request, __('Your account has been deactivated.'));
        }

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('auth.pending_2fa.user_id', $user->id);
            $request->session()->put('auth.pending_2fa.remember', false);
            $request->session()->forget('auth.2fa_passed');
            $request->session()->regenerate();

            return redirect()->route('frontend.auth.2fa.challenge');
        }

        auth()->login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.2fa_passed', true);
        event(new UserLoggedIn($user));

        if ($this->isPopupFlow($request)) {
            return $this->popupResult(true, __('Google sign-in is complete.'));
        }

        return redirect()->route(homeRoute());
    }

    public function popupStatus(Request $request)
    {
        abort_unless($this->googleAvailable(), 404);

        // A postMessage is only a hint. The opener polls this endpoint
        // using the shared, server-side authenticated session.
        $user = $request->user();
        $complete = $this->isPopupFlow($request)
            && $user
            && $user->isActive()
            && (! $user->hasTwoFactorEnabled()
                || $request->session()->get('auth.2fa_passed') === true);

        return response()->json([
            'complete' => (bool) $complete,
            'redirect' => $complete ? route(homeRoute()) : null,
        ])->header('Cache-Control', 'no-store, private');
    }

    private function isPopupFlow(Request $request): bool
    {
        $started = $request->session()->get(self::POPUP_SESSION_KEY);

        return is_numeric($started)
            && time() >= (int) $started
            && time() - (int) $started <= self::POPUP_LIFETIME;
    }

    private function failedLogin(Request $request, string $message)
    {
        if ($this->isPopupFlow($request)) {
            return $this->popupResult(false, $message);
        }

        return redirect()->route('frontend.auth.login')->withFlashDanger($message);
    }

    private function popupResult(bool $success, string $message)
    {
        return response()
            ->view('frontend.auth.social-google-popup-result', [
                'success' => $success,
                'message' => $message,
                'redirectUrl' => route(homeRoute()),
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}
