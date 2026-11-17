# LogViewer admin 2FA route redirect fix

The vendor LogViewer routes live at `/log-viewer/*`, outside the `/admin/*`
path. Before this fix, `TwoFactorAuthenticationStatus` treated those
routes as non-administrator pages even though they carry the `admin`
middleware group.

When `ADMIN_REQUIRES_2FA=false`, administrators without an enrolled
authenticator were still redirected away from the LogViewer dashboard and
log list. The redirect to `/account` could then redirect to
`/admin/account`, making the viewer seem broken.

The middleware now recognizes `log-viewer::*` named routes alongside
`/admin` and `/admin/*` for the **existing optional-admin-enrollment**
exemption. The change does not modify the LogViewer route authorization,
session authentication, completion of enabled 2FA challenges, download/
delete protections, or the configuration for mandatory admin 2FA.

When `ADMIN_REQUIRES_2FA=true`, unenrolled administrators must still
enable 2FA before using log routes. A separate review may improve the
onboarding redirect for administrators who have not yet enrolled.

No schema migrations or frontend builds are required. Following
deployment, clear config and route caches if previously cached:

```bash
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```
