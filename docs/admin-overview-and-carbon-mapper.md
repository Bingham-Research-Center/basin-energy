# Admin Overview and Carbon Mapper map reliability

## Admin overview

`/admin/dashboard` now shows:
- Database-backed counters for registered, active and awaiting-email-verification
  **regular User** accounts (not Admin accounts).
- Number of published announcements.
- Compact table of the eight latest recorded sign-ins, sorted by
  `users.last_login_at` descending: **Name, Active, Roles, Last seen**.
  Last seen is the **recorded login time**, shown in `America/Denver`
  as `27th Aug 16:24`. We do not claim to track continuous online
  presence or introduce a high-frequency user activity write.
- Last 24 Campbell Logger records for the four Uinta ozone stations,
  rendered with Chart.js and backed by the **existing** secure Admin
  `/admin/data/realtime/logger/{site}/history/json` endpoints.
  It loads asynchronously after the initial dashboard response and
  tolerates one or more offline stations. There is no duplicated
  external data fetch from the DashboardController.
- Direct links to Emission Trends, Carbon Mapper, Produced Water and
  the full Uinta ozone dashboard.

The **existing** permission `admin.access.user.list` controls
visibility of User data and User KPI counts. Admin accounts without
that permission see a neutral placeholder instead. The dashboard
retains the existing `admin` middleware; regular Users cannot access it.

The dashboard uses `public/css/admin-dashboard.css` (standalone)
and a small widget module imported into `resources/js/backend/app.js`.

## Carbon Mapper fixes

The Carbon Mapper page used to load Leaflet and MarkerCluster JavaScript
and CSS from `unpkg.com` in the page layout. If either remote resource
was blocked, the map initialization could exit without loading its
observation data, leaving the admin dashboard blank.

The existing research bundle now imports `leaflet` and
`leaflet.markercluster` from `node_modules` (both already installed
and locked via the markercluster peer dependency). Styles are imported
from these packages into `resources/sass/backend/app.scss`, which is
the stylesheet already served to both CoreUI dashboard layouts.

We removed the CDN tags, made map initialization work whether scripts
execute before or after `DOMContentLoaded`, and guard against repeated
initialization on a single map element. If endpoint configuration or
the map plugin is missing, a status message is shown.

**The base OpenStreetMap tile imagery still requires client network
access**, and the Carbon Mapper observation records must be populated
in the database. Map initialization and recorded-observation markers no
longer require remote CDN JavaScript. The data requests remain on
separate `admin.data.carbon-mapper.*` routes with admin middleware.
Regular Users retain their original protected routes.

## Verification

CI tests:
- Admin dashboard response includes counters, roles, last sign-in
  formatting, station history URL and permission guards.
- Users without user-list permission do not see user names or counts.
- Carbon Mapper Admin boundary and observation JSON contain expected
  data from a seeded in-basin observation.
- The rendered Admin map contains the correct admin URLs and excludes
  remote Leaflet resources; regular Users cannot fetch Admin data.
- Laravel Mix builds `backend.js`, `research-data.js` with
  MarkerCluster, and `backend.css` with Leaflet styles.

Browser checks:
1. Open Admin Dashboard. Verify four metric cards, recent user table,
   and the ozone trend with up to four lines.
2. In Firefox F12 > Network, look for
   `/admin/data/realtime/logger/<station>/history/json` responses.
   A station with 502/422 is treated as temporarily unavailable.
3. Open Admin > Data > Carbon Mapper. Check that
   `research-data.js` and `backend.css` load successfully and
   the boundary, observations and source requests use
   `/admin/data/carbon-mapper/utah/*`.
4. Check the Console for errors if the map remains blank.
   Missing GeoJSON records are distinct from missing map scripts.
5. Confirm the regular User Carbon Mapper page still works.

## Deployment

This PR **changes Laravel Mix source**, so **do not deploy only PHP
views without rebuilding the JavaScript and CSS**. After CI passes
and the PR is merged:

```bash
cd /var/www/brc_site
git status --short

# If generated public/css or public/js files block a pull, preserve them:
git stash push -u -m "Production assets before admin overview" -- public/css public/js

git pull --ff-only origin master
npm ci
npm run prod

test -s public/js/research-data.js
grep -q leaflet-container public/css/backend.css
docker exec brc_site php artisan optimize:clear
```

If using a separate asset-build environment, deploy the compiled
files **and** `public/mix-manifest.json` to the actual `brc_site`
volume. Check `docker exec brc_site ls ...` if files are missing.
Do not `git stash pop` older generated assets over the new build.

There are **no database migrations, Composer upgrades, or queue
changes** for this PR.
