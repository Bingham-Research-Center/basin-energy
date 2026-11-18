<?php

namespace Tests\Feature\Broadcast;

use App\Services\BlogHtmlSanitizer;
use App\Services\BroadcastRichText;
use Tests\TestCase;

class BroadcastSanitizerDependencyTest extends TestCase
{
    public function test_legacy_plain_text_is_rendered_without_constructing_html_purifier(): void
    {
        // Prove plain-text rendering never resolves the sanitizing service.
        $this->app->bind(BlogHtmlSanitizer::class, function () {
            throw new \RuntimeException('Unexpected HTMLPurifier construction');
        });

        $richText = $this->app->make(BroadcastRichText::class);

        $this->assertSame('', $richText->render(null, 'plain'));
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;',
            $richText->render('<script>alert(1)</script>', 'plain'));
        $this->assertSame("Line one<br />\nLine two",
            $richText->render("Line one\nLine two", 'plain'));
    }

    public function test_empty_broadcast_modals_do_not_resolve_html_purifier(): void
    {
        $this->loginAsAdmin();
        $this->app->bind(BlogHtmlSanitizer::class, function () {
            throw new \RuntimeException('Unexpected HTMLPurifier construction');
        });

        $this->get(route('admin.broadcast.newsletters.index'))
            ->assertOk()
            ->assertSee('newsletterEditorModal');

        $this->get(route('admin.broadcast.announcements.index'))
            ->assertOk()
            ->assertSee('announcementEditorModal');
    }

    public function test_html_content_is_still_sanitized_when_saved_or_rendered(): void
    {
        $richText = $this->app->make(BroadcastRichText::class);
        $unsafe = '<p>Published update</p><script>alert(1)</script>';
        $clean = $richText->sanitize($unsafe);

        $this->assertStringContainsString('<p>Published update</p>', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertSame($clean, $richText->render($unsafe, 'html'));
    }
}
