<?php

namespace Tests\Feature;

use App\Support\ExistingEmissionTrendsSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class EmissionTrendsMigrationReconciliationTest extends TestCase
{
    private function historicalMigration(): \CreateEmissionTrendsTable
    {
        require_once database_path('migrations/2026_01_30_164035_create_emission_trends_table.php');

        return new \CreateEmissionTrendsTable();
    }

    public function test_existing_emission_trends_table_is_accepted_without_data_loss(): void
    {
        DB::table('emission_trends')->insert(['year' => 2019, 'oil_million_bbls' => 12.5]);
        $before = DB::table('emission_trends')->where('year', 2019)->first();

        $this->historicalMigration()->up();
        $this->historicalMigration()->up();

        $after = DB::table('emission_trends')->where('year', 2019)->first();
        $this->assertNotNull($after);
        $this->assertSame($before->id, $after->id);
        $this->assertEquals($before->oil_million_bbls, $after->oil_million_bbls);
        $this->assertSame(1, DB::table('emission_trends')->where('year', 2019)->count());
    }

    public function test_incomplete_existing_schema_is_rejected_without_mutation(): void
    {
        // Simulate a missing historical field without altering the real
        // CI database schema, which is shared across other feature tests.
        Schema::shouldReceive('hasTable')->once()
            ->with('emission_trends')->andReturn(true);
        Schema::shouldReceive('getColumnListing')->once()
            ->with('emission_trends')->andReturn(['id', 'year']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing columns:');
        $this->historicalMigration()->up();
    }

    public function test_existing_table_schema_has_a_unique_year_constraint(): void
    {
        // Validation includes column definitions and the UNIQUE year index.
        // This must pass on both the CI MySQL schema and SQLite test runs.
        ExistingEmissionTrendsSchema::assertCompatible();

        $this->assertTrue(Schema::hasColumn('emission_trends', 'year'));
    }

    public function test_rollback_refuses_to_drop_a_potentially_imported_dataset(): void
    {
        DB::table('emission_trends')->insert(['year' => 2023]);

        try {
            $this->historicalMigration()->down();
            $this->fail('Rollback must refuse to delete a potentially imported dataset.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Refusing to drop emission_trends', $exception->getMessage());
        }

        $this->assertSame(1, DB::table('emission_trends')->where('year', 2023)->count());
    }
}
