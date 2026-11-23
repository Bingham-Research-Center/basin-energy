<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueObservationIdGasIndexFromCarbonMapperObservations extends Migration
{
    public function up()
    {
        // An imported table may already reflect this historical index drop.
        // Only remove the original named, matching unique constraint.
        if (\App\Support\ExistingCarbonMapperObservationsSchema::hasLegacyUniqueIndex()) {
            Schema::table('carbon_mapper_observations', function (Blueprint $table) {
                $table->dropUnique('carbon_mapper_observations_observation_id_gas_unique');
            });
        }
    }

    public function down()
    {
        // Restoring a uniqueness constraint on imported observations may fail
        // for genuine duplicate identifiers. Never mutate historical data
        // during rollback without an explicit operator decision.
        throw new \RuntimeException(
            'Refusing to re-create Carbon Mapper observation unique index '.
            'during rollback: imported research data may contain duplicates.'
        );
    }
}
