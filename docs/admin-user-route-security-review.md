# Admin and user route security review

Scope: `routes/web.php`, `routes/backend/*`, `routes/frontend/{auth,user}.php`,
the middleware stacks, role/user controllers and their request authorization.

## Addressed in this pull request

1. **Status permission boundary:** the `mark/{status}` route permitted either
   deactivate or reactivate permissions, without checking which status was requested.
   The controller now requires the matching permission for each operation.
   This retains both the URL and existing role permissions.
2. **Invalid status handling:** constrain the status route parameter to exactly
   `0` or `1`, not the comma inadvertently allowed by the previous regex.
3. **Status redirect:** a Reactivate-only operator now lands on the authorized
   deactivated-users screen rather than a user index that requires another permission.
4. **Social-login 2FA:** when a social account requires 2FA, store the pending
   account in the session and leave the visitor unauthenticated until the guest
   challenge succeeds. The previous callback immediately authenticated the
   visitor, then sent them to a guest-only challenge route. Defer login events
   until a successful challenge; regenerate sessions after authentication.
5. **Pending challenge safety:** validation and challenge completion reject a
   deleted, deactivated or no-longer-2FA-enabled account.
6. **Route protection:** require `2fa.confirmed` on the backend and the
   authenticated frontend user route group. The admin group's existing setting
   `admin_requires_2fa` still controls whether administrators must *enable*
   2FA; a user who already has it enabled must complete the challenge.
7. **Guess throttling:** limit 2FA challenge POST requests to 5 per minute
   using Laravel's existing throttle middleware.
8. **Password changes:** require a nonempty, confirmed password for backend
   user-password updates, in addition to the existing complexity/history checks.

Regression tests were added for cross-permission status changes, invalid
status values, authorized redirects, and missing/mismatched password updates.

## Compatibility and deployment

- No new database migrations, roles, columns, permissions or renamed routes.
- Existing Administrator, User and Data Categories remain unchanged.
- Social-login providers should be smoke tested with a real 2FA-enabled account.
- Login, logout, password recovery, verification, and self-service account
  pages should be manually verified in staging after deployment.

## Follow-up concerns outside this PR

- `routes/api.php` includes sensor telemetry endpoints. Their authorization
  and public read/write intent should be reviewed separately with the device
  ingestion protocol (not changed by this admin/user routing PR).
- `website-update` mutations are guarded by Admin account type but not by
  granular permissions; consider a dedicated content-management permission if
  non-master administrators will be given restricted responsibilities.
- Complete end-to-end OAuth-provider and hardware-token 2FA tests require
  provider/credential test fixtures and a staging environment.
- PHP unit tests and route-list smoke tests should run in CI before merge.

Do not merge until the Laravel CI workflow is green and the social-login flow
has been smoke tested on staging.
