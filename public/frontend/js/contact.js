(function ($) {
    'use strict';

    var form = $('.contact__form.ajax-contact-form'),
        message = $('.contact__msg'),
        form_data;

    if (!form.length) {
        return;
    }

    function done_func(response) {
        message.fadeIn().removeClass('alert-danger').addClass('alert-success');
        message.text(response.message || response);
        setTimeout(function () {
            message.fadeOut();
        }, 2000);
        form.find('input:not([type="submit"]), textarea').val('');
    }

    function fail_func(data) {
        message.fadeIn().removeClass('alert-success').addClass('alert-danger');
        message.text(data.responseText || 'Something went wrong.');
        setTimeout(function () {
            message.fadeOut();
        }, 2000);
    }

    form.submit(function (e) {
        e.preventDefault();
        form_data = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form_data
        })
        .done(done_func)
        .fail(fail_func);
    });

})(jQuery);