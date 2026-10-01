<div class="form-details">
    <h2>@lang('Two-Factor Verification')</h2>
    <p>@lang('Enter the 6-digit code from your authenticator app to continue.')</p>
</div>

<div class="form-content">
    <h2>@lang('Verify Code')</h2>

    <form method="POST" action="{{ route('frontend.auth.2fa.challenge.store') }}" id="two-factor-form">
        @csrf

        <div class="input-field">
            <input type="text"
                   name="code"
                   maxlength="6"
                   required
                   autofocus
                   inputmode="numeric"
                   pattern="[0-9]*">
            <label>@lang('Authentication Code')</label>
        </div>

        @error('code')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        <div style="margin-top:10px; margin-bottom:10px;">
            <label>
                <input type="checkbox" name="remember_device" value="1">
                @lang('Remember this device')
            </label>
        </div>

        <button type="submit" class="auth-submit-btn" data-loading-text="Verifying...">
            @lang('Verify')
        </button>

        <div class="bottom-link">
            <a href="#" id="back-to-login-from-2fa">@lang('Back to Login')</a>
        </div>
    </form>
</div>