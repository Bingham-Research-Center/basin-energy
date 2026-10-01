(function () {
    "use strict";

    /**
     * Called by the Google reCAPTCHA API after it has loaded.
     */
    window.initializeInvisibleCaptchas = function () {
        if (
            typeof window.grecaptcha === "undefined"
            || typeof window.grecaptcha.render !== "function"
        ) {
            console.error("Google reCAPTCHA API is not available.");
            return;
        }

        const captchaContainers = document.querySelectorAll(
            ".js-invisible-recaptcha"
        );

        captchaContainers.forEach(function (container) {
            if (container.dataset.rendered === "true") {
                return;
            }

            const form = container.closest("form");

            if (!form) {
                console.error(
                    "A reCAPTCHA container was found outside a form.",
                    container
                );

                return;
            }

            const siteKey = container.dataset.sitekey;
            const badge = container.dataset.badge || "bottomright";

            if (!siteKey) {
                console.error(
                    "No reCAPTCHA site key was provided.",
                    container
                );

                return;
            }

            let widgetId = null;
            let originalSubmitter = null;

            widgetId = window.grecaptcha.render(container, {
                sitekey: siteKey,
                size: "invisible",
                badge: badge,

                callback: function () {
                    /*
                     * Mark this submission as CAPTCHA-verified, then
                     * trigger the form again.
                     *
                     * requestSubmit() is important because it allows
                     * other submit handlers, such as popup-auth.js,
                     * to continue working normally.
                     */
                    form.dataset.captchaVerified = "true";

                    if (typeof form.requestSubmit === "function") {
                        if (
                            originalSubmitter
                            && originalSubmitter.form === form
                        ) {
                            form.requestSubmit(originalSubmitter);
                        } else {
                            form.requestSubmit();
                        }
                    } else {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                },

                "expired-callback": function () {
                    form.dataset.captchaVerified = "false";

                    if (widgetId !== null) {
                        window.grecaptcha.reset(widgetId);
                    }
                },

                "error-callback": function () {
                    form.dataset.captchaVerified = "false";

                    console.error(
                        "Google reCAPTCHA could not complete verification."
                    );
                }
            });

            container.dataset.rendered = "true";
            container.dataset.widgetId = String(widgetId);

            /*
             * Capture phase is used so CAPTCHA runs before other
             * form submit handlers, including the authentication
             * popup AJAX handler.
             */
            form.addEventListener(
                "submit",
                function (event) {
                    if (form.dataset.captchaVerified === "true") {
                        /*
                         * Allow this verified submission to continue.
                         * Reset the flag for any future submission.
                         */
                        form.dataset.captchaVerified = "false";

                        return;
                    }

                    event.preventDefault();
                    event.stopImmediatePropagation();

                    originalSubmitter = event.submitter || null;

                    window.grecaptcha.execute(widgetId);
                },
                true
            );
        });
    };
})();