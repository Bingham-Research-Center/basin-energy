<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('portfolio_items', 'categories')) {
            return;
        }

        // The original migration discarded the legacy JSON category values.
        // Never destroy imported non-null categories without a documented
        // data mapping into the pivot table.
        if (DB::table('portfolio_items')->whereNotNull('categories')->exists()) {
            throw new \RuntimeException(
                'Refusing to discard portfolio_items.categories: legacy rows contain data. '.
                'Migrate categories into portfolio_item_category explicitly first.'
            );
        }

        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropColumn('categories');
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback of legacy portfolio categories: schema changes require explicit review.'
        );
    }
};