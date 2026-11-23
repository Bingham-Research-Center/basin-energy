# Production schema reconciliation — structure-informed review

Updated November 20, 2026, using the privately supplied structure-only
`brc_database.sql` export from MariaDB 11.8.3. **The SQL export is not
committed**, and it contains no rows in INSERT statements. Record-level
data quality and the actual migration ledger must be checked on production.

## Why this is a narrow PR

Historically imported tables exist in production without corresponding
Laravel migration entries. A plain `php artisan migrate` attempts to recreate
them and fails. Earlier versions of this PR rewrote many original historical
migration files, including their `down()` methods. This revision **restores
all 20 altered original migration files unchanged** and removes the unnecessary
extra blog foreign-key migration.

The final approach separates three concerns:

1. `schema:audit-migrations` — strictly **read-only** audit of migration
   ledger, required tables, columns/types/indexes/FKs and known alterations.
2. `schema:repair-carbon-json` — **read-only by default**; an explicit
   `--apply` validates every existing `raw_payload` and fixes its JSON
   constraint only when safe.
3. `schema:reconcile-migrations` — **read-only by default**; an explicit
   `--apply` transactionally inserts Laravel `migrations` ledger entries
   **only for fully verified existing schemas**. This operation never changes
   research records or table structure.

Unresolved discrepancies block reconciliation. Later genuine migrations are
run by ordinary Laravel commands, not recreated in a separate repair system.

## Findings in the SQL structure export

The export contains **46 tables**. It has no row data and no migration-ledger
INSERT statements. It cannot prove record counts or valid JSON payloads.

| Object | Observed state | Action |
|---|---|---|
| `carbon_mapper_observations.raw_payload` | `LONGTEXT utf8mb4_bin NOT NULL`, no JSON_VALID CHECK | Validate every row in production, then add the missing CHECK with explicit `schema:repair-carbon-json --apply`. The exported collation already matches the JSON alias, so no collation change should be necessary. |
| `services_page_contents` | Both button_text and button_link are missing | **Run original** 2026-09-24 migration after adopting the table-creation migration. |
| `carbon_mapper_plume_outlines` | Table absent | **Run original** 2026-11-04 creation migration. |
| `blog_category_post` | Existing category/post foreign keys and unique index | Validate and preserve; do not create another FK migration. |
| `portfolio_items` | Legacy categories column already absent | Record completed drop migration only after confirming expected state. |
| `carbon_mapper_observations` | Historical (observation_id, gas) unique key already absent | Record completed index-drop migration only after confirming absence. |
| `sync_states` | created_at and updated_at already exist | No column alteration necessary. |
| `carbon_mapper_source_measurements` | Table and plume_id unique index exist | Validate and adopt if its creation migration is pending. |

The misleading file `2026_04_03_214557_create_blog_categories_table.php`
actually creates `blog_posts`, while a later file creates
`blog_categories`. Both migration names must remain unchanged so their
existing Laravel ledger references remain stable.

## How verification and adoption work

The schema inspector uses Laravel's SQL compiler in `Connection::pretend()`
to generate the expected schema for **explicitly catalogued creation
migrations**, then compares it to the actual `information_schema`. It checks
type, precision/scale, nullability, defaults, primary/foreign keys, indexes,
auto-increment, and the MariaDB JSON alias. Later known alterations are
classified separately.

The audit reports:

- `ran`: migration recorded and checked where supported.
- `create`: table absent; let the original migration create it.
- `adoptable`: complete existing schema or a verified already-performed alter.
- `alter`: a known, needed pending change; let the original migration run.
- `BLOCKED`: incompatible schema, missing dependency, or potential data loss.
- `review`: an unrecognized pending migration; do not auto-adopt it.

`schema:reconcile-migrations --apply` refuses to write anything while
a `BLOCKED` or `review` status exists. In a single database transaction
it locks the ledger, re-runs the audit to prevent stale assumptions, and
records only `adoptable` migrations. This is an explicit, operator-controlled
reconciliation, **not** automatic bypass during every Laravel deployment.
A second run makes no duplicate entries.

### Rollback caution

**Never run `migrate:rollback`, `migrate:reset` or `migrate:fresh` on this
production database** without a full impact review. We intentionally leave
historical migration files unchanged, including their original destructive
`down()` methods. Laravel cannot distinguish imported tables adopted through
its ledger from tables it originally created when rolling back.

## Production runbook

The existing verified backup must be retained, and a restore path should be
available before applying DDL. Do not put the full SQL export in the public
repository.

After this PR passes CI, receives approval, and is merged to master:

