jQuery(document).ready(function($) {
    if (typeof wtc_shipping_admin === 'undefined') {
        return;
    }

    const wrapper = '#wtcc-pickup-metabox-content-wrapper';

    // Toggle custom address fields
    $(document).on('change', '#wtcc_use_custom_address', function() {
        $('#wtcc_custom_address_fields').toggleClass('hidden', !$(this).is(':checked'));
    });
    
    function showLoading(btn) {
        btn.prop('disabled', true);
        btn.siblings('.spinner').addClass('is-active');
    }

    function hideLoading(btn, text) {
        btn.prop('disabled', false).text(text);
        btn.siblings('.spinner').removeClass('is-active');
    }

    // Schedule pickup
    $(document).on('click', '.wtcc-schedule-pickup', function(e) {
        e.preventDefault();
        const btn = $(this);
        const orderId = $(wrapper).data('order-id');
        
        if (!confirm(wtc_shipping_admin.i18n.confirm_schedule)) {
            return;
        }
        
        showLoading(btn);
        
        const ajaxData = {
            action: 'wtcc_schedule_pickup',
            nonce: $('#wtcc_pickup_nonce').val(),
            order_id: orderId,
            pickup_date: $('#wtcc_pickup_date').val(),
            package_count: $('#wtcc_package_count').val(),
            total_weight: $('#wtcc_total_weight').val(),
            package_location: $('#wtcc_package_location').val(),
            special_instructions: $('#wtcc_special_instructions').val(),
            use_custom_address: $('#wtcc_use_custom_address').is(':checked') ? 1 : 0
        };
        
        // Add custom address if checked
        if ($('#wtcc_use_custom_address').is(':checked')) {
            ajaxData.custom_address = $('#wtcc_custom_address').val();
            ajaxData.custom_city = $('#wtcc_custom_city').val();
            ajaxData.custom_state = $('#wtcc_custom_state').val();
            ajaxData.custom_zip = $('#wtcc_custom_zip').val();
            ajaxData.custom_phone = $('#wtcc_custom_phone').val();
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    $(wrapper).html(response.data.html);
                    // Optionally show a success message
                } else {
                    alert(wtc_shipping_admin.i18n.error_prefix + response.data.message);
                    hideLoading(btn, wtc_shipping_admin.i18n.schedule_pickup);
                }
            },
            error: function() {
                alert(wtc_shipping_admin.i18n.network_error);
                hideLoading(btn, wtc_shipping_admin.i18n.schedule_pickup);
            }
        });
    });
    
    // Cancel pickup
    $(document).on('click', '.wtcc-cancel-pickup', function(e) {
        e.preventDefault();
        const btn = $(this);
        const orderId = $(wrapper).data('order-id');
        const confirmation = btn.data('confirmation');
        
        if (!confirm(wtc_shipping_admin.i18n.confirm_cancel)) {
            return;
        }
        
        btn.prop('disabled', true).text(wtc_shipping_admin.i18n.cancelling);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wtcc_cancel_pickup',
                nonce: $('#wtcc_pickup_nonce').val(),
                order_id: orderId,
                confirmation: confirmation
            },
            success: function(response) {
                if (response.success) {
                    $(wrapper).html(response.data.html);
                } else {
                    alert(wtc_shipping_admin.i18n.error_prefix + response.data.message);
                    btn.prop('disabled', false).text(wtc_shipping_admin.i18n.cancel_pickup);
                }
            },
            error: function() {
                alert(wtc_shipping_admin.i18n.network_error);
                btn.prop('disabled', false).text(wtc_shipping_admin.i18n.cancel_pickup);
            }
        });
    });
});
