# Broadcast editor: recovery dialog and first-click Save Draft regression

## Symptoms

- Clicking **Edit Draft** opened a native browser confirmation prompt
  about recovering unsaved edits before the Bootstrap modal was visibly
  ready. This left the announcement table darkened behind the prompt.
- Clicking **Save Draft** sometimes did not submit until the *second*
  click, especially while the autosave request was in flight.

## Fix

The rich editor now opens the Bootstrap modal using `.modal('show')`
and initializes Summernote when the modal is visible. It ignores
out-of-date draft load responses and only prompts for recovery **after**
the editor has loaded and the modal is shown.

Browser recovery uses the existing SweetAlert2 confirmation, never a
native `window.confirm`. The JSON edit endpoints now include the
server's `updated_at` value. A browser copy is offered for recovery
**only when newer than the server's saved version**; identical or older
copies are cleaned up silently.

The form submit handler no longer calls `requestSubmit()` recursively.
It confirms Publish once (Save Draft requires no confirmation), waits
for an already-started autosave to settle, syncs Summernote HTML to
its textarea, preserves `intent=draft` or `intent=publish`, and
performs exactly one native form submission.

The same fix applies to Newsletters and Announcements.

## Deployment

The standalone editor JavaScript is cache-busted to `?v=5`.
No npm build, Composer change, or database migration is required.

After CI passes and the PR is merged:
```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Verify both **Edit Draft** popups, cancel/accept of the SweetAlert
browser recovery, formatted content, and first-click **Save Draft** while
autosave is still running. The updated frontend is committed directly
under `public/js/broadcast-editor.js`; do not run npm production build
on the server for this change.
