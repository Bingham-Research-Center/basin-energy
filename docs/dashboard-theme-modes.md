# CoreUI Light / Dark / Auto theme

The Administrator and regular User dashboards share a single mode preference
(`brc-theme`) saved to the browser's `localStorage`. Values: `light`,
`dark`, and `auto`. Invalid/absent values are treated as `auto`.

### Architecture

- `resources/views/includes/partials/dashboard-theme-bootstrap.blade.php`
  applies the saved/system choice **synchronously at the top of <body>**,
  before sidebar/header/dashboard markup is parsed. This avoids the usual
  light-theme flash when navigating to a dark page.
- `public/js/dashboard-theme.js` is the **single** dashboard theme controller.
  It is shared by both CoreUI layouts and does not rely on the Laravel Mix
  bundles to initialize.
- `resources/views/includes/partials/dashboard-theme-switcher.blade.php`
  renders the same accessible Light/Dark/Auto dropdown on both dashboards.
- `public/css/dashboard-theme.css` animates background/text/border colors
  only for deliberate mode changes; it never animates the initial page load.
- The existing CoreUI dark palette in `resources/sass/_dashboard.scss` is
  preserved without duplicating it.

The controller responds to system preference changes in Auto mode,
synchronizes mode changes across open tabs via the storage event, handles
private browsing/storage failures and respects reduced-motion preferences.
It broadcasts `brc:theme-changed` with `theme` and `resolvedTheme`
when the effective or preferred theme changes, for chart/widget consumers.

### Deploy (important)

The previous Laravel Mix bundle contains a **second, obsolete theme
initializer**. Its imports have been removed in source, but that requires a
fresh compiled frontend build to take effect in production.

After merging, rebuilding is **mandatory**, then deploy the updated
compiled assets before visiting the dashboards:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
npm ci
npm run production
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan view:clear
```

Adjust the build commands to the host's Node/npm deployment environment.
`npm run production` should update `public/js/{backend,frontend}.js`
and the Mix manifest. The GitHub Actions workflow also runs this build.

No migrations or environment configuration changes are required.

### Test

Visit `/admin/dashboard` and `/dashboard` using their respective
account types. Switch Light, Dark and Auto, reload, follow internal links,
open another tab and change the system color scheme. Verify Auto follows
the system while explicit Light/Dark overrides persist. Test keyboard
interaction, dark-mode tables/forms, and reduced-motion.
