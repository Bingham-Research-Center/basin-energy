# Website Content Editing Permissions

Administrator roles may be granted the broad permission **All website content edits**
(`admin.website.edit`) or individual permissions under that heading.

| Page | Permission | Endpoints guarded |
| --- | --- | --- |
| Home | `admin.website.edit.home` | Home view and update |
| About | `admin.website.edit.about` | About view/update and team member CRUD |
| Service | `admin.website.edit.service` | Service view/update and service item CRUD |
| Portfolio | `admin.website.edit.portfolio` | Portfolio view/update, category CRUD and item CRUD |
| Blog | `admin.website.edit.blog` | Blog view/update, category and post CRUD, and uploads |
| Contacts | `admin.website.edit.contact` | Contact view/update and contact message deletion |

**Parent semantics:** Unlike the non-assignable User Data Categories heading,
the `admin.website.edit` parent is a selectable permission and *does* grant
all six groups. Individual child permissions grant only their respective
sections. The existing Administrator role retains full access through its
existing application-wide admin authorization override.

Permissions are checked by the backend Laravel route middleware for both
GET and mutation endpoints (not just by hiding links). The sidebar shows
only permitted Website Updates sections and hides the group if none is allowed.
The overview route requires any one of the seven website editing permissions.

## Installation on production

After reviewing, testing, and merging the PR, and deploying the new master
commit, run this **targeted migration**:

```bash
docker exec -it brc_site php artisan migrate --path=database/migrations/2026_11_19_000000_add_website_content_permissions.php --force
docker exec -it brc_site php artisan permission:cache-reset
```

The migration is idempotent and adds only permission rows. It does **not**
remove any users, roles, role assignments, or existing permission records.
Do **not** run `php artisan db:seed` on populated production databases;
BasinEnergy's AuthSeeder truncates the authentication tables.

## Administrator workflows

Under **Role Management → Create/Edit Role → Type: Administrator**,
assign the overall permission or choose individual sections. When the role
is assigned to an Administrator-type account, access immediately follows
the assigned permissions. A User-type account never accesses admin routes.

## Deployment consideration

Previously, every Admin-type account could open Website Updates. After
deploying this change, non-super-admin staff must be explicitly assigned
the new permissions to retain access. Audit current Administrator users
and roles before enabling in production.
