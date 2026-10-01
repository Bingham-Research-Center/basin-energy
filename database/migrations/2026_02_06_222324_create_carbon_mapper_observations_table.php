<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarbonMapperObservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carbon_mapper_observations', function (Blueprint $table) {
            $table->id();

            // Source / identity
            $table->string('observation_id')->nullable()->index();
            $table->string('gas', 10)->index(); // CH4, CO2
            $table->string('satellite')->nullable();
            $table->string('instrument')->nullable();

            // Location
            $table->decimal('latitude', 10, 7)->index();
            $table->decimal('longitude', 10, 7)->index();
            $table->string('country')->nullable()->index();
            $table->string('region')->nullable()->index();

            // Emissions
            $table->float('emission_rate')->nullable(); // kg/hr or similar
            $table->string('emission_unit')->nullable();
            $table->float('confidence')->nullable(); // 0–1 or %
            $table->boolean('is_super_emitter')->default(true);

            // Time
            $table->timestamp('observed_at')->nullable()->index();

            // Raw upstream data (future-proofing)
            $table->json('raw_payload');

            $table->timestamps();

            // Avoid duplicates if Carbon Mapper provides a stable ID
            $table->unique(['observation_id', 'gas']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carbon_mapper_observations');
    }
}
