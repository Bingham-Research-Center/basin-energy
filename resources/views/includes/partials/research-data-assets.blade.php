{{-- A single versioned Mix entrypoint is loaded only on research DATA pages.
     Keep it after @stack('after-scripts'): Carbon Mapper's Leaflet scripts
     and the natural-gas page's inline API URL configuration must load first.
     Real-Time Data pages already use their own page-specific scripts. --}}
@php
    $adminResearchData = request()->routeIs('admin.data.*')
        && ! request()->routeIs('admin.data.realtime.*');
    $userResearchData = (request()->routeIs('frontend.user.data.*')
        && ! request()->routeIs('frontend.user.data.realtime.*'))
        || request()->routeIs('frontend.user.carbon-mapper.*');
@endphp

@if ($adminResearchData || $userResearchData)
    <script src="{{ mix('js/research-data.js') }}"></script>
@endif
