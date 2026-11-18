# LogViewer admin 2FA route redirect fix

Historically, the vendor LogViewer routes lived at `/log-viewer/*`,
outside the `/admin/*` path. Before the 2FA route fix,
`TwoFactorAuthenticationStatus` treated them as non-administrator pages
even though they carried the `admin` middleware group.

LogViewer now uses `/admin/log-viewer/*`, so the URLs follow the same
admin route convention. The previous named-route recognition remains
compatible with both the new URL and the optional-admin-2FA policy.

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
