<?php

namespace App\Console\Commands;

use App\Support\ExistingCarbonMapperObservationsSchema;
use App\Support\LegacyMigrationSchemaInspector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Read-only preflight for historical migrations whose database objects may
 * predate Laravel's migrations ledger. Does not run migrations or write rows.
 */
class AuditLegacyMigrations extends Command
{
    protected $signature = 'schema:audit-migrations {--json : Emit a JSON audit report}';

    protected $description = 'Read-only check of every pending historical migration against the existing database';

    /** Migration basename => actual table created. Blog posts has a misleading filename. */
    private const CREATES = [
        '2026_01_30_164035_create_emission_trends_table' => 'emission_trends',
        '2026_02_06_171501_create_produced_water_fluxes_table' => 'produced_water_fluxes',
        '2026_02_06_171506_create_produced_water_chemistry_table' => 'produced_water_chemistry',
        '2026_02_06_222324_create_carbon_mapper_observations_table' => 'carbon_mapper_observations',
        '2026_02_07_002933_create_sync_states_table' => 'sync_states',
        '2026_04_02_230814_create_about_page_contents_table' => 'about_page_contents',
        '2026_04_03_001116_create_services_page_contents_table' => 'services_page_contents',
        '2026_04_03_003825_create_portfolio_page_contents_table' => 'portfolio_page_contents',
        '2026_04_03_003950_create_portfolio_items_table' => 'portfolio_items',
        '2026_04_03_010040_create_portfolio_categories_table' => 'portfolio_categories',
        '2026_04_03_062100_create_portfolio_item_category_table' => 'portfolio_item_category',
        '2026_04_03_214527_create_blog_page_contents_table' => 'blog_page_contents',
        '2026_04_03_214557_create_blog_categories_table' => 'blog_posts',
        '2026_04_03_214630_create_blog_category_post_table' => 'blog_category_post',
        '2026_04_03_220515_create_blog_categories_table' => 'blog_categories',
        '2026_04_04_013225_create_contact_page_contents_table' => 'contact_page_contents',
        '2026_04_04_013253_create_contact_messages_table' => 'contact_messages',
        '2026_04_05_002933_create_test_sensor_readings_table' => 'test_sensor_readings',
        '2026_09_23_153859_create_home_page_contents_table' => 'home_page_contents',
        '2026_11_04_162803_create_carbon_mapper_source_measurements_table' => 'carbon_mapper_source_measurements',
        '2026_11_04_172430_create_carbon_mapper_plume_outlines_table' => 'carbon_mapper_plume_outlines',
    ];

    private const ALTERATIONS = [
        '2026_02_07_003620_add_timestamps_to_sync_states_table' => 'sync_states',
        '2026_02_08_194941_drop_unique_observation_id_gas_index_from_carbon_mapper_observations' => 'carbon_mapper_observations',
        '2026_04_03_213620_drop_categories_column_from_portfolio_items_table' => 'portfolio_items',
        '2026_09_24_220508_add_button_fields_to_services_page_contents_table' => 'services_page_contents',
    ];

