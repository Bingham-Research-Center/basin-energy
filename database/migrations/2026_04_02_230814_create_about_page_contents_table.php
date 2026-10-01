<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_page_contents', function (Blueprint $table) {
            $table->id();

            $table->string('section');
            $table->string('item_key')->nullable();

            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();

            $table->string('image')->nullable();

            $table->string('button_text')->nullable();
            $table->string('button_link')->nullable();

            $table->string('icon')->nullable();
            $table->string('value')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['section', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_page_contents');
    }
};