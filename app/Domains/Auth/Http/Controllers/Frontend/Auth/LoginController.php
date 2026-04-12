<?php

namespace App\Domains\Auth\Http\Controllers\Frontend\Auth;

use App\Domains\Auth\Events\User\UserLoggedIn;
use App\Rules\Captcha;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class LoginController.
 */
class LoginController
{
    use AuthenticatesUsers;

    public function redirectPath()
    {
        return route(homeRoute());
    }

    public function showLoginForm()
    {
        return view('frontend.auth.login');
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => ['required', 'max:255', 'string'],
            'password' => ['required', 'string', 'max:100'],
            'g-recaptcha-response' => ['required_if:captcha_status,true', new Captcha],
        ], [
            'g-recaptcha-response.required_if' => __('validation.required', ['attribute' => 'captcha']),
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        try {
            return $this->guard()->attempt(
                $this->credentials($request),
                $request->filled('remember')
            );
        } catch (HttpResponseException $exception) {
            $this->incrementLoginAttempts($request);
            throw $exception;
        }
    }

    protected function authenticated(Request $request, $user)
    {
        if (! $user->isActive()) {
            auth()->logout();

            throw ValidationException::withMessages([
                $this->username() => [__('Your account has been deactivated.')],
            ]);
        }
    }

    protected function finalizeSuccessfulLogin(Request $request, $user, bool $canLogoutOtherDevices = true)
    {
        event(new UserLoggedIn($user));

        if ($canLogoutOtherDevices && config('boilerplate.access.user.single_login')) {
            auth()->logoutOtherDevices($request->password);
        }
    }

    protected function sendLoginResponse(Request $request)
    {
        $this->clearLoginAttempts($request);

        $user = $this->guard()->user();

        $this->authenticated($request, $user);

        $hasTwoFactor = method_exists($user, 'hasTwoFactorEnabled') && $user->hasTwoFactorEnabled();

        if ($hasTwoFactor) {
            if ($user->isSafeDevice($request)) {
                $request->session()->regenerate();
                $request->session()->put('auth.2fa_passed', true);

                $user->setTwoFactorBypassedBySafeDevice(true);

                $this->finalizeSuccessfulLogin($request, $user);

                return response()->json([
                    'redirect' => $this->redirectPath(),
                ], 200);
            }

            $remember = $request->boolean('remember');

            $request->session()->put('auth.pending_2fa.user_id', $user->id);
            $request->session()->put('auth.pending_2fa.remember', $remember);
            $request->session()->forget('auth.2fa_passed');

            $this->guard()->logout();
            $request->session()->regenerate();

            return response()->json([
                'requires_two_factor' => true,
                'redirect' => route('frontend.auth.2fa.challenge'),
                'csrf_token' => csrf_token(),
            ], 200);
        }

        $request->session()->regenerate();
        $request->session()->put('auth.2fa_passed', true);

        $this->finalizeSuccessfulLogin($request, $user);

        return response()->json([
            'redirect' => $this->redirectPath(),
        ], 200);
    }
}