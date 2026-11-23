<?php

namespace Tests\Feature\Broadcast;

use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BroadcastDraftDiscardTest extends TestCase
{
    public function test_admin_can_discard_announcement_draft_using_json(): void
    {
        $this->loginAsAdmin();
        $this->postJson(route('admin.broadcast.editor.autosave', ['kind' => 'announcement']), [
            'title' => 'Unsaved planning',
            'body' => '<p>In progress</p>',
            'audience' => 'all_users',
        ])->assertOk();
        $draft = BroadcastAnnouncement::firstOrFail();

        $this->getJson(route('admin.broadcast.announcements.edit', $draft))
            ->assertJsonPath('delete_url', route('admin.broadcast.announcements.delete', $draft));

        $this->deleteJson(route('admin.broadcast.announcements.delete', $draft))
            ->assertOk()->assertJsonPath('deleted', true);
        $this->assertDatabaseMissing('broadcast_announcements', ['id' => $draft->id]);

        // Even a stale tab cannot resurrect this deleted ID by autosaving it.
        $this->postJson(route('admin.broadcast.editor.autosave', ['kind' => 'announcement']), [
            'id' => $draft->id, 'title' => 'Should not return', 'audience' => 'all_users',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('broadcast_announcements', 0);
    }

    public function test_admin_can_discard_newsletter_draft_but_cannot_discard_sent_issue(): void
    {
        $this->loginAsAdmin();
        $month = now('America/Denver')->addMonths(3)->format('Y-m');
        $issue = BroadcastNewsletter::create([
            'title' => 'Monthly planning', 'body' => 'First notes', 'issue_month' => $month.'-01',
            'status' => BroadcastNewsletter::DRAFT, 'created_by' => auth()->id(),
        ]);
        $this->getJson(route('admin.broadcast.newsletters.edit', $issue))
            ->assertJsonPath('delete_url', route('admin.broadcast.newsletters.delete', $issue));

        $this->deleteJson(route('admin.broadcast.newsletters.delete', $issue))
            ->assertOk()->assertJsonPath('id', $issue->id);
        $this->assertDatabaseMissing('broadcast_newsletters', ['id' => $issue->id]);

        $issue2 = BroadcastNewsletter::create([
            'title' => 'Delivered', 'body' => 'Done', 'issue_month' => $month.'-01',
            'status' => BroadcastNewsletter::SENT, 'sent_at' => now(),
        ]);
        $this->deleteJson(route('admin.broadcast.newsletters.delete', $issue2))
            ->assertForbidden();
        $this->assertDatabaseHas('broadcast_newsletters', ['id' => $issue2->id]);
    }

    public function test_deleting_draft_removes_unshared_images_but_preserves_shared_and_sent_images(): void
    {
        Storage::fake('local');
        $this->loginAsAdmin();

        $unused = str_repeat('a', 40).'.png';
        $shared = str_repeat('b', 40).'.png';
        Storage::disk('local')->put('broadcast/images/'.$unused, 'unused');
        Storage::disk('local')->put('broadcast/images/'.$shared, 'shared');

        $draft = BroadcastAnnouncement::create([
            'title' => 'Temporary',
            'body' => '<img src="/broadcast/images/'.$unused.'">'.
                '<img src="/broadcast/images/'.$shared.'">',
            'body_format' => 'html',
            'audience' => 'all_users',
            'status' => BroadcastAnnouncement::DRAFT,
            'published_at' => now(),
        ]);
        BroadcastNewsletter::create([
            'title' => 'Sent',
            'body' => '<img src="/broadcast/images/'.$shared.'">',
            'body_format' => 'html',
            'status' => BroadcastNewsletter::SENT,
            'issue_month' => now()->addMonth()->startOfMonth(),
            'sent_at' => now(),
        ]);

        $this->deleteJson(route('admin.broadcast.announcements.delete', $draft))
            ->assertOk();

        Storage::disk('local')->assertMissing('broadcast/images/'.$unused);
        Storage::disk('local')->assertExists('broadcast/images/'.$shared);
    }

    public function test_users_cannot_discard_admin_drafts_and_published_items_remain_immutable(): void
    {
        $this->loginAsAdmin();
        $published = BroadcastAnnouncement::create([
            'title' => 'Published content', 'body' => '<p>Permanent</p>',
            'audience' => 'all_users', 'status' => BroadcastAnnouncement::PUBLISHED,
            'published_at' => now(),
        ]);
        $draft = BroadcastAnnouncement::create([
            'title' => 'Admin draft', 'body' => '<p>Private</p>',
            'audience' => 'all_users', 'status' => BroadcastAnnouncement::DRAFT,
            'published_at' => now(),
        ]);

        $this->deleteJson(route('admin.broadcast.announcements.delete', $published))
            ->assertForbidden();

        $this->actingAs(User::factory()->user()->create());
        $this->deleteJson(route('admin.broadcast.announcements.delete', $draft))
            ->assertRedirect(route('frontend.index'));

        $this->assertDatabaseHas('broadcast_announcements', ['id' => $draft->id]);
        $this->assertDatabaseHas('broadcast_announcements', ['id' => $published->id]);
    }

    public function test_editor_renders_sweetalert_discard_and_summernote_configuration(): void
    {
        $this->loginAsAdmin();
        foreach ([
            route('admin.broadcast.newsletters.index'),
            route('admin.broadcast.announcements.index'),
        ] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('data-broadcast-discard', false)
                ->assertSee('data-broadcast-delete-template', false)
                ->assertSee('broadcast-editor.js');
        }

        $script = file_get_contents(public_path('js/broadcast-editor.js'));
        $this->assertStringContainsString('window.Swal.fire', $script);
        $this->assertStringContainsString('initEditor(rich, state)', $script);
    }
}
