<?php
/**
 * Inkfinit Shipping - Setup & Configuration Page
 * Uses simple custom form handler for reliability
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue scripts for the presets page.
 */
function wtcc_shipping_presets_enqueue_assets( $hook ) {
    if ( strpos( $hook, 'wtc-core-shipping-presets' ) === false ) {
        return;
    }

    wp_enqueue_script(
        'wtc-admin-presets-script',
        plugin_dir_url( __FILE__ ) . '../assets/admin-presets.js',
        array( 'jquery' ),
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/admin-presets.js' ),
        true
    );

    $config = wtcc_shipping_get_rates_config();
    $groups = wtcc_shipping_get_groups();

    wp_localize_script(
        'wtc-admin-presets-script',
        'wtc_presets_data',
        array(
            'config' => $config,
            'groups' => $groups,
        )
    );
}
add_action( 'admin_enqueue_scripts', 'wtcc_shipping_presets_enqueue_assets' );

/**
 * Handle form save on admin_init
 */
add_action( 'admin_init', 'wtcc_handle_presets_save' );
function wtcc_handle_presets_save() {
    if ( ! isset( $_POST['wtcc_save_presets_settings'] ) ) {
        return;
    }
    
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'wtcc_presets_settings' ) ) {
        return;
    }
    
    // Save enabled methods
    $enabled_methods = array();
    if ( isset( $_POST['wtc_enabled_methods'] ) && is_array( $_POST['wtc_enabled_methods'] ) ) {
        foreach ( $_POST['wtc_enabled_methods'] as $method => $value ) {
            $enabled_methods[ sanitize_key( $method ) ] = 1;
        }
    }
    update_option( 'wtc_enabled_methods', $enabled_methods );
    
    // Save rates config
    $config = get_option( 'wtcc_shipping_rates_config', array() );
    if ( isset( $_POST['wtcc_shipping_rates_config'] ) && is_array( $_POST['wtcc_shipping_rates_config'] ) ) {
        foreach ( $_POST['wtcc_shipping_rates_config'] as $group_key => $group_rates ) {
            $group_key = sanitize_key( $group_key );
            if ( $group_key === 'zone_multipliers' ) {
                foreach ( $group_rates as $zone_key => $multiplier ) {
                    $config['zone_multipliers'][ sanitize_key( $zone_key ) ] = floatval( $multiplier );
                }
            } else {
                $config[ $group_key ]['base_cost'] = floatval( $group_rates['base_cost'] ?? 0 );
                $config[ $group_key ]['per_oz'] = floatval( $group_rates['per_oz'] ?? 0 );
                $config[ $group_key ]['max_weight'] = intval( $group_rates['max_weight'] ?? 70 );
            }
        }
    }
    update_option( 'wtcc_shipping_rates_config', $config );
    
    // Save label overrides
    $labels = array();
    if ( isset( $_POST['wtc_label_overrides'] ) && is_array( $_POST['wtc_label_overrides'] ) ) {
        foreach ( $_POST['wtc_label_overrides'] as $key => $label ) {
            $label = sanitize_text_field( trim( $label ) );
            if ( ! empty( $label ) ) {
                $labels[ sanitize_key( $key ) ] = $label;
            }
        }
    }
    update_option( 'wtc_label_overrides', $labels );
    
    // Save shipping class dimensions (to term meta)
    if ( isset( $_POST['wtcc_shipping_class_data'] ) && is_array( $_POST['wtcc_shipping_class_data'] ) ) {
        foreach ( $_POST['wtcc_shipping_class_data'] as $class_slug => $dimensions ) {
            $term = get_term_by( 'slug', sanitize_key( $class_slug ), 'product_shipping_class' );
            if ( $term && ! is_wp_error( $term ) && function_exists( 'wtcc_update_shipping_class_data' ) ) {
                $data = array(
                    'length'           => floatval( $dimensions['length'] ?? 0 ),
                    'width'            => floatval( $dimensions['width'] ?? 0 ),
                    'height'           => floatval( $dimensions['height'] ?? 0 ),
                    'dimensions_unit'  => 'in',
                    'max_weight'       => floatval( $dimensions['max_weight'] ?? 0 ),
                );
                wtcc_update_shipping_class_data( $term->term_id, $data );
            }
        }
    }
    
    add_settings_error( 'wtcc_presets', 'saved', __( 'Settings saved.', 'wtc-shipping' ), 'success' );
}

