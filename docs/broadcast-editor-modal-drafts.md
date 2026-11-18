# Broadcast editor modals and resumable drafts

Both Broadcast list pages open a Bootstrap 4 modal when clicking Create
Newsletter or Create Announcement. The modal uses the existing Summernote
editor (as used on Website Updates → Blog) with formatting, font, color,
bullet/numbered lists, tables, hyperlinks and images by hosted HTTPS URL.

The table's **Edit draft** link opens the same modal, fetching the
persisted content through an authenticated Admin-only JSON endpoint.
If JavaScript or the modal fails, links still work as full editing pages.

Newsletter:
- Save Draft persists unfinished content without scheduling anything.
- Administrators reopen the draft and explicitly click Schedule.
- Updating a scheduled newsletter revokes approval and returns it to Draft.
- Only scheduled issues with a due send timestamp are processed.
- A month with **no scheduled newsletter never sends an email**.
- Sending/Sent issues remain read-only, including at the server endpoints.

Announcement:
- Save Draft stores the title/content/audience without publishing or
  creating delivery rows, and can be reopened or deleted.
- Publish validates that title, meaningful content and applicable User
  roles exist; it atomically snapshots recipients and publishes.
- Published announcements are immutable and already-public announcements
  retain their existing status during migration.

Security and compatibility:
- Existing plain-text records remain plain-text via `body_format=plain`.
- New HTML is sanitized server-side using the existing HTMLPurifier
  sanitizer, and is sanitized again before rendering in email/user views.
- The new `body_format` and `status` fields preserve all legacy records.
- No new npm packages or frontend build are required; the controller JS
  and CSS are standalone assets. The repository's existing Summernote
  CDN dependency is reused.

Deployment (after CI and merge):
```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan migrate \
  --path=database/migrations/2026_11_21_000000_add_broadcast_draft_and_richtext_columns.php \
  --force
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Before the migration, take a backup. Test opening, closing, saving,
resuming, editing, scheduling and publishing in Light/Dark/Auto and verify
the resulting email in a staging mail transport.
