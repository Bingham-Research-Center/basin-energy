# Production migration reconciliation audit (November 2026)

## Scope and key findings

Production runs Laravel/PHP inside `brc_site` and has historically imported research and content tables before all migration names were recorded in the `migrations` ledger. The first migrations blocked by MySQL/MariaDB error 1050 were `produced_water_fluxes`, `produced_water_chemistry`, and `carbon_mapper_observations`. The first two were reconciled while preserving 398 flux and 79 chemistry records (as reported at that time); Carbon Mapper needs a separate `raw_payload` JSON constraint check.

This audit covers **all 48 migration files** currently in `database/migrations`, reports the ledger state of each, and preflights every **catalogued outstanding historical creation/alteration migration** in read-only mode. It does not have access to the live production DB or the user's backup in GitHub; actual drift remains unknown until the command runs in production.

### Architecture findings

- `2026_04_03_214557_create_blog_categories_table` **creates `blog_posts`**, not `blog_categories`. The later `2026_04_03_220515_create_blog_categories_table` actually creates `blog_categories` and attaches the category FK to `blog_category_post`. Do not delete either migration or rename their basenames: the ledger keys are meaningful.
- `2026_02_07_002933_create_sync_states_table` creates timestamps, and the next migration can add missing timestamps to older imports.
- `2026_02_08_194941_drop_unique_observation_id_gas_index_from_carbon_mapper_observations` deliberately removes the original composite uniqueness constraint. Both earlier and later index states are accommodated.
- `2026_04_03_213620_drop_categories_column_from_portfolio_items_table` historically discarded legacy categories. The patched migration fails safely if that column still contains non-null data.
- `2026_09_24_220508_add_button_fields_to_services_page_contents_table` must not add existing columns twice.
- `2026_04_08_032957_create_two_factor_authentications_table` may encounter a table already created by the 2021 migration.
- Research and content tables with pre-existing compatible schemas now use a **read-only comparison of Laravel-compiled CREATE statements** before allowing `migrate` to adopt an existing table.
- Historical imported-table creation migrations refuse destructive `down()` rollbacks. **Do not use `migrate:fresh` or `migrate:reset` in production**.

## How the preflight works

`LegacyMigrationSchemaInspector` compiles the *specific allowlisted* migration's SQL with Laravel `Connection::pretend()`: no SQL is executed. It compares declared columns (type, nullability, default, auto-increment), primary key, indexes and foreign keys with read-only MySQL `information_schema` queries. MariaDB's `LONGTEXT` JSON alias is accepted **only** when the column is `utf8mb4_bin` and an enforced `CHECK (json_valid(column))` applies to that column; native MySQL `JSON` also works. Historical integer display widths are normalized.

Existing **extra** columns or indexes are tolerated so later migrations can have already been applied; missing required fields, incompatible types, missing constraints, or inconsistent data **block adoption**. The preflight does not automatically delete, rewrite, or invent research values.

`schema:audit-migrations` reports one of these states:

| State | Meaning |
| --- | --- |
| `ran` | Recorded in Laravel's ledger |
| `create` | Table is absent; normal migration should create it |
| `adoptable` | Table already matches the expected migration; `migrate` will record it normally |
| `alter` | Known schema-change migration remains to be applied safely |
| `BLOCKED` | Drift or possible data loss; do not run migrations blindly |
| `review` | Future/unrecognised migration requiring explicit review |

**Important:** This command is read-only, unlike `migrate`. It does not alter schema, change the migration ledger, modify records, or drop tables. A failed status is useful, not a reason to skip validation.

## Production deployment sequence

The full database backup has already been taken. Preserve it externally and verify restore availability before performing DDL, especially on a large Carbon Mapper table. After PR CI and manual review:

```bash
cd /var/www/brc_site
git status --short
git pull --ff-only origin master

# Read-only inspection. Save the JSON report on your server.
docker exec brc_site php artisan schema:audit-migrations --json > /tmp/basinenergy-migration-audit.json

# Read-only preview of the specific Carbon Mapper JSON repair.
docker exec brc_site php artisan schema:repair-carbon-json
```

If Carbon Mapper `raw_payload` is ordinary LONGTEXT rather than a validated JSON alias, and the preview verifies **zero invalid or null payloads**, the guarded repair can be explicitly authorized:

```bash
# This command DOES execute ALTER TABLE DDL; MariaDB may lock the table.
docker exec brc_site php artisan schema:repair-carbon-json --apply

# Repeat the read-only audit. It should have no BLOCKED or review rows.
docker exec brc_site php artisan schema:audit-migrations
```

The repair will only work for a non-null `LONGTEXT` column using a recognized `utf8mb4` collation, with no index or unrecognized JSON CHECK. It changes `raw_payload` to `utf8mb4_bin` if necessary and adds a `JSON_VALID` constraint. Existing JSON values are not rewritten. If any values are invalid, or the schema differs in another way, it refuses to modify data and instructs an explicit repair. MariaDB DDL auto-commits: a collation ALTER may succeed before a subsequent CHECK attempt fails. Rerun the read-only preview in that case.

Once all blockers are resolved:

```bash
docker exec brc_site php artisan migrate --force
docker exec brc_site php artisan migrate:status
docker exec brc_site php artisan optimize:clear
```

Verify no migrations remain pending, that the admin dashboard loads, and the row counts for important datasets remain unchanged relative to the backup (unless legitimate imports occurred). Example:

```php
DB::table('produced_water_fluxes')->count();
DB::table('produced_water_chemistry')->count();
DB::table('carbon_mapper_observations')->count();
DB::table('contact_messages')->count();
```

**Do not** manually insert `migrations` ledger entries, drop/recreate imported tables, run `migrate:fresh`, or force a destructive `down()`. The regular Laravel Migrator records each successful adoption after the guard passes.

## CI and limits

The new PHPUnit tests cover SQL parsing, MariaDB JSON validity checks, SQLite comparison of representative historical migrations, idempotent adoption, existing row retention, and blocking destructive category drops/rollbacks.

The repository tests run with an in-memory SQLite database. Their success is important but **cannot prove compatibility with a production MariaDB schema**. Production constraints, row contents, existing FKs and indexes must be checked with the read-only audit command. The PR intentionally fails closed when incompatible; this is safer than silently marking an inconsistent DB up to date.
