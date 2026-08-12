(function ($, window, document) {
    'use strict';

    if (!$) {
        return;
    }

    var inFlightRequests = {};

    function isMutation(method) {
        method = (method || 'GET').toUpperCase();

        return ['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) !== -1;
    }

    function requestKey(options) {
        return [
            (options.type || options.method || 'GET').toUpperCase(),
            options.url || '',
            typeof options.data === 'string' ? options.data : $.param(options.data || {})
        ].join('|');
    }

    $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
        if (!isMutation(options.type || options.method) || options.duplicateProtect === false) {
            return;
        }

        var key = requestKey(options);
        if (inFlightRequests[key]) {
            jqXHR.abort('duplicate-submit');

            return;
        }

        inFlightRequests[key] = true;
        jqXHR.always(function () {
            delete inFlightRequests[key];
        });
    });

    $(document).on('submit.duplicateSubmitProtection', 'form', function (event) {
        var form = this;
        var $form = $(form);
        var method = ($form.attr('method') || 'GET').toUpperCase();

        if (!isMutation(method) || $form.data('duplicateProtection') === 'off' || $form.data('allowMultipleSubmit')) {
            return;
        }

        if (form.checkValidity && !form.checkValidity()) {
            return;
        }

        if ($form.data('submitting')) {
            event.preventDefault();

            return false;
        }

        $form.data('submitting', true);
        $form.find(':submit:not([data-keep-enabled])').each(function () {
            var $button = $(this);
            $button.data('duplicateProtectionLabel', $button.html());
            $button.prop('disabled', true).addClass('disabled');

            if ($button.is('button') && !$button.data('processingLabel')) {
                $button.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            }
        });
    });

    $(window).on('pageshow.duplicateSubmitProtection', function () {
        $('form').each(function () {
            var $form = $(this);
            if (!$form.data('submitting')) {
                return;
            }

            $form.removeData('submitting');
            $form.find(':submit').filter(function () {
                return $(this).data('duplicateProtectionLabel') !== undefined;
            }).each(function () {
                var $button = $(this);
                $button.prop('disabled', false).removeClass('disabled').html($button.data('duplicateProtectionLabel'));
            });
        });
    });
})(window.jQuery, window, document);
