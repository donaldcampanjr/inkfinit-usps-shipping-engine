<?php
/**
 * Admin Page: Box Inventory Management
 * 
 * Allows admin to customize available shipping boxes/packaging
 * 
 * @package WTC_Shipping_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the box inventory page
 */
function wtcc_shipping_render_box_inventory_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('Access denied', 'wtc-shipping'));
    }
    
    // Require Pro license for this feature.
    if ( function_exists( 'wtcc_require_license_tier' ) && wtcc_require_license_tier( __( 'Box Inventory', 'wtc-shipping' ), 'pro' ) ) {
        return;
    }
    
    $custom_boxes = get_option('wtcc_shipping_custom_boxes', array());
    $enabled_defaults = get_option('wtcc_shipping_enabled_default_boxes', array_keys(wtcc_shipping_get_default_boxes()));
    $default_boxes = wtcc_shipping_get_default_boxes();
    
    // Handle ADD box form submission
    if (isset($_POST['wtc_add_box']) && wp_verify_nonce($_POST['wtc_add_box_nonce_field'] ?? '', 'wtc_add_box_nonce')) {
        $box_name = sanitize_text_field($_POST['box_name'] ?? '');
        if (!empty($box_name)) {
            $box_key = sanitize_key($box_name);
            if (empty($box_key)) {
                $box_key = 'custom_' . time();
            }
            $custom_boxes[$box_key] = array(
                'name'        => $box_name,
                'length'      => max(0.1, floatval($_POST['box_length'] ?? 6)),
                'width'       => max(0.1, floatval($_POST['box_width'] ?? 6)),
                'height'      => max(0.1, floatval($_POST['box_height'] ?? 4)),
                'max_weight'  => max(1, floatval($_POST['box_weight'] ?? 320)),
                'tare_weight' => 4,
                'type'        => in_array($_POST['box_type'] ?? 'rigid', array('soft', 'rigid')) ? $_POST['box_type'] : 'rigid',
                'units'       => sanitize_key($_POST['box_units'] ?? 'in'),
                'weight_units'=> sanitize_key($_POST['box_weight_units'] ?? 'oz'),
            );
            update_option('wtcc_shipping_custom_boxes', $custom_boxes);
            add_settings_error('wtcc_boxes', 'added', __('Box added successfully.', 'wtc-shipping'), 'success');
        }
    }
    
    // Handle DELETE boxes form submission
    if (isset($_POST['wtc_delete_boxes']) && wp_verify_nonce($_POST['wtc_delete_boxes_nonce_field'] ?? '', 'wtc_delete_boxes_nonce')) {
        $to_delete = isset($_POST['box_indices']) ? array_map('sanitize_key', $_POST['box_indices']) : array();
        if (!empty($to_delete)) {
            foreach ($to_delete as $key) {
                // Remove from custom boxes
                if (isset($custom_boxes[$key])) {
                    unset($custom_boxes[$key]);
                }
                // Remove from enabled defaults
                $enabled_defaults = array_diff($enabled_defaults, array($key));
            }
            update_option('wtcc_shipping_custom_boxes', $custom_boxes);
            update_option('wtcc_shipping_enabled_default_boxes', array_values($enabled_defaults));
            add_settings_error('wtcc_boxes', 'deleted', __('Selected boxes deleted.', 'wtc-shipping'), 'success');
        }
    }
    
    ?>
    <div class="wrap">
        <?php wtcc_admin_header(__( 'Box Inventory', 'wtc-shipping' )); ?>
        <?php settings_errors( 'wtcc_boxes' ); ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Main content -->
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <div class="postbox">
                            <h2 class="hndle"><?php echo wtcc_section_heading( __( 'Add a New Box', 'wtc-shipping' ) ); ?></h2>
                            <div class="inside">
                                <form method="post" action="">
                                    <?php wp_nonce_field('wtc_add_box_nonce', 'wtc_add_box_nonce_field'); ?>
                                    <table class="form-table">
                                        <!-- Form fields for adding a new box -->
                                        <tr valign="top">
                                            <th scope="row"><?php esc_html_e('Box Name', 'wtc-shipping'); ?></th>
                                            <td><input type="text" name="box_name" required /></td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php esc_html_e('Dimensions (L x W x H)', 'wtc-shipping'); ?></th>
                                            <td>
                                                <input type="number" step="any" min="0" name="box_length" placeholder="<?php esc_attr_e('L', 'wtc-shipping'); ?>" required class="small-text" /> x
                                                <input type="number" step="any" min="0" name="box_width" placeholder="<?php esc_attr_e('W', 'wtc-shipping'); ?>" required class="small-text" /> x
                                                <input type="number" step="any" min="0" name="box_height" placeholder="<?php esc_attr_e('H', 'wtc-shipping'); ?>" required class="small-text" />
                                                <select name="box_units">
                                                    <option value="in"><?php esc_html_e('in', 'wtc-shipping'); ?></option>
                                                    <option value="cm"><?php esc_html_e('cm', 'wtc-shipping'); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php esc_html_e('Max Weight', 'wtc-shipping'); ?></th>
                                            <td>
                                                <input type="number" step="any" min="0" name="box_weight" placeholder="<?php esc_attr_e('Weight', 'wtc-shipping'); ?>" required class="small-text" />
                                                <select name="box_weight_units">
                                                    <option value="lbs"><?php esc_html_e('lbs', 'wtc-shipping'); ?></option>
                                                    <option value="oz"><?php esc_html_e('oz', 'wtc-shipping'); ?></option>
                                                    <option value="kg"><?php esc_html_e('kg', 'wtc-shipping'); ?></option>
                                                    <option value="g"><?php esc_html_e('g', 'wtc-shipping'); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <th scope="row"><?php esc_html_e('Box Type', 'wtc-shipping'); ?></th>
                                            <td>
                                                <select name="box_type">
                                                    <option value="rigid"><?php esc_html_e('Rigid (Box)', 'wtc-shipping'); ?></option>
                                                    <option value="soft"><?php esc_html_e('Soft (Mailer)', 'wtc-shipping'); ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php submit_button(__('Add Box', 'wtc-shipping'), 'primary', 'wtc_add_box'); ?>
                                </form>
                            </div>
                        </div>

                        <div class="postbox">
                            <h2 class="hndle"><?php echo wtcc_section_heading( __( 'Your Box Inventory', 'wtc-shipping' ) ); ?></h2>
                            <div class="inside">
                                <form method="post" action="">
                                    <?php
                                    // Merge custom boxes and enabled default boxes
                                    $boxes = $custom_boxes;
                                    foreach ($enabled_defaults as $key) {
                                        if (isset($default_boxes[$key])) {
                                            $boxes[$key] = $default_boxes[$key];
                                        }
                                    }
                                    if (!empty($boxes)) {
                                        ?>
                                        <table class="wp-list-table widefat fixed striped">
                                            <thead>
                                                <tr>
                                                    <th class="check-column"><input type="checkbox" id="wtc-select-all" /></th>
                                                    <th><?php esc_html_e('Name', 'wtc-shipping'); ?></th>
                                                    <th><?php esc_html_e('Dimensions (L x W x H)', 'wtc-shipping'); ?></th>
                                                    <th><?php esc_html_e('Max Weight', 'wtc-shipping'); ?></th>
                                                    <th><?php esc_html_e('Type', 'wtc-shipping'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach ($boxes as $index => $box) {
                                                    ?>
                                                    <tr>
                                                        <td><input type="checkbox" name="box_indices[]" value="<?php echo esc_attr($index); ?>" /></td>
                                                        <td><?php echo esc_html($box['name']); ?></td>
                                                        <td><?php echo esc_html(sprintf('%s x %s x %s %s', $box['length'], $box['width'], $box['height'], $box['units'] ?? 'in')); ?></td>
                                                        <td><?php echo esc_html(sprintf('%s %s', $box['max_weight'], $box['weight_units'] ?? 'oz')); ?></td>
                                                        <td><?php echo esc_html(ucfirst($box['type'])); ?></td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                        <br>
                                        <?php wp_nonce_field('wtc_delete_boxes_nonce', 'wtc_delete_boxes_nonce_field'); ?>
                                        <?php submit_button(__('Delete Selected', 'wtc-shipping'), 'delete', 'wtc_delete_boxes', false); ?>
                                        <?php
                                    } else {
                                        echo '<p>' . esc_html__('You haven\'t added any boxes yet.', 'wtc-shipping') . '</p>';
                                    }
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><?php echo wtcc_section_heading( __( 'Box Types', 'wtc-shipping' ) ); ?></h2>
                        <div class="inside">
                            <ul class="ul-disc">
                                <li><strong><?php esc_html_e('Rigid:', 'wtc-shipping'); ?></strong> <?php esc_html_e('Cardboard boxes - good for fragile items, can stack heavy products.', 'wtc-shipping'); ?></li>
                                <li><strong><?php esc_html_e('Soft:', 'wtc-shipping'); ?></strong> <?php esc_html_e('Poly mailers, bubble mailers - good for clothing, lightweight items.', 'wtc-shipping'); ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h2 class="hndle"><?php echo wtcc_section_heading( __( 'How Box Packing Works', 'wtc-shipping' ) ); ?></h2>
                        <div class="inside">
                            <ol>
                                <li><?php esc_html_e('Products are sorted by volume (largest first).', 'wtc-shipping'); ?></li>
                                <li><?php esc_html_e('Each product is placed in the smallest available box that fits.', 'wtc-shipping'); ?></li>
                                <li><?php esc_html_e('Multiple items are combined into the same box if they fit together.', 'wtc-shipping'); ?></li>
                                <li><?php esc_html_e('If items don\'t fit in one box, multiple packages are created.', 'wtc-shipping'); ?></li>
                                <li><?php esc_html_e('Shipping cost is calculated for each package and then summed up.', 'wtc-shipping'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /#postbox-container-1 -->
            </div><!-- /#post-body -->
            <br class="clear">
        </div><!-- /#poststuff -->
    </div><!-- /.wrap -->
    <?php
}

/**
 * Get default boxes (without customization)
 */
function wtcc_shipping_get_default_boxes() {
    return array(
        'poly_mailer_small' => array(
            'name'        => 'Poly Mailer (Small)',
            'length'      => 10,
            'width'       => 13,
            'height'      => 2,
            'max_weight'  => 160,
            'tare_weight' => 0.5,
            'type'        => 'soft',
            'units'       => 'in',
            'weight_units'=> 'oz',
        ),
        'poly_mailer_large' => array(
            'name'        => 'Poly Mailer (Large)',
            'length'      => 14.5,
            'width'       => 19,
            'height'      => 3,
            'max_weight'  => 160,
            'tare_weight' => 1,
            'type'        => 'soft',
            'units'       => 'in',
            'weight_units'=> 'oz',
        ),
        'box_small' => array(
            'name'        => 'Small Box',
            'length'      => 8,
            'width'       => 6,
            'height'      => 4,
            'max_weight'  => 320,
            'tare_weight' => 4,
            'type'        => 'rigid',
            'units'       => 'in',
            'weight_units'=> 'oz',
        ),
        'box_medium' => array(
            'name'        => 'Medium Box',
            'length'      => 12,
            'width'       => 10,
            'height'      => 8,
            'max_weight'  => 480,
            'tare_weight' => 8,
            'type'        => 'rigid',
            'units'       => 'in',
            'weight_units'=> 'oz',
        ),
        'box_large' => array(
            'name'        => 'Large Box',
            'length'      => 18,
            'width'       => 14,
            'height'      => 12,
            'max_weight'  => 800,
            'tare_weight' => 16,
            'type'        => 'rigid',
            'units'       => 'in',
            'weight_units'=> 'oz',
        ),
        'box_xl' => array(
            'name'        => 'Extra Large Box',
            'length'      => 24,
            'width'       => 18,
            'height'      => 18,
            'max_weight'  => 1120,
            'tare_weight'=> 24,
            'type'       => 'rigid',
            'units'       => 'in',
            'weight_units'=> 'oz',
        ),
    );
}
