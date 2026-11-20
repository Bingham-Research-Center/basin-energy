<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One fixed setting row holds a nullable role ID. Unlike per-role
        // booleans, this structurally prevents two simultaneous defaults.
        Schema::create('default_user_role_settings', function (Blueprint $table) {
            $table->string('setting_key', 50)->primary();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')
                ->references('id')
                ->on(config('permission.table_names.roles'))
                ->nullOnDelete();
        });

        // No role is assigned automatically until an administrator selects
        // a User-type role. Production may have custom role names.
        DB::table('default_user_role_settings')->insert([
            'setting_key' => 'registration',
            'role_id' => null,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('default_user_role_settings');
    }
};
