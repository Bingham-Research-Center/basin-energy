<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services_page_contents')) {
            throw new \RuntimeException('services_page_contents must exist before button columns are added.');
        }

        foreach (['button_text', 'button_link'] as $column) {
            if (Schema::hasColumn('services_page_contents', $column)) {
                // An imported schema may already contain the target columns.
                // Verify their intended shape instead of blindly skipping.
                if (DB::getDriverName() === 'mysql') {
                    $row = DB::selectOne(
                        'SELECT COLUMN_TYPE AS type, IS_NULLABLE AS nullable '.
                        'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() '.
                        'AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                        ['services_page_contents', $column]
                    );
                    if (! $row || strtolower($row->type) !== 'varchar(255)'
                        || $row->nullable !== 'YES') {
                        throw new \RuntimeException(
                            'Existing services_page_contents.'.$column.
                            ' is incompatible with the nullable varchar(255) migration definition.'
                        );
                    }
                } elseif (DB::getDriverName() === 'sqlite') {
                    $found = false;
                    foreach (DB::select("PRAGMA table_info('services_page_contents')") as $row) {
                        if ($row->name === $column) {
                            $found = stripos($row->type, 'varchar') !== false
                                && (int) $row->notnull === 0;
                            break;
                        }
                    }
                    if (! $found) {
                        throw new \RuntimeException(
                            'Existing services_page_contents.'.$column.' has incompatible SQLite schema.'
                        );
                    }
                } else {
                    throw new \RuntimeException('Unsupported driver for services button migration.');
                }
                continue;
            }

            Schema::table('services_page_contents', function (Blueprint $table) use ($column) {
                $table->string($column)->nullable()
                    ->after($column === 'button_text' ? 'description' : 'button_text');
            });
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Refusing to delete service button fields during rollback: they may hold imported content.'
        );
    }
};
