# Adopting an existing Carbon Mapper observations table safely

## Why production migration stopped

The migration `2026_02_06_222324_create_carbon_mapper_observations_table` tried to create `carbon_mapper_observations`, which already exists in the production database. The Laravel migrations ledger has no completed record for the creation migration. This yields MySQL error 1050.

This table may contain imported research data; **do not drop or recreate it**. Back up the database before continuing.

## Two related historical migrations

1. `2026_02_06_222324_create_carbon_mapper_observations_table` originally creates the table with a unique index on `(observation_id, gas)`.
2. `2026_02_08_194941_drop_unique_observation_id_gas_index_from_carbon_mapper_observations` removes that unique index.

An existing table may be at either stage. A schema checker accepts the original legacy index or its absence; the later index-removal migration checks for the original matching named index before dropping it. The migration does not delete or update observation rows. The rollback paths refuse destructive data loss or speculative uniqueness restoration.

## Validation

The read-only validator checks every required column's type and nullability, the auto-increment primary key, the `is_super_emitter` default, and the seven single-column indexes. It validates `raw_payload` as either native MySQL JSON or MariaDB's `LONGTEXT` JSON alias with `utf8mb4_bin` collation and a column-local `CHECK (json_valid(raw_payload))` constraint.

**Important:** This fix has not been validated against the actual production table DDL, only against the original migration and the observed table-already-exists error. If production differs, the migration will stop safely and print a specific mismatch instead of changing data.

## Inspect production before deployment

```bash
cd /var/www/brc_site
docker exec -it brc_site php artisan tinker
```

Inside Tinker:

```php
DB::table('carbon_mapper_observations')->count();
DB::select('SHOW CREATE TABLE carbon_mapper_observations');
DB::table('migrations')
    ->whereIn('migration', [
        '2026_02_06_222324_create_carbon_mapper_observations_table',
        '2026_02_08_194941_drop_unique_observation_id_gas_index_from_carbon_mapper_observations',
    ])->get();
```

## Deployment

After taking a verified backup, merging the PR and confirming CI:

```bash
git status --short
git pull --ff-only origin master
docker exec brc_site php artisan migrate --force
docker exec brc_site php artisan migrate:status
```

Re-check the observation count after migration. It must not decrease except as a result of independent deliberate data changes.

Other tables may also have been imported before their migrations were recorded. Investigate each new failure independently. Do not use `migrate:fresh`, `migrate:reset`, or blindly insert rows in the `migrations` ledger. No Composer or npm rebuild is required for this PHP-only change.
