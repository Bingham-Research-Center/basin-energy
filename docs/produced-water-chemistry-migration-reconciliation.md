# Safely adopting historical produced-water chemistry data

Production already contains `produced_water_chemistry` with **79 records** but no ledger entry for `2026_02_06_171506_create_produced_water_chemistry_table`. The old migration attempted to recreate the table and stopped with MySQL error 1050.

The new migration checks the existing schema before adopting the migration. It validates columns, data types, nullability, auto-increment primary key and the three indexes. It accepts native MySQL JSON or MariaDB's `longtext` JSON alias only with binary utf8mb4 collation and a column-local `CHECK (json_valid(dissolved_compounds))` constraint. The inspected production definition is compatible.

**No existing rows or schema are modified.** An incompatible table raises an error rather than silently changing data or marking the migration applied. Rollback refuses to drop the chemistry table because it may contain historical imported research data.

The production table inspected November 20, 2026 has **79 rows**. Its `dissolved_compounds` column is `longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dissolved_compounds`))`.

## Deployment

Back up the production database before migrating. If the admin dashboard currently fails with `user_registration_admin_alerts` missing, it is possible to run that single migration first; its own documentation explains that migration must be run before the notification feature can work.

After PR merge and CI success:

```bash
cd /var/www/brc_site
git status --short
git pull --ff-only origin master
docker exec brc_site php artisan migrate --force
docker exec brc_site php artisan migrate:status
docker exec brc_site php artisan tinker
```

In Tinker, verify `DB::table('produced_water_chemistry')->count();` still equals 79 (unless legitimate subsequent imports occurred).

Migration commands may continue to the next pending migration and encounter another unrelated pre-existing table. Diagnose it separately; do not use `migrate:fresh`, `migrate:reset`, or manually insert ledger entries.

No Composer install or JavaScript asset build is required for this PHP-only change.
