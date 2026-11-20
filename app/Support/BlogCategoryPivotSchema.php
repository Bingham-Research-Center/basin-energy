<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Idempotently establish the category side of the historical blog pivot FK.
 * The old migrations created this relation across two separate files.
 */
final class BlogCategoryPivotSchema
{
    public static function exists(): bool
    {
        if (! Schema::hasTable('blog_category_post') || ! Schema::hasTable('blog_categories')) {
            return false;
        }

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

        throw new RuntimeException('Unsupported database driver for blog category FK inspection.');
    }

    public static function hasOrphans(): bool
    {
        if (! Schema::hasTable('blog_category_post') || ! Schema::hasTable('blog_categories')) {
            throw new RuntimeException('Blog categories and pivot tables must exist before FK inspection.');
        }

        return DB::table('blog_category_post as bcp')
            ->leftJoin('blog_categories as bc', 'bc.id', '=', 'bcp.blog_category_id')
            ->whereNull('bc.id')
            ->exists();
    }

    public static function ensure(): void
    {
        if (! Schema::hasTable('blog_category_post') || ! Schema::hasTable('blog_categories')) {
            throw new RuntimeException(
                'Cannot ensure blog category pivot FK: both prerequisite tables must exist.'
            );
        }

        if (self::exists()) {
            return;
        }

        if (self::hasOrphans()) {
            throw new RuntimeException(
                'Cannot add blog category pivot FK: orphaned blog_category_id references exist. '.
                'No data changed. Repair orphan rows explicitly before migration.'
            );
        }

        Schema::table('blog_category_post', function (Blueprint $table) {
            $table->foreign('blog_category_id', 'bcp_cat_fk')
                ->references('id')->on('blog_categories')->onDelete('cascade');
        });
    }
}
