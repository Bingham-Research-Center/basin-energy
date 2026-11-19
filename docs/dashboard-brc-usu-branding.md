# Transparent BRC/USU dashboard branding

This updates the branding introduced by PR #56. The supplied Aggie Blue
PNG has a transparent background; the former white tile was an explicit
CSS background, not part of the image.

## Behavior

- **Admin and User dark sidebars:** white logo, including the minimized
  USU emblem, with no surrounding card/tile. The sidebar remains dark
  even when the user selects Light.
- **Mobile headers in Light mode:** transparent Aggie Blue logo.
- **Mobile headers in Dark mode:** transparent white logo.
- **Auto mode:** follows the resolved theme from `dashboard-theme.js` and
  the existing `data-brc-resolved-theme` HTML attribute.
- The same transparent image is reused for all variants. CSS
  `filter: brightness(0) invert(1)` converts all non-transparent pixels
  to white while retaining each pixel's alpha transparency.

Both authenticated dashboards use the existing
`resources/views/includes/partials/dashboard-brand.blade.php`.
No public marketing-site logo, navigation, or favicon changes.

## Deployment

This is a standalone CSS and Blade update. The CSS URL is cache-busted
to `?v=2`. No migration, Composer update or npm build is needed.

After CI and merge:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Check Admin and User dashboards in Light/Dark/Auto, collapsed/expanded
sidebars, and narrow mobile widths. The logo must have no white box
and must remain legible against the actual sidebar/header color.
