/**
 * WTC Shipping Core - Admin JavaScript
 * Provides consistent UI interactions across admin pages
 */

(function($) {
	'use strict';

	/**
	 * Privacy Field Toggle - Show/Hide sensitive data
	 * Used for API keys, addresses, phone numbers, etc.
	 */
	window.wtcTogglePrivacy = function(fieldId, button) {
		var field = document.getElementById(fieldId);
		var icon = button.querySelector('.dashicons');
		
		if (!field || !icon) return;
		
		if (field.type === 'password') {
			field.type = 'text';
			icon.classList.remove('dashicons-visibility');
			icon.classList.add('dashicons-hidden');
			button.setAttribute('title', 'Hide');
			button.setAttribute('aria-label', 'Hide ' + fieldId);
		} else {
			field.type = 'password';
			icon.classList.remove('dashicons-hidden');
			icon.classList.add('dashicons-visibility');
			button.setAttribute('title', 'Show/Hide');
			button.setAttribute('aria-label', 'Toggle visibility of ' + fieldId);
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		
		// Auto-hide success messages after 5 seconds
		setTimeout(function() {
			$('.notice.is-dismissible').fadeOut(400, function() {
				$(this).remove();
			});
		}, 5000);

		// Confirm delete actions
		$('.wtc-confirm-delete').on('click', function(e) {
			var message = $(this).data('confirm') || 'Are you sure you want to delete this?';
			if (!confirm(message)) {
				e.preventDefault();
				return false;
			}
		});

		// Table row actions
		$('.wtc-row-actions a').on('click', function(e) {
			if ($(this).hasClass('delete')) {
				var message = 'Are you sure you want to delete this item?';
				if (!confirm(message)) {
					e.preventDefault();
					return false;
				}
			}
		});

		// Enhanced form validation
		$('form[data-validate]').on('submit', function(e) {
			var isValid = true;
			var firstInvalidField = null;

			$(this).find('[required]').each(function() {
				var $field = $(this);
				var value = $field.val();

				if (!value || value.trim() === '') {
					$field.addClass('error');
					$field.css('border-color', '#d63638');
					isValid = false;

					if (!firstInvalidField) {
						firstInvalidField = $field;
					}
				} else {
					$field.removeClass('error');
					$field.css('border-color', '');
				}
			});

			if (!isValid) {
				e.preventDefault();
				if (firstInvalidField) {
					firstInvalidField.focus();
					
					// Show error message
					var errorMsg = $('<div class="notice notice-error is-dismissible"><p>Please fill in all required fields.</p></div>');
					$('.wrap h1').after(errorMsg);
					
					// Scroll to top
					$('html, body').animate({
						scrollTop: $('.wrap').offset().top - 50
					}, 500);
				}
				return false;
			}
		});

		// Clear field error styling on input
		$('input, select, textarea').on('input change', function() {
			if ($(this).hasClass('error')) {
				$(this).removeClass('error');
				$(this).css('border-color', '');
			}
		});

		// Copy to clipboard functionality
		$('.wtc-copy-btn').on('click', function(e) {
			e.preventDefault();
			var target = $(this).data('target');
			var $target = $(target);
			
			if ($target.length) {
				var text = $target.val() || $target.text();
				
				// Use modern clipboard API if available
				if (navigator.clipboard) {
					navigator.clipboard.writeText(text).then(function() {
						showCopySuccess($(e.target));
					});
				} else {
					// Fallback for older browsers
					var $temp = $('<textarea>');
					$('body').append($temp);
					$temp.val(text).select();
					document.execCommand('copy');
					$temp.remove();
					showCopySuccess($(e.target));
				}
			}
		});

		function showCopySuccess($btn) {
			var originalText = $btn.text();
			$btn.text('Copied!').css('color', '#00a32a');
			setTimeout(function() {
				$btn.text(originalText).css('color', '');
			}, 2000);
		}

		// Tabs functionality (if needed)
		$('.wtc-tabs').each(function() {
			var $tabs = $(this);
			var $tabButtons = $tabs.find('.wtc-tab-button');
			var $tabPanels = $tabs.find('.wtc-tab-panel');

			$tabButtons.on('click', function(e) {
				e.preventDefault();
				var targetId = $(this).data('tab');

				$tabButtons.removeClass('active');
				$(this).addClass('active');

				$tabPanels.removeClass('active').hide();
				$('#' + targetId).addClass('active').fadeIn(200);
			});

			// Show first tab by default
			$tabButtons.first().click();
		});

	});

})(jQuery);
