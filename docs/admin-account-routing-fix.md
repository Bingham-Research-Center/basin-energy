# Admin / User Account Settings Routing Fix

## Root cause

The admin Edit Information form incorrectly posted to the **frontend** profile
endpoint, whose redirect went to `/account`. That rendered the regular-user
dashboard even though the account remained an administrator.

## Fixes

- Admin Edit Information posts to `admin.account.profile.update`.
- Both profile controllers redirect according to the account's actual
  `type` and preserve the `#information` tab.
- Shared password-change and expired-password flows redirect to the proper
  Admin or User account page. The expired-password update now passes the
  validated `current_password` field instead of the nonexistent
  `old_password` field, so the existing password is actually checked.
- The shared 2FA enable/disable/recovery controllers use the matching backend
  or frontend page. Admin-specific templates use the backend layout; 2FA
  disable navigates back to the matching account page.
- The regular-user `/account` URL redirects administrators to
  `/admin/account` (backward compatibility for old links/bookmarks).
- Any Administrator-type account, including limited-permission administrators,
  may manage their own backend account. The master Admin role is not required
  to visit this personal page, and the sidebar shows it accordingly.
- Profile changes only save name/email, preserving user type, roles, and
  permissions. Email verification notifications are generated **after**
  persisting the new email so the signature targets the updated address.

## Operational notes

No database migrations, permission changes, or asset builds are required.
Do not change Admin/User types when editing profile details.

Before merging: run the PHPUnit suite and verify admin and regular-user
profiles, password change/expiration, email verification, and two-factor
screens in staging. In particular, test a limited Admin account.
