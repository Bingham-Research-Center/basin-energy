<?php

namespace Tests\Feature;

use App\Console\Commands\AuditLegacyMigrations;
use App\Support\LegacyMigrationSchemaInspector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyMigrationReconciliationTest extends TestCase
{
    public function test_expected_create_sql_parser_preserves_types_defaults_and_nullability(): void
    {
        $sql = <<<'SQL'
create table @BT@carbon_mapper_observations@BT@ (@BT@id@BT@ bigint unsigned not null auto_increment primary key, @BT@latitude@BT@ decimal(10, 7) not null, @BT@raw_payload@BT@ json not null, @BT@is_super_emitter@BT@ tinyint(1) not null default '1')
SQL;
        $columns = LegacyMigrationSchemaInspector::parseCreateColumns(
            str_replace('@BT@', chr(96), $sql)
        );

        $this->assertSame('bigint unsigned', $columns['id']['type']);
        $this->assertTrue($columns['id']['primary']);
        $this->assertTrue($columns['id']['auto_increment']);
        $this->assertSame('decimal(10,7)',
            LegacyMigrationSchemaInspector::canonicalType($columns['latitude']['type']));
        $this->assertFalse($columns['latitude']['nullable']);
        $this->assertSame('json', $columns['raw_payload']['type']);
        $this->assertSame('1', $columns['is_super_emitter']['default']);
    }

    public function test_mariadb_quoted_defaults_are_normalized_without_hiding_drift(): void
    {
        $this->assertTrue(LegacyMigrationSchemaInspector::defaultsMatch("'CH4'", 'CH4'));
        $this->assertTrue(LegacyMigrationSchemaInspector::defaultsMatch('CH4', 'CH4'));
        $this->assertTrue(LegacyMigrationSchemaInspector::defaultsMatch("'sha1'", 'sha1'));
        $this->assertTrue(LegacyMigrationSchemaInspector::defaultsMatch("'it''s safe'", "it's safe"));
        $this->assertFalse(LegacyMigrationSchemaInspector::defaultsMatch(null, 'CH4'));
        $this->assertFalse(LegacyMigrationSchemaInspector::defaultsMatch("'CO2'", 'CH4'));
        $this->assertFalse(LegacyMigrationSchemaInspector::defaultsMatch("'sha256'", 'sha1'));
    }

    public function test_legacy_two_factor_secret_storage_types_are_explicitly_supported(): void
    {
        $this->assertTrue(\App\\Support\\ExistingTwoFactorAuthenticationSchema::isSecretStorageType('text'));
        $this->assertTrue(\App\\Support\\ExistingTwoFactorAuthenticationSchema::isSecretStorageType('varchar'));
        $this->assertTrue(\App\\Support\\ExistingTwoFactorAuthenticationSchema::isRecoveryStorageType('text'));
        $this->assertTrue(\App\\Support\\ExistingTwoFactorAuthenticationSchema::isRecoveryStorageType('json'));
        $this->assertFalse(\App\\Support\\ExistingTwoFactorAuthenticationSchema::isSecretStorageType('int'));
        $this->assertFalse(\App\\Support\\ExistingTwoFactorAuthenticationSchema::isRecoveryStorageType('blob'));
    }

    public function test_real_export_shape_without_raw_payload_json_check_is_rejected(): void
    {
        // Extracted DDL shape from the structure-only November 20, 2026
        // production export (no user data is committed to this repository).
        $ddl = <<<'SQL'
CREATE TABLE @BT@carbon_mapper_observations@BT@ (
  @BT@raw_payload@BT@ longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  @BT@created_at@BT@ timestamp NULL DEFAULT NULL
)
SQL;
        $ddl = str_replace('@BT@', chr(96), $ddl);

        $this->assertFalse(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'raw_payload', 'longtext', 'utf8mb4_bin', $ddl
        ));
    }

    public function test_named_json_check_is_accepted_only_for_the_correct_column(): void
    {
        $ddl = <<<'SQL'
CREATE TABLE @BT@carbon_mapper_observations@BT@ (
  @BT@raw_payload@BT@ longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  CONSTRAINT @BT@carbon_mapper_raw_payload_json_valid@BT@ CHECK (json_valid(@BT@raw_payload@BT@))
)
SQL;
        $ddl = str_replace('@BT@', chr(96), $ddl);

        $this->assertTrue(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'raw_payload', 'longtext', 'utf8mb4_bin', $ddl
        ));
        $this->assertFalse(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'raw_payload', 'longtext', 'utf8mb4_unicode_ci', $ddl
        ));
        $this->assertFalse(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'other_column', 'longtext', 'utf8mb4_bin', $ddl
        ));
    }

    public function test_original_migrations_match_existing_tables_in_clean_test_database(): void
    {
        $inspector = app(LegacyMigrationSchemaInspector::class);

        foreach ([
            ['2026_02_07_002933_create_sync_states_table', 'sync_states'],
            ['2026_04_02_230814_create_about_page_contents_table', 'about_page_contents'],
            ['2026_04_03_001116_create_services_page_contents_table', 'services_page_contents'],
            ['2026_04_03_003950_create_portfolio_items_table', 'portfolio_items'],
            ['2026_04_03_214557_create_blog_categories_table', 'blog_posts'],
            ['2026_04_04_013253_create_contact_messages_table', 'contact_messages'],
            ['2026_11_04_162803_create_carbon_mapper_source_measurements_table', 'carbon_mapper_source_measurements'],
        ] as [$migration, $table]) {
            $this->assertSame([], $inspector->inspect($migration, $table), $migration);
        }
    }

    public function test_read_only_audit_classifies_an_existing_table_without_ledger_entry(): void
    {
        DB::table('migrations')
            ->where('migration', '2026_04_04_013253_create_contact_messages_table')
            ->delete();

        $before = DB::table('contact_messages')->count();
        $report = (new AuditLegacyMigrations())->buildReport(
            app(LegacyMigrationSchemaInspector::class)
        );

        $contact = collect($report)->firstWhere(
            'migration', '2026_04_04_013253_create_contact_messages_table'
        );

        $this->assertSame('adoptable', $contact['state']);
        $this->assertSame($before, DB::table('contact_messages')->count());
        $this->assertSame(0, DB::table('migrations')
            ->where('migration', '2026_04_04_013253_create_contact_messages_table')->count());
    }

    public function test_audit_rejects_drift_without_modifying_existing_rows(): void
    {
        Schema::dropIfExists('test_sensor_readings');
        Schema::create('test_sensor_readings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('old_import_identifier')->nullable();
        });
        DB::table('test_sensor_readings')->insert(['old_import_identifier' => 'preserve-me']);
        $before = DB::table('test_sensor_readings')->first();

        $issues = app(LegacyMigrationSchemaInspector::class)->inspect(
            '2026_04_05_002933_create_test_sensor_readings_table', 'test_sensor_readings'
        );

        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('missing column', implode('; ', $issues));
        $after = DB::table('test_sensor_readings')->first();
        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->old_import_identifier, $after->old_import_identifier);
    }

    public function test_ledger_reconciler_refuses_non_mysql_connection(): void
    {
        $this->assertSame('sqlite', DB::getDriverName());
        $this->assertSame(1, Artisan::call('schema:reconcile-migrations'));
    }
}
