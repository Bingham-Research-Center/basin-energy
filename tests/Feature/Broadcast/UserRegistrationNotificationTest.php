<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\UserService;
use App\Events\UserRegistrationAlertCreated;
use App\Models\UserRegistrationAdminAlert;
use App\Services\AdminUserRegistrationNotifier;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleProfile;
use Mockery;
use Tests\TestCase;

class UserRegistrationNotificationTest extends TestCase
{
    private function registerByEmail(string $name = 'New Researcher'): User
    {
        Notification::fake();

        $this->post('/register', [
            'name' => $name,
            'email' => 'new-researcher@example.test',
            'password' => 'OC4Nzu270N!QBVi%U%qX',
            'password_confirmation' => 'OC4Nzu270N!QBVi%U%qX',
            'terms' => '1',
        ])->assertRedirect(route(homeRoute()));

        return User::where('email', 'new-researcher@example.test')->firstOrFail();
    }

    public function test_email_registration_creates_one_private_alert_and_admin_bell_item(): void
    {
        $admin = $this->getMasterAdmin();
        $this->assertTrue($admin->can('admin.access.user.list'));

        $newUser = $this->registerByEmail();

        $this->assertDatabaseHas('user_registration_admin_alerts', [
            'admin_id' => $admin->id,
            'user_id' => $newUser->id,
            'read_at' => null,
        ]);

        $this->actingAs($admin);
        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('1 unread notifications');

        $this->getJson(route('admin.broadcast.alerts'))
            ->assertOk()
            ->assertJsonPath('registration_count', 1)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.type', 'registration')
            ->assertJsonPath('items.0.title', 'New Researcher registered')
            ->assertJsonPath('items.0.body', 'New account registered with email.')
            ->assertJsonPath('items.0.url', route('admin.auth.user.show', [
                'user' => $newUser->id,
            ]));
    }

    public function test_registration_creates_independent_alerts_only_for_active_authorized_admins(): void
    {
        $master = $this->getMasterAdmin();
        $other = User::factory()->admin()->create();
        $other->assignRole($this->getAdminRole());
        $noUserPermission = User::factory()->admin()->create();
        $inactive = User::factory()->inactive()->admin()->create();
        $inactive->assignRole($this->getAdminRole());

        $newUser = $this->registerByEmail();

        $this->assertDatabaseHas('user_registration_admin_alerts', [
            'admin_id' => $master->id, 'user_id' => $newUser->id,
        ]);
        $this->assertDatabaseHas('user_registration_admin_alerts', [
            'admin_id' => $other->id, 'user_id' => $newUser->id,
        ]);
        $this->assertDatabaseMissing('user_registration_admin_alerts', [
            'admin_id' => $noUserPermission->id, 'user_id' => $newUser->id,
        ]);
        $this->assertDatabaseMissing('user_registration_admin_alerts', [
            'admin_id' => $inactive->id, 'user_id' => $newUser->id,
        ]);

        // Replays are idempotent, even after an admin has read the alert.
        app(AdminUserRegistrationNotifier::class)->notify($newUser);
        $this->assertSame(2, UserRegistrationAdminAlert::where('user_id', $newUser->id)->count());

        $this->actingAs($master);
        $this->postJson(route('admin.broadcast.alerts.read'))
            ->assertOk()->assertJsonPath('marked', 1);

        $this->assertNotNull(UserRegistrationAdminAlert::where('admin_id', $master->id)
            ->where('user_id', $newUser->id)->firstOrFail()->read_at);
        $this->assertNull(UserRegistrationAdminAlert::where('admin_id', $other->id)
            ->where('user_id', $newUser->id)->firstOrFail()->read_at);

        $this->actingAs($other);
        $this->getJson(route('admin.broadcast.alerts'))
            ->assertJsonPath('registration_count', 1)
            ->assertJsonPath('count', 1);

        $this->actingAs($noUserPermission);
        $this->getJson(route('admin.broadcast.alerts'))
            ->assertJsonPath('registration_count', 0)
            ->assertJsonPath('count', 0)
            ->assertJsonCount(0, 'items');
    }

    public function test_invalid_registration_and_manual_admin_creation_do_not_notify(): void
    {
        $this->post('/register', [
            'name' => 'Without Terms',
            'email' => 'invalid-signup@example.test',
            'password' => 'OC4Nzu270N!QBVi%U%qX',
            'password_confirmation' => 'OC4Nzu270N!QBVi%U%qX',
        ])->assertSessionHasErrors('terms');

        $admin = $this->getMasterAdmin();
        $this->actingAs($admin);
        app(UserService::class)->store([
            'type' => User::TYPE_USER,
            'name' => 'Admin Created Account',
            'email' => 'manual-account@example.test',
            'password' => 'OC4Nzu270N!QBVi%U%qX',
            'roles' => [],
            'permissions' => [],
            'active' => '1',
        ]);

        $this->assertSame(0, UserRegistrationAdminAlert::query()->count());
    }

    public function test_google_registration_notifies_only_after_terms_and_never_on_existing_login(): void
    {
        config([
            'services.google.active' => true,
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/login/google/callback',
            'boilerplate.access.user.registration' => true,
        ]);

        $profile = (new GoogleProfile())->setRaw([
            'sub' => 'google-new-registration',
            'email' => 'google-new-registration@example.test',
            'email_verified' => true,
            'name' => 'Google Researcher',
        ])->map([
            'id' => 'google-new-registration',
            'name' => 'Google Researcher',
            'email' => 'google-new-registration@example.test',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($profile);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);

        $this->get('/login/google/callback')
            ->assertRedirect(route('frontend.auth.social.signup'));
        $this->assertSame(0, UserRegistrationAdminAlert::query()->count());

        $this->post('/signup/google/complete')->assertSessionHasErrors('terms');
        $this->assertSame(0, UserRegistrationAdminAlert::query()->count());

        $this->post('/signup/google/complete', ['terms' => '1'])
            ->assertRedirect(route(homeRoute()));

        $user = User::where('email', 'google-new-registration@example.test')->firstOrFail();
        $this->assertDatabaseHas('user_registration_admin_alerts', [
            'admin_id' => $this->getMasterAdmin()->id,
            'user_id' => $user->id,
        ]);

        // UserService returns an existing matching Google identity without
        // firing another registration alert.
        $sameUser = app(UserService::class)->registerProvider((object) [
            'id' => 'google-new-registration',
            'name' => 'Google Researcher',
            'email' => 'google-new-registration@example.test',
        ], 'google');
        $this->assertSame($user->id, $sameUser->id);
        $this->assertSame(1, UserRegistrationAdminAlert::where('user_id', $user->id)->count());

        $this->actingAs($this->getMasterAdmin());
        $this->getJson(route('admin.broadcast.alerts'))
            ->assertJsonPath('items.0.type', 'registration')
            ->assertJsonPath('items.0.body', 'New account registered with Google.');
    }

    public function test_registered_user_cannot_access_admin_alerts_and_broadcast_has_no_personal_data(): void
    {
        $user = $this->registerByEmail();
        $this->actingAs($user);

        $this->getJson(route('admin.broadcast.alerts'))
            ->assertRedirect(route('frontend.index'));

        $event = new UserRegistrationAlertCreated([$this->getMasterAdmin()->id]);
        $this->assertSame(['refresh' => true], $event->broadcastWith());
        $this->assertSame('user.registration.created', $event->broadcastAs());

        $js = file_get_contents(public_path('js/dashboard-notifications.js'));
        $this->assertStringContainsString("window.Echo.private('admin.registrations.' + adminId)", $js);
        $this->assertStringContainsString("title.textContent = item.title", $js);
    }
}
