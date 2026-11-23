# Admin research data and real-time monitoring

The **Admin** sidebar now contains two expandable sections with the same
implemented datasets shown in the regular User dashboard:

- **Data:** Emission Trends, Carbon Mapper / Utah Super-Emitters, Produced
  Water, Subsurface Natural Gas Leaks, and Oil Well Pad Emissions.
- **Real-Time Data:** Uinta Ozone; each configured Campbell Logger (including
  Horsepool, Roosevelt, Castle Peak, Seven Sisters); and configured Other Sites.

Only implemented pages are linked; unfinished sensors remain out of these menus.

## Routing and security

Admin pages and their JSON/data-fetch endpoints live under
`/admin/data/` and are registered in `routes/backend/data.php`.
Every route inherits the site's existing **admin account middleware** from
`routes/web.php`. There is no public-data disclosure introduced by this PR.

The original `/data/*` and `/carbon-mapper/*` User routes retain their
`is_user` restriction. An Administrator browsing an original User-only
URL will still be redirected or forbidden; they should use the admin
dashboard's new routes instead. Regular Users cannot use admin endpoints.

The existing `DataController` and `CarbonMapperController` are reused,
so both account types see exactly the same research data and server-side
validation. Existing Blade dataset views render in the appropriate
admin/User CoreUI layout based on the route name.

JavaScript fetch URLs are generated from the **current route scope**,
including the dynamic Campbell Logger site routes, Emission Trends AI
summary, and all Carbon Mapper map/GeoJSON requests. No global access
middleware has been loosened.

## Breadcrumbs

- `Home / Data / Emission Trends`
- `Home / Data / Carbon Mapper / Utah Super-Emitters`
- `Home / Real-Time Data / Uinta Ozone`
- `Home / Real-Time Data / Horsepool`

## Deployment

Since `resources/js/backend/app.js` now loads the shared research
chart scripts and Carbon Mapper uses dynamic API URLs, **deploy freshly
compiled Laravel Mix assets** along with the PHP/Blade changes.

For the normal server deployment, after PR checks pass and the change
is merged:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
npm ci
npm run prod
docker exec brc_site php artisan optimize:clear
```

Run the asset build in the environment used for your existing production
Laravel Mix setup. If built artifacts are tracked in Git, the build may
show local changes in `git status`; handle those artifacts through the
team's build/deploy process before the next fast-forward-only pull.
Do not discard newly built JS and then expect the new admin dashboards
to keep working. If the CI/CD system deploys built assets, use those
instead of compiling on production.

No database migrations, new npm packages, queue changes, or permission
schema updates are necessary.

## Verification

Use the admin sidebar to open each dataset. Verify charts/maps and
their network requests use `/admin/data/*` and return JSON rather
than login redirects. Then sign in as a regular User and verify the
unchanged `/data/*` pages continue working. The new
`AdminResearchDataRoutesTest` tests page rendering, dynamic
endpoint URLs, and account-type isolation.
