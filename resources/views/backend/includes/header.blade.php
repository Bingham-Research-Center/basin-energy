<header class="c-header c-header-light c-header-fixed">
    <button class="c-header-toggler c-class-toggler d-lg-none mfe-auto" type="button" data-target="#sidebar" data-class="c-sidebar-show">
        <i class="c-icon c-icon-lg cil-menu"></i>
    </button>

    <div class="c-header-brand d-lg-none brc-mobile-brand">
        @include('includes.partials.dashboard-brand', [
            'destination' => route('admin.dashboard'),
            'compact' => false,
        ])
    </div>

    <button class="c-header-toggler c-class-toggler mfs-3 d-md-down-none" type="button" data-target="#sidebar" data-class="c-sidebar-lg-show" responsive="true">
        <i class="c-icon c-icon-lg cil-menu"></i>
    </button>

    <ul class="c-header-nav d-md-down-none">
        <li class="c-header-nav-item px-3"><a class="c-header-nav-link" href="{{ route('frontend.index') }}">@lang('Home')</a></li>

        @if(config('boilerplate.locale.status') && count(config('boilerplate.locale.languages')) > 1)
            <li class="c-header-nav-item dropdown">
                <x-utils.link
                    :text="__(getLocaleName(app()->getLocale()))"
                    class="c-header-nav-link dropdown-toggle"
                    id="navbarDropdownLanguageLink"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false" />

                @include('includes.partials.lang')
            </li>
        @endif
    </ul>

    <ul class="c-header-nav ml-auto mr-4">
        @include('includes.partials.dashboard-theme-switcher')
        @php
            $newsletterAlertCount = \App\Models\NewsletterAdminAlert::where('admin_id', $logged_in_user->id)
                ->whereNull('read_at')->count();
            $newsletterAlerts = \App\Models\NewsletterAdminAlert::where('admin_id', $logged_in_user->id)
                ->whereNull('read_at')->with('subscriber:id,name')
                ->latest('id')->take(5)->get();
        @endphp
        @php
            $unreadContactMessages = \App\Models\ContactMessage::where('is_read', false)
                ->latest()
                ->take(5)
                ->get();

            $unreadContactCount = \App\Models\ContactMessage::where('is_read', false)->count();
        @endphp

        <li class="c-header-nav-item dropdown">
            <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="c-icon c-icon-lg cil-bell"></i>
                <span id="contactNotificationBadge"
                    class="badge badge-danger"
                    data-contact-count="{{ $unreadContactCount }}"
                    style="{{ ($unreadContactCount + $newsletterAlertCount) ? '' : 'display:none;' }}">
                    {{ $unreadContactCount + $newsletterAlertCount }}
                </span>
            </a>

            <div class="dropdown-menu dropdown-menu-right pt-0" style="min-width: 360px;">
                <div class="dropdown-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <strong>Notifications</strong>
                    <a href="{{ route('admin.website-update.contact') }}" class="small">View all</a>
                </div>

                <div id="contactNotificationList">
                    @forelse($unreadContactMessages as $message)
                        <a href="{{ route('admin.website-update.contact') }}" class="dropdown-item border-bottom">
                            <div class="font-weight-bold">{{ $message->name }}</div>
                            <div class="small">&nbsp;:&nbsp;{{ \Illuminate\Support\Str::limit($message->message, 20) }}</div>
                            <div class="small text-muted">{{ optional($message->created_at)->format('Y-m-d H:i') }}</div>
                        </a>
                    @empty
                        <div id="noContactNotifications" class="dropdown-item text-muted">
                            No new contact messages
                        </div>
                    @endforelse
                </div>
                <div class="dropdown-header bg-light py-2">
                    <strong>Newsletter subscriptions</strong>
                    <span class="badge badge-info" id="newsletterAlertsCount">{{ $newsletterAlertCount }}</span>
                </div>
                <div id="newsletterAlertList">
                    @forelse($newsletterAlerts as $alert)
                        <div class="dropdown-item border-bottom">
                            <strong>{{ $alert->subscriber->name ?? 'Deleted user' }}</strong>
                            <div class="small">Subscribed to the monthly newsletter</div>
                        </div>
                    @empty
                        <div class="dropdown-item text-muted">No new subscriptions</div>
                    @endforelse
                </div>
                <div class="dropdown-item">
                    <form method="POST" action="{{ route('admin.broadcast.alerts.read') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm btn-block">Mark newsletter alerts as read</button>
                    </form>
                </div>
            </div>
        </li>
        <li class="c-header-nav-item dropdown">
            <x-utils.link class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <x-slot name="text">
                    <div class="c-avatar">
                        <img class="c-avatar-img" src="{{ $logged_in_user->avatar }}" alt="{{ $logged_in_user->email ?? '' }}">
                    </div>
                </x-slot>
            </x-utils.link>

            <div class="dropdown-menu dropdown-menu-right pt-0">
                <div class="dropdown-header bg-light py-2">
                    <strong>@lang('Account')</strong>
                </div>

                <x-utils.link
                    class="dropdown-item"
                    icon="c-icon mr-2 cil-account-logout"
                    onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                    <x-slot name="text">
                        @lang('Logout')
                        <x-forms.post :action="route('frontend.auth.logout')" id="logout-form" class="d-none" />
                    </x-slot>
                </x-utils.link>
            </div>
        </li>
    </ul>

    <div class="c-subheader justify-content-between px-3">
        @include('backend.includes.partials.breadcrumbs')

        <div class="c-subheader-nav mfe-2">
            @yield('breadcrumb-links')
        </div>
    </div><!--c-subheader-->
