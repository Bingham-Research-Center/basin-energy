<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsurface_leak_chamber_readings', function (Blueprint $table) {
            $table->id();
            $table->string('site_code', 20)->index();
            $table->string('site_type', 100)->nullable()->index();
            $table->dateTime('measured_at')->index();
            $table->double('air_temp_c')->nullable();
            $table->double('relative_humidity_pct')->nullable();
            $table->double('solar_radiation_w_m2')->nullable();
            $table->double('pressure_mbar')->nullable();
            $table->double('wind_speed_m_s')->nullable();
            $table->double('wind_direction_deg')->nullable();
            $table->double('soil_water_pct')->nullable();
            $table->double('soil_temp_c')->nullable();
            $table->double('chm1_ch4')->nullable();
            $table->double('chm2_ch4')->nullable();
            $table->double('chm3_ch4')->nullable();
            $table->double('chm4_ch4')->nullable();
            $table->double('chm5_ch4')->nullable();
            $table->double('chm6_ch4')->nullable();
            $table->double('chm1_co2')->nullable();
            $table->double('chm2_co2')->nullable();
            $table->double('chm3_co2')->nullable();
            $table->double('chm4_co2')->nullable();
            $table->double('chm5_co2')->nullable();
            $table->double('chm6_co2')->nullable();
            $table->string('source_file')->nullable();
            $table->timestamps();
            $table->unique(['site_code', 'measured_at'], 'subsurface_site_time_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsurface_leak_chamber_readings');
    }
};
