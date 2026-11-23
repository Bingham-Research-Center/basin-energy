<?php

namespace Tests\Feature;

use App\Support\ExistingProducedWaterFluxesSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ProducedWaterFluxesMigrationReconciliationTest extends TestCase
{
    private function historicalMigration(): \CreateProducedWaterFluxesTable
    {
        require_once database_path('migrations/2026_02_06_171501_create_produced_water_fluxes_table.php');

        return new \CreateProducedWaterFluxesTable();
    }

    public function test_existing_produced_water_table_is_adopted_without_changing_rows(): void
    {
        DB::table('produced_water_fluxes')->insert([
            'unique_id' => 'historic-produced-water-001',
            'can_set' => 4,
            'ch4_flux' => 1.2345,
            'co2_flux' => 2.3456,
            'wind_corrected' => 0,
            'state' => 'UT',
            'species_fluxes' => json_encode(['ethane' => 2.3]),
        ]);

        $before = DB::table('produced_water_fluxes')
            ->where('unique_id', 'historic-produced-water-001')
            ->first();

        // The migration may be pending even though the table was imported.
        // An idempotent up() cannot drop, recreate, or mutate this row.
        $this->historicalMigration()->up();
        $this->historicalMigration()->up();

        $after = DB::table('produced_water_fluxes')
            ->where('unique_id', 'historic-produced-water-001')
            ->first();

        $this->assertNotNull($after);
        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->species_fluxes, $after->species_fluxes);
        $this->assertEquals($before->ch4_flux, $after->ch4_flux);
        $this->assertSame(1, DB::table('produced_water_fluxes')
            ->where('unique_id', 'historic-produced-water-001')->count());
    }

    public function test_existing_table_must_have_all_required_columns(): void
    {
        Schema::shouldReceive('hasTable')->twice()
            ->with('produced_water_fluxes')->andReturn(true);
        Schema::shouldReceive('getColumnListing')->once()
            ->with('produced_water_fluxes')->andReturn(['id', 'unique_id']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing columns:');

        $this->historicalMigration()->up();
    }

    public function test_mysql_integer_display_width_normalization_keeps_other_constraints(): void
    {
        $this->assertSame('bigint unsigned',
            ExistingProducedWaterFluxesSchema::canonicalMySqlType('bigint(20) unsigned'));
        $this->assertSame('int',
            ExistingProducedWaterFluxesSchema::canonicalMySqlType('int(11)'));
        $this->assertSame('tinyint',
            ExistingProducedWaterFluxesSchema::canonicalMySqlType('tinyint(1)'));
        $this->assertSame('decimal(10,4)',
            ExistingProducedWaterFluxesSchema::canonicalMySqlType('decimal(10,4)'));
        $this->assertSame('int',
            ExistingProducedWaterFluxesSchema::canonicalMySqlType('int(10)'));
        $this->assertSame('int unsigned',
            ExistingProducedWaterFluxesSchema::canonicalMySqlType('int(10) unsigned'));
        $this->assertSame('int(11) unsigned zerofill',
            ExistingProducedWaterFluxesSchema::canonicalMySqlType('int(11) unsigned zerofill'));
    }

    public function test_mariadb_json_alias_matches_production_show_create_table(): void
    {
        // Exact column shape observed in the production SHOW CREATE TABLE
        // for all 398 existing produced-water research records.
        $ddl = <<<'SQL'
CREATE TABLE `produced_water_fluxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `species_fluxes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`species_fluxes`)),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        $this->assertTrue(ExistingProducedWaterFluxesSchema::isValidatedMariaDbJsonAlias(
            'longtext', 'utf8mb4_bin', $ddl
        ));
        $this->assertTrue(ExistingProducedWaterFluxesSchema::isValidatedMariaDbJsonAlias(
            'LONGTEXT', 'UTF8MB4_BIN', $ddl
        ));
    }

    public function test_unvalidated_or_incorrect_mariadb_longtext_is_not_silently_adopted(): void
    {
        $column = '`species_fluxes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL';
        $validCheck = ' CHECK (json_valid(`species_fluxes`))';
        $table = static fn (string $definition): string =>
            "CREATE TABLE `produced_water_fluxes` (\n  ".$definition."\n)";

        $invalidDefinitions = [
            $column,                                     // No JSON_VALID constraint
            $column.' CHECK (json_valid(`notes`))',        // Validates wrong column
            $column.' CHECK (length(`species_fluxes`) > 0)', // Not JSON validation
            $column."\n  , `notes` text CHECK (json_valid(`species_fluxes`))",
        ];

        foreach ($invalidDefinitions as $definition) {
            $this->assertFalse(
                ExistingProducedWaterFluxesSchema::isValidatedMariaDbJsonAlias(
                    'longtext', 'utf8mb4_bin', $table($definition)
                ),
                'An unsafe LONGTEXT definition must not pass.'
            );
        }

        $this->assertFalse(ExistingProducedWaterFluxesSchema::isValidatedMariaDbJsonAlias(
            'longtext', 'utf8mb4_unicode_ci', $table($column.$validCheck)
        ));
        $this->assertFalse(ExistingProducedWaterFluxesSchema::isValidatedMariaDbJsonAlias(
            'text', 'utf8mb4_bin', $table($column.$validCheck)
        ));
    }

    public function test_normal_migration_schema_is_compatible(): void
    {
        ExistingProducedWaterFluxesSchema::assertCompatible();

        $this->assertTrue(Schema::hasColumn('produced_water_fluxes', 'unique_id'));
        $this->assertTrue(Schema::hasColumn('produced_water_fluxes', 'species_fluxes'));
    }

    public function test_rollback_refuses_to_delete_imported_research_data(): void
    {
        DB::table('produced_water_fluxes')->insert([
            'unique_id' => 'imported-before-ledger-001',
            'ch4_flux' => 3.25,
            'wind_corrected' => 0,
        ]);

        try {
            $this->historicalMigration()->down();
            $this->fail('Rollback must not delete a potentially imported dataset.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'Refusing to drop produced_water_fluxes',
                $exception->getMessage()
            );
        }

        $this->assertSame(1, DB::table('produced_water_fluxes')
            ->where('unique_id', 'imported-before-ledger-001')->count());
    }
}
