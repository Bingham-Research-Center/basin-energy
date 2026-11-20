<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('sync_states')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_02_07_002933_create_sync_states_table', 'sync_states'
            );

            return;
        }

        Schema::create('sync_states', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        throw new \RuntimeException(
            'Refusing rollback: sync_states may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
}
