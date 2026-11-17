<?php

namespace App\Domains\Auth\Support;

use App\Domains\Auth\Models\User;

/**
 * One destination for shared account-setting actions.
 * Account type, not a user's name or current page, selects the dashboard.
 */
final class AccountNavigation
{
    public static function route(User $user): string
    {
        return $user->isAdmin() ? 'admin.account.index' : 'frontend.user.account';
    }

    public static function url(User $user, string $tab = ''): string
    {
        $url = route(self::route($user));

        return $tab !== '' ? $url.'#'.ltrim($tab, '#') : $url;
    }

    public static function twoFactorView(User $user, string $page): string
    {
        if (! in_array($page, ['enable', 'disable', 'recovery'], true)) {
            throw new \InvalidArgumentException('Invalid two-factor settings page.');
        }

        return ($user->isAdmin()
            ? 'backend.auth.admin.account.tabs.two-factor-authentication.'
            : 'frontend.user.account.tabs.two-factor-authentication.').$page;
    }
}
