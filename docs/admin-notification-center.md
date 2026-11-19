# Administrator notification center

The administrator header now renders a single, compact notification dropdown
for **Contact submissions** and **Newsletter subscriptions**, styled like
the provided notifications screenshot. Five newest messages are displayed
at once, with a **Load More** button to fetch additional pages. Unread
items have a different background and time labels appear as relative ages.

Existing data sources are reused:

- `contact_messages`: global `is_read` status; visible to authorized admins.
- `newsletter_admin_alerts`: `read_at` scoped to the logged-in admin.

## API and behavior

`GET /admin/broadcast/alerts?page=N` is administrator-only and returns
a combined, chronologically sorted response with item type, title, safe
textual summary, URL, timestamp, unread state, counts and `has_more`.
The server caps pages to 20 (five items per page), keeping queries bounded.
It never returns raw contact email addresses or unescaped HTML.

`POST /admin/broadcast/alerts/read` now marks both contact messages and
the current admin's newsletter alerts as read. For JSON requests it returns
`{ok:true, marked:N, count:0}` with HTTP 200; the existing redirect for
non-AJAX POST requests is preserved.

The header uses `public/js/dashboard-notifications.js` to call both
endpoints asynchronously, update the unread badge and list in place, and
display a **bottom-right SweetAlert2 toast** for **3 seconds** after the
Mark all as read action. No page reload or navigation occurs.
A fallback toast is shown if SweetAlert2 is unavailable.

The feed refreshes for existing Reverb/Echo events and polls the endpoint
every 20 seconds while the page is visible, as a connectivity fallback.
All message text is inserted via `textContent` rather than raw HTML.

This is **an Admin-only panel**; the normal User dashboard still has no
administrative contact-message visibility. CoreUI's existing Account and
theme menus are unchanged.

### Deployment

No database migration, Composer changes, or npm build required. The
standalone assets are versioned `?v=1`.

```bash
cd /var/www/brc_site
git pull --ff-only origin master
docker exec -it brc_site php artisan optimize:clear
docker exec -it brc_site php artisan config:cache
```

Browser-check Light, Dark, and Auto appearance; mobile dropdown positioning;
Load More, unread badges, Reverb updates and Mark all as read with no page
navigation. A published contact message is still accessible through the
existing Contact Management link.
