/* Admin notification center: merged contact and newsletter feeds.
 * Uses the existing Echo/Reverb events, an authenticated JSON endpoint,
 * and an AJAX Mark all as read action. No navigation or page refresh.
 */
(function () {
    'use strict';

    function initialize() {
        var panel = document.getElementById('brcNotificationPanel');
        if (!panel) return;

        var list = document.getElementById('brcNotificationList');
        var badge = document.getElementById('contactNotificationBadge');
        var more = document.getElementById('brcNotificationLoadMore');
        var markAll = document.getElementById('brcNotificationMarkRead');
        var unreadCount = document.getElementById('brcNotificationUnreadCount');
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var feedUrl = panel.dataset.feedUrl;
        var markUrl = panel.dataset.markUrl;
        var page = 0;
        var loading = false;
        var generation = 0;
        var hasMore = false;

        function toast(message, icon) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: icon || 'success',
                    titleText: String(message),
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
                return;
            }

            var element = document.createElement('div');
            element.className = 'brc-notification-toast';
            element.setAttribute('role', 'status');
            element.textContent = message;
            document.body.appendChild(element);
            setTimeout(function () { element.remove(); }, 3000);
        }

        function displayCount(count) {
            count = Math.max(0, Number(count) || 0);
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.setAttribute('aria-label', count + ' unread notifications');
            badge.hidden = !count;
            unreadCount.textContent = String(count);
            markAll.disabled = count === 0;
        }

        function relativeTime(iso) {
            if (!iso) return '';
            var timestamp = new Date(iso).getTime();
            if (!Number.isFinite(timestamp)) return '';
            var minutes = Math.max(0, Math.floor((Date.now() - timestamp) / 60000));
            if (minutes < 1) return 'now';
            if (minutes < 60) return minutes + 'm';
            var hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + 'h';
            var days = Math.floor(hours / 24);
            if (days < 7) return days + 'd';
            return new Date(iso).toLocaleDateString();
        }

        function row(item) {
            // All user-supplied notification text is assigned through
            // textContent to prevent content-message HTML injection.
            var link = document.createElement('a');
            link.className = 'brc-notification-item' + (item.unread ? ' is-unread' : '');
            link.href = item.url;
            link.setAttribute('data-notification-type', item.type);

            var heading = document.createElement('div');
            heading.className = 'brc-notification-item-head';
            var title = document.createElement('strong');
            title.className = 'brc-notification-item-title';
            title.textContent = item.title;
            var time = document.createElement('time');
            time.className = 'brc-notification-item-time';
            time.textContent = relativeTime(item.created_at);
            if (item.created_at) time.dateTime = item.created_at;
            heading.append(title, time);

            var description = document.createElement('div');
            description.className = 'brc-notification-item-message';
            description.textContent = item.body || '';
            link.append(heading, description);
            return link;
        }

        function emptyState(message) {
            var element = document.createElement('div');
            element.className = 'brc-notification-empty';
            element.textContent = message;
            list.appendChild(element);
        }

        async function load(nextPage, append) {
            if (loading) return;
            loading = true;
            var currentGeneration = ++generation;
            more.disabled = true;

            try {
                var url = new URL(feedUrl, window.location.href);
                url.searchParams.set('page', String(nextPage));
                var response = await fetch(url.toString(), {
                    headers: {'Accept': 'application/json'},
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                if (!response.ok) throw new Error('Unable to load notifications.');

                var data = await response.json();
                if (currentGeneration !== generation) return;
                if (!append) list.replaceChildren();

                (data.items || []).forEach(function (item) {
                    list.appendChild(row(item));
                });
                if (!list.children.length) emptyState('No notifications yet.');
                page = nextPage;
                hasMore = Boolean(data.has_more);
                more.hidden = !hasMore;
                displayCount(data.count);
            } catch (error) {
                if (!append) {
                    list.replaceChildren();
                    emptyState('Could not load notifications. Try again.');
                }
                toast(error.message, 'error');
            } finally {
                loading = false;
                more.disabled = false;
            }
        }

        function refresh() {
            if (!loading) load(1, false);
        }

        markAll.addEventListener('click', async function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (markAll.disabled) return;
            markAll.disabled = true;

            try {
                var response = await fetch(markUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf ? csrf.content : ''
                    }
                });
                if (!response.ok) throw new Error('Unable to mark notifications as read.');
                var result = await response.json();
                if (!result.ok) throw new Error('Could not update notifications.');
                displayCount(0);
                list.querySelectorAll('.is-unread').forEach(function (item) {
                    item.classList.remove('is-unread');
                });
                toast('All notifications marked as read', 'success');
                refresh();
            } catch (error) {
                toast(error.message, 'error');
                refresh();
            }
        });

        more.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            if (!loading && hasMore) load(page + 1, true);
        });

        // Bootstrap 4 normally closes menus on clicks. Keeping this menu
        // open makes Mark all as read and Load More truly in-place actions.
        panel.addEventListener('click', function (event) {
            if (event.target.closest('button')) event.stopPropagation();
        });

        if (window.jQuery) {
            window.jQuery(panel).closest('.dropdown')
                .on('shown.bs.dropdown', refresh);
        }

        // Receive real-time notifications from the already running Reverb
        // channels. Polling covers temporary WebSocket disconnections.
        if (window.Echo) {
            window.Echo.channel('admin.notifications.contacts')
                .listen('.contact.message.submitted', refresh);
            var adminId = panel.dataset.adminId;
            window.Echo.private('admin.newsletters.' + adminId)
                .listen('.newsletter.subscription.created', refresh);
        }
        refresh();
        setInterval(function () {
            if (!document.hidden) refresh();
        }, 20000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, {once: true});
    } else {
        initialize();
    }
}());
