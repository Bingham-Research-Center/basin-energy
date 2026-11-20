<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('test_sensor_readings')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_05_002933_create_test_sensor_readings_table', 'test_sensor_readings'
            );

            return;
        }

        Schema::create('test_sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->nullable();
            $table->float('temperature');
            $table->float('humidity');
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: test_sensor_readings may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};