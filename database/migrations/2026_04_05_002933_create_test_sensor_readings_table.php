<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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
        Schema::dropIfExists('test_sensor_readings');
    }
};