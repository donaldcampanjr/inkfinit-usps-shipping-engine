/**
 * Admin Diagnostics Page Script
 */
(function ($) {
	'use strict';

	$(function () {
		var btn = $('#wtcc-test-api-btn');
		var status = $('#wtcc-test-api-status');

		if (!btn.length) {
			return;
		}

		btn.on('click', function (e) {
			e.preventDefault();
			btn.prop('disabled', true);
			status.html(
				'<span class="spinner is-active" style="vertical-align: middle;"></span><span style="vertical-align: middle; margin-left: 5px;">' +
				wtcc_diagnostics.testing_text +
				'</span>'
			);

			$.ajax({
				url: wtcc_diagnostics.ajax_url,
				method: 'POST',
				data: {
					action: 'wtcc_test_api_connection',
					nonce: wtcc_diagnostics.nonce,
				},
				success: function (response) {
					if (response.success) {
						status.html(
							'<span class="dashicons dashicons-yes-alt" style="color: #46b450; vertical-align: middle;"></span><span style="vertical-align: middle; margin-left: 5px;">' +
							wtcc_diagnostics.success_text +
							'</span>'
						);
					} else {
						var message = response.data.message || wtcc_diagnostics.failed_text;
						status.html(
							'<span class="dashicons dashicons-dismiss" style="color: #dc3232; vertical-align: middle;"></span><span style="vertical-align: middle; margin-left: 5px;">' +
							message +
							'</span>'
						);
					}
				},
				error: function () {
					status.html(
						'<span style="color: #dc3232;">' +
						wtcc_diagnostics.error_text +
						'</span>'
					);
				},
				complete: function () {
					btn.prop('disabled', false);
				},
			});
		});
	});
})(jQuery);
