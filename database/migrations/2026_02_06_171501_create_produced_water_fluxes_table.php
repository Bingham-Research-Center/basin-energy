<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProducedWaterFluxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
          Schema::create('produced_water_fluxes', function (Blueprint $table) {
            $table->id();

            // Identifiers
            $table->string('unique_id')->index();
            $table->integer('can_set')->nullable();

            // Timing
            $table->dateTime('datetime_start')->nullable();
            $table->dateTime('datetime_end')->nullable();
            $table->decimal('duration_min', 6, 2)->nullable();

            // Major fluxes (mg/m2/hr)
            $table->decimal('ch4_flux', 10, 4)->nullable();
            $table->decimal('co2_flux', 10, 4)->nullable();

            // Aggregate organic groups
            $table->decimal('tnmhc', 10, 4)->nullable();
            $table->decimal('alkanes', 10, 4)->nullable();
            $table->decimal('alkenes', 10, 4)->nullable();
            $table->decimal('aromatics', 10, 4)->nullable();
            $table->decimal('alcohols', 10, 4)->nullable();
            $table->decimal('carbonyls', 10, 4)->nullable();

            // Individual species (C2–C12, alcohols, carbonyls)
            $table->json('species_fluxes')->nullable();

            // Context
            $table->string('facility_id')->nullable();
            $table->string('pond_type')->nullable();
            $table->string('state', 20)->nullable();

            // Raw vs wind-corrected
            $table->boolean('wind_corrected')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            // Common query patterns
            $table->index(['state', 'facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produced_water_fluxes');
    }
}
