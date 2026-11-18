# Broadcast editor: Discard Draft, SweetAlert and Summernote editing

The admin Broadcast editor supports **Discard Draft** in both Newsletter
and Announcement modals. After confirming through SweetAlert2:

1. Pending autosaves are paused and awaited, so the background HTTP request
   cannot recreate a deleted server-side draft.
2. The saved draft is deleted using its authenticated Admin DELETE endpoint.
3. The current browser's `brc-broadcast-recovery:<type>:new` and
   `brc-broadcast-recovery:<type>:<id>` records are cleared.
4. Image files referenced only by the deleted draft are removed from local
   storage. Images referenced by other drafts or published/sent broadcasts
   are retained, so historical email links do not break.
5. A success toast appears and the Livewire table refreshes.

A browser-only draft (not yet saved to the database) can be discarded by
removing its local recovery entry. Deleting in one browser cannot directly
delete browser-local copies on a different device; however, a stale
autosave targeting the deleted record ID fails validation and cannot
silently resurrect it.

**Limitation:** an uploaded image that was never saved in any draft is an
orphaned upload. It cannot be securely linked to a specific draft without
a separate upload ownership/retention model. Such images are not deleted
by Discard; consider a separate, safe maintenance job for unused media.

The Livewire tables use SweetAlert2 confirmation rather than browser
`confirm()` for Delete. Manual Save Draft redirects show SweetAlert2
toast feedback, while the autosave indicator remains unobtrusive.

For Edit Draft, the JavaScript initializes Summernote when loading the
saved record (and again checks initialization when the Bootstrap modal
finishes opening), then sets the saved HTML in the editor. A full-page
fallback remains available.

No new migration, Composer package or npm build is needed. The standalone
`public/js/broadcast-editor.js` script is versioned to `?v=3` to
invalidate browser caches.

After CI passes and merging:
```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Verify both editor popups with Light, Dark and Auto; Edit Draft displays
the Summernote toolbar; delete success/cancel; modal discard success/cancel;
and shared images remain available. No destructive action is allowed on
already published or sent items.
