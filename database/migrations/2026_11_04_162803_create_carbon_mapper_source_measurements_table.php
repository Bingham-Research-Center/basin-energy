<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarbonMapperSourceMeasurementsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('carbon_mapper_source_measurements')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_11_04_162803_create_carbon_mapper_source_measurements_table', 'carbon_mapper_source_measurements'
            );

            return;
        }

        Schema::create('carbon_mapper_source_measurements', function (Blueprint $table) {
            $table->id();

            // Link each measurement to its Carbon Mapper plume.
            $table->string('plume_id')->unique();

            // Source information.
            $table->string('source_name')->nullable();
            $table->string('gas', 10)->default('CH4');

            // Emission measurements.
            $table->double('emission_rate')->nullable();
            $table->double('emission_uncertainty')->nullable();
            $table->string('emission_unit')->nullable();

            // Source statistics.
            $table->double('persistence')->nullable();
            $table->unsignedInteger('plume_count')->nullable();
            $table->unsignedInteger('detection_date_count')->nullable();
            $table->unsignedInteger('observation_scene_count')->nullable();
            $table->unsignedInteger('observation_date_count')->nullable();

            // When the data was last successfully retrieved.
            $table->timestamp('last_fetched_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        throw new \RuntimeException(
            'Refusing rollback: carbon_mapper_source_measurements may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
}
