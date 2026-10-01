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
    <script>
        (function () {
            var theme = localStorage.getItem('brc-theme');

            if (!theme) {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            if (theme === 'dark') {
                document.addEventListener('DOMContentLoaded', function () {
                    document.body.classList.add('c-dark-theme');
                });
            }
        })();
    </script>
    @stack('before-styles')
    <link href="{{ mix('css/backend.css') }}" rel="stylesheet">
    <livewire:styles />
    @stack('after-styles')
</head>
<body class="c-app">
    @include('frontend.includes.sidebar')

    <div class="c-wrapper c-fixed-components">
        @include('frontend.includes.header')
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

        @include('frontend.includes.footer')
    </div><!--c-wrapper-->

    @stack('before-scripts')
    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/vendor.js') }}"></script>
    <script src="{{ mix('js/frontend.js') }}"></script>
    <script src="{{ mix('js/backend.js') }}"></script>
    <livewire:scripts />
    @stack('after-scripts')

   <script>
    (function () {
        var themeKey = 'brc-theme';

        function getTheme() {
            return localStorage.getItem(themeKey) || (
                window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
            );
        }

        function setTheme(theme) {
            localStorage.setItem(themeKey, theme);

            if (theme === 'dark') {
                document.body.classList.add('c-dark-theme');
                document.querySelector('.c-header')?.classList.remove('c-header-light');
                document.querySelector('.c-header')?.classList.add('c-header-dark');
            } else {
                document.body.classList.remove('c-dark-theme');
                document.querySelector('.c-header')?.classList.remove('c-header-dark');
                document.querySelector('.c-header')?.classList.add('c-header-light');
            }

            updateThemeButton(theme);
        }

        function updateThemeButton(theme) {
            var label = document.getElementById('theme-toggle-label');
            var icon = document.getElementById('theme-toggle-icon');

            if (!label || !icon) {
                return;
            }

            if (theme === 'dark') {
                label.innerText = 'Dark';
                icon.className = 'c-icon cil-moon mr-1';
            } else {
                label.innerText = 'Light';
                icon.className = 'c-icon cil-sun mr-1';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var currentTheme = getTheme();

            setTheme(currentTheme);

            var toggle = document.getElementById('theme-toggle');

            if (toggle) {
                toggle.addEventListener('click', function () {
                    var nextTheme = document.body.classList.contains('c-dark-theme') ? 'light' : 'dark';
                    setTheme(nextTheme);
                });
            }
        });
    })();
</script>
</body>
</html>