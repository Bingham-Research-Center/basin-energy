<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\User;
use App\Models\ContactMessage;
use App\Models\NewsletterAdminAlert;
use Tests\TestCase;

class AdminNotificationCenterTest extends TestCase
{
    public function test_admin_header_shows_the_new_notification_panel_and_assets(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/dashboard')->assertOk()
            ->assertSee('brcNotificationPanel')
            ->assertSee('brcNotificationMarkRead')
            ->assertSee('brcNotificationLoadMore')
            ->assertSee('Mark all as read')
            ->assertSee('dashboard-notifications.js?v=2')
            ->assertSee('dashboard-notifications.css?v=1');
    }

    public function test_combined_feed_paginates_contact_messages_and_subscription_alerts(): void
    {
        $admin = $this->loginAsAdmin();
        $subscriber = User::factory()->user()->create();

        for ($i = 0; $i < 4; $i++) {
            ContactMessage::create([
                'name' => 'Contact '.$i,
                'email' => 'sender'.$i.'@example.test',
                'message' => 'Contact text '.$i,
                'is_read' => false,
            ]);
        }

        for ($i = 0; $i < 4; $i++) {
            NewsletterAdminAlert::create([
                'admin_id' => $admin->id,
                'subscriber_id' => $subscriber->id,
            ]);
        }

        $first = $this->getJson(route('admin.broadcast.alerts').'?page=1')
            ->assertOk()
            ->assertJsonPath('count', 8)
            ->assertJsonPath('contact_count', 4)
            ->assertJsonPath('newsletter_count', 4)
            ->assertJsonPath('has_more', true)
            ->assertJsonCount(5, 'items');

        $this->assertContains('contact', array_column($first->json('items'), 'type'));
        $this->assertContains('subscription', array_column($first->json('items'), 'type'));

        $second = $this->getJson(route('admin.broadcast.alerts').'?page=2')
            ->assertOk()
            ->assertJsonPath('has_more', false)
            ->assertJsonCount(3, 'items');

        $firstIds = array_map(fn ($item) => $item['type'].':'.$item['id'], $first->json('items'));
        $secondIds = array_map(fn ($item) => $item['type'].':'.$item['id'], $second->json('items'));
        $this->assertSame([], array_intersect($firstIds, $secondIds));
    }

    public function test_mark_all_as_read_returns_json_and_scopes_subscription_alerts_to_current_admin(): void
    {
        $admin = $this->getMasterAdmin();
        $otherAdmin = User::factory()->admin()->create();
        $subscriber = User::factory()->user()->create();
        ContactMessage::create([
            'name' => 'Reporter',
            'email' => 'reporter@example.test',
            'message' => 'Sample monitoring question.',
            'is_read' => false,
        ]);
        $mine = NewsletterAdminAlert::create([
            'admin_id' => $admin->id,
            'subscriber_id' => $subscriber->id,
        ]);
        $other = NewsletterAdminAlert::create([
            'admin_id' => $otherAdmin->id,
            'subscriber_id' => $subscriber->id,
        ]);

        $this->actingAs($admin);
        $this->postJson(route('admin.broadcast.alerts.read'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('marked', 2)
            ->assertJsonPath('count', 0);

        $this->assertNotNull($mine->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
        $this->assertSame(0, ContactMessage::where('is_read', false)->count());

        $this->getJson(route('admin.broadcast.alerts'))
            ->assertJsonPath('count', 0)
            ->assertJsonCount(2, 'items');
    }

    public function test_regular_user_cannot_read_or_modify_admin_notifications(): void
    {
        $this->actingAs(User::factory()->user()->create());
        $this->getJson(route('admin.broadcast.alerts'))
            ->assertRedirect(route('frontend.index'));
        $this->postJson(route('admin.broadcast.alerts.read'))
            ->assertRedirect(route('frontend.index'));
    }

    public function test_html_in_contact_message_is_not_rendered_into_admin_header(): void
    {
        $this->loginAsAdmin();
        ContactMessage::create([
            'name' => '<script>malicious</script>',
            'email' => 'sender@example.test',
            'message' => '<img src=x onerror=alert(1)>',
            'is_read' => false,
        ]);

        // Contact text is loaded as JSON and inserted via textContent.
        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('<script>malicious</script>', false);

        $feed = $this->getJson(route('admin.broadcast.alerts'))
            ->assertOk()->assertJsonPath('items.0.type', 'contact');
        $this->assertSame('<script>malicious</script> sent a message',
            $feed->json('items.0.title'));

        $script = file_get_contents(public_path('js/dashboard-notifications.js'));
        $this->assertStringContainsString('title.textContent = item.title;', $script);
        $this->assertStringContainsString('description.textContent = item.body', $script);
        $this->assertStringContainsString("position: 'bottom-end'", $script);
        $this->assertStringContainsString('timer: 3000', $script);
    }

    public function test_legacy_post_without_json_still_redirects_with_flash(): void
    {
        $this->loginAsAdmin();
        $this->post(route('admin.broadcast.alerts.read'))
            ->assertRedirect(route('admin.broadcast.newsletters.index'));
    }
}
