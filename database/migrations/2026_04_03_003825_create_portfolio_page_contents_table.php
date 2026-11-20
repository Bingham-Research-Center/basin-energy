<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_page_contents')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_003825_create_portfolio_page_contents_table', 'portfolio_page_contents'
            );

            return;
        }

        Schema::create('portfolio_page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->string('item_key')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();
            $table->json('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['section', 'item_key']);
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: portfolio_page_contents may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};