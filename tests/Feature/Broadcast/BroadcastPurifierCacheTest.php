<?php

namespace Tests\Feature\Broadcast;

use App\Services\BlogHtmlSanitizer;
use App\Services\BroadcastRichText;
use Tests\TestCase;

class BroadcastPurifierCacheTest extends TestCase
{
    public function test_htmlpurifier_uses_writable_laravel_storage_instead_of_vendor(): void
    {
        $cachePath = storage_path('framework/cache/htmlpurifier');
        $sanitizer = app(BlogHtmlSanitizer::class);

        // Trigger creation / use of serialized HTML definition caches.
        $clean = $sanitizer->sanitize(
            '<p>Broadcast content</p><script>alert("unsafe")</script>'
        );

        $this->assertStringContainsString('Broadcast content', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertDirectoryExists($cachePath);
        $this->assertTrue(is_writable($cachePath));
        $this->assertDirectoryExists($cachePath.'/HTML');
        $this->assertStringNotContainsString('/vendor/', $cachePath);
    }

    public function test_broadcast_autosave_sanitization_remains_functional(): void
    {
        $richText = app(BroadcastRichText::class);
        $result = $richText->sanitize(
            '<h2>Newsletter</h2><img src="https://example.org/photo.png" alt="Test">'.
            '<script>alert("xss")</script>'
        );

        $this->assertStringContainsString('Newsletter', $result);
        $this->assertStringNotContainsString('<script', $result);
    }
}
