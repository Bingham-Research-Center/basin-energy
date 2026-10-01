<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTwoFactorChallenged
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if (
            $request->routeIs('frontend.auth.2fa.challenge') ||
            $request->routeIs('frontend.auth.2fa.challenge.store') ||
            $request->routeIs('frontend.auth.logout')
        ) {
            return $next($request);
        }

        $user = $request->user();

        if (! method_exists($user, 'hasTwoFactorEnabled') || ! $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->session()->get('auth.2fa_passed') === true) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('frontend.auth.2fa.challenge'),
            ], 423);
        }

        return redirect()->route('frontend.auth.2fa.challenge');
    }
}