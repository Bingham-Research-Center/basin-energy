<?php

namespace App\Console\Commands;

use App\Support\LegacyMigrationSchemaInspector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Recover Laravel migration ledger entries for schema changes that were
 * already applied historically, without modifying original migration code.
 *
 * Dry-run by default. --apply inserts migration-ledger records only after the
 * independent read-only audit confirms they are satisfied by the live schema.
 */
final class ReconcileLegacyMigrations extends Command
{
    protected $signature = 'schema:reconcile-migrations
        {--apply : Record only fully validated legacy migrations as completed}';

    protected $description = 'Safely reconcile missing historical migration ledger entries without changing data or schema';

    public function handle(
        AuditLegacyMigrations $audit,
        LegacyMigrationSchemaInspector $inspector
    ): int {
        if (DB::getDriverName() !== 'mysql') {
            $this->error('Schema reconciliation requires MySQL/MariaDB.');
            return self::FAILURE;
        }

        $report = $audit->buildReport($inspector);
        $blocked = $this->blockingFindings($report);
        $candidates = $this->adoptableFindings($report);

        $this->info(count($candidates).' already-satisfied migration(s) can be recorded.');
        foreach ($candidates as $row) {
            $this->line('  '.$row['migration'].' ['.$row['table'].']');
        }

        if ($blocked !== []) {
            $this->error('Reconciliation blocked by '.count($blocked).' audit finding(s):');
            foreach ($blocked as $row) {
                $this->error('  '.$row['migration'].': '.$row['detail']);
            }
            $this->warn('No migration-history, application, or research data was changed.');
            return self::FAILURE;
        }

        if ($candidates === []) {
            $this->info('Nothing to adopt. Run normal Laravel migrations for pending create/alter operations.');
            return self::SUCCESS;
        }

        if (! $this->option('apply')) {
            $this->warn('DRY RUN: no records or schema changed.');
            $this->line('After verifying your backup, run this command with --apply to update only the migrations ledger.');
            $this->line('Then run php artisan migrate --force for the remaining genuine schema changes.');
            return self::SUCCESS;
        }

        try {
            $inserted = DB::transaction(function () use ($audit, $inspector): array {
                // Serialize concurrent ledger writes within this transaction.
                DB::table('migrations')->orderBy('id')->lockForUpdate()->get(['id']);

                // Recheck against the actual database immediately before writes.
                // A changed schema or a new pending migration fails closed.
                $current = $audit->buildReport($inspector);
                $failures = $this->blockingFindings($current);
                if ($failures !== []) {
                    throw new RuntimeException(
                        'Database changed during reconciliation; aborting without ledger changes.'
                    );
                }

                $entries = $this->adoptableFindings($current);
                $batch = ((int) DB::table('migrations')->max('batch')) + 1;
                $names = [];

                foreach ($entries as $entry) {
                    $name = $entry['migration'];
                    if (DB::table('migrations')->where('migration', $name)->exists()) {
                        continue;
                    }

                    // Only migration names explicitly catalogued and classified
                    // as adoptable by AuditLegacyMigrations reach this branch.
                    DB::table('migrations')->insert([
                        'migration' => $name,
                        'batch' => $batch,
                    ]);
                    $names[] = $name;
                }

                return $names;
            });

            $this->info('Recorded '.count($inserted).' verified migration(s) in the Laravel migrations ledger.');
            $this->line('No research data, application files, or database table definitions were altered.');
            $this->warn('Historical migrations may have destructive down() methods. Do NOT use migrate:rollback/reset on imported datasets.');
            $this->line('Now run php artisan schema:audit-migrations, then php artisan migrate --force.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Reconciliation aborted: '.$exception->getMessage());
            $this->warn('The database transaction rolled back; inspect the read-only audit before retrying.');
            return self::FAILURE;
        }
    }

    /** @return list<array<string, mixed>> */
    private function blockingFindings(array $report): array
    {
        return array_values(array_filter($report, static fn (array $row): bool =>
            in_array($row['state'], ['BLOCKED', 'review'], true)
        ));
    }

    /** @return list<array<string, mixed>> */
    private function adoptableFindings(array $report): array
    {
        return array_values(array_filter($report, static fn (array $row): bool =>
            $row['state'] === 'adoptable' && $row['table'] !== null
        ));
    }
}
