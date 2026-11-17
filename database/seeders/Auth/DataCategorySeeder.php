<?php

namespace Database\Seeders\Auth;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\User;
use Illuminate\Database\Seeder;
use LogicException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Data-access permissions only. Role names and memberships are chosen by admins.
 */
class DataCategorySeeder extends Seeder
{
    public const CATEGORIES = [
        'public' => 'Public',
        'research' => 'Research only',
        'classified' => 'Classified',
        'industrial' => 'Industrial',
        'realtime' => 'Real-time',
        'weather' => 'Weather',
    ];

    public function run(): void
    {
        $group = Permission::firstOrCreate(
            ['name' => 'user.data', 'guard_name' => 'web'],
            [
                'type' => User::TYPE_USER,
                'description' => 'Data Categories',
                'parent_id' => null,
                'sort' => 1,
            ]
        );

        if ($group->type !== User::TYPE_USER || $group->parent_id !== null) {
            throw new LogicException('The Data Categories permission group is invalid.');
        }

        foreach (self::CATEGORIES as $index => $label) {
            $permission = Permission::firstOrCreate(
                ['name' => 'user.data.'.$index, 'guard_name' => 'web'],
                [
                    'type' => User::TYPE_USER,
                    'description' => $label,
                    'parent_id' => $group->id,
                    'sort' => array_search($index, array_keys(self::CATEGORIES), true) + 1,
                ]
            );

            if ($permission->type !== User::TYPE_USER || (int) $permission->parent_id !== (int) $group->id) {
                throw new LogicException('An existing data-category permission has the wrong type or parent.');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
