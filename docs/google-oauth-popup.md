## Optional popup-window Google authentication

The existing Google login and signup buttons now open Google consent in a
separate browser window. The original page remains open and shows a small,
accessible waiting panel. This is **not** a Google iframe: Google does not
permit embedding its account selection page.

The popup shares BasinEnergy's normal session cookie and OAuth state,
goes through optional first-time Terms confirmation and existing 2FA
verification, then displays a same-origin completion page that attempts
to close itself. The opener polls Laravel's session-confirmation endpoint
and navigates to the authenticated home page **only when the server confirms
success**. A `postMessage` notification is a hint, not an authentication
credential. The opener validates the message's origin and source.

If the browser blocks the popup, OAuth falls back to the original full-page
redirect. If the user closes the popup or 15 minutes pass, the waiting panel
ends and the visitor may retry. No new Google Cloud settings, billing,
OAuth scopes, redirect URIs, migrations, or secrets are required.

### QA

Verify:
- Login/signup buttons in the popup and standalone UI.
- Returning Google user, first-time signup + Terms, and user with 2FA.
- Closing the Google window without approval or denying consent.
- Popup blocked (full-page fallback), and multiple rapid button clicks.
- The original page does not redirect before a successful server check.
- The Google window closes and the original tab reaches the account page.
