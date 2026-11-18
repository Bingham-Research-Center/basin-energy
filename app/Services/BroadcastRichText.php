<?php

namespace App\Services;

class BroadcastRichText
{
    public function __construct(private BlogHtmlSanitizer $sanitizer)
    {
    }

    public function sanitize(?string $html): string
    {
        return (string) $this->sanitizer->sanitize($html ?? '');
    }

    public function hasText(?string $html): bool
    {
        $plainText = html_entity_decode(strip_tags($html ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\xc2\xa0", ' ', $plainText)) !== '';
    }

    /**
     * HTML is sanitized on save AND at render, including historical rows.
     * Legacy plain-text newsletters must not be interpreted as HTML.
     */
    public function render(?string $body, ?string $format): string
    {
        if ($format === 'html') {
            return $this->sanitize($body);
        }

        return nl2br(e($body ?? ''));
    }
}
