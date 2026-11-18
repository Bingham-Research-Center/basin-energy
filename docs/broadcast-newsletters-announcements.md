# Broadcast: newsletters and announcements

## Newsletter
- Only **User-type** accounts can subscribe from `/dashboard`. Subscription is opt-in, idempotent and reversible.
- Subscribing creates a persistent unread alert for each active Administrator-type account. The header shows an immediate Reverb private-channel alert, with a 20-second authenticated polling fallback.
- Under `Admin → Broadcast → Newsletters`, an administrator drafts a single issue per calendar month and explicitly selects **Schedule**.
- The issue is mailed on the **last day of its month at 6:00 PM America/Denver**. An administrator can edit or delete drafts and scheduled issues. Editing a scheduled issue returns it to Draft and requires approval again.
- At send time the scheduler snapshots the current active, verified, subscribed User accounts and creates one delivery record per recipient.
- Once delivery starts, the issue is immutable. A sent issue is never edited or deleted from the UI. The table lists history, schedule, state and sent/recipient counts.
- Recipient emails contain a signed unsubscribe link. A confirmation step avoids accidental unsubscribes from email link scanners.

## Announcement
- An administrator chooses **All Users** or one or more roles with `type=user`, then publishes the announcement.
- The recipients are frozen at publish time in a delivery table, so changing a role later does not expose announcements to unintended users.
- The announcement immediately appears in the recipients' `/broadcast/announcements` dashboard view. The background scheduler also emails each recipient.
- Existing legacy site announcements are untouched.
- Content is sent as escaped plain text, not raw user-controlled HTML.

## Delivery and operations
- All Broadcast management routes inherit BasinEnergy's Administrator middleware.
- `broadcast:deliver` runs via Laravel Scheduler **every minute**, using `withoutOverlapping(10)`. Only explicitly scheduled newsletter issues with `scheduled_at <= now()` are eligible for delivery.
- Deliveries are persisted per recipient; unsuccessful sends can retry up to three times.
- Once a row has entered `sending`, it is **not automatically retried** after a worker crash because the email provider may already have accepted the message. An operator must reconcile those rows before attempting manual resend.
- Delivery history contains status `sent`, `failed`, or `sending`. Operators should review incomplete issues and mail-provider logs.
- Use a real SMTP transport, not `MAIL_MAILER=log`, before enabling production email.

## Required production setup
1. Merge only after CI passes and browser/staging verification.
2. Deploy `master` and run `docker exec -it brc_site php artisan migrate --force`.
3. Ensure Laravel's scheduler executes once a minute, e.g. host cron:
   `* * * * * docker exec brc_site php artisan schedule:run >/dev/null 2>&1`
4. Configure the server mailer and sender address; verify SMTP delivery and bounce handling.
5. Configure `BROADCAST_DRIVER=reverb` plus active Reverb so header updates arrive instantly; the fallback polling works without Reverb.
6. Refresh caches using `php artisan optimize:clear`, then `php artisan config:cache`.
7. Test User subscribe/unsubscribe, Admin notifications, newsletter draft/update/delete/schedule, month-end dispatch, and role-targeted announcements.

## Security notes
- Avoid logging subscriber email/identity in public websocket channels.
- The email unsubscribe link is time-limited and signed. GET renders only a confirmation form; POST updates subscription.
- User-specific announcement views filter by stored recipient rows, not just roles or query parameters.
- Broadcast module adds dedicated tables and does **not** run destructive auth seeding in production.
