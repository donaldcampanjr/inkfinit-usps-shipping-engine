<?php
/**
 * Inkfinit Shipping - Flat Rate Settings Page
 * Provides a UI for managing USPS Flat Rate boxes and pricing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Flat Rate settings page.
 * This function creates the admin page with all the settings and forms.
 */
function wtcc_render_flat_rate_settings_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( 'Unauthorized' );
    }

    // Require Pro license for this feature.
    if ( function_exists( 'wtcc_require_license_tier' ) && wtcc_require_license_tier( __( 'Flat Rate Boxes', 'wtc-shipping' ), 'pro' ) ) {
        return;
    }

    // The wtcc_save_flat_rate_settings() function from flat-rate-boxes.php handles the saving
    // via admin_init hook - do NOT call it manually to avoid double notices.

    // Display any settings errors (e.g., "Settings saved.")
    settings_errors( 'wtcc_flat_rate' );

    // Retrieve all necessary data for the form
    $all_boxes = wtcc_get_flat_rate_boxes();
    $enabled_boxes = get_option( 'wtcc_enabled_flat_rate_boxes', array_keys( $all_boxes ) );
    $price_overrides = get_option( 'wtcc_flat_rate_price_overrides', array() );
    $pricing_type = wtcc_get_flat_rate_pricing_type();
    $preference = get_option( 'wtcc_flat_rate_preference', 'cheaper' );
    $markup = get_option( 'wtcc_flat_rate_markup', 0 );
    $is_enabled = get_option( 'wtcc_flat_rate_enabled', 'yes' );

    ?>
    <div class="wrap">
        <?php wtcc_admin_header( __( 'USPS Flat Rate Settings', 'wtc-shipping' ) ); ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'wtcc_flat_rate_settings' ); ?>
            <input type="hidden" name="wtcc_save_flat_rate_settings" value="1">

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- Main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="postbox">
                                <h2 class="hndle"><?php echo wtcc_section_heading( __( 'General Settings', 'wtc-shipping' ) ); ?></h2>
                                <div class="inside">
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Flat Rate Shipping', 'wtc-shipping' ); ?></th>
                                            <td>
                                                <select name="flat_rate_enabled">
                                                    <option value="yes" <?php selected( $is_enabled, 'yes' ); ?>><?php esc_html_e( 'Enable', 'wtc-shipping' ); ?></option>
                                                    <option value="no" <?php selected( $is_enabled, 'no' ); ?>><?php esc_html_e( 'Disable', 'wtc-shipping' ); ?></option>
                                                </select>
                                                <p class="description"><?php esc_html_e( 'Enable or disable offering USPS Flat Rate options at checkout.', 'wtc-shipping' ); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Pricing Level', 'wtc-shipping' ); ?></th>
                                            <td>
                                                <select name="flat_rate_pricing_type">
                                                    <option value="retail" <?php selected( $pricing_type, 'retail' ); ?>><?php esc_html_e( 'Retail Pricing', 'wtc-shipping' ); ?></option>
                                                    <option value="commercial" <?php selected( $pricing_type, 'commercial' ); ?>><?php esc_html_e( 'Commercial Pricing', 'wtc-shipping' ); ?></option>
                                                    <option value="business" <?php selected( $pricing_type, 'business' ); ?>><?php esc_html_e( 'Business Pricing', 'wtc-shipping' ); ?></option>
                                                </select>
                                                <p class="description"><?php esc_html_e( 'Select the rate tier to use for flat rate pricing.', 'wtc-shipping' ); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Offer Logic', 'wtc-shipping' ); ?></th>
                                            <td>
                                                <select name="flat_rate_preference">
                                                    <option value="cheaper" <?php selected( $preference, 'cheaper' ); ?>><?php esc_html_e( 'Offer if Cheaper', 'wtc-shipping' ); ?></option>
                                                    <option value="always" <?php selected( $preference, 'always' ); ?>><?php esc_html_e( 'Always Offer', 'wtc-shipping' ); ?></option>
                                                    <option value="never" <?php selected( $preference, 'never' ); ?>><?php esc_html_e( 'Never Offer (Disable)', 'wtc-shipping' ); ?></option>
                                                </select>
                                                <p class="description"><?php esc_html_e( 'When to show flat rate options to the customer.', 'wtc-shipping' ); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Rate Adjustment', 'wtc-shipping' ); ?></th>
                                            <td>
                                                <input type="number" step="0.01" name="flat_rate_markup" value="<?php echo esc_attr( $markup ); ?>" />
                                                <p class="description"><?php esc_html_e( 'Add a fee or discount to all flat rates. Use a negative number for a discount (e.g., -1.50).', 'wtc-shipping' ); ?></p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="postbox">
                                <h2 class="hndle"><?php echo wtcc_section_heading( __( 'Available Flat Rate Boxes & Envelopes', 'wtc-shipping' ) ); ?></h2>
                                <div class="inside">
                                    <p><?php esc_html_e( 'Select which USPS Flat Rate products you want to use for shipping calculations. You can also override the default pricing.', 'wtc-shipping' ); ?></p>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th class="check-column"><?php esc_html_e( 'Enable', 'wtc-shipping' ); ?></th>
                                                <th><?php esc_html_e( 'Box / Envelope', 'wtc-shipping' ); ?></th>
                                                <th><?php esc_html_e( 'Retail Price', 'wtc-shipping' ); ?></th>
                                                <th><?php esc_html_e( 'Commercial Price', 'wtc-shipping' ); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ( $all_boxes as $key => $box ) : 
                                                if (!empty($box['zone_based'])) continue; // Skip regional boxes for now
                                                $is_checked = in_array( $key, $enabled_boxes );
                                                $override_retail = $price_overrides[ $key ]['retail'] ?? '';
                                                $override_commercial = $price_overrides[ $key ]['commercial'] ?? '';
                                            ?>
                                            <tr>
                                                <td><input type="checkbox" name="enabled_boxes[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $is_checked ); ?>></td>
                                                <td>
                                                    <strong><?php echo esc_html( $box['name'] ); ?></strong>
                                                    <div class="description"><?php echo esc_html( $box['length'] . ' x ' . $box['width'] . ' x ' . $box['height'] . ' in' ); ?></div>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="price_override[<?php echo esc_attr( $key ); ?>][retail]" 
                                                           placeholder="<?php echo esc_attr( number_format( $box['price_retail'], 2 ) ); ?>" 
                                                           value="<?php echo esc_attr( $override_retail ); ?>">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="price_override[<?php echo esc_attr( $key ); ?>][commercial]" 
                                                           placeholder="<?php echo esc_attr( number_format( $box['price_commercial'], 2 ) ); ?>" 
                                                           value="<?php echo esc_attr( $override_commercial ); ?>">
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                             <h2 class="hndle"><?php echo wtcc_section_heading( __( 'Save Your Settings', 'wtc-shipping' ) ); ?></h2>
                            <div class="inside">
                                <p><?php esc_html_e('Click the button below to save all your flat rate configurations.', 'wtc-shipping'); ?></p>
                                <?php submit_button( __( 'Save Settings', 'wtc-shipping' ), 'primary', 'submit', false ); ?>
                            </div>
                        </div>
                        <div class="postbox">
                            <h2 class="hndle"><?php echo wtcc_section_heading( __( 'About Flat Rate', 'wtc-shipping' ) ); ?></h2>
                            <div class="inside">
                                <p><?php esc_html_e( 'USPS Priority Mail Flat Rate lets you ship packages up to 70 lbs to any state at the same price.', 'wtc-shipping' ); ?></p>
                                <p><?php esc_html_e( 'This plugin will automatically compare these flat rates against regular calculated rates to find the cheapest option for your customers, based on your settings.', 'wtc-shipping' ); ?></p>
                            </div>
                        </div>
                    </div><!-- /#postbox-container-1 -->
                </div><!-- /#post-body -->
                <br class="clear">
            </div><!-- /#poststuff -->
        </form>
    </div><!-- /.wrap -->
    <?php
}
