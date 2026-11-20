# Default role for new user registrations

Role Management now includes a **Default User Role** switch for every role with
`type = user`. Administrator-type roles do not have a switch.

An administrator can turn one role on, replacing any earlier selection. Turning
the selected role off restores the original behavior (no automatically
assigned role). Only **one** role can be active because the setting is stored
as a single nullable `role_id` in the fixed `registration` row of the new
`default_user_role_settings` table. A foreign key to `roles.id` clears the
setting if the selected role is deleted. The role-management service clears
it when a selected User role is converted to Administrator type.

The Livewire action checks that the actor is an administrator with the
Administrator role before changing the setting; unauthorized users cannot
bypass the role-management page restrictions by calling Livewire directly.
The update uses a database row lock to serialize concurrent selections.

## What registrations receive the role?

New public password registrations and **first-time Google sign-ups** both use
`UserService` to create accounts. After account creation and before its
transaction commits, that service assigns the currently selected User-type
role (and therefore its existing data-category permissions). If no default
has been selected, self-registrations keep the old behavior: they are
created with no automatically assigned role.

Returning Google users, existing accounts, and users created by administrators
retain their current/manual role assignments.
Public password registration now also whitelists only the name, email and password
fields, forcing User account type and preventing browser-supplied admin type,
OAuth provider IDs, verification flags or active status from being accepted. Changing the selection does
**not** backfill or modify any existing user or role membership.

For security the service checks the role's current type and guard before
assigning it, so an Administrator-type or incompatible guard role cannot be
assigned to a public registration if a role changes after selection.

## Deploying

This is an additive schema migration; run it on the production container
**after merging and pulling** the PR and before opening Role Management:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec brc_site php artisan migrate --force
docker exec brc_site php artisan optimize:clear
```

The new setting starts **unset** deliberately, because production has
several custom User roles and we must not silently choose an unexpectedly
privileged role. An authorized administrator should visit
**Admin → Role Management** and explicitly enable the intended default role
(e.g. `Regular User`). The green/active switch identifies the selected role.

The selection takes effect for **subsequent** password and first-time Google
registrations; previous users remain unchanged.

The change requires no new composer or npm packages and does not modify
existing research tables.

## Live role switching and toast feedback

Clicking a User-type role's switch saves the selection through Livewire without
reloading Role Management. The switch and any previously selected role reflect
the committed setting in the **same response**, including turning it off.

Once the update succeeds, a **top-right SweetAlert2 toast** confirms either
`<role name> is now the default user role.` or
`Default user role cleared.` The toast closes automatically after **3000 ms**.
An unsuccessful/unauthorized request does not fire a success toast. Other
dashboard toast positions and durations remain unchanged.

The table reads the selected setting once per render instead of querying it for
every row. Livewire's column definitions are prepared before the toggle action,
so the label uses a lazy render-time lookup and immediately updates its
in-request cache after a successful save.