/**
 * Render the settings page
 */
function wtcc_shipping_presets_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'wtc-shipping' ) );
	}
    
    $groups = wtcc_shipping_get_groups();
    $enabled_methods = get_option( 'wtc_enabled_methods', array( 'ground' => 1, 'priority' => 1, 'express' => 1 ) );
    $config = wtcc_shipping_get_rates_config();
    $label_overrides = get_option( 'wtc_label_overrides', array() );
    $zone_labels = wtcc_get_zone_labels();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Setup & Configuration', 'wtc-shipping' ); ?></h1>

		<?php settings_errors( 'wtcc_presets' ); ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'wtcc_presets_settings' ); ?>
            <input type="hidden" name="wtcc_save_presets_settings" value="1">

            <h2><?php esc_html_e( 'Shipping Methods', 'wtc-shipping' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enabled Methods', 'wtc-shipping' ); ?></th>
                    <td>
                        <fieldset>
                            <?php foreach ( $groups as $group_key => $group ) : 
                                $is_enabled = isset( $enabled_methods[ $group_key ] ) ? $enabled_methods[ $group_key ] : 1;
                            ?>
                            <p>
                                <label>
                                    <input type="checkbox" name="wtc_enabled_methods[<?php echo esc_attr( $group_key ); ?>]" value="1" <?php checked( $is_enabled, 1 ); ?>>
                                    <strong><?php echo esc_html( $group['label'] ); ?></strong>
                                    <span class="description"> &mdash; <?php echo esc_html( $group['description'] ); ?></span>
                                </label>
                            </p>
                            <?php endforeach; ?>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Base Rates', 'wtc-shipping' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Set the base price and per-ounce cost for each method:', 'wtc-shipping' ); ?></p>
            <table class="widefat striped" style="max-width: 800px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Method', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Base Price ($)', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Per Ounce ($)', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Max Weight (lb)', 'wtc-shipping' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $groups as $group_key => $group ) : 
                    $group_config = $config[ $group_key ] ?? array();
                    $base_cost = $group_config['base_cost'] ?? 5.00;
                    $per_oz = $group_config['per_oz'] ?? 0.10;
                    $max_weight = $group_config['max_weight'] ?? 70;
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $group['label'] ); ?></strong></td>
                        <td>
                            <input type="number" step="0.01" min="0" max="999"
                                name="wtcc_shipping_rates_config[<?php echo esc_attr( $group_key ); ?>][base_cost]" 
                                value="<?php echo esc_attr( $base_cost ); ?>" 
                                class="small-text">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="50"
                                name="wtcc_shipping_rates_config[<?php echo esc_attr( $group_key ); ?>][per_oz]" 
                                value="<?php echo esc_attr( $per_oz ); ?>" 
                                class="small-text">
                        </td>
                        <td>
                            <input type="number" step="1" min="1" max="500"
                                name="wtcc_shipping_rates_config[<?php echo esc_attr( $group_key ); ?>][max_weight]" 
                                value="<?php echo esc_attr( $max_weight ); ?>" 
                                class="small-text">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'International Zone Multipliers', 'wtc-shipping' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Adjust rates for international shipping (1.0 = base rate):', 'wtc-shipping' ); ?></p>
            <table class="widefat striped" style="max-width: 400px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Zone', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Multiplier', 'wtc-shipping' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $zone_labels as $zone_key => $zone_label ) :
                    $multiplier = $config['zone_multipliers'][ $zone_key ] ?? 1.0;
                ?>
                    <tr>
                        <td><?php echo esc_html( $zone_label ); ?></td>
                        <td>
                            <input type="number" step="0.1" min="0.5" max="10"
                                name="wtcc_shipping_rates_config[zone_multipliers][<?php echo esc_attr( $zone_key ); ?>]" 
                                value="<?php echo esc_attr( $multiplier ); ?>" 
                                class="small-text">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php 
            // Shipping Class Dimensions section
            $presets = function_exists( 'wtcc_shipping_get_presets' ) ? wtcc_shipping_get_presets() : array();
            $has_presets = false;
            foreach ( $presets as $preset ) {
                if ( isset( $preset['class'] ) ) {
                    $term = get_term_by( 'slug', $preset['class'], 'product_shipping_class' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $has_presets = true;
                        break;
                    }
                }
            }
            
            if ( $has_presets ) : ?>
            <h2><?php esc_html_e( 'Shipping Class Dimensions', 'wtc-shipping' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Define package dimensions and maximum weight for each shipping class:', 'wtc-shipping' ); ?></p>
            <table class="widefat striped" style="max-width: 800px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Shipping Class', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Length (in)', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Width (in)', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Height (in)', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Max Weight (oz)', 'wtc-shipping' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                foreach ( $presets as $preset_key => $preset ) : 
                    if ( ! isset( $preset['class'] ) ) continue;
                    
                    $term = get_term_by( 'slug', $preset['class'], 'product_shipping_class' );
                    if ( ! $term || is_wp_error( $term ) ) continue;
                    
                    $class_data = function_exists( 'wtcc_get_shipping_class_data' ) ? wtcc_get_shipping_class_data( $term->term_id ) : array();
                    $length = $class_data['length'] ?? $preset['length'] ?? 0;
                    $width = $class_data['width'] ?? $preset['width'] ?? 0;
                    $height = $class_data['height'] ?? $preset['height'] ?? 0;
                    $max_weight = $class_data['max_weight'] ?? $preset['max_weight'] ?? 0;
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $preset['class_label'] ?? $preset['label'] ); ?></strong></td>
                        <td>
                            <input type="number" step="0.1" min="0"
                                name="wtcc_shipping_class_data[<?php echo esc_attr( $preset['class'] ); ?>][length]" 
                                value="<?php echo esc_attr( $length ); ?>" 
                                class="small-text">
                        </td>
                        <td>
                            <input type="number" step="0.1" min="0"
                                name="wtcc_shipping_class_data[<?php echo esc_attr( $preset['class'] ); ?>][width]" 
                                value="<?php echo esc_attr( $width ); ?>" 
                                class="small-text">
                        </td>
                        <td>
                            <input type="number" step="0.1" min="0"
                                name="wtcc_shipping_class_data[<?php echo esc_attr( $preset['class'] ); ?>][height]" 
                                value="<?php echo esc_attr( $height ); ?>" 
                                class="small-text">
                        </td>
                        <td>
                            <input type="number" step="0.1" min="0"
                                name="wtcc_shipping_class_data[<?php echo esc_attr( $preset['class'] ); ?>][max_weight]" 
                                value="<?php echo esc_attr( $max_weight ); ?>" 
                                class="small-text">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Custom Labels', 'wtc-shipping' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Customize how shipping options appear at checkout (leave blank for default):', 'wtc-shipping' ); ?></p>
            <table class="widefat striped" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Method', 'wtc-shipping' ); ?></th>
                        <th><?php esc_html_e( 'Custom Label', 'wtc-shipping' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $groups as $group_key => $group ) : 
                    $custom_label = $label_overrides[ $group_key ] ?? '';
                ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $group['label'] ); ?></strong>
                            <br><span class="description"><?php printf( esc_html__( 'Default: %s', 'wtc-shipping' ), esc_html( $group['ux_label'] ) ); ?></span>
                        </td>
                        <td>
                            <input type="text"
                                name="wtc_label_overrides[<?php echo esc_attr( $group_key ); ?>]" 
                                value="<?php echo esc_attr( $custom_label ); ?>" 
                                placeholder="<?php echo esc_attr( $group['ux_label'] ); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Get zone labels
 */
function wtcc_get_zone_labels() {
    return array(
        'usa'           => __( 'United States', 'wtc-shipping' ),
        'canada'        => __( 'Canada', 'wtc-shipping' ),
        'uk'            => __( 'United Kingdom', 'wtc-shipping' ),
        'eu1'           => __( 'Western Europe', 'wtc-shipping' ),
        'eu2'           => __( 'Eastern Europe', 'wtc-shipping' ),
        'apac'          => __( 'Australia/NZ', 'wtc-shipping' ),
        'asia'          => __( 'Asia', 'wtc-shipping' ),
        'south-america' => __( 'South America', 'wtc-shipping' ),
        'middle-east'   => __( 'Middle East', 'wtc-shipping' ),
        'africa'        => __( 'Africa', 'wtc-shipping' ),
        'rest-of-world' => __( 'Rest of World', 'wtc-shipping' ),
    );
}
