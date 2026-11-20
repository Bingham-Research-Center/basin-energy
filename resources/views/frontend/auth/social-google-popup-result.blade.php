<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $success ? __('Google Sign-In Complete') : __('Google Sign-In Unavailable') }} — BasinEnergy</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; font: 16px/1.55 system-ui, sans-serif; background: #f6f8fa; color: #0f2439; }
        main { max-width: 420px; text-align: center; padding: 32px; border-radius: 16px; background: #fff; box-shadow: 0 12px 36px rgba(0,0,0,.08); }
        h1 { margin: 0 0 12px; font-size: 1.45rem; }
        a { color: #0c587f; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <h1>{{ $success ? __('Sign-In Complete') : __('Google Sign-In Unavailable') }}</h1>
    <p>{{ $message }}</p>
    <p id="window-hint">@lang('You may close this window and return to BasinEnergy.')</p>
    <p><a href="{{ $success ? $redirectUrl : route('frontend.auth.login') }}">
        @lang('Continue to BasinEnergy')
    </a></p>
</main>
<script>
(function () {
    var result = {
        type: 'basinenergy.google.oauth.result',
        success: @json($success),
        message: @json($message)
    };
    var notified = false;
    var channel = null;
    try {
        if (window.opener && !window.opener.closed) {
            window.opener.postMessage(result, window.location.origin);
            notified = true;
        }
    } catch (e) {
        // COOP or privacy settings may sever window.opener.
    }

    // Same-origin fallback when Google's Cross-Origin-Opener-Policy detaches
    // the original window. The opener still checks Laravel's auth session.
    if (typeof window.BroadcastChannel === 'function') {
        try {
            channel = new window.BroadcastChannel('basinenergy-google-oauth');
            channel.postMessage(result);
            notified = true;
        } catch (e) {
            channel = null;
        }
    }

    // Keep the "Continue to BasinEnergy" link usable when cross-window
    // notification is unavailable instead of silently closing this page.
    if (notified) {
        window.setTimeout(function () {
            if (channel) channel.close();
            window.close();
        }, 1200);
    } else {
        var hint = document.getElementById('window-hint');
        if (hint) hint.textContent = 'Return to BasinEnergy using the link below.';
    }
}());
</script>
</body>
</html>
