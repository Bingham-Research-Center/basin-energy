# Newsletter autosave HTTP 422 and validation feedback

## Reported symptom

Announcement drafts save successfully, but Newsletter drafts display:

```
Autosave failed (HTTP 422). Changes kept in this browser.
```

The Newsletter endpoint performs checks that Announcement autosave does
not: `issue_month` is required in `YYYY-MM` format and autosave previously
rejected months whose final day was in the past. Other Laravel validation
rules (e.g. maximum body length) can also result in 422.

Without the production request/response payload, we cannot identify
which specific validation rule failed for a particular user. This patch
addresses the past-month draft restriction and exposes the actual
Laravel error so future 422 responses are actionable.

## Behavior

- A newly created Newsletter popup defaults the month to the **current
  America/Denver month**. The administrator may change it.
- **Save Draft and autosave** can store historical Newsletter issues.
  Drafting alone never delivers email.
- **Schedule Newsletter** retains the original restriction: issues for
  past months cannot be scheduled for retroactive delivery. It remains
  a distinct, explicit action.
- When Laravel returns HTTP 422, the editor reads `errors` from the JSON
  response and displays the actual field error (such as an invalid month or
  content length), along with the existing browser-local recovery message.
- A rejected payload is **not retried endlessly every five seconds**.
  Editing a field clears the blocked state and triggers a new debounced save.
- Until the issue month is valid, the browser keeps a local recovery copy.

No changes to already-scheduled or sent newsletters, deliveries, or database
schema are made. No Composer or npm dependency is added.

## Deployment

After CI and merge:
```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

The Newsletter page uses `broadcast-editor.js?v=6` to bypass stale browser
caches. No `npm run production` is required. Confirm the newsletter's month
and title, verify autosave says `Saved automatically`, and reopen the draft
to verify it persisted. If a 422 continues, inspect the Network response
`errors` object; the field-specific message now appears in the popup.
