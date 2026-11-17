<?php

use App\Domains\Auth\Models\Permission;
use App\Domains\Auth\Models\User;
use Database\Seeders\Auth\DataCategorySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Add data-category permissions; retire previous audience permissions only.
 * Existing roles, users, and their role assignments are deliberately preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            app(DataCategorySeeder::class)->run();

            $oldNames = [
                'user.audience.normal',
                'user.audience.student',
                'user.audience.researcher',
                'user.audience.industry',
                'user.audience.stakeholder',
                'user.audience.legislative',
            ];

            Permission::query()
                ->where('type', User::TYPE_USER)
                ->where('guard_name', 'web')
                ->whereIn('name', $oldNames)
                ->get()
                ->each(fn (Permission $permission) => $permission->delete());

            // Do not cascade-delete any unexpected permissions added by an admin.
            $oldGroup = Permission::query()
                ->where('type', User::TYPE_USER)
                ->where('guard_name', 'web')
                ->where('name', 'user.audience')
                ->first();

            if ($oldGroup && ! $oldGroup->children()->exists()) {
                $oldGroup->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No destructive rollback: permissions may now be assigned to live roles.
        // The original audience migration remains in history for compatibility.
    }
};
