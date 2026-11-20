<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Read-only compatibility validation for a pre-migration Carbon Mapper import.
 *
 * In production this table can already contain research data despite missing
 * its creation-migration ledger entry. Never recreate or silently modify it.
 */
final class ExistingCarbonMapperObservationsSchema
{
    private const TABLE = 'carbon_mapper_observations';

    private const TYPES = [
        'id' => 'bigint unsigned',
        'observation_id' => 'varchar(255)',
        'gas' => 'varchar(10)',
        'satellite' => 'varchar(255)',
        'instrument' => 'varchar(255)',
        'latitude' => 'decimal(10,7)',
        'longitude' => 'decimal(10,7)',
        'country' => 'varchar(255)',
        'region' => 'varchar(255)',
        'emission_rate' => 'double(8,2)',
        'emission_unit' => 'varchar(255)',
        'confidence' => 'double(8,2)',
        'is_super_emitter' => 'tinyint',
        'observed_at' => 'timestamp',
        'raw_payload' => 'json',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    private const SINGLE_INDEX_COLUMNS = [
        'observation_id', 'gas', 'latitude', 'longitude', 'country',
        'region', 'observed_at',
    ];

    public const LEGACY_UNIQUE_INDEX = 'carbon_mapper_observations_observation_id_gas_unique';

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
            'IS_NULLABLE AS is_nullable, EXTRA AS extra, COLUMN_DEFAULT AS column_default, '.
            'COLLATION_NAME AS collation_name '.
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
                self::reject('missing '.$name);
            }

            $actualType = ExistingProducedWaterFluxesSchema::canonicalMySqlType($col->column_type);
            if ($actualType !== $expectedType) {
                if ($name !== 'raw_payload' || $actualType !== 'longtext') {
                    self::reject($name.' type is '.$col->column_type.
                        ' (expected '.$expectedType.')');
                }

                $create = DB::selectOne('SHOW CREATE TABLE `carbon_mapper_observations`');
                $ddl = (string) ($create->{'Create Table'} ?? '');

                if (! self::isValidatedMariaDbJsonAlias(
                    $actualType, (string) ($col->collation_name ?? ''), $ddl
                )) {
                    self::reject('raw_payload LONGTEXT is not a validated MariaDB JSON alias');
                }
            }

