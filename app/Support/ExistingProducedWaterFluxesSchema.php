<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Safely adopt a pre-existing produced_water_fluxes research dataset.
 *
 * Production imports can pre-date the Laravel migration ledger. Do not mark
 * a migration complete unless its existing schema matches the application.
 */
final class ExistingProducedWaterFluxesSchema
{
    private const TABLE = 'produced_water_fluxes';

    // Original migration definitions for MySQL 8; nullable measurement
    // columns and timestamps must remain nullable.
    private const TYPES = [
        'id' => 'bigint unsigned',
        'unique_id' => 'varchar(255)',
        'can_set' => 'int',
        'datetime_start' => 'datetime',
        'datetime_end' => 'datetime',
        'duration_min' => 'decimal(6,2)',
        'ch4_flux' => 'decimal(10,4)',
        'co2_flux' => 'decimal(10,4)',
        'tnmhc' => 'decimal(10,4)',
        'alkanes' => 'decimal(10,4)',
        'alkenes' => 'decimal(10,4)',
        'aromatics' => 'decimal(10,4)',
        'alcohols' => 'decimal(10,4)',
        'carbonyls' => 'decimal(10,4)',
        'species_fluxes' => 'json',
        'facility_id' => 'varchar(255)',
        'pond_type' => 'varchar(255)',
        'state' => 'varchar(20)',
        'wind_corrected' => 'tinyint',
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

        switch (DB::getDriverName()) {
            case 'mysql':
                self::verifyMySql();
                break;
            case 'sqlite':
                self::verifySqlite();
                break;
            default:
                self::reject('unsupported database driver: '.DB::getDriverName());
        }
    }

    private static function verifyMySql(): void
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type, '.
            'IS_NULLABLE AS is_nullable, EXTRA AS extra, COLUMN_DEFAULT AS column_default '.
            'FROM information_schema.COLUMNS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::TABLE]
        );

        $columns = [];
        foreach ($rows as $row) {
            $columns[$row->column_name] = $row;
        }

        foreach (self::TYPES as $name => $expectedType) {
            $col = $columns[$name] ?? null;
            if (! $col) {
                self::reject('column '.$name.' missing');
            }

            $actualType = self::canonicalMySqlType($col->column_type);
            if ($actualType !== $expectedType) {
                self::reject($name.' type is '.$col->column_type.
                    ' (expected '.$expectedType.')');
            }

            $nullable = ! in_array($name, ['id', 'unique_id', 'wind_corrected'], true);
            if (($col->is_nullable === 'YES') !== $nullable) {
                self::reject($name.' nullability differs');
            }

            if ($name === 'id' && stripos((string) $col->extra, 'auto_increment') === false) {
                self::reject('id must be AUTO_INCREMENT');
            }
            if ($name === 'wind_corrected' && (string) $col->column_default !== '0') {
                self::reject('wind_corrected must default to false (0)');
            }
        }

        $indexRows = DB::select(
            'SELECT INDEX_NAME AS index_name, COLUMN_NAME AS column_name, '.
            'NON_UNIQUE AS non_unique, SEQ_IN_INDEX AS seq '.
            'FROM information_schema.STATISTICS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '.
            'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [self::TABLE]
        );

        $indexes = [];
        foreach ($indexRows as $row) {
            $indexes[$row->index_name]['columns'][] = $row->column_name;
            $indexes[$row->index_name]['unique'] = (int) $row->non_unique === 0;
        }

        self::assertIndexes($indexes);
    }

    /**
     * MySQL may display legacy integer widths such as int(11), bigint(20)
     * or tinyint(1), which do not change the stored integer's range.
     * Do not normalize decimal scale, unsigned, or unrelated modifiers.
     */
    public static function canonicalMySqlType(string $type): string
    {
        return preg_replace(
            '/^(tinyint|smallint|mediumint|int|bigint)\([0-9]+\)( unsigned)?$/',
            '$1$2',
            strtolower(trim($type))
        );
    }

    private static function verifySqlite(): void
    {
        $columns = [];
        foreach (DB::select("PRAGMA table_info('produced_water_fluxes')") as $row) {
            $columns[$row->name] = $row;
        }

        if ((int) ($columns['id']->pk ?? 0) !== 1) {
            self::reject('id must be the primary key');
        }

        foreach (self::TYPES as $name => $type) {
            $col = $columns[$name] ?? null;
            if (! $col) {
                self::reject('column '.$name.' missing');
            }

            if ($name === 'id') {
                if (stripos((string) $col->type, 'int') === false) {
                    self::reject('id must be an integer primary key');
                }
                continue;
            }

            $nullable = ! in_array($name, ['unique_id', 'wind_corrected'], true);
            if (((int) $col->notnull === 0) !== $nullable) {
                self::reject($name.' nullability differs');
            }

            // SQLite stores JSON as text and uses integer for booleans.
            $actual = strtolower((string) $col->type);
            $expected = match ($name) {
                'wind_corrected', 'can_set' => 'int',
                'species_fluxes', 'notes', 'unique_id',
                'facility_id', 'pond_type', 'state' => 'text',
                'datetime_start', 'datetime_end', 'created_at',
                'updated_at' => 'date',
                default => 'decimal',
            };
            $compatible = match ($expected) {
                'int' => str_contains($actual, 'int'),
                'text' => str_contains($actual, 'text')
                    || str_contains($actual, 'char')
                    || str_contains($actual, 'json'),
                'date' => str_contains($actual, 'date') || str_contains($actual, 'time'),
                default => str_contains($actual, 'decimal')
                    || str_contains($actual, 'numeric'),
            };
            if (! $compatible) {
                self::reject($name.' has unexpected SQLite type '.$col->type);
            }
        }

        $indexes = [];
        foreach (DB::select("PRAGMA index_list('produced_water_fluxes')") as $index) {
            $quoted = str_replace("'", "''", $index->name);
            $parts = DB::select("PRAGMA index_info('".$quoted."')");
            $indexes[$index->name] = [
                'columns' => array_map(static fn ($col) => $col->name, $parts),
                'unique' => (int) $index->unique === 1,
            ];
        }

        self::assertIndexes($indexes);
    }

    private static function assertIndexes(array $indexes): void
    {
        if (! isset($indexes['PRIMARY']) && DB::getDriverName() === 'mysql') {
            self::reject('id PRIMARY KEY is missing');
        }
        if (DB::getDriverName() === 'mysql'
            && ($indexes['PRIMARY']['columns'] ?? null) !== ['id']) {
            self::reject('id PRIMARY KEY is incompatible');
        }

        // Search by index columns, not index names: historical imported
        // datasets can have different non-unique index names.
        foreach ([['unique_id'], ['state', 'facility_id']] as $expected) {
            $found = false;
            foreach ($indexes as $index) {
                if (($index['columns'] ?? []) === $expected) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                self::reject('missing index on '.implode(', ', $expected));
            }
        }
    }

    private static function reject(string $reason): void
    {
        throw new RuntimeException(
            'Cannot reconcile existing produced_water_fluxes table with migration '.
            '2026_02_06_171501_create_produced_water_fluxes_table: '.$reason.'. '.
            'No schema or data was changed. Back up the database, inspect '.
            'SHOW CREATE TABLE produced_water_fluxes, and repair schema '.
            'differences explicitly before retrying migrate.'
        );
    }
}
