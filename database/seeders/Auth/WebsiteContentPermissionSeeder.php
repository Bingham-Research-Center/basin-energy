<?php

namespace Database\Seeders\Auth;

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Support\WebsiteContentPermissions;
use Illuminate\Database\Seeder;
use LogicException;
use Spatie\Permission\PermissionRegistrar;

class WebsiteContentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $parent = Permission::firstOrCreate(
            ['name' => WebsiteContentPermissions::ALL, 'guard_name' => 'web'],
            [
                'type' => User::TYPE_ADMIN,
                'description' => 'All website content edits',
                'parent_id' => null,
                'sort' => 2,
            ]
        );

        if ($parent->type !== User::TYPE_ADMIN || $parent->parent_id !== null) {
            throw new LogicException('Website edit permission group is invalid.');
        }

        foreach (WebsiteContentPermissions::SECTIONS as $section => $label) {
            $permission = Permission::firstOrCreate(
                ['name' => WebsiteContentPermissions::permission($section), 'guard_name' => 'web'],
                [
                    'type' => User::TYPE_ADMIN,
                    'description' => $label,
                    'parent_id' => $parent->id,
                    'sort' => array_search($section, array_keys(WebsiteContentPermissions::SECTIONS), true) + 1,
                ]
            );

            if ($permission->type !== User::TYPE_ADMIN
                || (int) $permission->parent_id !== (int) $parent->id) {
                throw new LogicException('Website edit permission has the wrong type or parent.');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
