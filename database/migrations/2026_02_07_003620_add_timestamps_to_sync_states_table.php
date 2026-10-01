<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampsToSyncStatesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('sync_states', 'created_at') ||
            ! Schema::hasColumn('sync_states', 'updated_at')) {

            Schema::table('sync_states', function (Blueprint $table) {
                if (! Schema::hasColumn('sync_states', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }

                if (! Schema::hasColumn('sync_states', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    public function down()
    {
        // Intentionally left empty because the columns may have been
        // created by the original sync_states migration.
    }
}
