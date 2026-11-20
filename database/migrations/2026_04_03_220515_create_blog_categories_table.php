<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_categories')) {
            \App\Support\LegacyMigrationSchemaInspector::assertCompatible(
                '2026_04_03_220515_create_blog_categories_table', 'blog_categories'
            );

        } else {
            Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        // The pivot was historically created before blog_categories. The
        // category FK may already exist when adopting a historical import.
        if (Schema::hasTable('blog_category_post') && ! $this->hasCategoryForeignKey()) {
            if (DB::table('blog_category_post as bcp')
                ->leftJoin('blog_categories as bc', 'bc.id', '=', 'bcp.blog_category_id')
                ->whereNull('bc.id')->exists()) {
                throw new \RuntimeException(
                    'Cannot add bcp_cat_fk: blog_category_post contains orphaned category references. '.
                    'No rows were changed; inspect and repair those references explicitly.'
                );
            }
            Schema::table('blog_category_post', function (Blueprint $table) {
                $table->foreign('blog_category_id', 'bcp_cat_fk')
                    ->references('id')
                    ->on('blog_categories')
                    ->onDelete('cascade');
            });
        }
    }

    private function hasCategoryForeignKey(): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.KEY_COLUMN_USAGE '.
                'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '.
                'AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ? '.
                'AND REFERENCED_COLUMN_NAME = ? LIMIT 1',
                ['blog_category_post', 'blog_category_id', 'blog_categories', 'id']
            ) !== null;
        }

        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::select("PRAGMA foreign_key_list('blog_category_post')") as $row) {
                if ($row->from === 'blog_category_id' && $row->table === 'blog_categories'
                    && $row->to === 'id') {
                    return true;
                }
            }
            return false;
        }

        throw new \RuntimeException('Unsupported driver for blog category FK reconciliation');
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing rollback: blog_categories may contain historical imported data. '.
            'Back up and remove it only by an explicit administrator operation.'
        );
    }
};
