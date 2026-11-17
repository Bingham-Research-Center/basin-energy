<?php

namespace App\Domains\Auth\Support;

use App\Domains\Auth\Models\User;

/**
 * Permission keys and access rules for the six website-editing workstreams.
 *
 * The "All website content edits" parent permission is a real broad grant;
 * an individual child permission authorizes only its matching workstream.
 */
final class WebsiteContentPermissions
{
    public const ALL = 'admin.website.edit';

    public const SECTIONS = [
        'home' => 'Home',
        'about' => 'About',
        'service' => 'Service',
        'portfolio' => 'Portfolio',
        'blog' => 'Blog',
        'contact' => 'Contacts',
    ];

    public static function permission(string $section): string
    {
        if (! array_key_exists($section, self::SECTIONS)) {
            throw new \InvalidArgumentException('Unknown website section: '.$section);
        }

        return self::ALL.'.'.$section;
    }

    public static function middlewareFor(string $section): string
    {
        return 'permission:'.self::ALL.'|'.self::permission($section);
    }

    public static function middlewareForAny(): string
    {
        return 'permission:'.implode('|', array_merge(
            [self::ALL],
            array_map([self::class, 'permission'], array_keys(self::SECTIONS))
        ));
    }

    public static function canEdit(User $user, string $section): bool
    {
        return $user->can(self::ALL) || $user->can(self::permission($section));
    }

    public static function canEditAny(User $user): bool
    {
        if ($user->can(self::ALL)) {
            return true;
        }

        foreach (array_keys(self::SECTIONS) as $section) {
            if (self::canEdit($user, $section)) {
                return true;
            }
        }

        return false;
    }
}
