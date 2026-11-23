## Google OAuth popup: completion-driven session checks

Google sign-in and sign-up both use the same stateful Socialite redirect.
A first-time user must accept the Terms and an existing account with
two-factor authentication must complete the challenge before signing in.

### Why callback polling was changed

The previous opener polled `/login/google/popup/status` every 1.2
seconds **throughout Google consent**. Laravel 10 allows concurrent
requests using the same session by default. That can overwrite Socialite's
OAuth `state`, pending registration or a regenerated authenticated
session during the callback, leaving the visitor at the login screen
despite authorizing Google.

The opener now makes **no session-status HTTP request during
Google consent, Terms acceptance or a pending two-factor challenge**.

When the server has finished authenticating, the same-origin popup result
sends a success hint with `window.opener.postMessage` and, when supported,
a same-origin `BroadcastChannel`. The second channel covers browsers
where Google's Cross-Origin-Opener-Policy removes access to
`window.opener`.

**A hint never authenticates the visitor.** It only starts a short
confirmation retry window. The opener checks the existing Laravel
session endpoint, validates the returned URL's origin and navigates
to that URL only after `complete: true`. A short delay after the
completion hint allows the callback's session cookie to settle.
If confirmation fails, the user gets an explicit error instead of
being redirected blindly.

If cross-window signaling is unavailable, the final popup result page
keeps a **Continue to BasinEnergy** link; it will not silently close
without an available notification channel. The visitor may use the
waiting panel's **Cancel** control if they closed Google's window
manually. A 15-minute timeout protects against indefinite waiting.

### Full-page and error flows

If the popup is blocked, the browser falls back to a standard stateful
OAuth navigation in the current tab. This flow uses the normal Laravel
redirect for the authenticated account's type (regular User dashboard
or Admin dashboard).

A first-time Google account is held in a temporary session for up to
10 minutes; it is created only after accepting the Terms. A Google
subject ID is matched to the corresponding `provider='google'`
account; an existing password account with the same email is not
linked silently. Unverified addresses, deactivated accounts and
cancelled consent are rejected.

The standalone login card now shows the flash error from a failed
Google callback. OAuth error details are not displayed to users.

Passwordless Google accounts are exempt from **local password
expiration**. Existing password-backed accounts, including those
with a linked social identity and local password, retain the
configured expiration rules. This prevents a valid Google-only login
from being sent to a local password-reset requirement after 180 days.

### QA and operations checklist

- Test existing Google user → `/dashboard`; admin → `/admin/dashboard`.
- Test new Google visitor → Terms confirmation → User dashboard.
- Test Google user with 2FA → challenge, then dashboard after correct code.
- Test deactivated user and local email conflict (no silent linking).
- Test consent denial and OAuth state failure: show helpful error in
  the standalone login card or popup result.
- Test popup blocked, popup open, and browser
  Cross-Origin-Opener-Policy behavior.
- Test a long Google consent flow: **no polling requests** to
  `/login/google/popup/status` before the completion signal.
- Confirm the callback URI configured in Google Cloud matches
  `https://basinenergy.info/login/google/callback` exactly.
- Check production `APP_URL`, `SESSION_DOMAIN`,
  `SESSION_SECURE_COOKIE`, same-site policy, `GOOGLE_REDIRECT`,
  `TrustProxies` and application logs when redirects differ across
  hosts or HTTPS/HTTP.

A successful GitHub Actions run validates the controller routes and
the Node browser simulation; it cannot exercise a real Google consent
screen or production session-cookie settings. Perform the browser
checks after deployment.

No `stateless()` bypass, Google Cloud setting change, database migration
or new dependency is required.

For context: https://laravel.com/docs/10.x/session#session-blocking
