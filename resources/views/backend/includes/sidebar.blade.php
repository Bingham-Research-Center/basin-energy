<div class="c-sidebar c-sidebar-dark c-sidebar-fixed c-sidebar-lg-show" id="sidebar">
    <div class="c-sidebar-brand d-lg-down-none brc-sidebar-brand">
        @include('includes.partials.dashboard-brand', [
            'destination' => route('admin.dashboard'),
            'compact' => true,
        ])
    </div><!--c-sidebar-brand-->

    <ul class="c-sidebar-nav">
        <li class="c-sidebar-nav-item">
            <x-utils.link
                class="c-sidebar-nav-link"
                :href="route('admin.dashboard')"
                :active="activeClass(Route::is('admin.dashboard'), 'c-active')"
                icon="c-sidebar-nav-icon cil-speedometer"
                :text="__('Dashboard')" />
        </li>

        @if (
            $logged_in_user->hasAllAccess() ||
            (
                $logged_in_user->can('admin.access.user.list') ||
                $logged_in_user->can('admin.access.user.deactivate') ||
                $logged_in_user->can('admin.access.user.reactivate') ||
                $logged_in_user->can('admin.access.user.clear-session') ||
                $logged_in_user->can('admin.access.user.impersonate') ||
                $logged_in_user->can('admin.access.user.change-password')
            )
        )
            <li class="c-sidebar-nav-title">@lang('System')</li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.auth.user.*') || Route::is('admin.auth.role.*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-user"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Access')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    @if (
                        $logged_in_user->hasAllAccess() ||
                        (
                            $logged_in_user->can('admin.access.user.list') ||
                            $logged_in_user->can('admin.access.user.deactivate') ||
                            $logged_in_user->can('admin.access.user.reactivate') ||
                            $logged_in_user->can('admin.access.user.clear-session') ||
                            $logged_in_user->can('admin.access.user.impersonate') ||
                            $logged_in_user->can('admin.access.user.change-password')
                        )
                    )
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('admin.auth.user.index')"
                                class="c-sidebar-nav-link"
                                :text="__('User Management')"
                                :active="activeClass(Route::is('admin.auth.user.*'), 'c-active')" />
                        </li>
                    @endif

                    @if ($logged_in_user->hasAllAccess())
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('admin.auth.role.index')"
                                class="c-sidebar-nav-link"
                                :text="__('Role Management')"
                                :active="activeClass(Route::is('admin.auth.role.*'), 'c-active')" />
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        {{-- Log routes are protected by the admin account middleware. --}}
        <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('log-viewer::*'), 'c-open c-show') }}">
            <x-utils.link
                href="#"
                icon="c-sidebar-nav-icon cil-list"
                class="c-sidebar-nav-dropdown-toggle"
                :text="__('Application Logs')" />

            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item">
                    <x-utils.link
                        :href="route('log-viewer::dashboard')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('log-viewer::dashboard'), 'c-active')"
                        :text="__('Dashboard')" />
                </li>
                <li class="c-sidebar-nav-item">
                    <x-utils.link
                        :href="route('log-viewer::logs.list')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('log-viewer::logs.*'), 'c-active')"
                        :text="__('Log Files')" />
                </li>
            </ul>
        </li>

        {{-- Same research datasets as the regular User dashboard, using
             separate admin-protected pages and JSON endpoints. --}}
        <li class="c-sidebar-nav-title">@lang('Research')</li>
        <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.data.*') && ! Route::is('admin.data.realtime.*'), 'c-open c-show') }}">
            <x-utils.link href="#" icon="c-sidebar-nav-icon cil-chart-line"
                class="c-sidebar-nav-dropdown-toggle" :text="__('Data')" />
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.data.emission-trends')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.data.emission-trends*'), 'c-active')"
                        :text="__('Emission Trends')" />
                </li>
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.data.carbon-mapper.utah')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.data.carbon-mapper.*'), 'c-active')"
                        :text="__('Carbon Mapper — Utah Super-Emitters')" />
                </li>
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.data.produced-water')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.data.produced-water*'), 'c-active')"
                        :text="__('Produced Water')" />
                </li>
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.data.subsurface-natural-gas-leaks')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.data.subsurface-natural-gas-leaks*'), 'c-active')"
                        :text="__('Natural Gas Leaks')" />
                </li>
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.data.oil-well-pad-emissions')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.data.oil-well-pad-emissions*'), 'c-active')"
                        :text="__('Oil Well Pad Emissions')" />
                </li>
            </ul>
        </li>

        <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.data.realtime.*'), 'c-open c-show') }}">
            <x-utils.link href="#" icon="c-sidebar-nav-icon cil-clock"
                class="c-sidebar-nav-dropdown-toggle" :text="__('Real-Time Data')" />
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.data.realtime.ozone')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.data.realtime.ozone*'), 'c-active')"
                        :text="__('Uinta Ozone')" />
                </li>
                <li class="c-sidebar-nav-title">@lang('Campbell Loggers')</li>
                @foreach (config('services.campbell_sites', []) as $key => $site)
                    <li class="c-sidebar-nav-item">
                        <x-utils.link :href="route('admin.data.realtime.logger', ['site' => $key])"
                            class="c-sidebar-nav-link"
                            :active="activeClass(Route::is('admin.data.realtime.logger*') && request()->route('site') === $key, 'c-active')"
                            :text="$site['name'] ?? \Illuminate\Support\Str::headline($key)" />
                    </li>
                @endforeach
                <li class="c-sidebar-nav-title">@lang('Other Sites')</li>
                @foreach (config('services.other_monitoring_sites', []) as $key => $site)
                    <li class="c-sidebar-nav-item">
                        <x-utils.link :href="route('admin.data.realtime.other', ['site' => $key])"
                            class="c-sidebar-nav-link"
                            :active="activeClass(Route::is('admin.data.realtime.other') && request()->route('site') === $key, 'c-active')"
                            :text="$site['name'] ?? \Illuminate\Support\Str::headline($key)" />
                    </li>
                @endforeach
            </ul>
        </li>

        <li class="c-sidebar-nav-title">@lang('Broadcast')</li>
        <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.broadcast.*'), 'c-open c-show') }}">
            <x-utils.link href="#" icon="c-sidebar-nav-icon cil-envelope-closed"
                class="c-sidebar-nav-dropdown-toggle" :text="__('Broadcast')" />
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.broadcast.newsletters.index')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.broadcast.newsletters.*'), 'c-active')"
                        :text="__('Newsletters')" />
                </li>
                <li class="c-sidebar-nav-item">
                    <x-utils.link :href="route('admin.broadcast.announcements.index')"
                        class="c-sidebar-nav-link"
                        :active="activeClass(Route::is('admin.broadcast.announcements.*'), 'c-active')"
                        :text="__('Announcements')" />
                </li>
            </ul>
        </li>

        {{-- Personal account settings are for every Administrator-type account,
             including those without the master Administrator role. --}}
            <li class="c-sidebar-nav-title">Admin</li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.account.*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-user"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="$logged_in_user->name" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('admin.account.index')"
                            class="c-sidebar-nav-link"
                            :text="__('Account info')"
                            :active="activeClass(Route::is('admin.account.*'), 'c-active')" />
                    </li>
                </ul>
            </li>

        @if (\App\Domains\Auth\Support\WebsiteContentPermissions::canEditAny($logged_in_user))
            <li class="c-sidebar-nav-title">Frontend</li>
            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.website-update.*'), 'c-open c-show') }}">
                <x-utils.link href="#" icon="c-sidebar-nav-icon cil-info"
                    class="c-sidebar-nav-dropdown-toggle" :text="__('Website Updates')" />
                <ul class="c-sidebar-nav-dropdown-items">
                    @if (\App\Domains\Auth\Support\WebsiteContentPermissions::canEdit($logged_in_user, 'home'))
                        <li class="c-sidebar-nav-item">
                            <x-utils.link :href="route('admin.website-update.home')"
                                class="c-sidebar-nav-link"
                                :active="activeClass(Route::is('admin.website-update.home*'), 'c-active')"
                                :text="__('Home')" />
                        </li>
                    @endif
                    @if (\App\Domains\Auth\Support\WebsiteContentPermissions::canEdit($logged_in_user, 'about'))
                        <li class="c-sidebar-nav-item">
                            <x-utils.link :href="route('admin.website-update.about')"
                                class="c-sidebar-nav-link"
                                :active="activeClass(Route::is('admin.website-update.about*'), 'c-active')"
                                :text="__('About')" />
                        </li>
                    @endif
                    @if (\App\Domains\Auth\Support\WebsiteContentPermissions::canEdit($logged_in_user, 'service'))
                        <li class="c-sidebar-nav-item">
                            <x-utils.link :href="route('admin.website-update.service')"
                                class="c-sidebar-nav-link"
                                :active="activeClass(Route::is('admin.website-update.service*'), 'c-active')"
                                :text="__('Service')" />
                        </li>
                    @endif
                    @if (\App\Domains\Auth\Support\WebsiteContentPermissions::canEdit($logged_in_user, 'portfolio'))
                        <li class="c-sidebar-nav-item">
                            <x-utils.link :href="route('admin.website-update.portfolio')"
                                class="c-sidebar-nav-link"
                                :active="activeClass(Route::is('admin.website-update.portfolio*'), 'c-active')"
                                :text="__('Portfolio')" />
                        </li>
                    @endif
                    @if (\App\Domains\Auth\Support\WebsiteContentPermissions::canEdit($logged_in_user, 'blog'))
                        <li class="c-sidebar-nav-item">
                            <x-utils.link :href="route('admin.website-update.blog')"
                                class="c-sidebar-nav-link"
                                :active="activeClass(Route::is('admin.website-update.blog*'), 'c-active')"
                                :text="__('Blog')" />
                        </li>
                    @endif
                    @if (\App\Domains\Auth\Support\WebsiteContentPermissions::canEdit($logged_in_user, 'contact'))
                        <li class="c-sidebar-nav-item">
                            <x-utils.link :href="route('admin.website-update.contact')"
                                class="c-sidebar-nav-link"
                                :active="activeClass(Route::is('admin.website-update.contact*'), 'c-active')"
                                :text="__('Contacts')" />
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if ($logged_in_user->hasAllAccess())
        <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('admin.sensors.*'), 'c-open c-show') }}">
            <x-utils.link
                href="#"
                icon="c-sidebar-nav-icon cil-chart-line"
                class="c-sidebar-nav-dropdown-toggle"
                :text="__('Sensors')" />

            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item">
                    <x-utils.link
                        :href="route('admin.sensors.dashboard')"
                        class="c-sidebar-nav-link"
                        :text="__('Sensor Dashboard')"
                        :active="activeClass(Route::is('admin.sensors.dashboard'), 'c-active')" />
                </li>
            </ul>
        </li>

        @endif
    </ul>

    <button class="c-sidebar-minimizer c-class-toggler" type="button" data-target="_parent" data-class="c-sidebar-minimized"></button>
</div><!--sidebar-->
