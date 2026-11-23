<?php

namespace Tests\Feature;

use App\Support\ExistingCarbonMapperObservationsSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class CarbonMapperMigrationReconciliationTest extends TestCase
{
    private function creationMigration(): \CreateCarbonMapperObservationsTable
    {
        require_once database_path('migrations/2026_02_06_222324_create_carbon_mapper_observations_table.php');

        return new \CreateCarbonMapperObservationsTable();
    }

    private function dropUniqueMigration(): \DropUniqueObservationIdGasIndexFromCarbonMapperObservations
    {
        require_once database_path('migrations/2026_02_08_194941_drop_unique_observation_id_gas_index_from_carbon_mapper_observations.php');

        return new \DropUniqueObservationIdGasIndexFromCarbonMapperObservations();
    }

    public function test_existing_observations_are_adopted_without_changing_rows(): void
    {
        DB::table('carbon_mapper_observations')->insert([
            'observation_id' => 'historic-observation-001',
            'gas' => 'CH4',
            'latitude' => 40.4608560,
            'longitude' => -109.5621800,
            'raw_payload' => json_encode(['source' => 'historic']),
            'is_super_emitter' => true,
        ]);

        $before = DB::table('carbon_mapper_observations')
            ->where('observation_id', 'historic-observation-001')->first();

        $this->creationMigration()->up();
        $this->creationMigration()->up();

        $after = DB::table('carbon_mapper_observations')
            ->where('observation_id', 'historic-observation-001')->first();

        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->raw_payload, $after->raw_payload);
        $this->assertSame(1, DB::table('carbon_mapper_observations')
            ->where('observation_id', 'historic-observation-001')->count());
    }

    public function test_current_schema_without_legacy_unique_key_is_compatible(): void
    {
        ExistingCarbonMapperObservationsSchema::assertCompatible();
        $this->assertFalse(ExistingCarbonMapperObservationsSchema::hasLegacyUniqueIndex());
    }

    public function test_legacy_unique_key_is_accepted_and_subsequent_drop_is_idempotent(): void
    {
        Schema::table('carbon_mapper_observations', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->unique(['observation_id', 'gas']);
        });

        $this->assertTrue(ExistingCarbonMapperObservationsSchema::hasLegacyUniqueIndex());
        $this->creationMigration()->up();
        $this->dropUniqueMigration()->up();
        $this->dropUniqueMigration()->up();
        $this->assertFalse(ExistingCarbonMapperObservationsSchema::hasLegacyUniqueIndex());
        ExistingCarbonMapperObservationsSchema::assertCompatible();
    }

    public function test_missing_columns_fail_closed(): void
    {
        Schema::shouldReceive('hasTable')->twice()
            ->with('carbon_mapper_observations')->andReturn(true);
        Schema::shouldReceive('getColumnListing')->once()
            ->with('carbon_mapper_observations')->andReturn(['id']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing columns:');
        $this->creationMigration()->up();
    }

    public function test_mariadb_raw_payload_json_alias_is_validated(): void
    {
        $ddl = <<<'SQL'
CREATE TABLE @BT@carbon_mapper_observations@BT@ (
  @BT@id@BT@ bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  @BT@raw_payload@BT@ longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(@BT@raw_payload@BT@)),
  PRIMARY KEY (@BT@id@BT@)
)
SQL;
        $ddl = str_replace('@BT@', chr(96), $ddl);
        $this->assertTrue(ExistingCarbonMapperObservationsSchema::isValidatedMariaDbJsonAlias(
            'longtext', 'utf8mb4_bin', $ddl
        ));
        $this->assertFalse(ExistingCarbonMapperObservationsSchema::isValidatedMariaDbJsonAlias(
            'longtext', 'utf8mb4_unicode_ci', $ddl
        ));
    }

    public function test_longtext_without_column_local_json_check_is_rejected(): void
    {
        $cases = [
            '@BT@raw_payload@BT@ longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL',
            '@BT@raw_payload@BT@ longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(@BT@notes@BT@))',
            "@BT@raw_payload@BT@ longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,\n  @BT@notes@BT@ text CHECK (json_valid(@BT@raw_payload@BT@))",
        ];
        foreach ($cases as $definition) {
            $ddl = str_replace('@BT@', chr(96), "CREATE TABLE @BT@carbon_mapper_observations@BT@ (\n  ".$definition."\n)");
            $this->assertFalse(ExistingCarbonMapperObservationsSchema::isValidatedMariaDbJsonAlias(
                'longtext', 'utf8mb4_bin', $ddl
            ));
        }
    }

    public function test_creation_rollback_refuses_to_delete_imported_observations(): void
    {
        DB::table('carbon_mapper_observations')->insert([
            'observation_id' => 'historic-rollback-001',
            'gas' => 'CH4',
            'latitude' => 40.4608560,
            'longitude' => -109.5621800,
            'raw_payload' => '{}',
        ]);

        try {
            $this->creationMigration()->down();
            $this->fail('Rollback must not delete imported observations.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Refusing to drop carbon_mapper_observations', $e->getMessage());
        }
        $this->assertSame(1, DB::table('carbon_mapper_observations')
            ->where('observation_id', 'historic-rollback-001')->count());
    }
}
