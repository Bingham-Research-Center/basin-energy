<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_item_category')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_062100_create_portfolio_item_category_table', 'portfolio_item_category'
            );

            return;
        }

        Schema::create('portfolio_item_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_category_id')->constrained()->cascadeOnDelete();

            $table->unique(
                ['portfolio_item_id', 'portfolio_category_id'],
                'pic_item_cat_unique'
            );
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: portfolio_item_category may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};