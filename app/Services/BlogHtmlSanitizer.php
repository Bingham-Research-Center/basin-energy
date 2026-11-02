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
