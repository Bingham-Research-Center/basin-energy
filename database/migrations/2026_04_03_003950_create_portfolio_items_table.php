<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_items')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_003950_create_portfolio_items_table', 'portfolio_items'
            );

            return;
        }

        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: portfolio_items may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};
