# Google sign-in and sign-up — BasinEnergy

## Cost

Standard Google OAuth authentication using only `openid`, `email` and
`profile` does not require paid Google Cloud APIs or a billing account.
If Google asks for billing for another product, do not enable it.

## Google Cloud setup

1. Go to https://console.cloud.google.com/ and create or select a project.
2. Open **Google Auth Platform**; set application name `BasinEnergy`,
   audience **External**, a support email and a developer contact email.
3. Configure your authorized domain `basinenergy.info`, public homepage
   `https://basinenergy.info` and Terms page
   `https://basinenergy.info/terms`. Set a publicly accessible privacy
   policy before production branding verification.
4. Request only the identity scopes `openid`, `email`, `profile`.
5. Under **Clients**, create an OAuth Client ID of type **Web application**.
   Set authorized origin to `https://basinenergy.info` and the **exact**
   redirect URI to `https://basinenergy.info/login/google/callback`.
6. While Google OAuth is in **Testing**, add desired test users. Follow
   Google Auth Platform instructions to publish the application for public use.
7. Place credentials in the **server-side** `.env` only:

```dotenv
APP_URL=https://basinenergy.info
GOOGLE_ACTIVE=true
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-private-secret
GOOGLE_REDIRECT="${APP_URL}/login/google/callback"
```

Never commit client secrets. After editing deployment config:

```bash
docker exec -it brc_site php artisan config:clear
docker exec -it brc_site php artisan config:cache
```

No new composer package is needed: `laravel/socialite` already exists.

## Authentication behavior

- Google buttons appear in the popup login, popup signup, standalone login,
  and standalone signup forms when `GOOGLE_ACTIVE=true` and credentials
  and redirect are configured.
- Both buttons start the same stateful Google OAuth flow. Existing Google
  identities are signed in; first-time users are prompted to accept
  BasinEnergy's Terms before their account is created.
- Only Google-verified email addresses are accepted. Accounts are matched
  using the **provider name + Google subject ID**.
- If an email already belongs to a local or another-provider account, the
  OAuth login is rejected instead of silently linking identities. The user
  can continue logging in through their existing method.
- New Google accounts have `type=user`, a verified email, no password,
  and no automatically assigned User roles/permissions.
- Two-factor-enabled existing users must complete their existing challenge
  before authentication is finalized.
- New registrations honor `ENABLE_REGISTRATION`. The pending signup session
  expires after 10 minutes.
- GitHub and LinkedIn aren't enabled in this PR. Their future integration
  should use provider-specific account linking rather than treating provider
  identifiers as globally unique.

## Release checklist

Before merging, run PHPUnit and check its output. In staging, test Google
callbacks for (a) a brand-new email, (b) an existing Google user,
(c) a deactivated account, (d) an existing local-email account and
(e) a user with 2FA. Verify the Google consent screen, both popup and
standalone links, HTTP/HTTPS callback redirects, and a valid privacy policy.

No migration is required: the existing `provider` and `provider_id`
columns are used. A separate future schema for multiple linked providers
may be added when GitHub and LinkedIn are implemented.
