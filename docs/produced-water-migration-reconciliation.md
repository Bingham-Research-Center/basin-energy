# Adopting an existing produced_water_fluxes table safely

## Why the November 2026 production migration failed

Production already had a populated `produced_water_fluxes` table,
but the `2026_02_06_171501_create_produced_water_fluxes_table`
migration was missing from Laravel's `migrations` ledger. The original
migration unconditionally called `Schema::create()` and failed with MySQL
error 1050 ("Table already exists").

The fix uses the same **fail-closed adoption** approach as the existing
`emission_trends` migration:

1. If the table is missing, create it using the original definition.
2. If it exists, check all original column names, MySQL column types,
   nullability, the default on `wind_corrected`, primary key and required
   indexes. Normalize historical integer display widths, not actual schema
   differences. Do **not** change or delete table contents.
3. Only if validation succeeds, return from the migration without
   creating anything. Laravel then records the migration as completed.
4. If validation fails, throw an actionable exception and **do not**
   mark the migration as applied. Back up and compare the actual table
   before making any explicit schema corrections.

The migration's `down()` now refuses to drop a potentially imported
research dataset. Schema differences may require a separate remediation;
this PR does not attempt a destructive or speculative repair.

## Pre-deployment inspection

Make a proper database backup before continuing. Verify the deployed
branch is fully up to date and run on the correct production container.

```bash
cd /var/www/brc_site
docker exec -it brc_site php artisan tinker
```

Inside Tinker:

```php
Schema::hasTable('produced_water_fluxes');
DB::table('produced_water_fluxes')->count();
DB::select('SHOW CREATE TABLE produced_water_fluxes');
DB::table('migrations')->where('migration', '2026_02_06_171501_create_produced_water_fluxes_table')->get();
```

The table inspection should match the migration definition, including
the `unique_id` index and compound `(state, facility_id)` index. You
should not need to change existing rows.

## Deployment after PR merge and passing CI

```bash
cd /var/www/brc_site
git status --short
git pull --ff-only origin master
docker exec brc_site php artisan migrate --force
docker exec brc_site php artisan migrate:status
```

Note that `migrate --force` continues through **all pending migrations**.
An unrelated historical dataset whose table exists without a ledger entry
might cause the next migration to fail. Diagnose those individually; do
not use `migrate:fresh`, `migrate:reset`, or blindly insert migration
ledger entries.

**No Composer or npm build is required** for this PHP-only fix. The
CI PHPUnit tests check idempotent adoption, data retention, strict schema
compatibility, width normalization, and refusal to drop imported rows.