</header>
@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Echo) {
            console.error('Echo is not loaded in backend header.');
            return;
        }

        const badge = document.getElementById('contactNotificationBadge');
        const list = document.getElementById('contactNotificationList');

        window.Echo.channel('admin.notifications.contacts')
            .listen('.contact.message.submitted', function (e) {
                console.log('New contact notification received:', e);

                const emptyState = document.getElementById('noContactNotifications');
                if (emptyState) {
                    emptyState.remove();
                }

                let count = parseInt(badge.textContent || '0', 10);
                count += 1;
                badge.dataset.contactCount = String((parseInt(badge.dataset.contactCount || '0', 10) || 0) + 1);
                badge.textContent = (parseInt(badge.dataset.contactCount, 10) || 0) +
                    (parseInt(document.getElementById('newsletterAlertsCount').textContent, 10) || 0);
                badge.style.display = 'inline-block';

                const item = document.createElement('a');
                item.href = e.url;
                item.className = 'dropdown-item border-bottom';
                item.innerHTML = `
                    <div class="font-weight-bold">${e.name}</div>
                    <div class="small">&nbsp;:&nbsp; ${e.message}</div>
                    <div class="small text-muted">${e.created_at}</div>
                `;

                list.prepend(item);

                while (list.children.length > 5) {
                    list.removeChild(list.lastElementChild);
                }
            });
    });
</script>
@endpush
@push('after-scripts')
<script>
(function () {
    const badge = document.getElementById('contactNotificationBadge');
    const list = document.getElementById('newsletterAlertList');
    const countNode = document.getElementById('newsletterAlertsCount');
    if (!badge || !list || !countNode) return;

    const url = @json(route('admin.broadcast.alerts'));
    async function refresh() {
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'},
                cache: 'no-store'
            });
            if (!response.ok) return;
            const payload = await response.json();
            const newsletterCount = Number(payload.count) || 0;
            countNode.textContent = String(newsletterCount);
            const total = (Number(badge.dataset.contactCount) || 0) + newsletterCount;
            badge.textContent = String(total);
            badge.style.display = total ? 'inline-block' : 'none';
            list.replaceChildren();
            if (!payload.items || !payload.items.length) {
                const empty = document.createElement('div');
                empty.className = 'dropdown-item text-muted';
                empty.textContent = 'No new subscriptions';
                list.appendChild(empty);
                return;
            }
            payload.items.forEach(function (item) {
                const row = document.createElement('div');
                row.className = 'dropdown-item border-bottom';
                const name = document.createElement('strong');
                name.textContent = item.name;
                const description = document.createElement('div');
                description.className = 'small';
                description.textContent = 'Subscribed to the monthly newsletter';
                row.append(name, description);
                list.appendChild(row);
            });
        } catch (error) {
            // Continue trying on the next poll.
        }
    }
    if (window.Echo) {
        window.Echo.private('admin.newsletters.' + @json($logged_in_user->id))
            .listen('.newsletter.subscription.created', refresh);
    }
    refresh();
    setInterval(function () {
        if (!document.hidden) refresh();
    }, 20000);
}());
</script>
@endpush
