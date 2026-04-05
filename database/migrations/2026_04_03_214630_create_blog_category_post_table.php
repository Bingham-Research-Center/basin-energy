<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_category_post', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('blog_post_id');
            $table->unsignedBigInteger('blog_category_id');

            $table->foreign('blog_post_id', 'bcp_post_fk')
                ->references('id')
                ->on('blog_posts')
                ->onDelete('cascade');

            $table->foreign('blog_category_id', 'bcp_cat_fk')
                ->references('id')
                ->on('blog_categories')
                ->onDelete('cascade');

            $table->unique(['blog_post_id', 'blog_category_id'], 'bcp_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_category_post');
    }
};