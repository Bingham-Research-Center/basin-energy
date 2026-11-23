<?php

namespace App\Services;

class BroadcastRichText
{
    /**
     * Resolve HTMLPurifier only when HTML needs sanitizing.
     *
     * Opening an empty newsletter/announcement modal or rendering a legacy
     * plain-text message must not eagerly construct the HTML sanitizer.
     * Rich-text save/render still fails closed if Composer dependencies are
     * missing: never send unfiltered user-supplied HTML.
     */
    public function sanitize(?string $html): string
    {
        if (! class_exists(\HTMLPurifier_Config::class) || ! class_exists(\HTMLPurifier::class)) {
            throw new \RuntimeException(
                'HTMLPurifier is not installed in this PHP runtime. Run composer install '.
                '--no-dev --prefer-dist --no-interaction --optimize-autoloader '.
                'inside the production application container, then retry.'
            );
        }

        return (string) app(BlogHtmlSanitizer::class)->sanitize($html ?? '');
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
