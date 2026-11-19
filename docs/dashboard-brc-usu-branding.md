# Dashboard logo update — Bingham Research Center / USU

## Changes

Replace CoreUI placeholder branding with the supplied **Bingham Research
Center / Utah State University** horizontal wordmark in these places:

- Administrator sidebar (expanded and minimized)
- Administrator mobile header
- Authenticated User sidebar (expanded and minimized)
- Authenticated User mobile header

The single shared Blade partial at
`resources/views/includes/partials/dashboard-brand.blade.php`
renders the supplied logo from `public/img/brand/brc-usu-logo.png`.
The source artwork is the supplied Aggie Blue PNG, resized to a
web-appropriate resolution. The Dark-mode version uses the same
transparent logo with a black filter, matching the black supplied
variant without duplicating binary assets.

`public/css/dashboard-brand.css` gives the horizontal mark a
light background against the default dark CoreUI sidebar, crops the
left USU symbol (rather than shrinking unreadable text) in minimized
sidebar mode, and scales the full name in the responsive header.
Light/Dark/Auto themes remain supported. Logo links navigate to the
appropriate Admin or User dashboard, not a placeholder URL.

The marketing/front-facing BasinEnergy header and favicon are not
changed; only authenticated dashboard branding is in scope.

The old `public/img/brand/coreui.svg` remains in the repo as a
legacy asset for compatibility, but is no longer used in those
dashboard layouts.

## Production deployment

After GitHub Actions passes and the PR merges:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

No database migration, Composer update, or npm build is required;
the new image and CSS are committed to GitHub. The stylesheet uses
`?v=1` cache busting.

Check both roles in Light/Dark/Auto, collapsed and expanded sidebar,
and a narrow phone-width viewport. Verify the logo does not overlap
the menu controls, and clicking it returns to the correct dashboard.
