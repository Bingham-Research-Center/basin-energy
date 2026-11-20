# New user registration notifications

This feature adds a new persistent item type to the existing Admin
notification bell, alongside contact submissions and newsletter subscriptions.

## Trigger and recipients

- Trigger when a visitor successfully creates a new regular User account
  through the email registration form.
- Trigger when a first-time Google visitor completes the Terms step and
  the new account is committed to the database.
- Do not trigger on a Google login, an unfinished signup, a failed registration,
  user verification, or manual account creation by an administrator.
- Each active administrator with the existing User Management list permission
  gets an individual alert. Administrators without permission to view
  user records do not receive or see these alerts.

Both public registration methods call the same notification service after
the database transaction commits. A unique admin/user database key prevents
duplicate alerts. No notification is sent as email.

## Display and behavior

The admin bell displays a new item, e.g. “Example Person registered”,
with the registration method (email or Google). Clicking the item leads
to the existing protected User Management details screen; if the account
has been deleted it links to the User Management list.

The badge, unified paginated feed, 20-second fallback polling and AJAX
“Mark all as read” action include this third source. Each administrator
has an independent read_at marker.

Real-time updates reuse the existing Reverb/Echo setup with the new
private admin.registrations.{adminId} channel. Authorization requires an
active admin with the correct account ID and User Management list permission.
The event includes only a refresh hint, never personal details. If Reverb
is unavailable, the normal polling refresh still works.

## Tests

PHPUnit covers password registration, Google signup before/after Terms,
repeated account login, authorized/inactive/unauthorized admins,
independent read state, duplicate protection, manual user creation,
and the existing Admin endpoint authorization.

## Deployment

IMPORTANT: This PR includes a NEW database table and MUST be migrated
before the feature will work in production.

After the PR passes CI and is merged to master:

    cd /var/www/brc_site
    git status --short
    git pull --ff-only origin master
    docker exec brc_site php artisan migrate --force
    docker exec brc_site php artisan optimize:clear

No new Composer or npm dependencies, asset compilation, or service changes
are needed. The updated stand-alone notification JavaScript URL has
a new ?v=2 query string to refresh browser caches.

Confirm a new signup appears in the bell, including its notification count,
and Mark all as read works without a page reload. The production queue
worker is not involved in this in-app notification feature.
