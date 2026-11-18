<?php

namespace Tests\Feature\Broadcast;

use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use Tests\TestCase;

class BroadcastEditorModalRegressionTest extends TestCase
{
    public function test_edit_draft_json_includes_server_update_time_for_recovery_decisions(): void
    {
        $this->loginAsAdmin();

        $announcement = BroadcastAnnouncement::create([
            'title' => 'Existing announcement',
            'body' => '<p>Persisted message</p>',
            'body_format' => 'html',
            'status' => BroadcastAnnouncement::DRAFT,
            'audience' => 'all_users',
            'role_ids' => [],
            'published_at' => now(),
            'created_by' => auth()->id(),
        ]);

        $this->getJson(route('admin.broadcast.announcements.edit', $announcement))
            ->assertOk()
            ->assertJsonPath('editable', true)
            ->assertJsonStructure(['updated_at', 'body_html', 'update_url', 'delete_url'])
            ->assertJsonPath('body_html', '<p>Persisted message</p>');

        $newsletter = BroadcastNewsletter::create([
            'title' => 'Existing newsletter',
            'body' => '<p>Draft newsletter</p>',
            'body_format' => 'html',
            'status' => BroadcastNewsletter::DRAFT,
            'issue_month' => now()->addMonths(2)->startOfMonth(),
            'created_by' => auth()->id(),
        ]);

        $this->getJson(route('admin.broadcast.newsletters.edit', $newsletter))
            ->assertOk()
            ->assertJsonStructure(['updated_at', 'body_html', 'update_url', 'delete_url']);
    }

    public function test_broadcast_editor_script_has_sweetalert_recovery_and_single_submit_path(): void
    {
        $script = file_get_contents(public_path('js/broadcast-editor.js'));

        $this->assertStringContainsString("title: 'Recover unsaved draft?'", $script);
        $this->assertStringContainsString('data.updated_at', $script);
        $this->assertStringContainsString('state.modalShown', $script);
        $this->assertStringContainsString('HTMLFormElement.prototype.submit.call(form)', $script);
        $this->assertStringNotContainsString("window.confirm('Recover your unsaved", $script);
        $this->assertStringNotContainsString('form.requestSubmit(', $script);
    }

    public function test_both_broadcast_indexes_reference_versioned_editor_script(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()
            ->assertSee('broadcast-editor.js?v=5')
            ->assertSee('data-broadcast-save-button', false);

        $this->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('broadcast-editor.js?v=5')
            ->assertSee('data-broadcast-save-button', false);
    }
}
