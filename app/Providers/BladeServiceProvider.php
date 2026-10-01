<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

/**
 * Class BladeServiceProvider.
 */
class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function boot()
    {
        $this->registerCaptcha();
    }

    /**
     * Register the locale blade extensions.
     * See: App\Rules\Captcha for implementation
     * See LoginController/RegisterController for usage.
     */
    protected function registerCaptcha(): void
    {
        /*
        * Outputs only the reCAPTCHA container.
        *
        * The Google API and form-submission logic are loaded once
        * from the frontend layout. This allows multiple CAPTCHA
        * forms to coexist on the same page.
        */
        Blade::directive('captcha', function ($lang) {
            static $captchaCounter = 0;

            $captchaCounter++;

            $captchaId = '_g-recaptcha-'.$captchaCounter;

            $siteKey = htmlspecialchars(
                (string) config(
                    'boilerplate.access.captcha.configs.site_key'
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            $badgeLocation = htmlspecialchars(
                (string) config(
                    'boilerplate.access.captcha.configs.options.location',
                    'bottomright'
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            $html = '';

            if (
                $captchaCounter === 1
                && config('boilerplate.access.captcha.configs.options.hidden')
            ) {
                $html .= '
                    <style>
                        .grecaptcha-badge {
                            visibility: hidden !important;
                        }
                    </style>
                ';
            }

            $html .= '
                <div
                    id="'.$captchaId.'"
                    class="js-invisible-recaptcha"
                    data-sitekey="'.$siteKey.'"
                    data-badge="'.$badgeLocation.'"
                ></div>
            ';

            return new HtmlString($html);
        });
    }
}
