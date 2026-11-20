<div class="form-details">
    <h2>@lang('Reset Password')</h2>
    <p>@lang('Enter your email to receive a reset link.')</p>
</div>

<div class="form-content">
    <h2>@lang('Reset Password')</h2>

    <div class="password-reset-confirmation" id="password-reset-confirmation"
         role="status" aria-live="polite" hidden>
        <div class="password-reset-confirmation-icon" aria-hidden="true">✓</div>
        <h3>@lang('Check your email')</h3>
        <p data-password-reset-message>
            @lang('If an account exists with that email address, you will receive a password reset link shortly. Please check your inbox and spam folder.')
        </p>
    </div>

    <form method="POST" action="{{ route('frontend.auth.password.email') }}"
          data-password-reset-form>
        @csrf

        <div class="input-field">
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   autocomplete="email"
                   maxlength="255"
                   required>
            <label>@lang('E-mail Address')</label>
        </div>

        @error('email')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        <button type="submit" class="auth-submit-btn" data-loading-text="{{ __('Sending reset link...') }}">
            @lang('Send Reset Link')
        </button>
    </form>

    <div class="bottom-link">
        <a href="#" id="back-to-login">
            @lang('Back to Login')
        </a>
    </div>
</div>