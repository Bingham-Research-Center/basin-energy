<?php

namespace App\Domains\Auth\Http\Controllers\Frontend\Auth;

use App\Domains\Auth\Http\Requests\Frontend\Auth\TwoFactorChallengeRequest;
use App\Domains\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController
{
    public function show(Request $request)
    {
        if (! $request->session()->has('auth.pending_2fa.user_id')) {
            return redirect()->route('frontend.auth.login');
        }

        return view('frontend.auth.two-factor-challenge');
    }

    public function store(TwoFactorChallengeRequest $request)
    {
        $userId = $request->session()->get('auth.pending_2fa.user_id');
        $remember = (bool) $request->session()->get('auth.pending_2fa.remember', false);

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget([
                'auth.pending_2fa.user_id',
                'auth.pending_2fa.remember',
                'auth.2fa_passed',
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('Your login session expired. Please sign in again.'),
                    'redirect' => route('frontend.auth.login'),
                ], 422);
            }

            return redirect()->route('frontend.auth.login')
                ->withFlashDanger(__('Your login session expired. Please sign in again.'));
        }

        Auth::login($user, $remember);

        if ($request->boolean('remember_device')) {
            $user->addSafeDevice($request);
        }

        $request->session()->regenerate();
        $request->session()->put('auth.2fa_passed', true);

        event(new \App\Domains\Auth\Events\User\UserLoggedIn($user));

        $request->session()->forget([
            'auth.pending_2fa.user_id',
            'auth.pending_2fa.remember',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route(homeRoute()),
            ], 200);
        }

        return redirect()->intended(route(homeRoute()))
            ->withFlashSuccess(__('Two-factor authentication confirmed.'));
    }
}