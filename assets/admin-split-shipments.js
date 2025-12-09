jQuery(function($) {
    'use strict';

    if (typeof wtcc_split_shipments_params === 'undefined') {
        return;
    }

    const wrapper = '#wtcc-split-shipments-wrapper';

    // --- Helper Functions ---
    function showMessage(type, message) {
        const messageDiv = $(wrapper).find('#wtcc-shipment-message');
        messageDiv.removeClass('hidden notice-error notice-success').addClass('notice notice-' + type).html('<p>' + message + '</p>').slideDown();
        setTimeout(() => messageDiv.slideUp(), 5000);
    }

    function showSpinner(button) {
        const spinner = $(button).parent().find('.spinner');
        spinner.addClass('is-active');
        $(button).prop('disabled', true);
    }

    function hideSpinner(button) {
        const spinner = $(button).parent().find('.spinner');
        spinner.removeClass('is-active');
        $(button).prop('disabled', false);
    }

    function handleAjaxResponse(response, button) {
        hideSpinner(button);
        if (response.success) {
            $(wrapper).html(response.data.html);
            // Re-initialize postbox toggles if they exist
            if (typeof postboxes !== 'undefined' && typeof postboxes.add_postbox_toggles === 'function') {
                postboxes.add_postbox_toggles( get_page_id() );
            }
        } else {
            showMessage('error', response.data.message || wtcc_split_shipments_params.i18n.error);
        }
    }
    
    function get_page_id() {
        // In the new WC Order screen, the page is identified by the URL.
        // In the classic editor, it's just 'shop_order'.
        const screen = window.pagenow || '';
        if ( screen === 'shop_order' ) {
            return screen;
        }
        // For HPOS, construct a unique ID from the path.
        return window.location.pathname.split('/').pop();
    }

    // --- Event Handlers ---

    // Add new shipment
    $(document).on('click', '#wtcc-add-shipment', function() {
        const container = $('#wtcc-shipments-container');
        const shipmentCount = container.find('.wtcc-shipment-group').length + 1;
        
        const template = wp.template('wtcc-shipment-template');
        const newShipmentHtml = template();
        const newShipment = $(newShipmentHtml);

        newShipment.attr('data-shipment-id', shipmentCount);
        newShipment.find('.wtcc-shipment-title span').text(shipmentCount);
        newShipment.find('input.wtcc-item-select').attr('name', 'wtcc_shipment_' + shipmentCount + '[]');
        
        container.append(newShipment);
    });

    // Remove shipment
    $(document).on('click', '.wtcc-remove-shipment', function() {
        const shipmentGroups = $('.wtcc-shipment-group');
        if (shipmentGroups.length <= 1) {
            showMessage('error', wtcc_split_shipments_params.i18n.at_least_one_shipment);
            return;
        }
        $(this).closest('.wtcc-shipment-group').remove();
        
        // Re-number remaining shipments
        $('.wtcc-shipment-group').each(function(index) {
            const newId = index + 1;
            $(this).attr('data-shipment-id', newId);
            $(this).find('.wtcc-shipment-title').text(wtcc_split_shipments_params.i18n.shipment_title + newId);
            $(this).find('input.wtcc-item-select').attr('name', 'wtcc_shipment_' + newId + '[]');
        });
    });

    // Prevent item from being selected in multiple shipments
    $(document).on('change', '.wtcc-item-select', function() {
        if ($(this).is(':checked')) {
            const itemId = $(this).data('item-id');
            $('.wtcc-item-select[data-item-id=\"' + itemId + '\"]').not(this).prop('checked', false);
        }
    });

    // Save shipments
    $(document).on('click', '#wtcc-save-shipments', function() {
        const button = $(this);
        const orderId = $(wrapper).data('order-id');
        let shipments = [];
        let hasItems = false;

        $('.wtcc-shipment-group').each(function() {
            let items = [];
            $(this).find('input.wtcc-item-select:checked').each(function() {
                items.push($(this).val());
            });
            if (items.length > 0) {
                hasItems = true;
            }
            shipments.push({ items: items });
        });

        if (!hasItems) {
            showMessage('error', wtcc_split_shipments_params.i18n.no_items_in_shipment);
            return;
        }

        showSpinner(button);
        $(button).text(wtcc_split_shipments_params.i18n.saving);

        $.ajax({
            url: wtcc_split_shipments_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wtcc_save_shipments',
                nonce: wtcc_split_shipments_params.nonce,
                order_id: orderId,
                shipments: shipments
            },
            success: function(response) {
                handleAjaxResponse(response, button);
            },
            error: function() {
                hideSpinner(button);
                $(button).text(wtcc_split_shipments_params.i18n.save_shipments);
                showMessage('error', wtcc_split_shipments_params.i18n.error);
            }
        });
    });

    // Reset all shipments
    $(document).on('click', '.wtcc-reset-shipments', function() {
        if (!confirm(wtcc_split_shipments_params.i18n.confirm_reset)) {
            return;
        }
        
        const button = $(this);
        const orderId = $(wrapper).data('order-id');
        showSpinner(button);

        $.ajax({
            url: wtcc_split_shipments_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wtcc_reset_shipments',
                nonce: wtcc_split_shipments_params.nonce,
                order_id: orderId
            },
            success: function(response) {
                handleAjaxResponse(response, button);
            },
            error: function() {
                hideSpinner(button);
                showMessage('error', wtcc_split_shipments_params.i18n.error);
            }
        });
    });

    // Delete a single shipment
    $(document).on('click', '.wtcc-delete-shipment', function() {
        if (!confirm(wtcc_split_shipments_params.i18n.confirm_delete)) {
            return;
        }

        const button = $(this);
        const orderId = $(wrapper).data('order-id');
        const shipmentIndex = button.data('shipment-index');
        showSpinner(button);

        $.ajax({
            url: wtcc_split_shipments_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wtcc_delete_shipment',
                nonce: wtcc_split_shipments_params.nonce,
                order_id: orderId,
                shipment_index: shipmentIndex
            },
            success: function(response) {
                handleAjaxResponse(response, button);
            },
            error: function() {
                hideSpinner(button);
                showMessage('error', wtcc_split_shipments_params.i18n.error);
            }
        });
    });
});
