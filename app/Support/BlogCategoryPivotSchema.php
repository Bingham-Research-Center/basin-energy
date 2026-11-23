<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Read-only check of a historical blog pivot foreign key.
 * The November 2026 production structure already contains this constraint.
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

        throw new RuntimeException('Unsupported driver for blog pivot FK inspection.');
    }
}
