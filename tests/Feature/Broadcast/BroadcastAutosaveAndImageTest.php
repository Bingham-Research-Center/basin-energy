<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use App\Services\BroadcastDeliveryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BroadcastAutosaveAndImageTest extends TestCase
{
    public function test_newsletter_autosave_creates_one_unscheduled_draft_and_updates_it(): void
    {
        Mail::fake();
        $this->loginAsAdmin();
        $url = route('admin.broadcast.editor.autosave', ['kind' => 'newsletter']);
        $month = now('America/Denver')->addMonth()->format('Y-m');

        $first = $this->postJson($url, [
            'issue_month' => $month,
            'title' => 'An incomplete newsletter',
            'body' => '<p>Initial note</p>',
        ])->assertOk()->assertJsonPath('status', 'draft');

        $id = $first->json('id');
        $this->postJson($url, [
            'id' => $id, 'issue_month' => $month,
            'title' => 'Revised newsletter',
            'body' => '<p>More information</p><script>alert(1)</script>',
        ])->assertOk()->assertJsonPath('id', $id);

        $this->assertSame(1, BroadcastNewsletter::count());
        $draft = BroadcastNewsletter::findOrFail($id);
        $this->assertSame(BroadcastNewsletter::DRAFT, $draft->status);
        $this->assertNull($draft->scheduled_at);
        $this->assertSame('Revised newsletter', $draft->title);
        $this->assertStringNotContainsString('<script', $draft->body);

        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();
    }

    public function test_newsletter_autosave_requires_month_and_cannot_change_scheduled_content(): void
    {
        $this->loginAsAdmin();
        $url = route('admin.broadcast.editor.autosave', ['kind' => 'newsletter']);
        $this->postJson($url, ['body' => '<p>Missing month</p>'])
            ->assertUnprocessable()->assertJsonValidationErrors('issue_month');

        $month = now('America/Denver')->addMonths(2)->format('Y-m');
        $this->post(route('admin.broadcast.newsletters.store'), [
            'issue_month' => $month,
            'title' => 'Approved issue',
            'body' => '<p>Approved body</p>',
        ])->assertRedirect();

        $issue = BroadcastNewsletter::firstOrFail();
        $this->post(route('admin.broadcast.newsletters.schedule', $issue))->assertRedirect();

        $this->postJson($url, [
            'id' => $issue->id,
            'issue_month' => $month,
            'title' => 'Silently replaced!',
            'body' => '<p>Bad update</p>',
        ])->assertStatus(409);

        $this->assertSame(BroadcastNewsletter::SCHEDULED, $issue->fresh()->status);
        $this->assertSame('Approved issue', $issue->fresh()->title);
    }

    public function test_announcement_autosave_never_publishes_or_creates_deliveries(): void
    {
        Mail::fake();
        $this->loginAsAdmin();
        $url = route('admin.broadcast.editor.autosave', ['kind' => 'announcement']);

        $first = $this->postJson($url, [
            'title' => 'Draft',
            'body' => '<p>Work in progress</p>',
            'audience' => 'all_users',
        ])->assertOk()->assertJsonPath('status', 'draft');
        $id = $first->json('id');

        $this->postJson($url, [
            'id' => $id,
            'title' => 'Updated draft',
            'body' => '<p>Still preparing</p>',
            'audience' => 'all_users',
        ])->assertOk()->assertJsonPath('id', $id);

        $this->assertSame(1, BroadcastAnnouncement::count());
        $draft = BroadcastAnnouncement::findOrFail($id);
        $this->assertSame(BroadcastAnnouncement::DRAFT, $draft->status);
        $this->assertSame(0, $draft->deliveries()->count());

        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();
    }

    public function test_admin_image_upload_accepts_only_raster_images_and_public_read_works(): void
    {
        Storage::fake('local');
        $this->loginAsAdmin();

        $response = $this->post(route('admin.broadcast.editor.images'), [
            'image' => UploadedFile::fake()->image('announcement.png', 240, 120),
        ], ['Accept' => 'application/json'])->assertCreated();

        $imageUrl = $response->json('url');
        $this->assertStringContainsString('/broadcast/images/', $imageUrl);
        $this->assertStringEndsWith('.png', $imageUrl);

        // Email clients must be able to request images without logging in.
        auth()->logout();
        $this->get($imageUrl)->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get('/broadcast/images/../../.env')->assertNotFound();
    }

    public function test_image_upload_denies_untrusted_files_oversized_and_non_admins(): void
    {
        Storage::fake('local');
        $this->loginAsAdmin();

        $this->postJson(route('admin.broadcast.editor.images'), [
            'image' => UploadedFile::fake()->create('dangerous.svg', 2, 'image/svg+xml'),
        ])->assertUnprocessable();

        $this->postJson(route('admin.broadcast.editor.images'), [
            'image' => UploadedFile::fake()->image('large.jpg')->size(4000),
        ])->assertUnprocessable();

        $this->actingAs(User::factory()->user()->create());
        $this->postJson(route('admin.broadcast.editor.images'), [
            'image' => UploadedFile::fake()->image('sample.jpg'),
        ])->assertRedirect(route('frontend.index'));
        $this->postJson(route('admin.broadcast.editor.autosave', ['kind' => 'announcement']), [
            'audience' => 'all_users', 'body' => 'Denied',
        ])->assertRedirect(route('frontend.index'));
    }

    public function test_editor_popups_cannot_be_dismissed_by_clicking_backdrop(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()->assertSee('data-backdrop="static"', false)
            ->assertSee('data-autosave-url=', false)
            ->assertSee('data-image-upload-url=', false);

        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()->assertSee('data-backdrop="static"', false)
            ->assertSee('data-autosave-url=', false)
            ->assertSee('data-image-upload-url=', false);
    }
}
