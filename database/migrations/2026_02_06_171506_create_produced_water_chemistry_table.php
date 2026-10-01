<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProducedWaterChemistryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produced_water_chemistry', function (Blueprint $table) {
            $table->id();

            // Link back to flux measurements
            $table->string('unique_id_flux')->nullable()->index();
            $table->string('unique_id_water')->nullable()->index();

            // Sampling time
            $table->date('sample_date')->nullable();
            $table->time('sample_time')->nullable();

            // Water quality
            $table->decimal('temperature', 6, 2)->nullable();
            $table->decimal('ph', 4, 2)->nullable();
            $table->decimal('tds', 10, 2)->nullable();
            $table->decimal('turbidity', 10, 2)->nullable();

            // Carbon metrics
            $table->decimal('toc', 10, 2)->nullable();
            $table->decimal('tc', 10, 2)->nullable();
            $table->decimal('ic', 10, 2)->nullable();

            // Redox / biology
            $table->decimal('orp', 10, 2)->nullable();
            $table->decimal('odo', 6, 2)->nullable();
            $table->decimal('mpn', 12, 2)->nullable();

            // Dissolved hydrocarbons, alcohols, carbonyls
            $table->json('dissolved_compounds')->nullable();

            // Context
            $table->string('facility_id')->nullable();
            $table->string('state', 20)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

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
        Schema::dropIfExists('produced_water_chemistry');
    }
}
