<div class="form-details">
    <h2>@lang('Welcome Back')</h2>
    <p>@lang('Please log in to continue.')</p>
</div>

<div class="form-content">
    <h2>@lang('Login')</h2>

    <form method="POST" action="{{ route('frontend.auth.login') }}">
        @csrf

        <div class="input-field">
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   autocomplete="email">
            <label>@lang('E-mail Address')</label>
        </div>

        @error('email')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        <div class="input-field">
            <input type="password"
                   name="password"
                   required
                   autocomplete="current-password">
            <label>@lang('Password')</label>
        </div>

        @error('password')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        @if(config('boilerplate.access.captcha.login'))
        <div style="margin-top:10px; margin-bottom:10px;">
            <div class="g-recaptcha" id="recaptcha-login" data-sitekey="{{ env('INVISIBLE_RECAPTCHA_SITEKEY') }}"></div>
            <input type="hidden" name="captcha_status" value="true" />
        </div>
        @endif
        <div style="margin-top:10px; margin-bottom:10px;">
            <label>
                <input type="checkbox"
                       name="remember"
                       {{ old('remember') ? 'checked' : '' }}>
                @lang('Remember Me')
            </label>
        </div>

        <!-- <button type="submit">@lang('Login')</button> -->
        <!-- <button type="submit" class="auth-submit-btn" data-loading-text="Logging in..."> Login </button> -->
        <button type="submit"class="auth-submit-btn" data-loading-text="Logging in...">Login</button>

        <div style="margin-top:10px;">
            <a href="#" class="forgot-pass-link">
                @lang('Forgot Your Password?')
            </a>
        </div>

        <div class="text-center mt-3">
            @include('frontend.auth.includes.social')
        </div>
    </form>

    <div class="bottom-link">
        @lang("Don't have an account?")
        <a href="#" id="signup-link">@lang('Signup')</a>
    </div>
</div>