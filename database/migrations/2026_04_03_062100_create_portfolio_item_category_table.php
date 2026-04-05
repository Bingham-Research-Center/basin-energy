<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        Schema::dropIfExists('portfolio_item_category');
    }
};