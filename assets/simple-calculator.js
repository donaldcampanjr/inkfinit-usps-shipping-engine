/**
 * Simple Shipping Rate Calculator
 * 
 * @package Inkfinit_Shipping_Engine
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initSimpleCalculator();
    });

    function initSimpleCalculator() {
        const $button = $('#wtcc_calc_button');
        const $spinner = $('#wtcc_calc_spinner');
        const $resultsBox = $('#wtcc_calc_results_box');
        const $results = $('#wtcc_calc_results');
        const $errorBox = $('#wtcc_calc_error_box');
        const $errorMsg = $('#wtcc_calc_error_msg');

        if (!$button.length) {
            return;
        }

        // Calculate button click
        $button.on('click', function() {
            const weight = parseFloat($('#wtcc_calc_weight').val()) || 0;
            const weightUnit = $('#wtcc_calc_weight_unit').val();
            const originZip = $('#wtcc_calc_origin_zip').val().trim();
            const destZip = $('#wtcc_calc_dest_zip').val().trim();

            // Basic validation
            if (weight <= 0) {
                showError('Please enter a valid package weight.');
                return;
            }

            if (!/^\d{5}$/.test(originZip)) {
                showError('Please enter a valid 5-digit origin ZIP code.');
                return;
            }

            if (!/^\d{5}$/.test(destZip)) {
                showError('Please enter a valid 5-digit destination ZIP code.');
                return;
            }

            // Show loading state
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            hideError();
            hideResults();

            // Make AJAX request
            $.ajax({
                url: wtcc_calc.ajax_url,
                type: 'POST',
                data: {
                    action: 'wtcc_calculate_simple_rates',
                    nonce: wtcc_calc.nonce,
                    weight: weight,
                    weight_unit: weightUnit,
                    origin_zip: originZip,
                    dest_zip: destZip
                },
                success: function(response) {
                    if (response.success && response.data.rates) {
                        displayRates(response.data.rates, weight, weightUnit, originZip, destZip);
                    } else {
                        showError(response.data?.message || 'Unable to calculate rates. Please try again.');
                    }
                },
                error: function() {
                    showError('Connection error. Please check your internet connection and try again.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Allow Enter key to trigger calculation
        $('#wtcc_calc_weight, #wtcc_calc_origin_zip, #wtcc_calc_dest_zip').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $button.click();
            }
        });

        /**
         * Display calculated rates
         */
        function displayRates(rates, weight, weightUnit, originZip, destZip) {
            if (!rates.length) {
                showError('No shipping rates available for this route.');
                return;
            }

            // Build summary
            const displayWeight = weightUnit === 'lb' 
                ? weight.toFixed(2) + ' lb' 
                : weight.toFixed(1) + ' oz';
            
            let html = '<div class="wtcc-rate-summary" style="background: #f0f0f1; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px;">';
            html += '<strong>Route:</strong> ' + originZip + ' → ' + destZip + ' | ';
            html += '<strong>Weight:</strong> ' + displayWeight;
            html += '</div>';

            // Build rate cards
            html += '<div class="wtcc-rate-cards" style="display: grid; gap: 15px;">';
            
            rates.forEach(function(rate, index) {
                const isFirst = index === 0;
                const borderColor = isFirst ? '#00a32a' : '#c3c4c7';
                const badge = isFirst 
                    ? '<span style="background: #00a32a; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin-left: 10px;">BEST VALUE</span>' 
                    : '';
                
                html += '<div class="wtcc-rate-card" style="border: 2px solid ' + borderColor + '; border-radius: 6px; padding: 15px; background: #fff;">';
                html += '<div style="display: flex; justify-content: space-between; align-items: center;">';
                html += '<div>';
                html += '<span style="font-size: 20px; margin-right: 8px;">' + (rate.icon || '📦') + '</span>';
                html += '<strong style="font-size: 16px;">' + escapeHtml(rate.name) + '</strong>' + badge;
                html += '<div style="color: #666; font-size: 13px; margin-top: 4px; margin-left: 32px;">' + escapeHtml(rate.delivery) + '</div>';
                html += '</div>';
                html += '<div style="text-align: right;">';
                html += '<div style="font-size: 24px; font-weight: 600; color: #1d2327;">$' + rate.cost.toFixed(2) + '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';

            $results.html(html);
            $resultsBox.slideDown(200);
        }

        /**
         * Show error message
         */
        function showError(message) {
            $errorMsg.text(message);
            $errorBox.slideDown(200);
            hideResults();
        }

        /**
         * Hide error message
         */
        function hideError() {
            $errorBox.slideUp(200);
        }

        /**
         * Hide results
         */
        function hideResults() {
            $resultsBox.slideUp(200);
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

})(jQuery);
