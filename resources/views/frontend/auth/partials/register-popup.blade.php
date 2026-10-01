<div class="form-details">
    <h2>@lang('Create Account')</h2>
    <p>@lang('Join our community.')</p>
</div>

<div class="form-content">
    <h2>@lang('Register')</h2>

    <form method="POST" action="{{ route('frontend.auth.register') }}">
        @csrf

        <div class="input-field">
            <input type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   autofocus
                   autocomplete="name">
            <label>@lang('Name')</label>
        </div>

        @error('name')
            <small class="text-danger">{{ $message }}</small>
        @enderror

        <div class="input-field">
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="email">
            <label>@lang('E-mail Address')</label>
        </div>

        <div class="input-field">
            <input type="password"
                   name="password"
                   required
                   autocomplete="new-password">
            <label>@lang('Password')</label>
        </div>

        <div class="input-field">
            <input type="password"
                   name="password_confirmation"
                   required
                   autocomplete="new-password">
            <label>@lang('Password Confirmation')</label>
        </div>

        <div style="margin-top:10px;">
            <label>
                <input type="checkbox"
                       name="terms"
                       value="1"
                       required>
                @lang('I agree to the')
                <a href="{{ route('frontend.pages.terms') }}" target="_blank">
                    @lang('Terms & Conditions')
                </a>
            </label>
        </div>

        @if(config('boilerplate.access.captcha.registration'))
            <div style="margin-top:10px;">
                <div class="g-recaptcha" id="recaptcha-register" data-sitekey="{{ env('INVISIBLE_RECAPTCHA_SITEKEY') }}"></div>
            <input type="hidden" name="captcha_status" value="true" />
        </div>
        @endif
        </br>
        <!-- <button type="submit">@lang('Register')</button> -->
        <!-- <button type="submit" class="auth-submit-btn" data-loading-text="Creating account..."> Register </button> -->
         <button type="submit"class="auth-submit-btn" data-loading-text="Creating account...">Register</button>
    </form>

    <div class="bottom-link">
        @lang('Already have an account?')
        <a href="#" id="login-link">@lang('Login')</a>
    </div>
</div>