# Separate Admin and User dashboard access

The authenticated frontend routes in `routes/frontend/user.php` previously
protected `/dashboard` with `is_user`, but omitted it on the entire
`/data/*` and `/carbon-mapper/*` groups and the User profile update
endpoint. Consequently, a logged-in Administrator could view user-only
dashboards, invoke their JSON endpoints, and reach the User update form.

## Changes
- Enforce `is_user` on the **whole** `data` group, including data views,
  JSON endpoints, AI summaries and legacy redirect routes.
- Enforce `is_user` on the **whole** `carbon-mapper` group, including
  detections, plumes, boundary and JSON endpoints.
- Enforce `is_user` on `profile/update`. Administrators must use their
  separate `admin.account.profile.update` route.
- For an Administrator visiting a User-only page, redirect to
  `admin.dashboard`. JSON/AJAX requests and state-changing requests
  return HTTP 403 instead of accessing User data or changing data.
- Leave shared authentication routes (password, verification, 2FA) and
  public pages (`/`, `/about`, `/service`, etc.) untouched.
- No change to existing routes, URL paths, User permissions, or Admin roles.

## Deploy
No migrations or frontend asset builds. After merging/deploying:

```bash
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Test as an Admin that `/data/produced-water` redirects to
`/admin/dashboard` and `/data/produced-water/flux/json` returns 403;
test as a User that both resources remain available. A public site visitor
continues to see the public Home/About pages.

**Note:** This is an account-type boundary, not a replacement for granular
Data Category permissions. Data-category checks should be enforced
separately for restricted datasets.
