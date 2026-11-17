<?php

namespace App\Domains\Auth\Http\Controllers\Frontend\Auth;

use App\Domains\Auth\Http\Requests\Frontend\Auth\DisableTwoFactorAuthenticationRequest;
use App\Domains\Auth\Support\AccountNavigation;
use Illuminate\Http\Request;

/**
 * Class DisableTwoFactorAuthenticationController.
 */
class DisableTwoFactorAuthenticationController
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request)
    {
        return view(AccountNavigation::twoFactorView($request->user(), 'disable'));
    }

    /**
     * @param  DisableTwoFactorAuthenticationRequest  $request
     * @return mixed
     */
    public function destroy(DisableTwoFactorAuthenticationRequest $request)
    {
        $request->user()->disableTwoFactorAuth();
        $request->session()->forget('auth.2fa_passed');

        return redirect()->to(AccountNavigation::url($request->user(), 'two-factor-authentication'))
            ->withFlashSuccess(__('Two Factor Authentication Successfully Disabled'));
    }
}
