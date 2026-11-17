# Application Logs: Administrator-only CoreUI integration

BasinEnergy uses `arcanedev/log-viewer` 10.1.1. Its routes are registered
by the package and do **not** inherit the `admin` group from
`routes/web.php`.

## Authorization

The new `config/log-viewer.php` sets
`route.attributes.middleware` to `['web', 'admin']`.
That applies authentication, two-factor/password policy and
`type=admin` checking to **all** package routes:
- `/log-viewer` (dashboard)
- `/log-viewer/logs` (file list)
- Daily logs, level filters, searches, downloads and DELETE requests.

Regular-user accounts and guests cannot access raw application logs.
All Administrator-type accounts can view and manage logs, as requested.
For stricter separation in the future, a distinct log-read/log-delete
permission can be added without changing the view integration.

## CoreUI integration

Vendor theme `bootstrap-4` is configured because BasinEnergy uses
CoreUI 3 and Bootstrap 4. The published LogViewer theme views extend
`backend.layouts.app` to display the existing admin sidebar, header,
footer and account navigation. Table/search/delete functionality remains
handled by LogViewer. Its dashboard uses a CSS conic-gradient instead
of loading an incompatible second Chart.js version.

## Deployment

No migrations, new packages, or Node build.

After updating code, **clear the old configuration and view caches**:

```sh
docker exec -it brc_site php artisan config:clear
docker exec -it brc_site php artisan view:clear
docker exec -it brc_site php artisan route:clear
docker exec -it brc_site php artisan config:cache
```

Test with a guest, User-type account and Administrator-type account.
Check log detail, filter, search, download and delete behavior on staging.
Avoid deleting logs needed for auditing or incident investigation.
