<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Validate an imported chemistry table before adopting its migration.
 *
 * This class is read-only: incompatible tables fail closed rather than being
 * silently altered, replaced, or marked as migrated.
 */
final class ExistingProducedWaterChemistrySchema
{
    private const TABLE = 'produced_water_chemistry';

    private const TYPES = [
        'id' => 'bigint unsigned',
        'unique_id_flux' => 'varchar(255)',
        'unique_id_water' => 'varchar(255)',
        'sample_date' => 'date',
        'sample_time' => 'time',
        'temperature' => 'decimal(6,2)',
        'ph' => 'decimal(4,2)',
        'tds' => 'decimal(10,2)',
        'turbidity' => 'decimal(10,2)',
        'toc' => 'decimal(10,2)',
        'tc' => 'decimal(10,2)',
        'ic' => 'decimal(10,2)',
        'orp' => 'decimal(10,2)',
        'odo' => 'decimal(6,2)',
        'mpn' => 'decimal(12,2)',
        'dissolved_compounds' => 'json',
        'facility_id' => 'varchar(255)',
        'state' => 'varchar(20)',
        'notes' => 'text',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    public static function assertCompatible(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            self::reject('table does not exist');
        }

        $missing = array_diff(array_keys(self::TYPES), Schema::getColumnListing(self::TABLE));
        if ($missing !== []) {
            self::reject('missing columns: '.implode(', ', $missing));
        }

        if (DB::getDriverName() === 'mysql') {
            self::verifyMySql();
        } elseif (DB::getDriverName() === 'sqlite') {
            self::verifySqlite();
        } else {
            self::reject('unsupported database driver: '.DB::getDriverName());
        }
    }

    private static function verifyMySql(): void
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type, '.
            'IS_NULLABLE AS is_nullable, EXTRA AS extra, COLLATION_NAME AS collation_name '.
            'FROM information_schema.COLUMNS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::TABLE]
        );

        $columns = [];
        foreach ($rows as $row) {
            $columns[$row->column_name] = $row;
        }

        foreach (self::TYPES as $name => $expectedType) {
            $column = $columns[$name] ?? null;
            if (! $column) {
                self::reject('column '.$name.' missing');
            }

            $actualType = ExistingProducedWaterFluxesSchema::canonicalMySqlType($column->column_type);
            if ($actualType !== $expectedType) {
                if ($name !== 'dissolved_compounds' || $actualType !== 'longtext') {
                    self::reject($name.' type is '.$column->column_type.
                        ' (expected '.$expectedType.')');
                }

                // MariaDB stores its validated JSON alias as LONGTEXT.
                // Unconstrained LONGTEXT must never be adopted as JSON.
                $create = DB::selectOne('SHOW CREATE TABLE `produced_water_chemistry`');
                $ddl = (string) ($create->{'Create Table'} ?? '');

                if (! self::isValidatedMariaDbJsonAlias(
                    $actualType,
                    (string) ($column->collation_name ?? ''),
                    $ddl
                )) {
                    self::reject('dissolved_compounds is LONGTEXT without the expected '.
                        'MariaDB JSON alias (utf8mb4_bin and CHECK json_valid)');
                }
            }

            // All chemistry fields are nullable except the auto-increment id.
            if (($column->is_nullable === 'YES') !== ($name !== 'id')) {
                self::reject($name.' nullability differs');
            }
            if ($name === 'id' && stripos((string) $column->extra, 'auto_increment') === false) {
                self::reject('id must be AUTO_INCREMENT');
            }
        }

        $rows = DB::select(
            'SELECT INDEX_NAME AS index_name, COLUMN_NAME AS column_name, '.
            'NON_UNIQUE AS non_unique, SEQ_IN_INDEX AS seq '.
            'FROM information_schema.STATISTICS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '.
            'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [self::TABLE]
        );

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->index_name]['columns'][] = $row->column_name;
            $indexes[$row->index_name]['unique'] = (int) $row->non_unique === 0;
        }
        self::assertIndexes($indexes, true);
    }

    /**
     * Accept only the column-local JSON_VALID check observed in production
     * together with MariaDB's binary utf8mb4 JSON alias collation.
     */
    public static function isValidatedMariaDbJsonAlias(
        string $type,
        ?string $collation,
        string $ddl
    ): bool {
        if (strtolower(trim($type)) !== 'longtext'
            || strtolower(trim((string) $collation)) !== 'utf8mb4_bin') {
            return false;
        }

        return preg_match(
            '/^\s*`dissolved_compounds`\s+longtext\b[^\r\n]*'.
            '\bcheck\s*\(\s*json_valid\s*\(\s*'.
            '`?dissolved_compounds`?\s*\)\s*\)/im',
            $ddl
        ) === 1;
    }

    private static function verifySqlite(): void
    {
        $columns = [];
        foreach (DB::select("PRAGMA table_info('produced_water_chemistry')") as $row) {
            $columns[$row->name] = $row;
        }

        if ((int) ($columns['id']->pk ?? 0) !== 1) {
            self::reject('id must be primary key');
        }

        foreach (self::TYPES as $name => $type) {
            $column = $columns[$name] ?? null;
            if (! $column) {
                self::reject('column '.$name.' missing');
            }
            $actual = strtolower((string) $column->type);
            if ($name === 'id') {
                if (! str_contains($actual, 'int')) {
                    self::reject('id must be an integer primary key');
                }
                continue;
            }
            if ((int) $column->notnull !== 0) {
                self::reject($name.' must be nullable');
            }

            $compatible = match (true) {
                str_starts_with($type, 'decimal') =>
                    str_contains($actual, 'decimal') || str_contains($actual, 'numeric'),
                $type === 'date' => str_contains($actual, 'date'),
                $type === 'time' => str_contains($actual, 'time'),
                $type === 'timestamp' =>
                    str_contains($actual, 'date') || str_contains($actual, 'time'),
                default => str_contains($actual, 'text')
                    || str_contains($actual, 'char') || str_contains($actual, 'json'),
            };
            if (! $compatible) {
                self::reject($name.' has unexpected SQLite type '.$column->type);
            }
        }

        $indexes = [];
        foreach (DB::select("PRAGMA index_list('produced_water_chemistry')") as $index) {
            $quoted = str_replace("'", "''", $index->name);
            $parts = DB::select("PRAGMA index_info('".$quoted."')");
            $indexes[$index->name] = [
                'columns' => array_map(static fn ($col) => $col->name, $parts),
                'unique' => (int) $index->unique === 1,
            ];
        }
        self::assertIndexes($indexes, false);
    }

    private static function assertIndexes(array $indexes, bool $mysql): void
    {
        if ($mysql && ($indexes['PRIMARY']['columns'] ?? null) !== ['id']) {
            self::reject('id PRIMARY KEY is missing or incompatible');
        }

        foreach ([['unique_id_flux'], ['unique_id_water'], ['state', 'facility_id']] as $expected) {
            $found = false;
            foreach ($indexes as $index) {
                if (($index['columns'] ?? []) === $expected
                    && ! ($index['unique'] ?? false)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                self::reject('missing non-unique index on '.implode(', ', $expected));
            }
        }
    }

    private static function reject(string $reason): void
    {
        throw new RuntimeException(
            'Cannot reconcile existing produced_water_chemistry table with migration '.
            '2026_02_06_171506_create_produced_water_chemistry_table: '.$reason.'. '.
            'No schema or data was changed. Back up the database, inspect '.
            'SHOW CREATE TABLE produced_water_chemistry and repair differences '.
            'explicitly before retrying migrate.'
        );
    }
}
