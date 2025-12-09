jQuery(document).ready(function($) {
    var nonce = wtcc_bulk_manager.nonce;
    var ajaxurl = wtcc_bulk_manager.ajaxurl;

    // Get terms on attribute change
    $('#wtcc_attribute').on('change', function() {
        var attribute = $(this).val();
        var $termSelect = $('#wtcc_term');
        var $previewBtn = $('#wtcc_preview_button');

        console.log('Attribute changed to:', attribute);

        $termSelect.prop('disabled', true).html('<option value="">— ' + wtcc_bulk_manager.i18n.loading + ' —</option>');
        $previewBtn.prop('disabled', true);
        $('#wtcc_preview_card, #wtcc_action_card').hide();

        if (!attribute) {
            $termSelect.html('<option value="">— ' + wtcc_bulk_manager.i18n.select_attribute_first + ' —</option>');
            return;
        }

        console.log('Making AJAX request to get attribute terms for:', attribute);
        console.log('AJAX URL:', ajaxurl);
        console.log('Nonce:', nonce);

        $.post(ajaxurl, {
            action: 'wtcc_get_attribute_terms',
            nonce: nonce,
            attribute: attribute
        }, function(response) {
            console.log('Get attribute terms response:', response);
            if (response.success) {
                $termSelect.html('<option value="">— ' + wtcc_bulk_manager.i18n.select_value + ' —</option>');
                $.each(response.data, function(slug, name) {
                    $termSelect.append($('<option>', { value: slug, text: name }));
                });
                $termSelect.prop('disabled', false);
            } else {
                console.error('Get attribute terms failed:', response);
                $termSelect.html('<option value="">— ' + wtcc_bulk_manager.i18n.no_values_found + ' —</option>');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX request for attribute terms failed:', status, error, xhr.responseText);
            $termSelect.html('<option value="">— Error loading values —</option>');
        });
    });

    $('#wtcc_term').on('change', function() {
        $('#wtcc_preview_button').prop('disabled', !$(this).val());
    });

    // Preview variations
    $('#wtcc_preview_button').on('click', function() {
        var attribute = $('#wtcc_attribute').val();
        var term = $('#wtcc_term').val();
        var $btn = $(this);
        var $spinner = $btn.next('.spinner').addClass('is-active');
        $btn.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'wtcc_preview_variation_changes',
            nonce: nonce,
            attribute: attribute,
            term: term
        }).done(function(response) {
            $spinner.removeClass('is-active');
            $btn.prop('disabled', false);

            console.log('Bulk manager preview response:', response);

            if (response.success) {
                var data = response.data;
                $('#wtcc_preview_count').html('<p><strong>' + data.count + ' ' + wtcc_bulk_manager.i18n.variations_found + '</strong> ' + wtcc_bulk_manager.i18n.showing_preview + '</p>').show();
                
                var $tableBody = $('#wtcc_preview_table tbody');
                $tableBody.empty();
                if (data.preview && data.preview.length) {
                    $.each(data.preview, function(i, item) {
                        var row = '<tr>' +
                            '<td><strong>' + item.parent_name + '</strong><br>' + item.variation + '</td>' +
                            '<td>' + (item.sku || '—') + '</td>' +
                            '<td>' + (item.regular_price || '—') + '</td>' +
                            '<td>' + (item.sale_price || '—') + '</td>' +
                            '<td>' + (item.stock === null ? '∞' : item.stock) + '</td>' +
                            '</tr>';
                        $tableBody.append(row);
                    });
                } else {
                    $tableBody.append('<tr><td colspan="5">' + wtcc_bulk_manager.i18n.no_variations_to_preview + '</td></tr>');
                }
                $('#wtcc_preview_card').show();
                if (data.count > 0) {
                    $('#wtcc_action_card').show();
                }
            } else {
                console.error('Bulk manager preview error:', response);
                alert('Error: ' + (response.data || response.message || 'Unknown error'));
            }
        }).fail(function(xhr, status, error) {
            $spinner.removeClass('is-active');
            $btn.prop('disabled', false);
            console.error('AJAX request failed:', status, error, xhr.responseText);
            alert('Request failed: ' + error);
        });
    });

    // Show/hide action fields
    $('#wtcc_action_type').on('change', function() {
        var action = $(this).val();
        $('.wtcc-action-fields').hide();
        if (action) {
            $('#wtcc_action_' + action).show();
        }
        $('#wtcc_apply_button').prop('disabled', !action);
    });

    // Apply changes
    $('#wtcc_apply_button').on('click', function() {
        if (!confirm(wtcc_bulk_manager.i18n.confirm_apply)) {
            return;
        }

        var $btn = $(this);
        var $spinner = $btn.next('.spinner').addClass('is-active');
        $btn.prop('disabled', true);
        $('#wtcc_results').hide();

        var data = {
            action: 'wtcc_apply_variation_changes',
            nonce: nonce,
            attribute: $('#wtcc_attribute').val(),
            term: $('#wtcc_term').val(),
            action_type: $('#wtcc_action_type').val()
        };

        if (data.action_type === 'price') {
            data.price_type = $('#wtcc_price_type').val();
            data.adjustment_type = $('#wtcc_adjustment_type').val();
            data.amount = $('#wtcc_amount').val();
        } else if (data.action_type === 'stock') {
            data.stock_action = $('#wtcc_stock_action').val();
            data.quantity = $('#wtcc_quantity').val();
        } else if (data.action_type === 'preset') {
            data.preset_key = $('#wtcc_preset_key').val();
        }

        $.post(ajaxurl, data, function(response) {
            $spinner.removeClass('is-active');
            $btn.prop('disabled', false);

            if (response.success) {
                var result = response.data;
                var message = '<div class="notice notice-success inline"><p>' +
                    '<strong>' + wtcc_bulk_manager.i18n.update_complete + '</strong><br>' +
                    result.updated + ' ' + wtcc_bulk_manager.i18n.variations_updated + '<br>' +
                    (result.failed > 0 ? result.failed + ' ' + wtcc_bulk_manager.i18n.failed : '') +
                    '</p></div>';
                $('#wtcc_results').html(message).show();
                // Re-trigger preview to show updated values
                $('#wtcc_preview_button').click();
            } else {
                var errorMsg = '<div class="notice notice-error inline"><p><strong>' + wtcc_bulk_manager.i18n.error + '</strong> ' + response.data + '</p></div>';
                $('#wtcc_results').html(errorMsg).show();
            }
        });
    });
});
