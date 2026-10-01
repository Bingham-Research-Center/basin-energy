@php use Illuminate\Support\Str; @endphp
<div class="c-sidebar c-sidebar-dark c-sidebar-fixed c-sidebar-lg-show" id="sidebar">
    <div class="c-sidebar-brand d-lg-down-none">
        <svg class="c-sidebar-brand-full" width="118" height="46" alt="CoreUI Logo">
            <use xlink:href="{{ asset('img/brand/coreui.svg#full') }}"></use>
        </svg>

        <svg class="c-sidebar-brand-minimized" width="46" height="46" alt="CoreUI Logo">
            <use xlink:href="{{ asset('img/brand/coreui.svg#signet') }}"></use>
        </svg>
    </div>

    <ul class="c-sidebar-nav">
        @auth
            <li class="c-sidebar-nav-item">
                <x-utils.link
                    class="c-sidebar-nav-link"
                    :href="route('frontend.user.dashboard')"
                    :active="activeClass(Route::is('frontend.user.dashboard'), 'c-active')"
                    icon="c-sidebar-nav-icon cil-speedometer"
                    :text="__('Dashboard')" />
            </li>

            <li class="c-sidebar-nav-title">@lang('Data')</li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.data.emission-trends*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-chart-line"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Emission Trends')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('frontend.user.data.emission-trends')"
                            class="c-sidebar-nav-link"
                            :text="__('Explore Data')"
                            :active="activeClass(Route::is('frontend.user.data.emission-trends*'), 'c-active')" />
                    </li>
                </ul>
            </li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.carbon-mapper.*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-map"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Carbon Mapper')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('frontend.user.carbon-mapper.utah')"
                            class="c-sidebar-nav-link"
                            :text="__('Utah Super-Emitters')"
                            :active="activeClass(Route::is('frontend.user.carbon-mapper.*'), 'c-active')" />
                    </li>
                </ul>
            </li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.data.produced-water*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-drop"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Produced Water')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('frontend.user.data.produced-water')"
                            class="c-sidebar-nav-link"
                            :text="__('Flux Dashboard')"
                            :active="activeClass(Route::is('frontend.user.data.produced-water*'), 'c-active')" />
                    </li>
                </ul>
            </li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.data.subsurface-natural-gas-leaks*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-warning"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Natural Gas Leaks')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('frontend.user.data.subsurface-natural-gas-leaks')"
                            class="c-sidebar-nav-link"
                            :text="__('Subsurface Soil Emissions')"
                            :active="activeClass(Route::is('frontend.user.data.subsurface-natural-gas-leaks*'), 'c-active')" />
                    </li>
                </ul>
            </li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.data.oil-well-pad-emissions*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-industry"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Oil Well Pad Emissions')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('frontend.user.data.oil-well-pad-emissions')"
                            class="c-sidebar-nav-link"
                            :text="__('Duchesne County')"
                            :active="activeClass(Route::is('frontend.user.data.oil-well-pad-emissions*'), 'c-active')" />
                    </li>
                </ul>
            </li>
            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-camera"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Optical Gas Imaging Survey')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            href="#"
                            class="c-sidebar-nav-link"
                            :text="__('Coming Soon')" />
                    </li>
                </ul>
            </li>

            <li class="c-sidebar-nav-dropdown">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-settings"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Pumpjack Engine Emissions')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            href="#"
                            class="c-sidebar-nav-link"
                            :text="__('Coming Soon')" />
                    </li>
                </ul>
            </li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.data.realtime.*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-clock"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="__('Real-Time Data')" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('frontend.user.data.realtime.ozone')"
                            class="c-sidebar-nav-link"
                            :text="__('Uinta Ozone')"
                            :active="activeClass(Route::is('frontend.user.data.realtime.ozone*'), 'c-active')" />
                    </li>

                    <li class="c-sidebar-nav-title">@lang('Campbell Loggers')</li>

                    @foreach(config('services.campbell_sites', []) as $key => $site)
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.data.realtime.logger', ['site' => $key])"
                                class="c-sidebar-nav-link"
                                :text="$site['name'] ?? Str::headline($key)"
                                :active="activeClass(Route::is('frontend.user.data.realtime.logger') && request()->route('site') === $key, 'c-active')" />
                        </li>
                    @endforeach

                    <li class="c-sidebar-nav-title">@lang('Other Sites')</li>

                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            href="#"
                            class="c-sidebar-nav-link"
                            :text="__('Dinosaur NM')" />
                    </li>

                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            href="#"
                            class="c-sidebar-nav-link"
                            :text="__('Little Mountain')" />
                    </li>

                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            href="#"
                            class="c-sidebar-nav-link"
                            :text="__('Ouray')" />
                    </li>

                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            href="#"
                            class="c-sidebar-nav-link"
                            :text="__('Whiterocks')" />
                    </li>
                </ul>
            </li>

            <li class="c-sidebar-nav-title">@lang('User')</li>

            <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.account') || Route::is('frontend.user.account.*'), 'c-open c-show') }}">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-user"
                    class="c-sidebar-nav-dropdown-toggle"
                    :text="$logged_in_user->name" />

                <ul class="c-sidebar-nav-dropdown-items">
                    <li class="c-sidebar-nav-item">
                        <x-utils.link
                            :href="route('frontend.user.account')"
                            class="c-sidebar-nav-link"
                            :text="__('Account Info')"
                            :active="activeClass(Route::is('frontend.user.account') || Route::is('frontend.user.account.*'), 'c-active')" />
                    </li>
                </ul>
            </li>
        @endauth
    </ul>

    <button
        class="c-sidebar-minimizer c-class-toggler"
        type="button"
        data-target="_parent"
        data-class="c-sidebar-minimized">
    </button>
</div>