<?php

namespace Tests\Feature\Frontend;

use App\Domains\Auth\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleProfile;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    protected function enableGoogle(): void
    {
        config([
            'services.google.active' => true,
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/login/google/callback',
            'boilerplate.access.user.registration' => true,
        ]);
    }

    protected function googleProfile(string $id, string $email, bool $verified = true): GoogleProfile
    {
        return (new GoogleProfile())->setRaw([
            'sub' => $id,
            'email' => $email,
            'email_verified' => $verified,
            'name' => 'Google Visitor',
        ])->map([
            'id' => $id,
            'name' => 'Google Visitor',
            'email' => $email,
        ]);
    }

    protected function mockGoogleCallback(GoogleProfile $profile): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($profile);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }

    public function test_google_login_button_is_hidden_when_not_configured(): void
    {
        config(['services.google.active' => false]);
        $this->get('/login')->assertOk()->assertDontSee('Continue with Google');
        $this->get('/register')->assertOk()->assertDontSee('Sign up with Google');
    }

    public function test_google_login_and_signup_buttons_are_visible_in_both_interfaces(): void
    {
        $this->enableGoogle();

        $this->get('/login')
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee(route('frontend.auth.social.login', 'google'));

        $this->get('/register')
            ->assertOk()
            ->assertSee('Sign up with Google');

        // The shared frontend layout always includes the popup signup form;
        // the popup login form is loaded from the template when opened.
        $this->get('/')
            ->assertOk()
            ->assertSee('Sign up with Google');
    }

    public function test_disabled_google_oauth_returns_not_found(): void
    {
        config(['services.google.active' => false]);

        $this->get('/login/google')->assertNotFound();
        $this->get('/signup/google/complete')->assertNotFound();
        $this->get('/login/github')->assertNotFound();
    }

    public function test_verified_new_google_account_is_held_until_terms_are_accepted(): void
    {
        $this->enableGoogle();
        $this->mockGoogleCallback($this->googleProfile('google-new-123', 'new-google@example.test'));

        $this->get('/login/google/callback')
            ->assertRedirect(route('frontend.auth.social.signup'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'new-google@example.test']);

        $this->get('/signup/google/complete')
            ->assertOk()
            ->assertSee('new-google@example.test')
            ->assertSee('Terms & Conditions', false);

        $this->post('/signup/google/complete')->assertSessionHasErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'new-google@example.test']);

        $this->post('/signup/google/complete', ['terms' => '1'])
            ->assertRedirect(route(homeRoute()));

        $user = User::where('email', 'new-google@example.test')->firstOrFail();
        $this->assertSame(User::TYPE_USER, $user->type);
        $this->assertSame('google', $user->provider);
        $this->assertSame('google-new-123', $user->provider_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertSame(0, $user->passwordHistories()->count());
        $this->assertFalse($user->roles()->exists());
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_google_email_cannot_create_an_account(): void
    {
        $this->enableGoogle();
        $this->mockGoogleCallback($this->googleProfile('unverified-123', 'bad@example.test', false));

        $this->get('/login/google/callback')
            ->assertRedirect(route('frontend.auth.login'));
        $this->assertDatabaseMissing('users', ['email' => 'bad@example.test']);
        $this->assertGuest();
    }

    public function test_google_email_matching_existing_local_account_is_not_silently_linked(): void
    {
        $this->enableGoogle();
        $local = User::factory()->user()->create(['email' => 'used@example.test', 'provider' => null]);
        $this->mockGoogleCallback($this->googleProfile('different-google-id', 'used@example.test'));

        $this->get('/login/google/callback')
            ->assertRedirect(route('frontend.auth.login'));

        $this->assertGuest();
        $this->assertNull($local->fresh()->provider_id);
        $this->assertSame(1, User::where('email', 'used@example.test')->count());
    }

    public function test_returning_google_account_logs_in_by_provider_and_identifier(): void
    {
        $this->enableGoogle();
        $user = User::factory()->user()->create([
            'provider' => 'google',
            'provider_id' => 'provider-123',
            'email' => 'existing@example.test',
        ]);
        $this->mockGoogleCallback($this->googleProfile('provider-123', 'existing@example.test'));

        $this->get('/login/google/callback')
            ->assertRedirect(route(homeRoute()));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_google_account_cannot_login(): void
    {
        $this->enableGoogle();
        $user = User::factory()->inactive()->user()->create([
            'provider' => 'google',
            'provider_id' => 'inactive-google',
        ]);
        $this->mockGoogleCallback($this->googleProfile('inactive-google', $user->email));

        $this->get('/login/google/callback')
            ->assertRedirect(route('frontend.auth.login'));
        $this->assertGuest();
    }

    public function test_google_registration_respects_registration_switch(): void
    {
        $this->enableGoogle();
        config(['boilerplate.access.user.registration' => false]);
        $this->mockGoogleCallback($this->googleProfile('blocked-new', 'blocked@example.test'));
        $this->get('/login/google/callback')->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.test']);
    }

    public function test_google_signup_requires_a_valid_pending_session(): void
    {
        $this->enableGoogle();
        $this->post('/signup/google/complete', ['terms' => '1'])
            ->assertRedirect(route('frontend.auth.login'));
        $this->assertGuest();
    }

    public function test_google_popup_status_is_incomplete_without_a_popup_flow(): void
    {
        $this->enableGoogle();
        $this->get('/login/google/popup/status')
            ->assertOk()->assertJson(['complete' => false, 'redirect' => null]);
    }

    public function test_google_popup_status_requires_a_completed_authenticated_session(): void
    {
        $this->enableGoogle();
        $user = User::factory()->user()->create([
            'provider' => 'google',
            'provider_id' => 'popup-existing',
            'email' => 'popup-existing@example.test',
        ]);
        $this->withSession(['auth.google.popup_started_at' => time()])
            ->get('/login/google/popup/status')
            ->assertOk()->assertJson(['complete' => false]);

        $this->actingAs($user)->withSession([
            'auth.google.popup_started_at' => time(),
            'auth.2fa_passed' => true,
        ])->get('/login/google/popup/status')
            ->assertOk()->assertJson(['complete' => true, 'redirect' => route(homeRoute())]);
    }

    public function test_popup_callback_returns_completion_view_after_google_login(): void
    {
        $this->enableGoogle();
        $user = User::factory()->user()->create([
            'provider' => 'google',
            'provider_id' => 'popup-login-id',
            'email' => 'popup-login@example.test',
        ]);
        $this->mockGoogleCallback($this->googleProfile('popup-login-id', $user->email));

        $this->withSession(['auth.google.popup_started_at' => time()])
            ->get('/login/google/callback')
            ->assertOk()->assertSee('basinenergy.google.oauth.result');

        $this->assertAuthenticatedAs($user);
    }

    public function test_first_time_google_popup_signup_stays_in_popup_until_terms_are_accepted(): void
    {
        $this->enableGoogle();
        $this->mockGoogleCallback($this->googleProfile('popup-new-id', 'popup-new@example.test'));

        $this->withSession(['auth.google.popup_started_at' => time()])
            ->get('/login/google/callback')
            ->assertRedirect(route('frontend.auth.social.signup'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'popup-new@example.test']);

        $this->post('/signup/google/complete', ['terms' => '1'])
            ->assertOk()
            ->assertSee('basinenergy.google.oauth.result');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'provider' => 'google',
            'provider_id' => 'popup-new-id',
            'email' => 'popup-new@example.test',
        ]);
    }

    public function test_google_popup_status_rejects_expired_popup_marker(): void
    {
        $this->enableGoogle();
        $user = User::factory()->user()->create([
            'provider' => 'google',
            'provider_id' => 'stale-flow',
        ]);
        $this->actingAs($user)->withSession([
            'auth.google.popup_started_at' => time() - 3600,
            'auth.2fa_passed' => true,
        ])->get('/login/google/popup/status')
            ->assertOk()->assertJson(['complete' => false]);
    }

    public function test_popup_status_route_works_for_authenticated_users_and_requires_google_enabled(): void
    {
        $this->enableGoogle();
        $this->actingAs(User::factory()->user()->create())
            ->get('/login/google/popup/status')
            ->assertOk();

        config(['services.google.active' => false]);
        $this->get('/login/google/popup/status')->assertNotFound();
    }

}
