<?php

namespace App\Domains\Auth\Http\Controllers\Frontend\Auth;

use App\Domains\Auth\Events\User\UserLoggedIn;
use App\Domains\Auth\Services\UserService;
use Laravel\Socialite\Facades\Socialite;

/**
 * Class SocialController.
 */
class SocialController
{
    /**
     * @param $provider
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * @param $provider
     * @param  UserService  $userService
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \App\Exceptions\GeneralException
     */
    public function callback($provider, UserService $userService)
    {
        $user = $userService->registerProvider(Socialite::driver($provider)->user(), $provider);

        if (! $user->isActive()) {
            auth()->logout();

            return redirect()->route('frontend.auth.login')->withFlashDanger(__('Your account has been deactivated.'));
        }

        $hasTwoFactor = method_exists($user, 'hasTwoFactorEnabled') && $user->hasTwoFactorEnabled();

        if ($hasTwoFactor) {
            // Match the password-login challenge flow: keep the user logged out
            // until a valid second factor has been verified.
            session()->put('auth.pending_2fa.user_id', $user->id);
            session()->put('auth.pending_2fa.remember', false);
            session()->forget('auth.2fa_passed');
            session()->regenerate();

            return redirect()->route('frontend.auth.2fa.challenge');
        }

        auth()->login($user);
        session()->regenerate();
        session()->put('auth.2fa_passed', true);
        event(new UserLoggedIn($user));

        return redirect()->route(homeRoute());
    }
}
