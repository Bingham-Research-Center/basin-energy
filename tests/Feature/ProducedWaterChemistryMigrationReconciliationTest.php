<?php

namespace Tests\Feature;

use App\Support\ExistingProducedWaterChemistrySchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ProducedWaterChemistryMigrationReconciliationTest extends TestCase
{
    private function historicalMigration(): \CreateProducedWaterChemistryTable
    {
        require_once database_path('migrations/2026_02_06_171506_create_produced_water_chemistry_table.php');

        return new \CreateProducedWaterChemistryTable();
    }

    public function test_existing_chemistry_data_is_preserved_by_idempotent_migration(): void
    {
        DB::table('produced_water_chemistry')->insert([
            'unique_id_flux' => 'historic-flux-001',
            'unique_id_water' => 'historic-water-001',
            'ph' => 7.25,
            'state' => 'UT',
            'dissolved_compounds' => json_encode(['ethane' => 2.3]),
        ]);

        $before = DB::table('produced_water_chemistry')
            ->where('unique_id_water', 'historic-water-001')->first();

        $this->historicalMigration()->up();
        $this->historicalMigration()->up();

        $after = DB::table('produced_water_chemistry')
            ->where('unique_id_water', 'historic-water-001')->first();

        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->dissolved_compounds, $after->dissolved_compounds);
        $this->assertEquals($before->ph, $after->ph);
        $this->assertSame(1, DB::table('produced_water_chemistry')
            ->where('unique_id_water', 'historic-water-001')->count());
    }

    public function test_compatible_fresh_database_schema_is_accepted(): void
    {
        ExistingProducedWaterChemistrySchema::assertCompatible();
        $this->assertTrue(Schema::hasColumn('produced_water_chemistry', 'dissolved_compounds'));
    }

    public function test_missing_columns_fail_closed(): void
    {
        Schema::shouldReceive('hasTable')->twice()
            ->with('produced_water_chemistry')->andReturn(true);
        Schema::shouldReceive('getColumnListing')->once()
            ->with('produced_water_chemistry')->andReturn(['id']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing columns:');
        $this->historicalMigration()->up();
    }

    public function test_production_mariadb_json_alias_is_accepted(): void
    {
        $ddl = <<<'SQL'
CREATE TABLE <BT>produced_water_chemistry<BT> (
  <BT>id<BT> bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  <BT>dissolved_compounds<BT> longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(<BT>dissolved_compounds<BT>)),
  PRIMARY KEY (<BT>id<BT>)
)
SQL;
        $ddl = str_replace('<BT>', chr(96), $ddl);

        $this->assertTrue(ExistingProducedWaterChemistrySchema::isValidatedMariaDbJsonAlias(
            'longtext', 'utf8mb4_bin', $ddl
        ));
    }

    public function test_unvalidated_longtext_is_rejected(): void
    {
        $column = '<BT>dissolved_compounds<BT> longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL';
        $validCheck = ' CHECK (json_valid(<BT>dissolved_compounds<BT>))';
        $cases = [
            $column,
            $column.' CHECK (json_valid(<BT>notes<BT>))',
            $column.' CHECK (length(<BT>dissolved_compounds<BT>) > 0)',
            $column."\n  , <BT>notes<BT> text CHECK (json_valid(<BT>dissolved_compounds<BT>))",
        ];

        foreach ($cases as $definition) {
            $ddl = str_replace('<BT>', chr(96), "CREATE TABLE <BT>produced_water_chemistry<BT> (\n ".$definition."\n)");
            $this->assertFalse(ExistingProducedWaterChemistrySchema::isValidatedMariaDbJsonAlias(
                'longtext', 'utf8mb4_bin', $ddl
            ));
        }

        $ddl = str_replace('<BT>', chr(96), "CREATE TABLE <BT>produced_water_chemistry<BT> (\n ".$column.$validCheck."\n)");
        $this->assertFalse(ExistingProducedWaterChemistrySchema::isValidatedMariaDbJsonAlias(
            'longtext', 'utf8mb4_unicode_ci', $ddl
        ));
    }

    public function test_rollback_cannot_delete_imported_research_data(): void
    {
        DB::table('produced_water_chemistry')->insert([
            'unique_id_water' => 'historic-rollback-001',
        ]);
        try {
            $this->historicalMigration()->down();
            $this->fail('Rollback must not drop imported chemistry data.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Refusing to drop produced_water_chemistry', $e->getMessage());
        }

        $this->assertSame(1, DB::table('produced_water_chemistry')
            ->where('unique_id_water', 'historic-rollback-001')->count());
    }
}
