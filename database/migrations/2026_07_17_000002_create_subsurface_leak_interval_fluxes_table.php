<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subsurface_leak_interval_fluxes', function (Blueprint $table) {
            $table->id();
            $table->string('facility', 150)->nullable()->index();
            $table->string('well_id', 100)->nullable()->index();
            $table->string('paper_id', 50)->nullable()->index();
            $table->double('distance_from_wellhead_m')->nullable();
            $table->string('direction_from_wellhead', 30)->nullable();
            $table->dateTime('started_at')->nullable()->index();
            $table->dateTime('ended_at')->nullable();
            $table->double('duration_min')->nullable();
            $table->double('ch4_flux')->nullable()->index();
            $table->double('ch4_confidence_95')->nullable();
            $table->double('co2_flux')->nullable();
            $table->double('co2_confidence_95')->nullable();
            $table->double('soil_water_pct')->nullable();
            $table->double('soil_temp_c')->nullable();
            $table->double('air_temp_c')->nullable();
            $table->double('relative_humidity_pct')->nullable();
            $table->double('pressure_mbar')->nullable();
            $table->double('solar_radiation_w_m2')->nullable();
            $table->double('wind_speed_m_s')->nullable();
            $table->double('wind_direction_deg')->nullable();
            $table->double('tnmhc_flux')->nullable();
            $table->double('alkanes_flux')->nullable();
            $table->double('alkenes_flux')->nullable();
            $table->double('aromatics_flux')->nullable();
            $table->double('alcohols_flux')->nullable();
            $table->json('compound_fluxes')->nullable();
            $table->string('source_file')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsurface_leak_interval_fluxes');
    }
};
