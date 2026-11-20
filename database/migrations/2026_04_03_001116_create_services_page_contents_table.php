<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('services_page_contents')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_001116_create_services_page_contents_table', 'services_page_contents'
            );

            return;
        }

        Schema::create('services_page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->string('item_key')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
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
            'Refusing rollback: services_page_contents may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};