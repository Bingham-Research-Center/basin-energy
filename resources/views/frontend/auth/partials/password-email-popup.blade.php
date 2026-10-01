<div class="form-details">
    <h2>@lang('Reset Password')</h2>
    <p>@lang('Enter your email to receive reset link.')</p>
</div>

<div class="form-content">
    <h2>@lang('Reset Password')</h2>

    <form method="POST" action="{{ route('frontend.auth.password.email') }}">
        @csrf

        <div class="input-field">
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required>
            <label>@lang('E-mail Address')</label>
        </div>

        @error('email')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        <!-- <button type="submit">
            @lang('Send Reset Link')
        </button> -->
        <!-- <button type="submit" class="auth-submit-btn" data-loading-text="Sending reset link...">Send Reset Link</button> -->
        <button type="submit" class="auth-submit-btn" data-loading-text="Sending reset link...">Send Reset Link</button>
    </form>

    <div class="bottom-link">
        <a href="#" id="back-to-login">
            @lang('Back to Login')
        </a>
    </div>
</div>