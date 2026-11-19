@if (Breadcrumbs::has())
    <ol class="breadcrumb border-0 m-0" aria-label="Breadcrumb">
        @foreach (Breadcrumbs::current() as $crumb)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}"
                @if ($loop->last) aria-current="page" @endif>
                @if ($crumb->url() && ! $loop->last)
                    <x-utils.link :href="$crumb->url()" :text="$crumb->title()" />
                @else
                    {{ $crumb->title() }}
                @endif
            </li>
        @endforeach
    </ol>
@elseif (Route::is('log-viewer::*'))
    {{-- LogViewer registers its own routes, outside our Tabuna route files. --}}
    <ol class="breadcrumb border-0 m-0" aria-label="Breadcrumb">
        <li class="breadcrumb-item">
            <x-utils.link :href="route('admin.dashboard')" :text="__('Home')" />
        </li>
        @if (Route::is('log-viewer::dashboard'))
            <li class="breadcrumb-item active" aria-current="page">@lang('Application Logs')</li>
        @else
            <li class="breadcrumb-item">
                <x-utils.link :href="route('log-viewer::dashboard')" :text="__('Application Logs')" />
            </li>
            @if (Route::is('log-viewer::logs.list'))
                <li class="breadcrumb-item active" aria-current="page">@lang('Log Files')</li>
            @else
                <li class="breadcrumb-item">
                    <x-utils.link :href="route('log-viewer::logs.list')" :text="__('Log Files')" />
                </li>
                <li class="breadcrumb-item active" aria-current="page">@lang('Log Details')</li>
            @endif
        @endif
    </ol>
@endif
