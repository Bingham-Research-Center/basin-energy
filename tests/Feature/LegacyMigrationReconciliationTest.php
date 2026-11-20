<?php

namespace Tests\Feature;

use App\Support\LegacyMigrationSchemaInspector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class LegacyMigrationReconciliationTest extends TestCase
{
    private function migration(string $file)
    {
        $path = database_path('migrations/'.$file.'.php');
        if (preg_match('/\\breturn\\s+new\\s+class\\b/', file_get_contents($path))) {
            return require $path;
        }
        require_once $path;
        return app('migrator')->resolve($file);
    }

    public function test_expected_create_sql_parser_handles_integer_decimal_and_defaults(): void
    {
        $sql = <<<'SQL'
create table @BT@carbon_mapper_observations@BT@ (@BT@id@BT@ bigint unsigned not null auto_increment primary key, @BT@latitude@BT@ decimal(10, 7) not null, @BT@raw_payload@BT@ json not null, @BT@is_super_emitter@BT@ tinyint(1) not null default '1') default character set utf8mb4
SQL;
        $columns = LegacyMigrationSchemaInspector::parseCreateColumns(
            str_replace('@BT@', chr(96), $sql)
        );

        $this->assertSame('bigint unsigned', $columns['id']['type']);
        $this->assertTrue($columns['id']['primary']);
        $this->assertTrue($columns['id']['auto_increment']);
        $this->assertSame('decimal(10, 7)', $columns['latitude']['type']);
        $this->assertSame('decimal(10,7)', LegacyMigrationSchemaInspector::canonicalType($columns['latitude']['type']));
        $this->assertFalse($columns['latitude']['nullable']);
        $this->assertSame('json', $columns['raw_payload']['type']);
        $this->assertSame('1', $columns['is_super_emitter']['default']);
    }

    public function test_json_check_must_apply_to_correct_column(): void
    {
        $valid = <<<'SQL'
CREATE TABLE @BT@carbon_mapper_observations@BT@ (
  @BT@raw_payload@BT@ longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  CONSTRAINT @BT@carbon_mapper_raw_payload_json_valid@BT@ CHECK (json_valid(@BT@raw_payload@BT@))
)
SQL;
        $valid = str_replace('@BT@', chr(96), $valid);
        $this->assertTrue(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'raw_payload', 'longtext', 'utf8mb4_bin', $valid
        ));
        $this->assertFalse(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'raw_payload', 'longtext', 'utf8mb4_unicode_ci', $valid
        ));
        $this->assertFalse(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'other_column', 'longtext', 'utf8mb4_bin', $valid
        ));
        $this->assertFalse(LegacyMigrationSchemaInspector::validMariaDbJsonAlias(
            'raw_payload', 'text', 'utf8mb4_bin', $valid
        ));
    }

    public function test_existing_legacy_tables_are_compatible_with_their_original_migrations(): void
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

    public function test_existing_contact_message_survives_migration_adoption(): void
    {
        DB::table('contact_messages')->insert([
            'name' => 'Historic contact',
            'email' => 'historic@example.test',
            'message' => 'Preserve this record',
            'is_read' => false,
        ]);

        $before = DB::table('contact_messages')->where('name', 'Historic contact')->first();
        $this->migration('2026_04_04_013253_create_contact_messages_table')->up();
        $this->migration('2026_04_04_013253_create_contact_messages_table')->up();
        $after = DB::table('contact_messages')->where('name', 'Historic contact')->first();

        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->message, $after->message);
        $this->assertSame(1, DB::table('contact_messages')->where('name', 'Historic contact')->count());
    }

    public function test_incompatible_existing_table_fails_without_deleting_rows(): void
    {
        Schema::dropIfExists('test_sensor_readings');
        Schema::create('test_sensor_readings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('old_import_identifier')->nullable();
        });
        DB::table('test_sensor_readings')->insert(['old_import_identifier' => 'preserve-me']);

        $exception = null;
        try {
            $this->migration('2026_04_05_002933_create_test_sensor_readings_table')->up();
        } catch (RuntimeException $error) {
            $exception = $error;
        }

        $this->assertNotNull($exception);
        $this->assertStringContainsString('missing column', $exception->getMessage());
        $this->assertSame(1, DB::table('test_sensor_readings')->count());
    }

    public function test_destructive_portfolio_category_drop_requires_empty_legacy_column(): void
    {
        Schema::table('portfolio_items', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->text('categories')->nullable();
        });
        DB::table('portfolio_items')->insert(['title' => 'Historic project', 'categories' => '["energy"]']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing to discard portfolio_items.categories');

        $this->migration('2026_04_03_213620_drop_categories_column_from_portfolio_items_table')->up();
    }

    public function test_services_buttons_migration_is_idempotent(): void
    {
        $this->migration('2026_09_24_220508_add_button_fields_to_services_page_contents_table')->up();
        $this->migration('2026_09_24_220508_add_button_fields_to_services_page_contents_table')->up();

        $this->assertTrue(Schema::hasColumn('services_page_contents', 'button_text'));
        $this->assertTrue(Schema::hasColumn('services_page_contents', 'button_link'));
    }

    public function test_final_blog_category_fk_reconciliation_is_idempotent(): void
    {
        $this->assertTrue(Schema::hasTable('blog_categories'));
        $this->assertTrue(Schema::hasTable('blog_category_post'));
        $this->assertFalse(\App\Support\BlogCategoryPivotSchema::hasOrphans());

        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support adding foreign keys to an existing
            // pivot table. MySQL CI independently verifies the actual FK.
            return;
        }

        $this->migration('2026_11_23_000000_ensure_blog_category_pivot_foreign_key')->up();
        $this->migration('2026_11_23_000000_ensure_blog_category_pivot_foreign_key')->up();

        $this->assertTrue(\App\Support\BlogCategoryPivotSchema::exists());
    }

    public function test_legacy_import_rollback_refuses_to_drop_table(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Refusing rollback');
        $this->migration('2026_04_04_013253_create_contact_messages_table')->down();
    }
}
