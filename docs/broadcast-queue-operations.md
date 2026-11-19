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

## Keep the worker running with systemd (existing Docker host)

For a deployment where the long-running PHP container is named `brc_site`,
host-level systemd can supervise `docker exec` without adding a container,
rebuilding an image, or publishing an extra port. On the **Ubuntu host**
(as root), confirm the binary path with `command -v docker` (the unit below
expects `/usr/bin/docker`) and create:

```sh
cat > /etc/systemd/system/brc-broadcast-queue.service <<'EOF'
[Unit]
Description=BasinEnergy Broadcast Laravel queue worker
Requires=docker.service
After=docker.service

[Service]
Type=simple
User=root
ExecStart=/usr/bin/docker exec brc_site php artisan queue:work database --queue=broadcast-mail --sleep=2 --tries=1 --timeout=60 --max-time=3600
ExecStop=/usr/bin/docker exec brc_site php artisan queue:restart
Restart=always
RestartSec=10
TimeoutStopSec=90
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now brc-broadcast-queue.service
systemctl status brc-broadcast-queue.service --no-pager
journalctl -u brc-broadcast-queue.service -n 30 --no-pager
```

The service monitors the foreground `docker exec` process. It restarts it
if the worker exits normally after one hour, crashes, or loses the container
connection. The initial `Processing jobs from...` line followed by no
further output means it is idle and waiting, **not stuck**.

Use `systemctl restart brc-broadcast-queue.service` when you deploy
updated code. The stop hook also issues Laravel's graceful
`queue:restart` command; this requires a functioning shared cache driver.
Use `systemctl stop brc-broadcast-queue.service` before container
replacement, and `systemctl start brc-broadcast-queue.service` afterward.
Avoid starting other persistent Broadcast workers manually; check running
workers using `docker top brc_site`.

If the host uses another Docker binary path, container name, or non-systemd
init system, adjust these instructions. A standalone Docker Compose worker
service with `restart: unless-stopped` is a longer-term alternative and
must reuse the exact app environment, network, code and storage mounts.

## Published announcement with 0 / 0 recipients

`DispatchBroadcastAnnouncement DONE` only means the orchestration job
finished. It does not imply a recipient email was queued or sent.
The original recipient snapshot is taken *when the announcement is
published*. It includes only active, email-verified regular User
accounts with an email address, and (when targeting roles) membership
in a selected User role. Role numbers displayed in the admin table are
role **IDs**, not recipient counts.

Starting with this change, an empty eligible audience prevents publication
and produces a validation error, preserving the draft. Legacy already-
published announcements with zero delivery records display
`No eligible recipients` instead of a misleading 0% loading bar.

An old published announcement cannot be re-targeted by assigning roles
after publication. To reach users who were not eligible at the original
publish time, first correct their role assignment/activation/verification
through the normal account workflow, then create a **new** announcement
and publish to those users. Do not manually backfill or replay old
delivery rows without reconciling recipient consent and duplication risk.

For read-only diagnosis in `php artisan tinker`:

```php
$a = \\App\\Models\\BroadcastAnnouncement::where('title', 'Test Announcement')->latest('id')->first();
$a?->only(['id', 'status', 'audience', 'role_ids']);
$a?->deliveries()->count();
$role = \\App\\Domains\\Auth\\Models\\Role::find(3);
$role?->only(['id', 'name', 'type']);
$userQuery = \\App\\Domains\\Auth\\Models\\User::query()->whereHas('roles', fn ($q) => $q->where('roles.id', 3));
$userQuery->count();
$userQuery->where('type', 'user')->where('active', true)->whereNotNull('email')->whereNotNull('email_verified_at')->count();
```

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
