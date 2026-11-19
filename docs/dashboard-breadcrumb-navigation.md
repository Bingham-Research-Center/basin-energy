# Dashboard breadcrumbs (Admin + User)

BasinEnergy uses the existing `tabuna/breadcrumbs` integration and the
CoreUI subheader. This update brings the navigation trail to the remaining
**authenticated dashboard pages**, without changing the public Bulma website
or API/JSON endpoints.

## Hierarchy

| Section | Example rendered breadcrumb |
|---|---|
| Existing Access | Home / User Management |
| Website Updates | Home / Website Updates / Blog |
| Broadcast | Home / Broadcast / Announcements |
| Broadcast editor | Home / Broadcast / Newsletters / Edit: November Research Digest |
| Admin account | Home / Account Info |
| Sensors | Home / Sensor Dashboard |
| Application Logs | Home / Application Logs / Log Files |
| User data | Home / Dashboard / Data / Emission Trends |
| User real-time data | Home / Dashboard / Data / Real-Time Data / Horsepool |
| Carbon Mapper | Home / Dashboard / Carbon Mapper / Utah Super-Emitters |
| User announcements | Home / Dashboard / My Announcements |

Breadcrumb route definitions live with the GET page routes in:
- `routes/backend/admin.php`, `account.php`, `broadcast.php` and the
  existing `auth.php` definitions.
- `routes/frontend/user.php`, `broadcast.php` and the existing
  `auth.php`/ `home.php` definitions.

The single breadcrumb header for each dashboard continues to live in
`resources/views/{backend,frontend}/includes/partials/breadcrumbs.blade.php`.
Vendor LogViewer routes do not use the app's route file and are handled by
a read-only fallback in the backend breadcrumb partial.

Breadcrumbs do not introduce access bypasses: normal route middleware still
protects all links. Parent sections such as `Data` link to the existing
`/data` redirect, and Website Updates links to its existing section index.
Dynamic monitoring station names come from `config/services.php`, not from
unsanitized user input. Active/current pages have `aria-current="page"`.

## For new pages

Add a breadcrumb closure to every new authenticated `GET` page route:

```php
use Tabuna\Breadcrumbs\Trail;

Route::get('my-page', [MyController::class, 'index'])
    ->name('my-page')
    ->breadcrumbs(function (Trail $trail) {
        $trail->parent('admin.dashboard')
            ->push(__('My Page'), route('admin.my-page'));
    });
```

Attach to **HTML page GET** routes; do not add breadcrumbs to JSON,
mutation, webhook, file upload or API routes. Existing Guest/Public website
pages retain their standalone frontend design.

## Verify

The `DashboardBreadcrumbCoverageTest` exercises admin, regular User, and
LogViewer routes, including nested content and parameterized station pages.
On production, after merging and deploying with your normal workflow:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec brc_site php artisan optimize:clear
docker exec brc_site php artisan view:cache
```

No new Composer/npm dependencies, migrations, or queue configuration changes.
