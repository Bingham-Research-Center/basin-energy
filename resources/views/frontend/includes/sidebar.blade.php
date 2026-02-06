<div class="c-sidebar c-sidebar-dark c-sidebar-fixed c-sidebar-lg-show" id="sidebar">
    <div class="c-sidebar-brand d-lg-down-none">
        <svg class="c-sidebar-brand-full" width="118" height="46" alt="CoreUI Logo">
            <use xlink:href="{{ asset('img/brand/coreui.svg#full') }}"></use>
        </svg>
        <svg class="c-sidebar-brand-minimized" width="46" height="46" alt="CoreUI Logo">
            <use xlink:href="{{ asset('img/brand/coreui.svg#signet') }}"></use>
        </svg>
    </div><!--c-sidebar-brand-->

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

        <li class="c-sidebar-nav-title">DATA</li>
        <li class="c-sidebar-nav-dropdown ">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-chart-line"
                    class="c-sidebar-nav-dropdown-toggle"
                    text="Emission trends" />

                <ul class="c-sidebar-nav-dropdown-items">
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.data.emission-trends')"
                                class="c-sidebar-nav-link"
                                text="Explore data"
                                :active="activeClass(Route::is('frontend.user.data.emission-trends*'), 'c-active')" />
                        </li>
                </ul>
        </li>

        <li class="c-sidebar-nav-dropdown ">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-map"
                    class="c-sidebar-nav-dropdown-toggle"
                    text="Carbon mapper" />

                <ul class="c-sidebar-nav-dropdown-items">
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.account')"
                                class="c-sidebar-nav-link"
                                text="TBD"
                                :active="activeClass(Route::is('frontend.user.account.*'), 'c-active')" />
                        </li>
                </ul>
        </li>

        <li class="c-sidebar-nav-dropdown ">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-drop"
                    class="c-sidebar-nav-dropdown-toggle"
                    text="Produced water" />

                <ul class="c-sidebar-nav-dropdown-items">
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.data.produced-water')"
                                class="c-sidebar-nav-link"
                                text="Produced Water"
                                :active="activeClass(Route::is('frontend.user.data.produced-water*'), 'c-active')" />
                        </li>
                </ul>
        </li>

        <li class="c-sidebar-nav-dropdown ">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-warning"
                    class="c-sidebar-nav-dropdown-toggle"
                    text="Natural gas leaks" />

                <ul class="c-sidebar-nav-dropdown-items">
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.account')"
                                class="c-sidebar-nav-link"
                                text="Account info"
                                :active="activeClass(Route::is('frontend.user.account.*'), 'c-active')" />
                        </li>
                </ul>
        </li>

        <li class="c-sidebar-nav-dropdown ">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-industry"
                    class="c-sidebar-nav-dropdown-toggle"
                    text="Oil well pads emission" />

                <ul class="c-sidebar-nav-dropdown-items">
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.account')"
                                class="c-sidebar-nav-link"
                                text="Duchesne County"
                                :active="activeClass(Route::is('frontend.user.account.*'), 'c-active')" />
                        </li>
                </ul>
        </li>

        <li class="c-sidebar-nav-dropdown ">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-camera"
                    class="c-sidebar-nav-dropdown-toggle"
                    text="Optical gas imaging survey" />

                <ul class="c-sidebar-nav-dropdown-items">
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.account')"
                                class="c-sidebar-nav-link"
                                text="Account info"
                                :active="activeClass(Route::is('frontend.user.account.*'), 'c-active')" />
                        </li>
                </ul>
        </li>

        <li class="c-sidebar-nav-dropdown ">
                <x-utils.link
                    href="#"
                    icon="c-sidebar-nav-icon cil-settings"
                    class="c-sidebar-nav-dropdown-toggle"
                    text="Pumpjack Engine Emissions" />

                <ul class="c-sidebar-nav-dropdown-items">
                        <li class="c-sidebar-nav-item">
                            <x-utils.link
                                :href="route('frontend.user.account')"
                                class="c-sidebar-nav-link"
                                text="Account info"
                                :active="activeClass(Route::is('frontend.user.account.*'), 'c-active')" />
                        </li>
                </ul>
        </li>
            

        <li class="c-sidebar-nav-title">USER</li>
        <li class="c-sidebar-nav-dropdown {{ activeClass(Route::is('frontend.user.account*'), 'c-open c-show') }}">
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
                                text="Account info"
                                :active="activeClass(Route::is('frontend.user.account.*'), 'c-active')" />
                        </li>
                </ul>
            </li>
    @endauth

</ul>


    <button class="c-sidebar-minimizer c-class-toggler" type="button" data-target="_parent" data-class="c-sidebar-minimized"></button>
</div><!--sidebar-->
