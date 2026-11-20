<?php

namespace App\Domains\Auth\Http\Middleware;

use Carbon\Carbon;
use Closure;

/**
 * Class PasswordExpires.
 */
class PasswordExpires
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return \Illuminate\Http\RedirectResponse|mixed
     *
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        // Google-only accounts have no local password to expire or change.
        // Without this, they are redirected to the password-expired form
        // after 180 days even after successful OAuth authentication.
        // Social accounts WITH a local password keep the normal policy.
        $user = $request->user();
        if ($user && $user->isSocial() && $user->password === null) {
            return $next($request);
        }

        if (is_numeric(config('boilerplate.access.user.password_expires_days'))) {
            $password_changed_at = new Carbon($request->user()->password_changed_at ?? $request->user()->created_at);

            if (now()->diffInDays($password_changed_at) >= config('boilerplate.access.user.password_expires_days')) {
                return redirect()
                    ->route('frontend.auth.password.expired')
                    ->withFlashWarning(__('Your password has expired. We require you to change your password every :days days for security purposes.', [
                        'days' => config('boilerplate.access.user.password_expires_days'),
                    ]));
            }
        }

        return $next($request);
    }
}
