<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortfolioCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('portfolio_categories')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_010040_create_portfolio_categories_table', 'portfolio_categories'
            );

            return;
        }

        Schema::create('portfolio_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
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
            'Refusing rollback: portfolio_categories may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
}
