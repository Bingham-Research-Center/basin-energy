<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Read-only inspection of a historical creation migration against the live
 * MySQL/MariaDB schema. The expected SQL is compiled by Laravel itself inside
 * Connection::pretend(): no CREATE/ALTER/INSERT is executed.
 *
 * Only migration files explicitly allowlisted by the reconciliation command
 * may be passed here. Never compile arbitrary third-party or data migrations.
 */
final class LegacyMigrationSchemaInspector
{
    public static function assertCompatible(string $migration, string $table): void
    {
        $errors = (new self())->inspect($migration, $table);
        if ($errors !== []) {
            throw new RuntimeException(
                'Cannot safely adopt existing '.$table.' for migration '.$migration.': '.
                implode('; ', $errors).'. Existing schema and rows were not modified. '.
                'Run php artisan schema:audit-migrations and compare the actual schema '.
                'against its migration before making an explicit repair.'
            );
        }
    }

    public function inspect(string $migration, string $table): array
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'sqlite'], true)) {
            return ['Unsupported database driver for strict comparison: '.DB::getDriverName()];
        }

        $sql = $this->expectedSql($migration, $table);
        $definitions = self::parseCreateColumns($sql['create']);
        if ($definitions === []) {
            return ['Unable to compile expected columns for '.$migration];
        }

        $actual = $this->columnMetadata($table);
        $errors = [];

        // Original create already has timestamps, but an additional migration
        // can add them to legacy sync_states tables if they are absent.
        $laterColumns = $table === 'sync_states' ? ['created_at', 'updated_at'] : [];

        $ddl = null;
        foreach ($definitions as $name => $expected) {
            if (! isset($actual[$name])) {
                if (! in_array($name, $laterColumns, true)) {
                    $errors[] = 'missing column '.$name;
                }
                continue;
            }

            $column = $actual[$name];
            $type = self::canonicalType((string) $column->column_type);
            $expectedType = self::canonicalType($expected['type']);
            if ($type !== $expectedType) {
                if ($expectedType === 'json' && $type === 'longtext') {
                    $ddl ??= $this->createTableSql($table);
                    if (! self::validMariaDbJsonAlias(
                        $name, $type, (string) ($column->collation_name ?? ''), $ddl
                    )) {
                        $errors[] = $name.' LONGTEXT is not a validated MariaDB JSON alias';
                    }
                } else {
                    $errors[] = $name.' is '.$column->column_type.' (expected '.$expectedType.')';
                }
            }

            if (($column->is_nullable === 'YES') !== $expected['nullable']) {
                $errors[] = $name.' nullability differs';
            }

            if ($expected['auto_increment'] && stripos((string) $column->extra, 'auto_increment') === false) {
                $errors[] = $name.' must be AUTO_INCREMENT';
            }

            if ($expected['default'] !== null
                && strtolower((string) $column->column_default) !== strtolower($expected['default'])) {
                $errors[] = $name.' default differs (expected '.$expected['default'].')';
            }
        }

        $indexes = $this->indexMetadata($table);
        $expectsPrimary = false;
        $primaryColumns = [];
        foreach ($definitions as $name => $expected) {
            if ($expected['primary']) {
                $expectsPrimary = true;
                $primaryColumns[] = $name;
            }
        }
        if ($expectsPrimary && ($indexes['PRIMARY']['columns'] ?? []) !== $primaryColumns) {
            $errors[] = 'PRIMARY KEY is missing or incompatible';
        }

        foreach ($sql['statements'] as $statement) {
            // Laravel's MySQL grammar generates ALTER TABLE ADD INDEX / UNIQUE
            // statements separately from CREATE TABLE.
            if (preg_match(
                '/\badd\s+(unique|index|primary key)\s+(?:[`\"][^`\"]+[`\"]\s*)?\(([^)]+)\)/i',
                $statement, $match
            ) || preg_match(
                '/\bcreate\s+(unique\s+)?index\s+[`\"][^`\"]+[`\"]\s+on\s+[`\"][^`\"]+[`\"]\s*\(([^)]+)\)/i',
                $statement, $match
            )) {
                $kind = strtolower(trim($match[1])) === 'unique' ? 'unique' : strtolower(trim($match[1]));
                if ($kind === '') {
                    $kind = 'index';
                }
                $columns = self::quotedIdentifiers($match[2]);
                if ($kind === 'primary key') {
                    if (($indexes['PRIMARY']['columns'] ?? []) !== $columns) {
                        $errors[] = 'missing PRIMARY KEY '.implode(',', $columns);
                    }
                    continue;
                }
                $unique = $kind === 'unique';
                $found = false;
                foreach ($indexes as $index) {
                    if ($index['columns'] === $columns && $index['unique'] === $unique) {
                        $found = true;
                        break;
                    }
                }

                // A later 2026 migration deliberately removes this constraint.
                if (! $found && $unique && $table === 'carbon_mapper_observations'
                    && $columns === ['observation_id', 'gas']) {
                    $found = true;
                }
                if (! $found) {
                    $errors[] = 'missing '.($unique ? 'unique' : 'non-unique').
                        ' index ('.implode(', ', $columns).')';
                }
            }

            if (preg_match(
                '/\badd\s+constraint\s+`[^`]+`\s+foreign\s+key\s*\(([^)]+)\)\s+'.
                'references\s+`([^`]+)`\s*\(([^)]+)\)/i',
                $statement, $match
            )) {
                $fk = self::quotedIdentifiers($match[1]);
                $reference = self::quotedIdentifiers($match[3]);
                if (! $this->hasForeignKey($table, $fk, $match[2], $reference)) {
                    $errors[] = 'missing foreign key ('.implode(', ', $fk).
                        ') -> '.$match[2].'('.implode(', ', $reference).')';
                }
            }
        }

        return array_values(array_unique($errors));
    }

    private function expectedSql(string $migration, string $table): array
    {
        $path = database_path('migrations/'.$migration.'.php');
        if (! is_file($path)) {
            throw new RuntimeException('Missing migration file '.$path);
        }

        $queries = DB::connection()->pretend(static function () use ($path): void {
            // Laravel resolves named and anonymous migrations the same way
            // its own Migrator does, but does not run SQL under pretend().
            app('migrator')->resolvePath($path)->up();
        });

        $statements = array_map(static fn (array $query): string =>
            (string) ($query['query'] ?? ''), $queries);
        $create = '';
        foreach ($statements as $statement) {
            if (preg_match('/^\s*create\s+table\s+[`\"]?'.preg_quote($table, '/').'[`\"]?\s*\(/i', $statement)) {
                $create = $statement;
                break;
            }
        }
        if ($create === '') {
            throw new RuntimeException(
                'Could not compile CREATE TABLE for '.$migration.' ('.$table.'); no adoption permitted.'
            );
        }

        return ['create' => $create, 'statements' => $statements];
    }

    /**
     * Parse only Laravel's compiled MySQL CREATE TABLE column definitions.
     * Parentheses in DECIMAL/DOUBLE types and quoted defaults are supported.
     */
    public static function parseCreateColumns(string $createSql): array
    {
        $start = strpos($createSql, '(');
        if ($start === false) {
            return [];
        }

        $parts = [];
        $piece = '';
        $depth = 1;
        $quote = null;
        $length = strlen($createSql);
        for ($i = $start + 1; $i < $length; $i++) {
            $char = $createSql[$i];
            if ($quote !== null) {
                $piece .= $char;
                if ($char === $quote) {
                    if ($i + 1 < $length && $createSql[$i + 1] === $quote) {
                        $piece .= $createSql[++$i];
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }
            if ($char === "'" || $char === '`' || $char === '"') {
                $quote = $char;
                $piece .= $char;
            } elseif ($char === '(') {
                $depth++;
                $piece .= $char;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    $parts[] = trim($piece);
                    break;
                }
                $piece .= $char;
            } elseif ($char === ',' && $depth === 1) {
                $parts[] = trim($piece);
                $piece = '';
            } else {
                $piece .= $char;
            }
        }

        $columns = [];
        foreach ($parts as $part) {
            if (! preg_match(
                '/^[`\"]([^`\"]+)[`\"]\s+([a-z][a-z0-9_]*(?:\([^)]*\))?(?: unsigned)?)/i',
                $part, $match
            )) {
                continue;
            }
            $type = strtolower(trim($match[2]));
            // Integer display widths are presentation-only on older MariaDB.
            $default = null;
            if (preg_match('/\bdefault\s+(?:\'([^\']*)\'|([^\s,]+))/i', $part, $dm)) {
                $default = $dm[1] !== '' ? $dm[1] : ($dm[2] ?? '');
                if (strtolower($default) === 'null') {
                    $default = null;
                }
            }

            $columns[$match[1]] = [
                'type' => $type,
                'nullable' => ! preg_match('/\bnot\s+null\b/i', $part),
                'default' => $default,
                'auto_increment' => (bool) preg_match('/\bauto_?increment\b/i', $part),
                'primary' => (bool) preg_match('/\bprimary\s+key\b/i', $part),
            ];
        }

        return $columns;
    }

    private function columnMetadata(string $table): array
    {
        if (DB::getDriverName() === 'sqlite') {
            $map = [];
            // Only table names in the static command allowlist reach here.
            foreach (DB::select("PRAGMA table_info('".$table."')") as $row) {
                $map[$row->name] = (object) [
                    'column_type' => strtolower((string) $row->type),
                    'is_nullable' => ((int) $row->notnull === 0) ? 'YES' : 'NO',
                    'column_default' => trim((string) $row->dflt_value, "'\\""),
                    'extra' => (int) $row->pk === 1 && str_contains(strtolower($row->type), 'int')
                        ? 'auto_increment' : '',
                    'collation_name' => '',
                ];
            }
            return $map;
        }

        $rows = DB::select(
            'SELECT COLUMN_NAME AS column_name, COLUMN_TYPE AS column_type, '.
            'IS_NULLABLE AS is_nullable, COLUMN_DEFAULT AS column_default, '.
            'EXTRA AS extra, COLLATION_NAME AS collation_name '.
            'FROM information_schema.COLUMNS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[$row->column_name] = $row;
        }
        return $map;
    }

    private function createTableSql(string $table): string
    {
        // Table name comes exclusively from our static allowlist.
        $row = DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
        return (string) ($row->{'Create Table'} ?? '');
    }

    private function indexMetadata(string $table): array
    {
        if (DB::getDriverName() === 'sqlite') {
            $map = ['PRIMARY' => ['columns' => ['id'], 'unique' => true]];
            // sync_states uses a string primary key instead of id.
            if ($table === 'sync_states') {
                $map['PRIMARY']['columns'] = ['key'];
            }
            foreach (DB::select("PRAGMA index_list('".$table."')") as $row) {
                $escaped = str_replace("'", "''", $row->name);
                $columns = DB::select("PRAGMA index_info('".$escaped."')");
                $map[$row->name] = [
                    'columns' => array_map(static fn ($col) => $col->name, $columns),
                    'unique' => (int) $row->unique === 1,
                ];
            }
            return $map;
        }

        $rows = DB::select(
            'SELECT INDEX_NAME AS index_name, COLUMN_NAME AS column_name, '.
            'NON_UNIQUE AS non_unique, SEQ_IN_INDEX AS seq '.
            'FROM information_schema.STATISTICS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '.
            'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [$table]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[$row->index_name]['columns'][] = $row->column_name;
            $map[$row->index_name]['unique'] = (int) $row->non_unique === 0;
        }
        return $map;
    }

    private function hasForeignKey(string $table, array $columns, string $target, array $referenced): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $matches = [];
            foreach (DB::select("PRAGMA foreign_key_list('".$table."')") as $row) {
                $matches[$row->id]['columns'][$row->seq] = $row->from;
                $matches[$row->id]['referenced'][$row->seq] = $row->to;
                $matches[$row->id]['table'] = $row->table;
            }
            foreach ($matches as $match) {
                if (array_values($match['columns']) === $columns && $match['table'] === $target
                    && array_values($match['referenced']) === $referenced) {
                    return true;
                }
            }
            return false;
        }

        $rows = DB::select(
            'SELECT CONSTRAINT_NAME AS name, COLUMN_NAME AS source_col, '.
            'REFERENCED_TABLE_NAME AS target_table, REFERENCED_COLUMN_NAME AS target_col, '.
            'ORDINAL_POSITION AS seq FROM information_schema.KEY_COLUMN_USAGE '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? '.
            'AND REFERENCED_TABLE_NAME IS NOT NULL '.
            'ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION',
            [$table]
        );
        $keys = [];
        foreach ($rows as $row) {
            $keys[$row->name]['columns'][] = $row->source_col;
            $keys[$row->name]['referenced'][] = $row->target_col;
            $keys[$row->name]['table'] = $row->target_table;
        }
        foreach ($keys as $key) {
            if ($key['columns'] === $columns && $key['table'] === $target
                && $key['referenced'] === $referenced) {
                return true;
            }
        }
        return false;
    }

    public static function canonicalType(string $type): string
    {
        return ExistingProducedWaterFluxesSchema::canonicalMySqlType($type);
    }

    public static function validMariaDbJsonAlias(
        string $column, string $type, string $collation, string $ddl
    ): bool {
        if ($type !== 'longtext' || strtolower($collation) !== 'utf8mb4_bin') {
            return false;
        }
        $name = preg_quote($column, '/');
        $local = '/^\s*`'.$name.'`\s+longtext\b[^\r\n]*'.
            '\bcheck\s*\(\s*json_valid\s*\(\s*`?'.$name.'`?\s*\)\s*\)/im';
        $tableCheck = '/^\s*(?:CONSTRAINT\s+`[^`]+`\s+)?CHECK\s*'.
            '\(\s*json_valid\s*\(\s*`?'.$name.'`?\s*\)\s*\)/im';
        return preg_match($local, $ddl) === 1 || preg_match($tableCheck, $ddl) === 1;
    }

    private static function quotedIdentifiers(string $expression): array
    {
        preg_match_all('/[`\"]([^`\"]+)[`\"]/', $expression, $matches);
        return $matches[1];
    }
}
