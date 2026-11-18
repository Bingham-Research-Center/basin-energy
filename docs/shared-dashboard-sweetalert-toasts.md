# Dashboard SweetAlert2 notifications

Admin and authenticated User dashboards now share a single top-right
SweetAlert2 toast interface for Laravel flash action outcomes.

The existing Blade partial `resources/views/includes/partials/messages.blade.php`
collects `flash_success`, `flash_warning`, `flash_info`,
`flash_message`, `flash_danger`, `status`, `broadcast_toast`,
`resent` and `verified`. They become safe JSON data in a non-executable
script block, consumed by the standalone `public/js/dashboard-toasts.js`.
Messages use SweetAlert2's `titleText` to prevent HTML injection.

Validation errors continue to appear inline so administrators and users
can see and correct them. Static security/read-only announcements are
unchanged. Flash messages no longer appear in duplicated inline banners.

`window.BrcToasts.show(message, icon)` is available for future AJAX
actions; supported icons are success, error, warning, info and question.
`window.BrcToasts.confirm(options)` provides a consistent confirmation
dialog with explicit Cancel and Continue action.

The Broadcast modal now uses the shared SweetAlert2 confirmation for
**Publish Announcement** and **Schedule Newsletter**, including waiting
for any active autosave before submitting. The previous native browser
confirmation dialogs on these two modal buttons were removed.

The shared JS is loaded by `backend.layouts.app` and
`frontend.layouts.app`, not by the public marketing-site layout.
Both dashboard layouts already load `backend.js` which contains SweetAlert2,
so no new npm dependency or build is necessary.

Deployment after CI passes and merging:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Validate announcement publishing, cancellation (nothing published),
newsletter scheduling and cancellation, top-right action outcomes
on both dashboards, and inline validation errors. Browser-level
smoke testing is required in Light, Dark and Auto modes.