            $nullable = ! in_array($name, [
                'id', 'gas', 'latitude', 'longitude',
                'is_super_emitter', 'raw_payload',
            ], true);
            if (($col->is_nullable === 'YES') !== $nullable) {
                self::reject($name.' nullability differs');
            }
            if ($name === 'id' && stripos((string) $col->extra, 'auto_increment') === false) {
                self::reject('id must be AUTO_INCREMENT');
            }
            if ($name === 'is_super_emitter' && (string) $col->column_default !== '1') {
                self::reject('is_super_emitter must default to 1');
            }
        }

        self::assertIndexes(self::mySqlIndexes());
    }

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
            '/^\s*`raw_payload`\s+longtext\b[^\r\n]*'.
            '\bcheck\s*\(\s*json_valid\s*\(\s*`?raw_payload`?\s*\)\s*\)/im',
            $ddl
        ) === 1;
    }

    private static function verifySqlite(): void
    {
        $columns = [];
        foreach (DB::select("PRAGMA table_info('carbon_mapper_observations')") as $row) {
            $columns[$row->name] = $row;
        }

        if ((int) ($columns['id']->pk ?? 0) !== 1) {
            self::reject('id must be an integer primary key');
        }

        foreach (self::TYPES as $name => $type) {
            $col = $columns[$name] ?? null;
            if (! $col) {
                self::reject('missing '.$name);
            }

            $actual = strtolower((string) $col->type);
            if ($name === 'id') {
                if (! str_contains($actual, 'int')) {
                    self::reject('id must be an integer primary key');
                }
                continue;
            }

            $nullable = ! in_array($name, [
                'gas', 'latitude', 'longitude', 'is_super_emitter', 'raw_payload',
            ], true);
            if (((int) $col->notnull === 0) !== $nullable) {
                self::reject($name.' nullability differs');
            }
            $compatible = match (true) {
                $name === 'is_super_emitter' =>
                    str_contains($actual, 'int') || str_contains($actual, 'bool'),
                in_array($name, ['emission_rate', 'confidence'], true) =>
                    str_contains($actual, 'float') || str_contains($actual, 'double')
                    || str_contains($actual, 'real') || str_contains($actual, 'numeric'),
                str_starts_with($type, 'decimal') =>
                    str_contains($actual, 'decimal') || str_contains($actual, 'numeric'),
                $type === 'timestamp' =>
                    str_contains($actual, 'date') || str_contains($actual, 'time'),
                default => str_contains($actual, 'char') || str_contains($actual, 'text')
                    || str_contains($actual, 'json'),
            };
            if (! $compatible) {
                self::reject($name.' has unexpected SQLite type '.$col->type);
            }
            if ($name === 'is_super_emitter'
                && ! in_array(trim((string) $col->dflt_value, "'\""), ['1', 'true'], true)) {
                self::reject('is_super_emitter must default to 1');
            }
        }

        self::assertIndexes(self::sqliteIndexes());
    }

    /** @return array<string, array{columns: array, unique: bool}> */
    private static function mySqlIndexes(): array
    {
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
        return $indexes;
    }

    /** @return array<string, array{columns: array, unique: bool}> */
    private static function sqliteIndexes(): array
    {
        $indexes = ['PRIMARY' => ['columns' => ['id'], 'unique' => true]];
        foreach (DB::select("PRAGMA index_list('carbon_mapper_observations')") as $index) {
            $quoted = str_replace("'", "''", $index->name);
            $parts = DB::select("PRAGMA index_info('".$quoted."')");
            $indexes[$index->name] = [
                'columns' => array_map(static fn ($col) => $col->name, $parts),
                'unique' => (int) $index->unique === 1,
            ];
        }
        return $indexes;
    }

    private static function assertIndexes(array $indexes): void
    {
        if (($indexes['PRIMARY']['columns'] ?? null) !== ['id']) {
            self::reject('id PRIMARY KEY is missing or incompatible');
        }

        // One later historical migration removes the composite unique key.
        // Either before- or after-drop state is acceptable, but a matching
        // named legacy index must have the expected definition.
        if (isset($indexes[self::LEGACY_UNIQUE_INDEX])) {
            $legacy = $indexes[self::LEGACY_UNIQUE_INDEX];
            if (($legacy['columns'] ?? []) !== ['observation_id', 'gas']
                || ! ($legacy['unique'] ?? false)) {
                self::reject('unexpected definition for legacy unique index');
            }
        }

        foreach (self::SINGLE_INDEX_COLUMNS as $name) {
            $found = false;
            foreach ($indexes as $index) {
                if (($index['columns'] ?? []) === [$name] && ! ($index['unique'] ?? false)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                self::reject('missing non-unique index on '.$name);
            }
        }
    }

    /**
     * Used by the later unique-index removal migration; does not change data.
     */
    public static function hasLegacyUniqueIndex(): bool
    {
        if (! Schema::hasTable(self::TABLE)) {
            self::reject('table does not exist');
        }
        $indexes = match (DB::getDriverName()) {
            'mysql' => self::mySqlIndexes(),
            'sqlite' => self::sqliteIndexes(),
            default => throw new RuntimeException('Unsupported database driver for Carbon Mapper index check'),
        };
        $index = $indexes[self::LEGACY_UNIQUE_INDEX] ?? null;
        if (! $index) {
            return false;
        }
        if (($index['columns'] ?? []) !== ['observation_id', 'gas']
            || ! ($index['unique'] ?? false)) {
            self::reject('unexpected definition for legacy unique index');
        }
        return true;
    }

    private static function reject(string $reason): void
    {
        throw new RuntimeException(
            'Cannot reconcile existing carbon_mapper_observations table with migration '.
            '2026_02_06_222324_create_carbon_mapper_observations_table: '.$reason.'. '.
            'No schema or rows were modified. Back up the database and inspect '.
            'SHOW CREATE TABLE carbon_mapper_observations before attempting explicit repairs.'
        );
    }
}
