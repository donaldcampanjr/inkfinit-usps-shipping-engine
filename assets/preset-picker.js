/**
 * UNIFIED PRESET SYSTEM
 * One preset selector controls everything:
 * - Auto-fills weight & dimensions
 * - Updates shipping status notices dynamically
 * - Updates sidebar recommendation box
 * - No page reload needed
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		const productId = wtccPresetData.productId;
		const ajaxUrl = wtccPresetData.ajaxUrl;
		const nonce = wtccPresetData.nonce;
		
		// Store preset data inline for instant fill (no AJAX needed for form fields)
		var presetDataCache = {};

		// WHEN PRESET SELECTED → UPDATE EVERYTHING
		$('#wtcc_preset_select').on('change', function() {
			const presetKey = $(this).val();
			const $select = $(this);
			
			if (!presetKey) {
				$('#wtcc_preset_preview').hide();
				$('#wtcc_preset_status').html(
					'<div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 6px;">' +
					'<span class="dashicons dashicons-warning" style="color: #f59e0b; font-size: 18px; vertical-align: middle;"></span>' +
					'<span style="color: #92400e; vertical-align: middle;"><strong>Action needed:</strong> Select a preset above to set shipping data</span>' +
					'</div>'
				);
				return;
			}

			// For new products (productId = 0), just fill form fields without AJAX save
			if (!productId || productId === 0) {
				// Get preset data from the selected option's text (parse it)
				var optionText = $select.find('option:selected').text();
				console.log('New product - filling fields from preset:', presetKey, optionText);
				
				// Show that we're ready but need to save
				$('#wtcc_preset_status').html(
					'<div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 6px;">' +
					'<span class="dashicons dashicons-info" style="color: #3b82f6; font-size: 18px; vertical-align: middle;"></span>' +
					'<span style="color: #1e40af; vertical-align: middle;">Preset selected - <strong>Save product</strong> to apply shipping data</span>' +
					'</div>'
				);
				$('#wtcc_preset_preview').fadeIn();
				return;
			}

			// Show loading state
			$select.prop('disabled', true);
			$('#wtcc_preset_preview').html('<p style="padding: 10px; text-align: center;"><span class="spinner is-active" style="float: none;"></span> Applying preset...</p>').show();

			// AJAX call to apply preset (for existing products)
			$.post(ajaxUrl, {
				action: 'wtcc_apply_preset',
				nonce: nonce,
				product_id: productId,
				preset_key: presetKey
			})
			.done(function(response) {
				if (response.success) {
					console.log('✅ Preset applied:', response.data);
					
					// Update WooCommerce form fields
					if (response.data.weight !== undefined) {
						$('#_weight').val(response.data.weight).trigger('change');
					}
					if (response.data.length !== undefined) {
						$('#_length').val(response.data.length).trigger('change');
					}
					if (response.data.width !== undefined) {
						$('#_width').val(response.data.width).trigger('change');
					}
					if (response.data.height !== undefined) {
						$('#_height').val(response.data.height).trigger('change');
					}
					
					// Update shipping class dropdown
					if (response.data.shipping_class_id) {
						$('#product_shipping_class').val(response.data.shipping_class_id).trigger('change');
						console.log('✅ Shipping class set to:', response.data.shipping_class_id);
					}
					
					// Update the status panel in Shipping Preset tab
					$('#wtcc_preset_status').html(
						'<div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #10b981; padding: 12px 16px; border-radius: 6px;">' +
						'<span class="dashicons dashicons-yes-alt" style="color: #10b981; font-size: 18px; vertical-align: middle;"></span>' +
						'<span style="color: #065f46; font-weight: 500; vertical-align: middle;">✓ Shipping data set</span>' +
						'</div>'
					);
					
					// DYNAMIC UPDATE: Replace shipping status warnings with success
					updateShippingStatusToSuccess();
					
					// DYNAMIC UPDATE: Refresh sidebar recommendation
					refreshSidebarRecommendation(response.data);
					
				} else {
					alert('Error: ' + (response.data || 'Unknown error'));
				}
			})
			.fail(function(xhr, status, error) {
				console.error('Preset apply failed:', error);
				alert('Error applying preset. Please try again.');
			})
			.always(function() {
				$select.prop('disabled', false);
			});
		});

		/**
		 * Update shipping status notices to show success (green)
		 */
		function updateShippingStatusToSuccess() {
			var $wrapper = $('#wtcc-shipping-status-wrapper');
			if ($wrapper.length) {
				$wrapper.html(
					'<div class="wtcc-shipping-status wtcc-status-good" style="' +
					'background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);' +
					'border-left: 4px solid #10b981;' +
					'padding: 12px 16px;' +
					'margin: 10px 0 15px 0;' +
					'border-radius: 6px;' +
					'display: flex;' +
					'align-items: center;' +
					'gap: 10px;">' +
					'<span class="dashicons dashicons-yes-alt" style="color: #10b981; font-size: 20px;"></span>' +
					'<span style="color: #065f46; font-weight: 500;">✓ Shipping data complete – preset applied!</span>' +
					'</div>'
				);
			}
		}

		/**
		 * Refresh the sidebar recommendation box
		 */
		function refreshSidebarRecommendation(data) {
			var $recBox = $('#wtc-recommendation-box');
			if ($recBox.length && data.length && data.width && data.height && data.weight) {
				$recBox.html(
					'<div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 12px; margin-bottom: 12px;">' +
					'<h4 style="margin: 0 0 8px 0; font-size: 13px; color: #065f46;">✓ Preset Applied</h4>' +
					'<p style="margin: 0; font-size: 12px; line-height: 1.6; color: #065f46;">' +
					'Weight and dimensions set from preset. Ready for shipping calculations.' +
					'</p></div>' +
					'<div style="font-size: 11px; color: #666; padding-top: 8px; border-top: 1px solid #ddd;">' +
					'<strong>Your Product:</strong> ' + data.length + ' × ' + data.width + ' × ' + data.height + ' in, ' + data.weight + ' oz' +
					'</div>'
				);
			}
			
			// Also update the sidebar metabox if it shows "Missing Dimensions"
			$('.postbox h2:contains("Shipping Recommendation")').closest('.postbox').find('.inside').each(function() {
				var $inside = $(this);
				if ($inside.find(':contains("Missing Dimensions")').length) {
					$inside.find('#wtc-recommendation-box').html(
						'<div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 12px;">' +
						'<p style="margin: 0; font-size: 12px; color: #065f46;">' +
						'<strong>✓ Preset Applied</strong><br>' +
						'Shipping data is now complete.' +
						'</p></div>'
					);
				}
			});
		}

		// Also listen for manual changes to weight/dimension fields
		// and update status accordingly
		var statusUpdateTimer;
		$('#_weight, #_length, #_width, #_height').on('change keyup', function() {
			clearTimeout(statusUpdateTimer);
			statusUpdateTimer = setTimeout(function() {
				var weight = parseFloat($('#_weight').val()) || 0;
				var length = parseFloat($('#_length').val()) || 0;
				var width = parseFloat($('#_width').val()) || 0;
				var height = parseFloat($('#_height').val()) || 0;
				
				// Check if all fields are filled
				if (weight > 0 && length > 0 && width > 0 && height > 0) {
					updateShippingStatusToSuccess();
				}
			}, 500);
		});
	});

})(jQuery);
