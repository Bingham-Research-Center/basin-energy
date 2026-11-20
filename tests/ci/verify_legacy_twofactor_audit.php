<?php

/**
 * Integration regression for an imported MariaDB-style two-factor table.
 * Runs on the disposable MySQL CI database only. Does not use production data.
 */
require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Console\Commands\AuditLegacyMigrations;
use App\Support\ExistingTwoFactorAuthenticationSchema;
use App\Support\LegacyMigrationSchemaInspector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

if (! app()->environment('testing')
    || DB::getDriverName() !== 'mysql'
    || DB::connection()->getDatabaseName() !== 'basinenergy_test') {
    throw new \RuntimeException('Refusing two-factor integration test outside basinenergy_test.');
}

$names = [
    '2026_04_08_032957_create_two_factor_authentications_table',
    '2026_11_04_162803_create_carbon_mapper_source_measurements_table',
];

// These are already marked Ran on clean CI. Simulate the legacy state where
// both tables exist but their migration ledger entries were never added.
DB::table('migrations')->whereIn('migration', $names)->delete();

DB::statement(
    'ALTER TABLE `two_factor_authentications` '.
    'MODIFY COLUMN `shared_secret` TEXT NOT NULL, '.
    'MODIFY COLUMN `recovery_codes` TEXT NULL'
);

try {
    $issues = ExistingTwoFactorAuthenticationSchema::issues();
    if ($issues !== []) {
        throw new \RuntimeException('Legacy two-factor table was rejected: '.implode('; ', $issues));
    }

    $rows = app(AuditLegacyMigrations::class)
        ->buildReport(app(LegacyMigrationSchemaInspector::class));
    foreach ($names as $name) {
        $row = collect($rows)->firstWhere('migration', $name);
        if (($row['state'] ?? '') !== 'adoptable') {
            throw new \RuntimeException('Expected adoptable '.$name.': '.json_encode($row));
        }
    }

    if (Artisan::call('schema:reconcile-migrations') !== 0) {
        throw new \RuntimeException('Read-only preview rejected valid legacy schema.');
    }
    if (DB::table('migrations')->whereIn('migration', $names)->count() !== 0) {
        throw new \RuntimeException('Preview unexpectedly changed ledger.');
    }

    if (Artisan::call('schema:reconcile-migrations', ['--apply' => true]) !== 0) {
        throw new \RuntimeException('Ledger reconciliation rejected valid legacy schema.');
    }
    if (DB::table('migrations')->whereIn('migration', $names)->count() !== count($names)) {
        throw new \RuntimeException('Not all validated legacy migrations were recorded.');
    }

    if (Artisan::call('schema:reconcile-migrations', ['--apply' => true]) !== 0) {
        throw new \RuntimeException('Reconciliation must remain idempotent.');
    }

    echo "Legacy TEXT secret/recovery storage and Carbon Mapper gas default reconciled safely.\n";
} finally {
    // Restore the original schema for the remaining PHPUnit test suite.
    DB::statement(
        'ALTER TABLE `two_factor_authentications` '.
        'MODIFY COLUMN `shared_secret` VARCHAR(255) NOT NULL, '.
        'MODIFY COLUMN `recovery_codes` JSON NULL'
    );
}
