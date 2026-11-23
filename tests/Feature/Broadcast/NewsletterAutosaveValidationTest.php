<?php

namespace Tests\Feature\Broadcast;

use App\Models\BroadcastNewsletter;
use App\Services\BroadcastDeliveryService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterAutosaveValidationTest extends TestCase
{
    public function test_historical_issue_can_be_autosaved_as_draft_but_cannot_be_scheduled(): void
    {
        Mail::fake();
        $this->loginAsAdmin();

        $response = $this->postJson(
            route('admin.broadcast.editor.autosave', ['kind' => 'newsletter']),
            [
                'issue_month' => '2023-03',
                'title' => 'Historical research highlights',
                'body' => '<p>Archived findings</p>',
            ]
        )->assertOk()->assertJsonPath('status', 'draft');

        $draft = BroadcastNewsletter::findOrFail($response->json('id'));
        $this->assertSame('draft', $draft->status);
        $this->assertNull($draft->scheduled_at);
        $this->assertSame('2023-03', $draft->issue_month->format('Y-m'));

        $this->post(route('admin.broadcast.newsletters.schedule', $draft))
            ->assertStatus(422);
        $this->assertSame('draft', $draft->fresh()->status);

        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();
    }

    public function test_manual_save_and_edit_of_historical_draft_are_allowed(): void
    {
        $this->loginAsAdmin();

        $this->post(route('admin.broadcast.newsletters.store'), [
            'issue_month' => '2022-08',
            'title' => 'Archived draft',
            'body' => '<p>Not scheduled</p>',
        ])->assertRedirect(route('admin.broadcast.newsletters.index'));

        $issue = BroadcastNewsletter::firstOrFail();
        $this->put(route('admin.broadcast.newsletters.update', $issue), [
            'issue_month' => '2022-08',
            'title' => 'Updated historical issue',
            'body' => '<p>Updated content</p>',
        ])->assertRedirect(route('admin.broadcast.newsletters.index'));

        $this->assertSame('Updated historical issue', $issue->fresh()->title);
        $this->assertNull($issue->fresh()->scheduled_at);
    }

    public function test_invalid_month_returns_a_specific_json_validation_error(): void
    {
        $this->loginAsAdmin();
        $url = route('admin.broadcast.editor.autosave', ['kind' => 'newsletter']);

        $this->postJson($url, [
            'issue_month' => '2026-1',
            'body' => '<p>Incomplete month</p>',
        ])->assertStatus(422)->assertJsonValidationErrors('issue_month');

        $this->postJson($url, [
            'body' => '<p>Missing month</p>',
        ])->assertStatus(422)->assertJsonValidationErrors('issue_month');

        $this->assertDatabaseCount('broadcast_newsletters', 0);
    }

    public function test_new_newsletter_editor_prefills_a_valid_issue_month(): void
    {
        $this->loginAsAdmin();
        $month = now('America/Denver')->format('Y-m');

        $this->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('value="'.$month.'"', false)
            ->assertSee('broadcast-editor.js?v=6');
    }

    public function test_sending_still_requires_explicit_schedule(): void
    {
        Mail::fake();
        $this->loginAsAdmin();

        $this->postJson(
            route('admin.broadcast.editor.autosave', ['kind' => 'newsletter']),
            [
                'issue_month' => now('America/Denver')->format('Y-m'),
                'title' => 'Current month draft',
                'body' => '<p>Not approved</p>',
            ]
        )->assertOk();

        $draft = BroadcastNewsletter::firstOrFail();
        $this->assertSame(BroadcastNewsletter::DRAFT, $draft->status);
        $this->assertNull($draft->scheduled_at);

        app(BroadcastDeliveryService::class)->run();
        Mail::assertNothingSent();
    }
}
