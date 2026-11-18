<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class BlogHtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // HTMLPurifier otherwise writes its serialized definitions under
        // vendor/, which is deliberately read-only on production servers.
        // Cache these generated definitions in Laravel's writable storage.
        $cachePath = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cachePath)) {
            \Illuminate\Support\Facades\File::ensureDirectoryExists($cachePath, 0755, true);
        }

        if (! is_dir($cachePath) || ! is_writable($cachePath)) {
            throw new \RuntimeException(
                'HTMLPurifier definition cache is not writable: '.$cachePath.
                '. Ensure Laravel storage/framework/cache is writable by the PHP web process.'
            );
        }

        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

        $config->set(
            'HTML.Allowed',
            'p,br,h1,h2,h3,h4,h5,h6,strong,b,em,i,u,s,'
            .'ul,ol,li,blockquote,'
            .'table,thead,tbody,tr,th,td,'
            .'a[href|title|target],'
            .'img[src|alt|title|width|height],'
            .'span[style],div[style],'
            .'p[style],td[style],th[style]'
        );

        $config->set('CSS.AllowedProperties', [
            'color',
            'background-color',
            'text-align',
            'font-weight',
            'font-style',
            'text-decoration',
        ]);

        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return $this->purifier->purify($html);
    }
}
