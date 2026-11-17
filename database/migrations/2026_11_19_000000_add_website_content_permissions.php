<?php

use Database\Seeders\Auth\WebsiteContentPermissionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Only add permissions: never delete or reassign live roles or users.
        app(WebsiteContentPermissionSeeder::class)->run();
    }

    public function down(): void
    {
        // Deliberately non-destructive: permissions may already be assigned
        // to roles on a running BasinEnergy installation.
    }
};
