# Broadcast rich editor: images and autosave

## Why the editor lost work

Summernote's previous image callback only displayed an alert for local files.
Bootstrap modals closed on backdrop clicks, and the only persistence action
was the manual Save Draft button.

## Changes

- **Images:** `POST /admin/broadcast/editor/images` accepts administrator-only
  JPG, PNG, GIF and WebP images (maximum **3 MiB**). Uploaded files have
  unpredictable random names, are stored in the application's local storage,
  and are embedded using public read-only `/broadcast/images/{filename}`
  URLs. The GET route uses a strict filename pattern, MIME mapping and
  `X-Content-Type-Options: nosniff`; no SVG/HTML execution is allowed.
  Email clients can load these images without signing in.
- **Modal:** backdrop clicks and Escape no longer close the editor. Only
  explicit Close or X closes it.
- **Autosave:** editor events use a **1.3-second debounce** to save changed
  newsletter/announcement drafts via authenticated JSON endpoints. The
  UI displays `Saving…` / `Saved automatically` / error status.
  Requests are serialized to avoid older saves overwriting newer edits.
- **Recovery:** `localStorage` retains unsaved changes if the network fails,
  the page closes or the newsletter's issue month has not yet been chosen.
  Reopening the editor offers to recover the local version.
- **Safety:** autosave creates/updates **drafts only**. It never
  schedules newsletters or publishes announcements. A scheduled issue or
  published announcement cannot be changed silently (HTTP 409).
- **No Reverb required:** HTTP debounce saves are durable and efficient for
  one administrator editing at a time; Reverb is not a persistence layer.

## Operational caveats

- For a new monthly newsletter, **choose the issue month** before expecting
  server-side saving. Until then the browser keeps a local recovery copy.
- Uploads are deliberately persisted on disk, not base64 embedded in DB;
  `storage/app/broadcast/images` must be writable and **persist across
  Docker container restarts/replacements** (use a persistent bind mount/
  volume). The application serves them through its own validated GET route,
  so no `storage:link` is necessary.
- Ensure the `APP_URL` value points to the public HTTPS domain so image
  links in outgoing emails are reachable.
- Uploaded but unused files can accumulate. An operator should eventually
  implement a retention/garbage-collection policy after examining references
  in sent broadcasts; do not indiscriminately delete images referenced by
  historical emails.
- Autosave is not real-time multi-editor collaboration. When two
  administrators edit one draft concurrently, the last accepted change wins;
  consider optimistic locking before adding collaborative editing.

## Deployment

No new migration, Composer dependency, or npm compilation is required.
Deploy the merge, then:

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Verify `storage/app/broadcast/images` is writable and on persistent storage.
Test upload with PNG/JPG, verify the public image URL loads, test multiple
draft edits and refresh, and verify clicking outside the modal leaves it open.
