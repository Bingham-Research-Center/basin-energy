<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Models\Role;
use App\Domains\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Exactly one role can be selected for new self-registrations.
 *
 * A fixed, single-row setting stores the selected role ID. Existing user
 * assignments are never changed by a setting update.
 */
final class DefaultUserRoleService
{
    private const TABLE = 'default_user_role_settings';

    private const KEY = 'registration';

    public function currentRoleId(): ?int
    {
        $roleId = DB::table(self::TABLE)
            ->where('setting_key', self::KEY)
            ->value('role_id');

        return $roleId === null ? null : (int) $roleId;
    }

    public function roleForRegistration(): ?Role
    {
        $id = $this->currentRoleId();
        if ($id === null) {
            return null;
        }

        // A role whose type/guard changed in the meantime must never grant
        // admin privileges to a newly self-registered User account.
        $role = Role::query()
            ->whereKey($id)
            ->where('type', User::TYPE_USER)
            ->where('guard_name', config('auth.defaults.guard', 'web'))
            ->first();

        return $role && ! $role->isAdmin() ? $role : null;
    }

    /**
     * Switch this role on, replacing any previous default. Clicking the
     * already-selected role switches it off, returning to no default.
     *
     * The settings row is locked to serialize concurrent admin selections.
     */
    public function toggle(int $roleId): ?int
    {
        return DB::transaction(function () use ($roleId) {
            $setting = DB::table(self::TABLE)
                ->where('setting_key', self::KEY)
                ->lockForUpdate()
                ->first();

            if (! $setting) {
                throw new RuntimeException('The default user role setting is missing. Run migrations.');
            }

            $role = Role::query()->whereKey($roleId)->lockForUpdate()->first();

            if (! $role || $role->type !== User::TYPE_USER
                || $role->guard_name !== config('auth.defaults.guard', 'web')
                || $role->isAdmin()) {
                throw ValidationException::withMessages([
                    'role' => __('Only User-type roles can be selected as the default registration role.'),
                ]);
            }

            $selected = $setting->role_id !== null && (int) $setting->role_id === $roleId
                ? null : $roleId;

            DB::table(self::TABLE)
                ->where('setting_key', self::KEY)
                ->update(['role_id' => $selected]);

            return $selected;
        }, 3);
    }

    /**
     * Clear the setting if an admin changes the role to an Administrator type.
     * Executed within the existing RoleService update transaction.
     */
    public function clearIfCurrent(int $roleId): void
    {
        DB::table(self::TABLE)
            ->where('setting_key', self::KEY)
            ->where('role_id', $roleId)
            ->update(['role_id' => null]);
    }
}
