<!doctype html>
<html lang="{{ htmlLang() }}" @langrtl dir="rtl" @endlangrtl>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ appName() }} | @yield('title')</title>
    <meta name="description" content="@yield('meta_description', appName())">
    <meta name="author" content="@yield('meta_author', 'Arjun Kula')">
    @yield('meta')

    @stack('before-styles')
    <link rel="stylesheet" href="{{ asset('frontend/plugins/themify/css/themify-icons.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet">
    <link href="{{ mix('css/backend.css') }}" rel="stylesheet">
    <link href="{{ asset('css/dashboard-theme.css') }}?v=2" rel="stylesheet">
    <link href="{{ asset('css/dashboard-brand.css') }}?v=2" rel="stylesheet">
    <link href="{{ asset('css/dashboard-tables.css') }}?v=1" rel="stylesheet">
    <link href="{{ asset('css/dashboard-notifications.css') }}?v=1" rel="stylesheet">
    <livewire:styles />
    @stack('after-styles')
</head>
<body class="c-app">
    @include('includes.partials.dashboard-theme-bootstrap')
    @include('backend.includes.sidebar')

    <div class="c-wrapper c-fixed-components">
        @include('backend.includes.header')
        @include('includes.partials.read-only')
        @include('includes.partials.logged-in-as')
        @include('includes.partials.announcements')

        <div class="c-body">
            <main class="c-main">
                <div class="container-fluid">
                    <div class="fade-in">
                        @include('includes.partials.messages')
                        @yield('content')
                    </div><!--fade-in-->
                </div><!--container-fluid-->
            </main>
        </div><!--c-body-->

        @include('backend.includes.footer')
    </div><!--c-wrapper-->

    @stack('before-scripts')
    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/vendor.js') }}"></script>
    <script src="{{ mix('js/backend.js') }}"></script>
    <script src="{{ asset('js/dashboard-theme.js') }}" defer></script>
    <script src="{{ asset('js/dashboard-toasts.js') }}?v=2"></script>
    <script src="{{ asset('js/dashboard-notifications.js') }}?v=2" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.js"></script>
    <livewire:scripts />
    @stack('after-scripts')
    @include('includes.partials.research-data-assets')
</body>
</html>
