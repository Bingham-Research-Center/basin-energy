<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_posts')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_214557_create_blog_categories_table', 'blog_posts'
            );

            return;
        }

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('author_name')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: blog_posts may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};