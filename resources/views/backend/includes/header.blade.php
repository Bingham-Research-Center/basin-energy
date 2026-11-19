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
            $contactUnread = \App\Models\ContactMessage::where('is_read', false)->count();
            $newsletterUnread = \App\Models\NewsletterAdminAlert::where('admin_id', $logged_in_user->id)
                ->whereNull('read_at')->count();
            $notificationUnread = $contactUnread + $newsletterUnread;
        @endphp

        <li class="c-header-nav-item dropdown brc-notification-dropdown">
            <button type="button" class="c-header-nav-link brc-notification-trigger"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                id="brcNotificationTrigger" aria-label="Open notifications">
                <i class="c-icon c-icon-lg cil-bell" aria-hidden="true"></i>
                <span id="contactNotificationBadge" class="brc-notification-badge"
                    aria-label="{{ $notificationUnread }} unread notifications"
                    @if(!$notificationUnread) hidden @endif>{{ $notificationUnread > 99 ? '99+' : $notificationUnread }}</span>
            </button>

            <div class="dropdown-menu dropdown-menu-right brc-notification-panel"
                id="brcNotificationPanel" aria-labelledby="brcNotificationTrigger"
                data-feed-url="{{ route('admin.broadcast.alerts') }}"
                data-mark-url="{{ route('admin.broadcast.alerts.read') }}"
                data-admin-id="{{ $logged_in_user->id }}">
                <div class="brc-notification-heading">
                    <div class="d-flex align-items-center">
                        <strong>Notifications</strong>
                        <span class="brc-notification-unread-count ml-2"
                            id="brcNotificationUnreadCount">{{ $notificationUnread }}</span>
                    </div>
                    <button type="button" id="brcNotificationMarkRead"
                        class="brc-notification-mark-read"
                        @if(!$notificationUnread) disabled @endif>
                        <i class="c-icon cil-check" aria-hidden="true"></i>
                        Mark all as read
                    </button>
                </div>
                <div id="brcNotificationList" class="brc-notification-items"
                    aria-live="polite" aria-label="Recent notifications">
                    <div class="brc-notification-empty">Loading notifications…</div>
                </div>
                <div class="brc-notification-footer">
                    <button type="button" id="brcNotificationLoadMore"
                        class="brc-notification-load-more" hidden>Load More</button>
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
