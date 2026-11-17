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

        // Keep the flow in the current browser session; Socialite verifies OAuth state.
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(string $provider, Request $request, UserService $userService)
    {
        abort_unless($provider === 'google' && $this->googleAvailable(), 404);

        try {
            $profile = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('Google sign-in could not be completed. Please try again.'));
        }

        $verified = ($profile->user['email_verified'] ?? $profile->user['verified_email'] ?? false) === true;
        $email = mb_strtolower(trim((string) $profile->getEmail()));
        $providerId = (string) $profile->getId();

        if (! $verified || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $providerId === '') {
            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('Google did not provide a verified email address.'));
        }

        // Match by both provider AND immutable provider user ID, never by email alone.
        $user = User::where('provider', 'google')->where('provider_id', $providerId)->first();

        if ($user) {
            if (! $user->isActive()) {
                return redirect()->route('frontend.auth.login')
                    ->withFlashDanger(__('Your account has been deactivated.'));
            }

            return $this->completeLogin($request, $user);
        }

        // A previous local or other-provider account must not be silently
        // attached to Google based solely on an email claim.
        if (User::where('email', $email)->exists()) {
            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('This email is already registered. Sign in using your existing method.'));
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
            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('Your Google sign-up session has expired. Please try again.'));
        }

        return view('frontend.auth.social-google-signup', ['pending' => $pending]);
    }

    public function completeSignup(Request $request, UserService $userService)
    {
        abort_unless(config('boilerplate.access.user.registration') && $this->googleAvailable(), 404);

        $pending = $this->pendingSignup($request);
        if (! $pending) {
            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('Your Google sign-up session has expired. Please try again.'));
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

            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('This account already exists. Please sign in.'));
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

            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('Unable to create your account. Please try again or contact support.'));
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

        return redirect()->route(homeRoute());
    }
}
