@if(config('services.google.active')
    && filled(config('services.google.client_id'))
    && filled(config('services.google.client_secret'))
    && filled(config('services.google.redirect')))
    <div class="google-auth-option">
        <div class="google-auth-divider"><span>@lang('or continue with')</span></div>
        <a class="google-auth-button"
            href="{{ route('frontend.auth.social.login', 'google') }}"
            data-google-oauth-popup
            data-google-oauth-status="{{ route('frontend.auth.social.popup.status') }}">
            <i class="fab fa-google" aria-hidden="true"></i>
            <span>{{ ($context ?? 'login') === 'signup' ? __('Sign up with Google') : __('Continue with Google') }}</span>
        </a>
    </div>
@endif
