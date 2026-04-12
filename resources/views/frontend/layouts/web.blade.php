<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', appName())</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/bulma/bulma.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/themify/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/fontawesome/css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/aos/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/slick-carousel/slick/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/slick-carousel/slick/slick-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/magnific-popup/dist/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/plugins/modal-video/modal-video.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/popup-auth.css') }}">
    @stack('after-styles')
</head>

<body>
    @php
        $needsAuthPopup =auth()->guest() || (auth()->check() && method_exists(auth()->user(), 'hasVerifiedEmail') && !auth()->user()->hasVerifiedEmail());
    @endphp
    @if ($needsAuthPopup)
        <div class="blur-bg-overlay"></div>
        <div class="form-popup">
            <span class="close-btn material-symbols-rounded">close</span>

            <div class="form-box login">
                @include('frontend.auth.partials.login-popup')
            </div>

            <div class="form-box signup">
                @include('frontend.auth.partials.register-popup')
            </div>

            <div class="form-box reset">
                @include('frontend.auth.partials.password-email-popup')
            </div>

            <div class="form-box verify">
                @include('frontend.auth.partials.verify')
            </div>

            <div class="form-box two-factor">
                @include('frontend.auth.partials.two-factor-popup')
            </div>
        </div>
    @endif

    @include('frontend.website.header')

    @yield('content')

    @include('frontend.website.footer')

    <script src="{{ asset('frontend/plugins/jquery/jquery.js') }}"></script>
    <script src="{{ asset('frontend/plugins/magnific-popup/dist/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('frontend/plugins/slick-carousel/slick/slick.min.js') }}"></script>
    <script src="{{ asset('frontend/plugins/counterup/jquery.waypoints.min.js') }}"></script>
    <script src="{{ asset('frontend/plugins/counterup/jquery.counterup.min.js') }}"></script>
    <script src="{{ asset('frontend/plugins/shuffle/shuffle.min.js') }}"></script>
    <script src="{{ asset('frontend/plugins/aos/aos.js') }}"></script>
    <script src="{{ asset('frontend/plugins/animate-css/wow.min.js') }}"></script>
    <script src="{{ asset('frontend/plugins/modal-video/jquery-modal-video.min.js') }}"></script>
    <script src="{{ asset('frontend/plugins/google-map/map.js') }}"></script>
    @if($needsAuthPopup)<script src="{{ asset('frontend/js/popup-auth.js') }}"></script> @endif
    <script src="{{ asset('frontend/js/script.js') }}"></script>
    <script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>
    @stack('after-scripts')
</body>
</html>