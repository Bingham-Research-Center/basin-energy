# Production schema-history reconciliation: emission_trends

## Why regular migration failed

The production database already had `emission_trends`, but its entry
`2026_01_30_164035_create_emission_trends_table` was missing from Laravel's
`migrations` history. Laravel therefore tried `CREATE TABLE` and MySQL
returned SQLSTATE 42S01 (table already exists).

## Non-destructive correction

The original migration now:
1. Creates the table normally on clean installations.
2. When the table already exists, checks **all expected columns**, MySQL
   column types/precision/nullability, primary key, year unique index, and
   auto-increment ID (SQLite has corresponding local checks).
3. Returns successfully **only if the table is compatible**, so Laravel's
   migrator records the previously missing history entry.
4. Aborts with a clear diagnostic if the schema is incomplete or incompatible.
   It **never** drops, recreates, empties, or overwrites production data.
5. Refuses rollback because an adopted table may contain historical data
   predating the migration. A deliberate, backed-up manual action is
   required if the table ever needs removing.

This PR only addresses the confirmed `emission_trends` collision; there
may be **other** legacy pending migrations against already-created tables.
Do not mark those as run merely because a table of the same name exists:
inspect their schemas and migration side effects first.

## Safe production workflow

**Before running migrations, take a verified database backup.**

First inspect the ledger without changing data:

```bash
docker exec -it brc_site php artisan migrate:status
```

Check the existing table schema and its migration record:

```bash
docker exec -it brc_mysql mysql -u <user> -p <database> -e "SHOW CREATE TABLE emission_trends\\G"
docker exec -it brc_mysql mysql -u <user> -p <database> -e "SELECT migration,batch FROM migrations WHERE migration='2026_01_30_164035_create_emission_trends_table';"
```

**Do not include database credentials on command lines in shared logs.**

After the PR merges, deploy through the normal fast-forward workflow:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
```

If `migrate:status` still shows the emission-trends migration pending,
run only that migration first:

```bash
docker exec -it brc_site php artisan migrate \\
  --path=database/migrations/2026_01_30_164035_create_emission_trends_table.php \\
  --force
```

A successful run marks the migration as completed without altering the
existing compatible table. **If validation fails, stop and reconcile the
reported schema difference before retrying. Do not force migration rows
into the database manually.**

Then inspect remaining pending migrations using `migrate:status`. Run
`php artisan migrate --force` only when the remaining pending migrations
have been audited; separate existing-table collisions may still need a
targeted follow-up patch.

The Broadcast migration
`2026_11_20_000000_create_broadcast_module_tables` was already run
successfully using its targeted `--path` command. Do not rerun it
unnecessarily.

## Tests

`tests/Feature/EmissionTrendsMigrationReconciliationTest.php` covers:
- Existing rows surviving repeated `up()` calls.
- Missing columns triggering a clear exception, without changing the table.
- Schema compatibility on the CI database driver.
- Rollback failing closed while retaining records.

The normal CI `php artisan migrate --force` step still validates clean
database installation.
