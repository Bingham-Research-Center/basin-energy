@php
    // Shared action outcomes for the administrator and user dashboards.
    // Form validation remains inline where users can resolve input errors.
    $dashboardToasts = [];
    $flashTypes = [
        'flash_success' => 'success',
        'flash_warning' => 'warning',
        'flash_info' => 'info',
        'flash_message' => 'info',
        'flash_danger' => 'error',
        'status' => 'success',
        'broadcast_toast' => 'success',
    ];

    foreach ($flashTypes as $flashKey => $icon) {
        $message = session($flashKey);
        if (is_string($message) && trim($message) !== '') {
            $dashboardToasts[] = ['icon' => $icon, 'message' => $message];
        }
    }

    if (session('resent')) {
        $dashboardToasts[] = [
            'icon' => 'success',
            'message' => __('A fresh verification link has been sent to your email address.'),
        ];
    }
    if (session('verified')) {
        $dashboardToasts[] = [
            'icon' => 'success',
            'message' => __('Thank you for verifying your e-mail address.'),
        ];
    }

    // Avoid duplicates if the same flash is stored under different keys.
    $dashboardToasts = array_values(array_unique($dashboardToasts, SORT_REGULAR));
@endphp

@if(count($dashboardToasts))
    <script type="application/json" id="brc-dashboard-toast-data">@json($dashboardToasts)</script>
@endif

@if(isset($errors) && $errors->any())
    <x-utils.alert type="danger" class="header-message">
        @foreach($errors->all() as $error)
            {{ $error }}<br/>
        @endforeach
    </x-utils.alert>
@endif
