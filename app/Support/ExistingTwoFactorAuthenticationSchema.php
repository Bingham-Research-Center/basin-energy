<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only compatibility check for an existing Laragear two-factor table.
 *
 * The 2026 migration explicitly skips table creation if this table already
 * exists. It must not be compared as if it recreated the original 2021 schema:
 * valid legacy deployments may store encrypted secrets and recovery codes as
 * TEXT rather than VARCHAR/JSON. No columns or existing secrets are changed.
 */
final class ExistingTwoFactorAuthenticationSchema
{
    private const TABLE = 'two_factor_authentications';

    private const REQUIRED_COLUMNS = [
        'id', 'authenticatable_type', 'authenticatable_id',
        'shared_secret', 'enabled_at', 'label',
        'digits', 'seconds', 'window', 'algorithm',
        'recovery_codes', 'recovery_codes_generated_at', 'safe_devices',
        'created_at', 'updated_at',
    ];

    /** @return list<string> */
    public static function issues(): array
    {
        if (! Schema::hasTable(self::TABLE)) {
            return ['two_factor_authentications is missing'];
        }

        if (DB::getDriverName() !== 'mysql') {
            return ['Only MySQL/MariaDB supports strict existing two-factor inspection'];
        }

        $rows = DB::select(
            'SELECT COLUMN_NAME AS name, DATA_TYPE AS type, COLUMN_TYPE AS full_type, '.
            'IS_NULLABLE AS nullable, COLUMN_DEFAULT AS default_value, EXTRA AS extra '.
            'FROM information_schema.COLUMNS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::TABLE]
        );

        $columns = [];
        foreach ($rows as $row) {
            $columns[$row->name] = $row;
        }

        $issues = [];
        foreach (self::REQUIRED_COLUMNS as $name) {
            if (! isset($columns[$name])) {
                $issues[] = 'missing required two-factor column '.$name;
            }
        }

        if ($issues !== []) {
            return $issues;
        }

        if (strtolower($columns['id']->type) !== 'bigint'
            || stripos((string) $columns['id']->extra, 'auto_increment') === false) {
            $issues[] = 'two-factor id must be an auto-incrementing BIGINT';
        }

        if (strtolower($columns['authenticatable_type']->type) !== 'varchar'
            || strtolower($columns['authenticatable_id']->type) !== 'bigint') {
            $issues[] = 'two-factor authenticatable morph key types are incompatible';
        }

        if (! self::isSecretStorageType($columns['shared_secret']->type)
            || $columns['shared_secret']->nullable !== 'NO') {
            $issues[] = 'shared_secret must be non-null TEXT or VARCHAR';
        }

        if (! self::isRecoveryStorageType($columns['recovery_codes']->type)) {
            $issues[] = 'recovery_codes must be JSON or TEXT storage';
        }

        if (strtolower($columns['algorithm']->type) !== 'varchar'
            || ! LegacyMigrationSchemaInspector::defaultsMatch(
                $columns['algorithm']->default_value === null
                    ? null : (string) $columns['algorithm']->default_value,
                'sha1'
            )) {
            $issues[] = 'algorithm must be VARCHAR with default sha1';
        }

        if (strtolower($columns['safe_devices']->type) !== 'json') {
            // Native JSON is represented as validated LONGTEXT in MariaDB.
            $create = DB::selectOne('SHOW CREATE TABLE ' . chr(96).self::TABLE.chr(96));
            $ddl = (string) ($create->{'Create Table'} ?? '');
            $metadata = DB::selectOne(
                'SELECT COLLATION_NAME AS collation_name FROM information_schema.COLUMNS '.
                'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [self::TABLE, 'safe_devices']
            );

            if (! LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
                'safe_devices',
                strtolower((string) $columns['safe_devices']->type),
                (string) ($metadata->collation_name ?? ''),
                $ddl
            )) {
                $issues[] = 'safe_devices must be JSON or a validated MariaDB JSON alias';
            }
        }

        $indexes = DB::select(
            'SELECT INDEX_NAME AS name, COLUMN_NAME AS column_name, '.
            'SEQ_IN_INDEX AS seq FROM information_schema.STATISTICS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '.
            'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [self::TABLE]
        );

        $byName = [];
        foreach ($indexes as $row) {
            $byName[$row->name][] = $row->column_name;
        }
        if (($byName['PRIMARY'] ?? []) !== ['id']) {
            $issues[] = 'two-factor PRIMARY KEY must be id';
        }

        $hasMorphIndex = false;
        foreach ($byName as $index) {
            if (array_slice($index, 0, 2) === ['authenticatable_type', 'authenticatable_id']) {
                $hasMorphIndex = true;
            }
        }
        if (! $hasMorphIndex) {
            $issues[] = 'missing authenticatable_type/authenticatable_id index';
        }

        return $issues;
    }

    public static function isSecretStorageType(string $type): bool
    {
        return in_array(strtolower($type), ['varchar', 'text', 'mediumtext', 'longtext'], true);
    }

    public static function isRecoveryStorageType(string $type): bool
    {
        return in_array(strtolower($type), ['json', 'text', 'mediumtext', 'longtext'], true);
    }
}
