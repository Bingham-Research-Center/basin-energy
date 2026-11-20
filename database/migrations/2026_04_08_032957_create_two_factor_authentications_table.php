<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laragear\TwoFactor\Models\TwoFactorAuthentication;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('two_factor_authentications')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2021_04_05_153840_create_two_factor_authentications_table',
                'two_factor_authentications'
            );

            return;
        }

        if (! Schema::hasTable('two_factor_authentications')) {
            TwoFactorAuthentication::migration(function (Blueprint $table) {
                // Custom columns can be added here if needed.
            });
        }
    }

    public function down(): void
    {
        // The table may have been created by the original 2021 migration.
    }
};
