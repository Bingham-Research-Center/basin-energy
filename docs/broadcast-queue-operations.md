# Production Broadcast queue

Newsletter and announcement mail is sent by dedicated Laravel queue jobs.
The scheduler **only creates jobs**; it no longer connects to SMTP. Each
recipient remains tracked in the existing delivery table. Newsletter
audiences are frozen when the issue becomes due; announcement audiences
are frozen on publish. Only approved/scheduled issues are delivered.

## Required deployment

Back up the database first. After the PR passes CI and merges:

```sh
cd /var/www/brc_site
git pull --ff-only origin master
docker exec brc_site php artisan migrate --force
docker exec brc_site php artisan optimize:clear
docker exec brc_site php artisan config:cache
```

Set these variables in the running application environment:

```dotenv
BROADCAST_QUEUE_CONNECTION=database
BROADCAST_QUEUE=broadcast-mail
MAIL_TIMEOUT=30
```

The default `QUEUE_CONNECTION=sync` may remain for unrelated jobs;
Broadcast always uses the configured asynchronous database connection.
Do **not** set `BROADCAST_QUEUE_CONNECTION=sync` in production.

The existing scheduler must run every minute:

```cron
* * * * * docker exec brc_site php artisan schedule:run >/dev/null 2>&1
```

Run a continuously supervised, dedicated queue worker **in addition** to
the scheduler. For a smoke test from the container:

```sh
docker exec brc_site php artisan queue:work database --queue=broadcast-mail --sleep=2 --tries=1 --timeout=60 --max-time=3600
```

For production, supervise this command with the host's systemd, Supervisor,
or a dedicated Docker service (with restart policy). Workers must restart
after code deployment via `php artisan queue:restart`. Do not run the
test command only once and assume it remains active after shell exit.

The `database` queue has `retry_after=90`, greater than the worker's
60-second timeout; the SMTP timeout is bounded at 30 seconds.

## Monitoring and recovery

```sh
docker exec brc_site php artisan queue:monitor database:broadcast-mail --max=100
docker exec brc_site php artisan queue:failed
docker exec brc_site php artisan schedule:list
```

Delivery statuses are `pending`, `queued`, `sending`, `sent`,
`failed`, `skipped`. The admin tables show sent/recipient counts,
a compact percentage bar, and failed/skipped counts. They poll every
10 seconds without reloading the page.

A confirmed SMTP exception produces a `failed` record and the scheduler
requeues it after 2 or 5 minutes, up to 3 recorded attempts. The
`sending` status is **not** automatically replayed after an abrupt
worker crash: SMTP acceptance may have happened before PHP recorded
success. Review such rows against mail-provider logs before any manual
recovery; Laravel queues cannot guarantee exactly-once external email
delivery. A `queued` row with no surviving database job also requires
manual reconciliation before requeueing. Do not blindly retry the entire
newsletter or announcement.

The percentage represents the portion actually recorded as sent, not
SMTP inbox delivery, opens, or clicks.
