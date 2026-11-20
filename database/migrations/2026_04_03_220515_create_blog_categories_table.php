<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_categories')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_220515_create_blog_categories_table', 'blog_categories'
            );

            return;
        }

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('blog_category_post')) {
            Schema::table('blog_category_post', function (Blueprint $table) {
                $table->foreign('blog_category_id', 'bcp_cat_fk')
                    ->references('id')
                    ->on('blog_categories')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: blog_categories may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};
