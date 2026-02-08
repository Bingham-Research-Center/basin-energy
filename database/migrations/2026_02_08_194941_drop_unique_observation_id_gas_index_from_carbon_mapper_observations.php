<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueObservationIdGasIndexFromCarbonMapperObservations extends Migration
{
    public function up()
    {
        Schema::table('carbon_mapper_observations', function (Blueprint $table) {
            $table->dropUnique('carbon_mapper_observations_observation_id_gas_unique');
        });
    }

    public function down()
    {
        Schema::table('carbon_mapper_observations', function (Blueprint $table) {
            $table->unique(['observation_id', 'gas']);
        });
    }
}
