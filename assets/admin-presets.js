jQuery(function($) {
    if (typeof wtc_presets_data === 'undefined') {
        return;
    }

    var config = wtc_presets_data.config;
    var groups = wtc_presets_data.groups;

    $('#calc_button').on('click', function() {
        var weight = parseFloat($('#calc_weight').val()) || 10;
        var zone = $('#calc_zone').val();
        var multiplier = (config.zone_multipliers && config.zone_multipliers[zone]) || 1;
        
        var html = '<table class="wp-list-table widefat striped">';
        $.each(groups, function(key, group) {
            if (!$('input[name="enabled_methods[' + key + ']"]').is(':checked')) {
                return;
            }
            var gc = config[key] || {};
            var base = parseFloat(gc.base_cost) || 0;
            var perOz = parseFloat(gc.per_oz) || 0;
            var cost = (base + (weight * perOz)) * multiplier;
            html += '<tr><td>' + group.label + '</td><td>$' + cost.toFixed(2) + '</td></tr>';
        });
        html += '</table>';
        
        $('#calc_results').html(html).slideDown();
    });
});
