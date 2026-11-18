<li class="c-header-nav-item dropdown px-2">
    <button
        class="btn btn-link c-header-nav-link dropdown-toggle d-flex align-items-center"
        type="button"
        id="themeDropdown"
        data-dashboard-theme-toggle
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="@lang('Theme')"
        title="@lang('Theme')">
        <i data-dashboard-theme-icon class="c-icon cil-screen-desktop" aria-hidden="true"></i>
        <span class="sr-only" data-dashboard-theme-label>@lang('Auto')</span>
    </button>
    <div class="dropdown-menu dropdown-menu-right brc-theme-menu" aria-labelledby="themeDropdown">
        <button class="dropdown-item d-flex align-items-center" type="button"
            data-dashboard-theme-value="light" aria-pressed="false">
            <i class="c-icon cil-sun mr-3" aria-hidden="true"></i>
            <span>@lang('Light')</span>
        </button>
        <button class="dropdown-item d-flex align-items-center" type="button"
            data-dashboard-theme-value="dark" aria-pressed="false">
            <i class="c-icon cil-moon mr-3" aria-hidden="true"></i>
            <span>@lang('Dark')</span>
        </button>
        <button class="dropdown-item d-flex align-items-center" type="button"
            data-dashboard-theme-value="auto" aria-pressed="false">
            <i class="c-icon cil-screen-desktop mr-3" aria-hidden="true"></i>
            <span>@lang('Auto')</span>
        </button>
    </div>
</li>
