<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsurface_leak_surveys', function (Blueprint $table) {
            $table->id();
            $table->date('flux_sample_date')->nullable()->index();
            $table->date('soil_gas_sample_date')->nullable();
            $table->string('sample_month', 30)->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('well_id', 100)->nullable()->index();
            $table->string('completion_decade', 30)->nullable();
            $table->string('well_type', 100)->nullable()->index();
            $table->string('well_status', 100)->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->double('flux_distance_m')->nullable();
            $table->string('flux_direction', 30)->nullable();
            $table->double('soil_gas_distance_m')->nullable();
            $table->string('soil_gas_direction', 30)->nullable();
            $table->double('total_combustible_soil_gas')->nullable();
            $table->time('flux_start')->nullable();
            $table->time('flux_end')->nullable();
            $table->double('flux_duration_min')->nullable();
            $table->double('ch4_flux')->nullable()->index();
            $table->double('co2_flux')->nullable();
            $table->double('tnmhc_flux')->nullable();
            $table->double('alkanes_flux')->nullable();
            $table->double('alkenes_flux')->nullable();
            $table->double('aromatics_flux')->nullable();
            $table->double('ambient_temp_c')->nullable();
            $table->double('relative_humidity_pct')->nullable();
            $table->double('dewpoint_c')->nullable();
            $table->double('solar_radiation_w_m2')->nullable();
            $table->double('pressure_mbar')->nullable();
            $table->double('wind_speed_m_s')->nullable();
            $table->double('wind_direction_deg')->nullable();
            $table->double('wind_direction_sd_deg')->nullable();
            $table->double('soil_water_content')->nullable();
            $table->double('soil_temp_c')->nullable();
            $table->json('compound_fluxes')->nullable();
            $table->string('source_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsurface_leak_surveys');
    }
};
