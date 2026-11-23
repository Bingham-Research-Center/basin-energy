# Research Data graph loading (Admin and User)

## Background

Real-Time Data pages have independent embedded scripts. The research
**Data** pages are different: their Chart.js and Carbon Mapper
visualization logic lives in Laravel Mix JavaScript modules.

The older configuration bundled the same Data modules into
`frontend.js` and `backend.js`. The regular User layout loads both
of those bundles, so two copies could initialize the same canvas,
whereas the Admin layout loads only `backend.js`.
If the production admin bundle was not rebuilt after the Admin Data
feature was deployed, no Data page chart code would run at all.

## Fix

All existing visualization modules are now imported in a **single**
`resources/js/research-data.js` entrypoint, compiled to
`public/js/research-data.js`. Each authenticated research Data page
loads it **once**, through
`resources/views/includes/partials/research-data-assets.blade.php`.

Both CoreUI layouts load this module after the page's own
`@stack('after-scripts')`. Thus Carbon Mapper's Leaflet dependencies
and the natural-gas dashboard's inline JSON URLs are available before
the module initializes. Real-Time Data pages do not load the extra
bundle and remain unchanged.

Subsurface Natural Gas Leaks now imports Chart.js from the bundled
dependency instead of relying on a separately downloaded Chart.js CDN
script. No new npm dependencies or database migrations are required.

## Production deployment

**Important:** Deploy JavaScript artifacts along with the Blade/PHP
change. New pages reference a new versioned Mix entry; merging without
rebuilding the production assets will cause an absent-mix-manifest
error. After the PR passes CI and is merged:

```bash
cd /var/www/brc_site
git status --short
git pull --ff-only origin master
npm ci
npm run prod
test -s public/js/research-data.js
grep -q 'emissionTrendsChartRoot' public/js/research-data.js
docker exec brc_site php artisan optimize:clear
```

Run the build wherever you normally build and deploy the Laravel Mix
assets; it must write the files actually served by the `brc_site`
container. Do not run `npm run prod` without accounting for already
modified/committed Mix artifacts: `git status --short` may show
tracked asset changes after building, which should be managed through
your normal deployment process rather than committed on production.

Verify in the browser **while signed in as Admin**:

1. Load `/admin/data/emission-trends`.
2. Browser DevTools > Network: `/js/research-data.js?id=...` should
   have HTTP 200 and the Emission Trends JSON request should return
   JSON (not a redirected HTML login page).
3. Browser DevTools > Console: no Chart.js canvas ownership errors.
4. Repeat for Produced Water, Natural Gas Leaks, Oil Well Pad
   Emissions, and Carbon Mapper. Each page should load the shared
   script **once**.
5. Confirm regular User Data pages still render and the Real-Time Data
   pages continue operating without loading `research-data.js`.

If the new bundle returns 404, 500, or an old script version, verify the
asset build directory, container bind mount, browser cache, and generated
`public/mix-manifest.json` before changing the dataset APIs.

## Safety

Existing `/admin/data/*` and `/data/*` authorization boundaries
are unchanged. This is a frontend asset lifecycle fix; the controllers,
database queries, AI summaries, and queue workers are not modified.