    public function handle(LegacyMigrationSchemaInspector $inspector): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->error('Strict schema reconciliation audit requires MySQL/MariaDB.');
            return self::FAILURE;
        }

        $ran = array_fill_keys(DB::table('migrations')->pluck('migration')->all(), true);
        $files = glob(database_path('migrations/*.php')) ?: [];
        sort($files);
        $report = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $table = self::CREATES[$name] ?? self::ALTERATIONS[$name] ?? null;
            $status = isset($ran[$name]) ? 'ran' : 'pending';
            $detail = '';

            // A ledger entry is not proof that its table still exists. Check
            // every literal CREATE TABLE target, including multi-table
            // migrations such as the broadcast module, without executing code.
            if ($status === 'ran') {
                $source = file_get_contents($file);
                preg_match_all("/Schema::create\\(\\s*'([^']+)'/", $source, $created);
                foreach (array_unique($created[1]) as $createdTable) {
                    if (! Schema::hasTable($createdTable)) {
                        $status = 'BLOCKED';
                        $detail .= 'Migration recorded as ran, but '.$createdTable.' is missing. ';
                    }
                }
            }

            if ($status === 'pending' && isset(self::CREATES[$name])) {
                try {
                    if (! Schema::hasTable($table)) {
                        $status = 'create';
                        $detail = 'Table absent; normal Laravel migration should create it.';
                    } else {
                        $issues = $inspector->inspect($name, $table);
                        $status = $issues === [] ? 'adoptable' : 'BLOCKED';
                        $detail = $issues === []
                            ? 'Existing schema compatible; normal migration can adopt it.'
                            : implode('; ', $issues);
                        if ($name === '2026_04_03_220515_create_blog_categories_table'
                            && Schema::hasTable('blog_category_post')) {
                            $detail .= ' The migration also checks/repairs the pivot category FK.';
                        }
                    }
                } catch (Throwable $exception) {
                    $status = 'BLOCKED';
                    $detail = $exception->getMessage();
                }
            } elseif ($status === 'pending' && isset(self::ALTERATIONS[$name])) {
                try {
                    [$status, $detail] = $this->inspectAlteration($name, $table);
                } catch (Throwable $exception) {
                    $status = 'BLOCKED';
                    $detail = $exception->getMessage();
                }
            } elseif ($status === 'pending' && $name === '2026_04_08_032957_create_two_factor_authentications_table') {
                try {
                    $issues = Schema::hasTable('two_factor_authentications')
                        ? $inspector->inspect('2021_04_05_153840_create_two_factor_authentications_table', 'two_factor_authentications')
                        : [];
                    $status = $issues === [] ? (Schema::hasTable('two_factor_authentications') ? 'adoptable' : 'create') : 'BLOCKED';
                    $detail = implode('; ', $issues);
                    $table = 'two_factor_authentications';
                } catch (Throwable $exception) {
                    $status = 'BLOCKED';
                    $detail = $exception->getMessage();
                }
            } elseif ($status === 'pending') {
                $status = 'review';
                $detail = 'Not a catalogued historical create/alter; inspect before migration.';
            }

            $report[] = [
                'migration' => $name,
                'table' => $table,
                'state' => $status,
                'detail' => $detail,
            ];
        }

        $counts = array_count_values(array_column($report, 'state'));
        if ($this->option('json')) {
            $this->line(json_encode([
                'read_only' => true,
                'database_driver' => DB::getDriverName(),
                'database_name' => DB::connection()->getDatabaseName(),
                'summary' => $counts,
                'migrations' => $report,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(
                ['Migration', 'Table', 'State', 'Details'],
                array_map(static fn (array $row) => [
                    $row['migration'], $row['table'] ?? '—', $row['state'], $row['detail'],
                ], $report)
            );
            $this->line('Read-only audit. No migrations ran and no data or schema changed.');
            $this->line('Summary: '.json_encode($counts));
        }

        return ($counts['BLOCKED'] ?? 0) > 0 || ($counts['review'] ?? 0) > 0
            ? self::FAILURE : self::SUCCESS;
    }

    /** @return array{string, string} */
    private function inspectAlteration(string $name, string $table): array
    {
        if (! Schema::hasTable($table)) {
            return ['BLOCKED', 'Prerequisite table '.$table.' is missing.'];
        }

        if ($name === '2026_02_07_003620_add_timestamps_to_sync_states_table') {
            $exists = Schema::hasColumn('sync_states', 'created_at')
                && Schema::hasColumn('sync_states', 'updated_at');
            return [$exists ? 'adoptable' : 'alter',
                $exists ? 'Timestamps already present; migration is idempotent.'
                    : 'Migration will add missing nullable timestamps.'];
        }

        if ($name === '2026_02_08_194941_drop_unique_observation_id_gas_index_from_carbon_mapper_observations') {
            $exists = ExistingCarbonMapperObservationsSchema::hasLegacyUniqueIndex();
            return [$exists ? 'alter' : 'adoptable',
                $exists ? 'Migration will remove the legacy named unique index.'
                    : 'Legacy unique index already absent; migration is idempotent.'];
        }

        if ($name === '2026_04_03_213620_drop_categories_column_from_portfolio_items_table') {
            if (! Schema::hasColumn('portfolio_items', 'categories')) {
                return ['adoptable', 'Legacy categories column already absent.'];
            }
            if (DB::table('portfolio_items')->whereNotNull('categories')->exists()) {
                return ['BLOCKED', 'Non-null legacy categories would be lost; migrate values into pivot first.'];
            }
            return ['alter', 'Legacy categories are entirely NULL; migration may remove empty column.'];
        }

        if ($name === '2026_09_24_220508_add_button_fields_to_services_page_contents_table') {
            $missing = [];
            foreach (['button_text', 'button_link'] as $col) {
                if (! Schema::hasColumn('services_page_contents', $col)) {
                    $missing[] = $col;
                    continue;
                }
                $row = DB::selectOne(
                    'SELECT COLUMN_TYPE AS type, IS_NULLABLE AS nullable '.
                    'FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() '.
                    'AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    ['services_page_contents', $col]
                );
                if (! $row || strtolower($row->type) !== 'varchar(255)' || $row->nullable !== 'YES') {
                    return ['BLOCKED', $col.' differs from nullable varchar(255).'];
                }
            }
            return [$missing === [] ? 'adoptable' : 'alter',
                $missing === [] ? 'Both fields already exist.' : 'Will add missing fields: '.implode(', ', $missing)];
        }

        return ['review', 'Unknown alteration migration.'];
    }
}
