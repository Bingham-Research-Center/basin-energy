<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_category_post')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_214630_create_blog_category_post_table', 'blog_category_post'
            );

            return;
        }

        Schema::create('blog_category_post', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('blog_post_id');
            $table->unsignedBigInteger('blog_category_id');

            $table->foreign('blog_post_id', 'bcp_post_fk')
                ->references('id')
                ->on('blog_posts')
                ->onDelete('cascade');

            $table->unique(
                ['blog_post_id', 'blog_category_id'],
                'bcp_unique'
            );
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: blog_category_post may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};
