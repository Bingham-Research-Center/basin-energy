<?php

namespace App\Domains\Auth\Http\Middleware;

use Closure;

/**
 * Class TwoFactorAuthenticationStatus.
 */
class TwoFactorAuthenticationStatus
{
    /**
     * @param $request
     * @param  Closure  $next
     * @param  string  $status
     * @return mixed
     */
    public function handle($request, Closure $next, $status = 'enabled')
    {
        if (! in_array($status, ['enabled', 'disabled'])) {
            abort(404);
        }

        // If the backend does not require 2FA then continue
        if ($status === 'enabled' && $request->is('admin*') && ! config('boilerplate.access.user.admin_requires_2fa')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('frontend.auth.login');
        }

        $twoFactor = $user->twoFactorAuth;
        $hasTwoFactorEnabled = $twoFactor && $twoFactor->enabled_at !== null;

        if (
            ($status === 'enabled' && ! $hasTwoFactorEnabled) ||
            ($status === 'disabled' && $hasTwoFactorEnabled)
        ) {
            return redirect()
                ->route('frontend.user.account')
                ->withFlashDanger(__('Two-factor Authentication must be :status to view this page.', ['status' => $status]));
        }

        return $next($request);
    }
}