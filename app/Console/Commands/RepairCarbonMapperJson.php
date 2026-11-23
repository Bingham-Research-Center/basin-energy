<?php

namespace App\Console\Commands;

use App\Support\ExistingCarbonMapperObservationsSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Explicitly opt-in, data-preserving repair for a historical MariaDB LONGTEXT
 * raw_payload column that predates Laravel's JSON_VALID CHECK constraint.
 * Without --apply, this command performs SELECT statements only.
 */
class RepairCarbonMapperJson extends Command
{
    protected $signature = 'schema:repair-carbon-json
        {--apply : Perform the guarded ALTER TABLE operations after a verified backup}';

    protected $description = 'Inspect, then optionally constrain an existing Carbon Mapper JSON payload column';

    public function handle(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->error('This repair is restricted to MySQL/MariaDB.');
            return self::FAILURE;
        }

        if (! Schema::hasTable('carbon_mapper_observations')) {
            $this->error('carbon_mapper_observations is absent; run normal migrations instead.');
            return self::FAILURE;
        }

        $row = DB::selectOne(
            'SELECT COLUMN_TYPE AS type, IS_NULLABLE AS nullable, COLLATION_NAME AS collation '.
            'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() '.
            'AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['carbon_mapper_observations', 'raw_payload']
        );

        if (! $row) {
            $this->error('raw_payload is missing; no changes made.');
            return self::FAILURE;
        }

        $create = DB::selectOne('SHOW CREATE TABLE `carbon_mapper_observations`');
        $ddl = (string) ($create->{'Create Table'} ?? '');
        $type = strtolower(trim((string) $row->type));
        $collation = strtolower((string) $row->collation);
        $this->line('raw_payload type='.$type.', nullable='.$row->nullable.', collation='.$collation);

        if ($type === 'json' || ExistingCarbonMapperObservationsSchema::isValidatedMariaDbJsonAlias(
            $type, $collation, $ddl
        )) {
            $this->info('Already compatible JSON. No changes needed.');
            return self::SUCCESS;
        }

        if ($type !== 'longtext' || $row->nullable !== 'NO'
            || ! str_starts_with($collation, 'utf8mb4_')) {
            $this->error('Unexpected column type/charset/nullability. Refusing automatic repair.');
            return self::FAILURE;
        }

        $indexed = DB::selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.STATISTICS '.
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['carbon_mapper_observations', 'raw_payload']
        );
        if ((int) $indexed->total !== 0) {
            $this->error('raw_payload is indexed; manual operator review is required.');
            return self::FAILURE;
        }

        $invalid = (int) DB::selectOne(
            'SELECT COUNT(*) AS invalid_count FROM `carbon_mapper_observations` '.
            'WHERE `raw_payload` IS NULL OR JSON_VALID(`raw_payload`) = 0'
        )->invalid_count;
        if ($invalid > 0) {
            $this->error($invalid.' records have NULL or invalid JSON. No changes made.');
            return self::FAILURE;
        }

        // Do not overwrite or duplicate an existing unrecognized constraint.
        if (preg_match('/\bcheck\b[^\r\n]*\bjson_valid\s*\(/i', $ddl)) {
            $this->error('A JSON_VALID check already exists in an unrecognized form. Inspect SHOW CREATE TABLE manually.');
            return self::FAILURE;
        }

        $this->info('All raw_payload values are valid JSON.');
        if (! $this->option('apply')) {
            $this->warn(
                'READ-ONLY preview: the repair would normalize the column to utf8mb4_bin (if needed) '.
                'and add a JSON_VALID CHECK without changing any stored payload values.'
            );
            $this->warn('After a verified database backup, use --apply to authorize the DDL.');
            return self::SUCCESS;
        }

        try {
            if ($collation !== 'utf8mb4_bin') {
                $this->warn('Changing raw_payload collation to utf8mb4_bin (DDL may lock the table).');
                DB::statement(
                    'ALTER TABLE `carbon_mapper_observations` '.
                    'MODIFY COLUMN `raw_payload` LONGTEXT CHARACTER SET utf8mb4 '.
                    'COLLATE utf8mb4_bin NOT NULL'
                );
            }

            // MariaDB emits table-level CHECK constraints for ALTER ADD
            // CONSTRAINT. The compatibility validator accepts this form.
            DB::statement(
                'ALTER TABLE `carbon_mapper_observations` '.
                'ADD CONSTRAINT `carbon_mapper_raw_payload_json_valid` '.
                'CHECK (JSON_VALID(`raw_payload`))'
            );
            ExistingCarbonMapperObservationsSchema::assertCompatible();
            $this->info('JSON validation enabled; existing observations were retained.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Repair interrupted: '.$exception->getMessage());
            $this->warn(
                'MariaDB ALTER TABLE auto-commits. A collation change may have completed; '.
                'rerun the read-only preview and inspect before another attempt.'
            );
            return self::FAILURE;
        }
    }
}
