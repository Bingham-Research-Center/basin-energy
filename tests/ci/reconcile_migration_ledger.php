<?php

/**
 * MySQL CI-only integration exercise: simulate valid historical tables with
 * missing Laravel migration ledger entries, reconcile, then prove idempotence.
 * This intentionally alters only the throwaway basinenergy_test database.
 */
require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! app()->environment('testing')
    || DB::getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'basinenergy_test') {
    throw new \RuntimeException('Refusing to run migration-ledger integration test outside basinenergy_test.');
}

$names = [
    '2026_04_04_013253_create_contact_messages_table',
    '2026_02_08_194941_drop_unique_observation_id_gas_index_from_carbon_mapper_observations',
    '2026_09_24_220508_add_button_fields_to_services_page_contents_table',
];

$originalCount = DB::table('contact_messages')->count();
$originalColumns = Schema::getColumnListing('services_page_contents');

DB::table('migrations')->whereIn('migration', $names)->delete();

if (Artisan::call('schema:reconcile-migrations') !== 0) {
    throw new \RuntimeException('Read-only migration reconciliation preview failed.');
}
if (DB::table('migrations')->whereIn('migration', $names)->count() !== 0) {
    throw new \RuntimeException('Dry-run unexpectedly changed migration ledger.');
}

if (Artisan::call('schema:reconcile-migrations', ['--apply' => true]) !== 0) {
    throw new \RuntimeException('Validated migration ledger reconciliation failed.');
}
if (DB::table('migrations')->whereIn('migration', $names)->count() !== count($names)) {
    throw new \RuntimeException('Expected migration ledger entries were not adopted.');
}

if (Artisan::call('schema:reconcile-migrations', ['--apply' => true]) !== 0) {
    throw new \RuntimeException('Repeated reconciliation was not idempotent.');
}
if (DB::table('migrations')->whereIn('migration', $names)->count() !== count($names)) {
    throw new \RuntimeException('Repeated reconciliation inserted duplicate entries.');
}

if (DB::table('contact_messages')->count() !== $originalCount
    || Schema::getColumnListing('services_page_contents') !== $originalColumns) {
    throw new \RuntimeException('Reconciliation modified application schema or existing data.');
}

if (Artisan::call('schema:audit-migrations') !== 0) {
    throw new \RuntimeException('Post-reconciliation read-only audit failed.');
}

echo "Verified dry-run, schema-validated migration adoption, idempotence, and data preservation.\n";
