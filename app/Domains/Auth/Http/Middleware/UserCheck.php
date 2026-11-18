<?php

namespace App\Domains\Auth\Http\Middleware;

use App\Domains\Auth\Models\User;
use Closure;

/**
 * Class UserCheck.
 */
class UserCheck
{
    /**
     * @param $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user() && $request->user()->isType(User::TYPE_USER)) {
            return $next($request);
        }

        // An Administrator must not access User-only data or mutate User forms.
        if ($request->user() && $request->user()->isType(User::TYPE_ADMIN)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => __('You do not have access to do that.'),
                ], 403);
            }

            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                abort(403);
            }

            return redirect()->route('admin.dashboard')
                ->withFlashDanger(__('You do not have access to do that.'));
        }

        return redirect()->route('frontend.index')
            ->withFlashDanger(__('You do not have access to do that.'));
    }
}
