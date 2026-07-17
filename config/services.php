<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5.2-mini'),
    ],

    'ai_summary' => [
            'enabled' => env('AI_SUMMARY_ENABLED', false),
    ],

    /*
     * Socialite Credentials
     * Redirect URL's need to be the same as specified on each network you set up this application on
     * as well as conform to the route:
     * http://localhost/public/login/SERVICE/callback
     * Where service can github, facebook, twitter, google, linkedin, or bitbucket
     * Docs: https://github.com/laravel/socialite
     */
    'bitbucket' => [
        'active' => env('BITBUCKET_ACTIVE', false),
        'client_id' => env('BITBUCKET_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_CLIENT_SECRET'),
        'redirect' => env('BITBUCKET_REDIRECT'),
    ],

    'facebook' => [
        'active' => env('FACEBOOK_ACTIVE', false),
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT'),
    ],

    'github' => [
        'active' => env('GITHUB_ACTIVE', false),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT'),
    ],

    'google' => [
        'active' => env('GOOGLE_ACTIVE', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT'),
    ],

    'linkedin' => [
        'active' => env('LINKEDIN_ACTIVE', false),
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT'),
    ],

    'twitter' => [
        'active' => env('TWITTER_ACTIVE', false),
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect' => env('TWITTER_REDIRECT'),
    ],

    'carbon_mapper' => [
        'base_url' => 'https://api.carbonmapper.org/api/v1',
        'token'    => env('CARBON_MAPPER_TOKEN'),
    ],

    'synoptic' => [
        'token' => env('SYNOPTIC_TOKEN'),
    ],


    'campbell_sites' => [
        'seven-sisters' => [
            'name' => 'Seven Sisters',
            'source' => 'Campbell CR1000 Logger',
            'latitude' => 39.9826,
            'longitude' => -109.3443,
            'elevation_ft' => 5307,
            'url' => env('CAMPBELL_SEVEN_SISTERS_URL', 'http://69.55.104.136/'),
            'query' => [
                'command' => 'DataQuery',
                'uri' => 'dl:Synoptic',
                'mode' => 'most-recent',
                'p1' => 1,
            ],
            'timeout' => 12,
        ],

        'horsepool' => [
            'name' => 'Horsepool',
            'source' => 'Campbell CR1000 Logger',
            'url' => env('CAMPBELL_HORSEPOOL_URL'),
            'query' => [
                'command' => 'DataQuery',
                'uri' => 'dl:Synoptic',
                'mode' => 'most-recent',
                'p1' => 1,
            ],
            'timeout' => 12,
        ],

        'castle-peak' => [
            'name' => 'Castle Peak',
            'source' => 'Campbell CR1000 Logger',
            'latitude' => 40.0509,
            'longitude' => -110.0194,
            'elevation_ft' => 5265,
            'url' => env('CAMPBELL_CASTLE_PEAK_URL', 'http://69.55.104.137/'),
            'query' => [
                'command' => 'DataQuery',
                'uri' => 'dl:Synoptic',
                'mode' => 'most-recent',
                'p1' => 1,
            ],
            'timeout' => 12,
        ],

        'roosevelt' => [
            'name' => 'Roosevelt',
            'source' => 'Campbell CR1000 Logger',
            'latitude' => 40.2942,
            'longitude' => -110.0090,
            'elevation_ft' => 5207,
            'url' => env('CAMPBELL_ROOSEVELT_URL', 'http://67.213.230.76:7003/'),
            'query' => [
                'command' => 'DataQuery',
                'uri' => 'dl:Public',
                'mode' => 'most-recent',
                'p1' => 1,
            ],
            'timeout' => 20,
        ],


    ],


];
