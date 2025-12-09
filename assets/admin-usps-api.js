/**
 * USPS API Admin JS
 */
(function($, wtc_usps_api) {
    $(function() {
        var $testBtn = $('#wtcc-test-api-btn');
        var $statusSpan = $('#wtcc-test-api-status');

        if (!$testBtn.length) {
            return;
        }

        $testBtn.on('click', function() {
            $testBtn.prop('disabled', true);
            $statusSpan.addClass('is-active').html('');

            wp.ajax.send('wtcc_test_api_connection', {
                data: {
                    nonce: wtc_usps_api.nonce
                },
                beforeSend: function() {
                    $statusSpan.removeClass('success error').addClass('is-active');
                    $testBtn.siblings('.notice').remove();
                },
                success: function(response) {
                    var message = '<div class="notice notice-success is-dismissible inline"><p>' + response.message + '</p></div>';
                    $testBtn.after(message);
                },
                error: function(response) {
                    var message = '<div class="notice notice-error is-dismissible inline"><p>' + response.message + '</p></div>';
                    $testBtn.after(message);
                },
                complete: function() {
                    $testBtn.prop('disabled', false);
                    $statusSpan.removeClass('is-active');
                }
            });
        });
    });
})(jQuery, window.wtc_usps_api);
