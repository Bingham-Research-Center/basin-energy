<div class="form-details">
    <h2>Verify Email</h2>
    <p>Please check your email for a verification link.</p>
</div>

<div class="form-content">
    <h2>Verify Your Email</h2>

    <p style="margin-bottom:20px;">
        Before proceeding, please check your email for a verification link.
    </p>

    {{-- <input type="hidden" id="verify-email" value=""> --}}
    <input type="hidden" id="verify-email" value="{{ route('frontend.auth.verification.resend') }}">
    {{-- <input type="hidden" id="resend-url" value="{{ route('frontend.auth.verification.resend') }}"> --}}
    <button id="resend-verification" type="button" class="auth-submit-btn">
        Resend Verification Email
    </button>

    <div id="verify-message" style="margin-top:10px;"></div>

    <div class="bottom-link">
        <a href="#" id="back-to-login-from-verify">Back to Login</a>
    </div>
</div>