```bash
cd /var/www/brc_site
git status --short
git pull --ff-only origin master

# READ-ONLY: inspect the complete migration history and schema.
docker exec brc_site php artisan schema:audit-migrations --json \
  > /tmp/basinenergy-migration-audit.json

# READ-ONLY: checks the actual production JSON payloads and schema.
docker exec brc_site php artisan schema:repair-carbon-json
```

The audit may initially report Carbon Mapper JSON as `BLOCKED`, which
is expected from the structure-only export. If the JSON preview confirms
**zero invalid or NULL values**, that the actual column is non-null LONGTEXT
with utf8mb4 collation, and no conflicting JSON constraint exists,
authorize the constraint repair:

```bash
# WRITES DDL: explicitly authorized, may lock a large table.
docker exec brc_site php artisan schema:repair-carbon-json --apply

# READ-ONLY: must now have no BLOCKED or review findings.
docker exec brc_site php artisan schema:audit-migrations

# READ-ONLY preview: lists exactly which ledger records would be added.
docker exec brc_site php artisan schema:reconcile-migrations

# WRITES ONLY migration ledger entries; no DDL or research-row changes.
docker exec brc_site php artisan schema:reconcile-migrations --apply

# Applies genuine remaining migrations using the original code.
docker exec brc_site php artisan migrate --force

docker exec brc_site php artisan migrate:status
docker exec brc_site php artisan schema:audit-migrations
docker exec brc_site php artisan optimize:clear
```

If any command returns a blocking error, **stop and investigate** instead of
inserting ledger entries manually. In particular, invalid JSON rows cannot
be repaired automatically without an explicit scientific/data decision.

Verify important row counts against the actual database backup, especially
`produced_water_fluxes`, `produced_water_chemistry`,
`carbon_mapper_observations`, and website content tables. The provided
structure-only export cannot provide the expected row counts.

## CI

GitHub Actions checks PHP syntax, Laravel routes, a clean migration on
MySQL 8, the **read-only** audit, existing blog FK integrity, frontend builds,
and PHPUnit. A new MySQL-only integration script on the **disposable test DB**
temporarily removes a few migration ledger entries and demonstrates dry-run
safety, validated reinsertion, and repeatability. The SQLite test suite
covers parser behavior, valid/invalid JSON constraint shapes and schema drift.

Passing CI does not replace the production audit: the actual server is
MariaDB 11.8.3 with historical imported data, not CI's clean MySQL 8.


## Follow-up: MariaDB quoted defaults and an existing Laragear 2FA table

A live production audit on November 20, 2026 identified two remaining
false-positive blockers that prevented **all** 18 adoptable migrations from
being recorded:

- The imported `carbon_mapper_source_measurements.gas` field already has
  `DEFAULT 'CH4'` in MariaDB, but `information_schema.COLUMN_DEFAULT`
  includes SQL quotes where the Laravel compiler reports `CH4`. The schema
  inspector now compares the unquoted *literal value*, without accepting a
  different gas default.
- The **2026** Laragear two-factor migration explicitly does nothing if
  `two_factor_authentications` exists. The older 2021 table in production has
  `shared_secret TEXT` and `recovery_codes TEXT` instead of the original
  `VARCHAR` and JSON. The audit previously required exact matching with the
  2021 migration even though it was already recorded as Ran. The replacement
  guard checks the existing table's required fields, key/index, authentication
  identity column types, algorithm default, and validated `safe_devices`
  JSON alias. It permits known VARCHAR/TEXT variants for secrets and recovery
  codes without changing, converting, or exposing any 2FA data.

Neither difference requires altering production database columns. The fix
changes the **audit code only**; the original migration files and application
authentication behavior remain unchanged.

After the follow-up PR has passed CI, merged, and been pulled:

```bash
cd /var/www/brc_site
git pull --ff-only origin master

# Read-only: verify BLOCKED = 0 and review = 0.
docker exec brc_site php artisan schema:audit-migrations
docker exec brc_site php artisan schema:reconcile-migrations

# Writes only completed records to the migrations table.
docker exec brc_site php artisan schema:reconcile-migrations --apply

# Then let the original Laravel migrations create or alter genuinely missing schema.
docker exec brc_site php artisan migrate --force
docker exec brc_site php artisan migrate:status
docker exec brc_site php artisan schema:audit-migrations
```

Note: the previous `php artisan migrate --force` invocation already
recorded `2026_02_06_222324_create_carbon_mapper_observations_table` as
completed. The live audit then reported **18 adoptable** and **2 blocked**
migrations. With both false-positive blockers resolved, the preview should
show approximately **20 adoptable** migrations (18 + 2), unless other
independently applied migrations changed that count.

Do not rerun `schema:repair-carbon-json --apply` simply to resolve these
false-positive audit findings. If the Carbon Mapper observation creation
migration is already recorded as Ran and passes its schema audit, the JSON
repair is not part of this step. Preserve the verified DB backup.
