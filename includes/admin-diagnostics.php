<?php
/**
 * Admin Dashboard & Diagnostics
 * Native WordPress Admin UI
 * 
 * @package WTC_Shipping_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard page
 * 
 * Shows different content based on license tier:
 * - FREE: Upgrade prompts, calculator link, basic stats
 * - PRO+: Full dashboard with all features
 */
function wtcc_shipping_dashboard() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Unauthorized' );
	}

	// Check license status
	$is_licensed = function_exists( 'wtcc_is_pro' ) && wtcc_is_pro();
	$edition = function_exists( 'wtcc_get_edition' ) ? wtcc_get_edition() : 'free';
	$edition_label = ucfirst( $edition );

	// Gather stats
	$options = function_exists( 'wtcc_get_usps_api_options' ) ? wtcc_get_usps_api_options() : array();
	$is_configured = ! empty( $options['consumer_key'] ) && ! empty( $options['consumer_secret'] );
	$zones_count = 0;
	$methods_count = 0;
	$enabled_methods = 0;

	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		$zones = WC_Shipping_Zones::get_zones();
		$zones_count = count( $zones );
		foreach ( $zones as $zone ) {
			if ( isset( $zone['shipping_methods'] ) ) {
				$methods_count += count( $zone['shipping_methods'] );
				foreach ( $zone['shipping_methods'] as $method ) {
					if ( $method->is_enabled() ) {
						$enabled_methods++;
					}
				}
			}
		}
	}

	// Product stats
	$products_with_weight = 0;
	$products_with_dimensions = 0;
	$products_total = 0;
	$products = wc_get_products( array( 'limit' => -1, 'status' => 'publish' ) );
	if ( $products ) {
		$products_total = count( $products );
		foreach ( $products as $product ) {
			if ( $product->get_weight() > 0 ) {
				$products_with_weight++;
			}
			if ( $product->get_length() > 0 && $product->get_width() > 0 && $product->get_height() > 0 ) {
				$products_with_dimensions++;
			}
		}
	}

	// Recent orders
	$recent_orders = wc_get_orders( array(
		'limit' => 5,
		'orderby' => 'date',
		'order' => 'DESC',
		'status' => array( 'processing', 'completed', 'on-hold' ),
	) );

	// Box count
	$boxes = get_option( 'wtcc_shipping_boxes', array() );
	$box_count = is_array( $boxes ) ? count( $boxes ) : 0;

	// Flat rate
	$flat_rate_enabled = get_option( 'wtcc_enable_flat_rate_boxes', 'no' ) === 'yes';

	// Progress percentages
	$weight_pct = $products_total > 0 ? round( ( $products_with_weight / $products_total ) * 100 ) : 0;
	$dim_pct = $products_total > 0 ? round( ( $products_with_dimensions / $products_total ) * 100 ) : 0;
	$weight_class = $weight_pct >= 80 ? 'success' : ( $weight_pct >= 50 ? 'warning' : 'error' );
	$dim_class = $dim_pct >= 80 ? 'success' : ( $dim_pct >= 50 ? 'warning' : 'error' );

	// Enqueue drag-and-drop script for dashboard widgets
	add_action('admin_footer', function() {
		echo '<script src="' . esc_url( plugins_url( '../assets/admin-dashboard-drag.js', __FILE__ ) ) . '"></script>';
	});
	?>
	<div class="wrap">
		<?php wtcc_admin_header(__( 'Dashboard', 'wtc-shipping' )); ?>

		<?php if ( ! $is_licensed ) : ?>
		<!-- FREE EDITION DASHBOARD -->
		<div class="notice notice-info" style="margin: 15px 0; padding: 15px 20px; border-left-color: #2271b1;">
			<h3 style="margin-top: 0;">📦 <?php esc_html_e( 'Welcome to Inkfinit USPS Shipping Calculator', 'wtc-shipping' ); ?></h3>
			<p><?php esc_html_e( 'You are using the Free Edition. Use the rate calculator to estimate USPS shipping costs.', 'wtc-shipping' ); ?></p>
			<p><strong><?php esc_html_e( 'Upgrade to Pro to unlock:', 'wtc-shipping' ); ?></strong></p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Live USPS API rates at checkout', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Print shipping labels directly from WooCommerce', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Customer tracking notifications', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Box packing optimization', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Shipping presets and rules', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Flat rate box support', 'wtc-shipping' ); ?></li>
				<li><?php esc_html_e( 'Priority support', 'wtc-shipping' ); ?></li>
			</ul>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Activate License', 'wtc-shipping' ); ?>
				</a>
				<a href="https://inkfinit.pro/plugins/usps-shipping/" target="_blank" class="button button-secondary" style="margin-left: 10px;">
					<?php esc_html_e( 'Get Pro License', 'wtc-shipping' ); ?>
				</a>
			</p>
		</div>

		<div id="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder">
				<div id="postbox-container-1" class="postbox-container" style="width: 100%;">
					<div class="meta-box-sortables">
						<!-- Calculator Card -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( '📦 Rate Calculator', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<p><?php esc_html_e( 'Use the built-in rate calculator to estimate USPS shipping costs for any package.', 'wtc-shipping' ); ?></p>
								<p>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-simple-calculator' ) ); ?>" class="button button-primary button-hero">
										<span class="dashicons dashicons-calculator" style="margin-top: 5px;"></span> <?php esc_html_e( 'Open Calculator', 'wtc-shipping' ); ?>
									</a>
								</p>
							</div>
						</div>

						<!-- Free Features -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( '✅ Free Edition Features', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<ul class="wtcc-stats-list">
									<li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'USPS Rate Calculator', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'Ground Advantage, Priority Mail, Express estimates', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'Zone-based pricing', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'User Guide', 'wtc-shipping' ); ?></li>
								</ul>
							</div>
						</div>

						<!-- Locked Features -->
						<div class="postbox" style="opacity: 0.7;">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( '🔒 Pro Features (Locked)', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<ul class="wtcc-stats-list">
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'USPS API Integration', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'Live Rates at Checkout', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'Label Printing', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'Package Tracking', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'Box Inventory', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'Shipping Presets', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'Flat Rate Boxes', 'wtc-shipping' ); ?></li>
									<li><span class="dashicons dashicons-lock" style="color: #b32d2e;"></span> <?php esc_html_e( 'Diagnostics', 'wtc-shipping' ); ?></li>
								</ul>
								<p>
									<a href="https://inkfinit.pro/plugins/usps-shipping/" target="_blank" class="button">
										<?php esc_html_e( 'Unlock All Features →', 'wtc-shipping' ); ?>
									</a>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php else : ?>
		<!-- PRO/PREMIUM/ENTERPRISE DASHBOARD -->
		<div id="dashboard-widgets-wrap">
			<div id="dashboard-widgets" class="metabox-holder">
				<!-- Left Column -->
				<div id="postbox-container-1" class="postbox-container">
					<div class="meta-box-sortables">
						<!-- Edition Badge -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( '🎉 License Active', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<p><strong><?php printf( esc_html__( 'Edition: %s', 'wtc-shipping' ), esc_html( $edition_label ) ); ?></strong></p>
								<p class="description"><?php esc_html_e( 'All features are unlocked. Thank you for supporting Inkfinit!', 'wtc-shipping' ); ?></p>
							</div>
						</div>

						<!-- At a Glance -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( 'At a Glance', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<ul class="wtcc-stats-list">
									<li>
										<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">
											<span class="dashicons dashicons-products"></span>
											<?php echo esc_html( $products_total ); ?> Products
										</a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-shipping-boxes' ) ); ?>">
											<span class="dashicons dashicons-archive"></span>
											<?php echo esc_html( $box_count ); ?> Shipping Boxes
										</a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>">
											<span class="dashicons dashicons-admin-site"></span>
											<?php echo esc_html( $zones_count ); ?> Shipping Zones
										</a>
									</li>
									<li>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>">
											<span class="dashicons dashicons-car"></span>
											<?php echo esc_html( $enabled_methods ); ?> / <?php echo esc_html( $methods_count ); ?> Methods Active
										</a>
									</li>
								</ul>
								<p class="description">
									<?php if ( $is_configured ) : ?>
										<span class="dashicons dashicons-yes-alt wtcc-status-success"></span> <strong><?php esc_html_e( 'USPS API Connected', 'wtc-shipping' ); ?></strong>
									<?php else : ?>
										<span class="dashicons dashicons-warning wtcc-status-warning"></span> <strong><?php esc_html_e( 'USPS API Not Configured', 'wtc-shipping' ); ?></strong>
									<?php endif; ?>
									<?php if ( $flat_rate_enabled ) : ?>
										&nbsp;&bull;&nbsp; <span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Flat Rate Active', 'wtc-shipping' ); ?>
									<?php endif; ?>
								</p>
							</div>
						</div>

						<!-- Quick Actions -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Quick Actions', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<ul class="wtcc-link-list">
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-usps-api' ) ); ?>"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Configure USPS API', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-shipping-boxes' ) ); ?>"><span class="dashicons dashicons-archive"></span> <?php esc_html_e( 'Manage Box Inventory', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-flat-rate' ) ); ?>"><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Configure Flat Rate', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets' ) ); ?>"><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Setup & Config', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-simple-calculator' ) ); ?>"><span class="dashicons dashicons-calculator"></span> <?php esc_html_e( 'Rate Calculator', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-diagnostics' ) ); ?>"><span class="dashicons dashicons-search"></span> <?php esc_html_e( 'Run Diagnostics', 'wtc-shipping' ); ?></a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>

				<!-- Right Column -->
				<div id="postbox-container-2" class="postbox-container">
					<div class="meta-box-sortables">
						<!-- Product Health -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Product Shipping Health', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<p><strong><?php esc_html_e( 'Weight Data', 'wtc-shipping' ); ?></strong> &mdash; <?php echo esc_html( $products_with_weight ); ?> / <?php echo esc_html( $products_total ); ?> (<?php echo esc_html( $weight_pct ); ?>%)</p>
								<div class="progress-bar">
									<div class="progress-bar-fill <?php echo esc_attr( $weight_class ); ?>" style="width: <?php echo esc_attr( $weight_pct ); ?>%;"></div>
								</div>
								<br>
								<p><strong><?php esc_html_e( 'Dimension Data', 'wtc-shipping' ); ?></strong> &mdash; <?php echo esc_html( $products_with_dimensions ); ?> / <?php echo esc_html( $products_total ); ?> (<?php echo esc_html( $dim_pct ); ?>%)</p>
								<div class="progress-bar">
									<div class="progress-bar-fill <?php echo esc_attr( $dim_class ); ?>" style="width: <?php echo esc_attr( $dim_pct ); ?>%;"></div>
								</div>
								<?php if ( $weight_pct < 100 || $dim_pct < 100 ) : ?>
								<p class="description"><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Complete product data = more accurate USPS rates.', 'wtc-shipping' ); ?></p>
								<?php endif; ?>
							</div>
						</div>

						<!-- Recent Orders -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Recent Orders', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<?php if ( ! empty( $recent_orders ) ) : ?>
								<table class="wp-list-table widefat striped">
									<tbody>
										<?php foreach ( $recent_orders as $order ) : 
											$shipping_method = '';
											foreach ( $order->get_shipping_methods() as $method ) {
												$shipping_method = $method->get_method_title();
												break;
											}
										?>
										<tr>
											<td>
												<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>"><strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong></a>
												<br><span class="description"><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></span>
											</td>
											<td class="description">
												<?php if ( $shipping_method ) : ?>
													<span class="dashicons dashicons-car"></span> <?php echo esc_html( $shipping_method ); ?>
												<?php endif; ?>
												<br><?php echo esc_html( human_time_diff( $order->get_date_created()->getTimestamp(), time() ) ); ?> ago
											</td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>" class="button"><?php esc_html_e( 'View All Orders', 'wtc-shipping' ); ?></a></p>
								<?php else : ?>
								<div class="notice notice-info inline"><p><?php esc_html_e( 'No recent orders.', 'wtc-shipping' ); ?></p></div>
								<?php endif; ?>
							</div>
						</div>

						<!-- System Status -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( 'System Status', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<table class="wp-list-table widefat striped">
									<tbody>
										<tr><td><strong><?php esc_html_e( 'Plugin', 'wtc-shipping' ); ?></strong></td><td><?php echo esc_html( WTCC_SHIPPING_VERSION ); ?></td></tr>
										<tr><td><strong><?php esc_html_e( 'WordPress', 'wtc-shipping' ); ?></strong></td><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
										<tr><td><strong><?php esc_html_e( 'WooCommerce', 'wtc-shipping' ); ?></strong></td><td><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A' ); ?></td></tr>
										<tr><td><strong><?php esc_html_e( 'PHP', 'wtc-shipping' ); ?></strong></td><td><?php echo esc_html( phpversion() ); ?></td></tr>
										<tr>
											<td><strong><?php esc_html_e( 'Origin ZIP', 'wtc-shipping' ); ?></strong></td>
											<td><?php 
												$origin = get_option( 'wtcc_origin_zip', '' );
												echo $origin ? esc_html( $origin ) : '<span class="wtcc-status-warning">' . esc_html__( 'Not Set', 'wtc-shipping' ) . '</span>';
											?></td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

						<!-- Resources -->
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Resources', 'wtc-shipping' ) ); ?></h2>
							<div class="inside">
								<ul class="wtcc-link-list">
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-user-guide' ) ); ?>"><span class="dashicons dashicons-book"></span> <?php esc_html_e( 'User Guide', 'wtc-shipping' ); ?></a></li>
									<li><a href="https://www.usps.com/business/web-tools-apis/" target="_blank" rel="noopener"><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'USPS Web Tools', 'wtc-shipping' ); ?></a></li>
									<li><a href="https://store.usps.com/store/results?Ntt=free+shipping+supplies" target="_blank" rel="noopener"><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'Free USPS Supplies', 'wtc-shipping' ); ?></a></li>
									<li><a href="https://postcalc.usps.com/" target="_blank" rel="noopener"><span class="dashicons dashicons-external"></span> <?php esc_html_e( 'USPS Calculator', 'wtc-shipping' ); ?></a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php endif; // End PRO/PREMIUM/ENTERPRISE dashboard ?>

	</div>
	<?php
}

/**
 * Diagnostics page
 */
function wtcc_shipping_diagnostics_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Unauthorized' );
	}
	if ( function_exists( 'wtcc_is_pro' ) && ! wtcc_is_pro() ) {
		?>
		<div class="wrap">
			<?php wtcc_admin_header( __( 'Diagnostics', 'wtc-shipping' ) ); ?>
			<div class="notice notice-info" style="margin-top:15px;">
				<p>
					<?php esc_html_e( 'The full diagnostics dashboard is available in Inkfinit USPS Shipping Engine Pro.', 'wtc-shipping' ); ?>
				</p>
				<p>
					<a href="https://inkfinit.pro/pricing" class="button button-primary" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View Pro Plans', 'wtc-shipping' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
		return;
	}
	?>
	<div class="wrap">
        <?php wtcc_admin_header(__( 'Diagnostics', 'wtc-shipping' )); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-1">
				<div id="post-body-content" class="meta-box-sortables ui-sortable">
					<?php wtcc_diagnostics_usps_health(); ?>
					<?php wtcc_diagnostics_shipping_config(); ?>
					<?php wtcc_diagnostics_products(); ?>
				</div>
			</div>
		</div>

	</div>
	<?php
}

/**
 * Enqueue scripts for the diagnostics page.
 */
function wtcc_diagnostics_enqueue_scripts( $hook ) {
    // The page hook is: {sanitized parent}_page_{menu_slug}
    // Parent is 'wtc-core-shipping' which becomes 'shipping-engine' in the hook
    if ( strpos( $hook, 'wtc-core-shipping-diagnostics' ) === false ) {
        return;
    }

    wp_enqueue_script(
        'wtcc-diagnostics-js',
        plugin_dir_url( __FILE__ ) . '../assets/admin-diagnostics.js',
        array( 'jquery' ),
        WTCC_SHIPPING_VERSION,
        true
    );

    wp_localize_script(
        'wtcc-diagnostics-js',
        'wtcc_diagnostics',
        array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'wtcc_test_api' ),
            'testing_text' => esc_html__( 'Testing...', 'wtc-shipping' ),
            'success_text' => esc_html__( 'Success!', 'wtc-shipping' ),
            'failed_text'  => esc_html__( 'Failed', 'wtc-shipping' ),
            'error_text'   => esc_html__( 'Network error', 'wtc-shipping' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'wtcc_diagnostics_enqueue_scripts' );

/**
 * USPS API health
 */
function wtcc_diagnostics_usps_health() {
	// Get from new unified options, with fallback to legacy options
	$options = get_option( 'wtcc_usps_api_options', array() );
	$consumer_key = ! empty( $options['consumer_key'] ) ? $options['consumer_key'] : get_option( 'wtcc_usps_consumer_key', '' );
	$consumer_secret = ! empty( $options['consumer_secret'] ) ? $options['consumer_secret'] : get_option( 'wtcc_usps_consumer_secret', '' );
	$origin_zip = ! empty( $options['origin_zip'] ) ? $options['origin_zip'] : get_option( 'wtcc_origin_zip', '' );
	$last_success = get_option( 'wtcc_last_usps_success', 0 );
	$last_failure = get_option( 'wtcc_last_usps_failure', 0 );
	?>
	<div class="postbox">
		<h2 class="hndle"><?php echo wtcc_section_heading( __( 'USPS API Health', 'wtc-shipping' ) ); ?></h2>
		<div class="inside">
			<table class="wp-list-table widefat striped">
				<thead>
					<tr><th><?php esc_html_e( 'Check', 'wtc-shipping' ); ?></th><th><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></th><th><?php esc_html_e( 'Details', 'wtc-shipping' ); ?></th></tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'API Credentials', 'wtc-shipping' ); ?></strong></td>
						<td>
							<?php if ( ! empty( $consumer_key ) && ! empty( $consumer_secret ) ) : ?>
								<span class="dashicons dashicons-yes-alt wtcc-status-success"></span> <?php esc_html_e( 'Configured', 'wtc-shipping' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-dismiss wtcc-status-error"></span> <?php esc_html_e( 'Not Set', 'wtc-shipping' ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $consumer_key ) ) : ?>
								<?php esc_html_e( 'Key', 'wtc-shipping' ); ?>: <?php echo esc_html( substr( $consumer_key, 0, 8 ) ); ?>...
							<?php else : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-usps-api' ) ); ?>"><?php esc_html_e( 'Configure Now', 'wtc-shipping' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Origin ZIP', 'wtc-shipping' ); ?></strong></td>
						<td>
							<?php if ( ! empty( $origin_zip ) ) : ?>
								<span class="dashicons dashicons-yes-alt wtcc-status-success"></span> <?php esc_html_e( 'Set', 'wtc-shipping' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-warning wtcc-status-warning"></span> <?php esc_html_e( 'Not Set', 'wtc-shipping' ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo ! empty( $origin_zip ) ? esc_html( $origin_zip ) : esc_html__( 'Required for rates', 'wtc-shipping' ); ?></td>
					</tr>
					<?php if ( $last_success > 0 ) : ?>
					<tr>
						<td><strong><?php esc_html_e( 'Last Success', 'wtc-shipping' ); ?></strong></td>
						<td><span class="dashicons dashicons-yes-alt wtcc-status-success"></span> <?php esc_html_e( 'Working', 'wtc-shipping' ); ?></td>
						<td><?php echo esc_html( human_time_diff( $last_success, time() ) ); ?> <?php esc_html_e( 'ago', 'wtc-shipping' ); ?></td>
					</tr>
					<?php endif; ?>
					<?php if ( $last_failure > 0 ) : ?>
					<tr>
						<td><strong><?php esc_html_e( 'Last Failure', 'wtc-shipping' ); ?></strong></td>
						<td><span class="dashicons dashicons-dismiss wtcc-status-error"></span> <?php esc_html_e( 'Error', 'wtc-shipping' ); ?></td>
						<td><?php echo esc_html( human_time_diff( $last_failure, time() ) ); ?> <?php esc_html_e( 'ago', 'wtc-shipping' ); ?></td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
			<p class="submit">
				<button type="button" id="wtcc-test-api-btn" class="button button-primary">
					<?php esc_html_e( 'Test Connection', 'wtc-shipping' ); ?>
				</button>
				<span id="wtcc-test-api-status" style="margin-left: 10px;"></span>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Shipping config diagnostics
 */
function wtcc_diagnostics_shipping_config() {
	$methods = array();
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		$zones = WC_Shipping_Zones::get_zones();
		foreach ( $zones as $zone ) {
			if ( isset( $zone['shipping_methods'] ) ) {
				foreach ( $zone['shipping_methods'] as $method ) {
					$methods[] = array(
						'zone' => $zone['zone_name'],
						'method' => $method->get_title(),
						'enabled' => $method->is_enabled(),
					);
				}
			}
		}
	}
	?>
	<div class="postbox">
		<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Shipping Configuration', 'wtc-shipping' ) ); ?></h2>
		<div class="inside">
			<?php if ( ! empty( $methods ) ) : ?>
			<table class="wp-list-table widefat striped">
				<thead><tr><th><?php esc_html_e( 'Zone', 'wtc-shipping' ); ?></th><th><?php esc_html_e( 'Method', 'wtc-shipping' ); ?></th><th><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $methods as $m ) : ?>
					<tr>
						<td><?php echo esc_html( $m['zone'] ); ?></td>
						<td><?php echo esc_html( $m['method'] ); ?></td>
						<td>
							<?php if ( $m['enabled'] ) : ?>
								<span class="dashicons dashicons-yes-alt wtcc-status-success"></span> <?php esc_html_e( 'Enabled', 'wtc-shipping' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-dismiss wtcc-status-error"></span> <?php esc_html_e( 'Disabled', 'wtc-shipping' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'No shipping methods found.', 'wtc-shipping' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php esc_html_e( 'Configure zones', 'wtc-shipping' ); ?></a></p></div>
			<?php endif; ?>
			
			<?php
			// Flat Rate Box Status
			$flat_rate_enabled = get_option( 'wtcc_flat_rate_enabled', 'yes' );
			$flat_rate_boxes = function_exists( 'wtcc_get_flat_rate_boxes' ) ? wtcc_get_flat_rate_boxes() : array();
			$enabled_boxes = get_option( 'wtcc_enabled_flat_rate_boxes', array() );
			if ( empty( $enabled_boxes ) ) {
				// Default to all enabled if not set
				$enabled_boxes = array_keys( $flat_rate_boxes );
			}
			?>
			<h3 style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
				<?php esc_html_e( 'USPS Flat Rate Boxes', 'wtc-shipping' ); ?>
			</h3>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Setting', 'wtc-shipping' ); ?></th>
						<th><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></th>
						<th><?php esc_html_e( 'Details', 'wtc-shipping' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Flat Rate System', 'wtc-shipping' ); ?></strong></td>
						<td>
							<?php if ( 'yes' === $flat_rate_enabled ) : ?>
								<span class="dashicons dashicons-yes-alt wtcc-status-success"></span> <?php esc_html_e( 'Enabled', 'wtc-shipping' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-dismiss wtcc-status-error"></span> <?php esc_html_e( 'Disabled', 'wtc-shipping' ); ?>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-flat-rate' ) ); ?>"><?php esc_html_e( 'Configure Flat Rate', 'wtc-shipping' ); ?></a>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Available Boxes', 'wtc-shipping' ); ?></strong></td>
						<td>
							<span class="dashicons dashicons-yes-alt wtcc-status-success"></span> 
							<?php echo esc_html( count( $flat_rate_boxes ) ); ?> <?php esc_html_e( 'box types', 'wtc-shipping' ); ?>
						</td>
						<td>
							<?php echo esc_html( count( $enabled_boxes ) ); ?> <?php esc_html_e( 'enabled for checkout', 'wtc-shipping' ); ?>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Box Categories', 'wtc-shipping' ); ?></strong></td>
						<td colspan="2">
							<?php
							$envelopes = $small_boxes = $medium_boxes = $large_boxes = 0;
							foreach ( $flat_rate_boxes as $box ) {
								if ( isset( $box['type'] ) && $box['type'] === 'envelope' ) {
									$envelopes++;
								} elseif ( strpos( $box['name'] ?? '', 'Small' ) !== false ) {
									$small_boxes++;
								} elseif ( strpos( $box['name'] ?? '', 'Medium' ) !== false ) {
									$medium_boxes++;
								} elseif ( strpos( $box['name'] ?? '', 'Large' ) !== false ) {
									$large_boxes++;
								}
							}
							?>
							<?php esc_html_e( 'Envelopes:', 'wtc-shipping' ); ?> <strong><?php echo esc_html( $envelopes ); ?></strong> |
							<?php esc_html_e( 'Small:', 'wtc-shipping' ); ?> <strong><?php echo esc_html( $small_boxes ); ?></strong> |
							<?php esc_html_e( 'Medium:', 'wtc-shipping' ); ?> <strong><?php echo esc_html( $medium_boxes ); ?></strong> |
							<?php esc_html_e( 'Large:', 'wtc-shipping' ); ?> <strong><?php echo esc_html( $large_boxes ); ?></strong>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

/**
 * Products diagnostics
 */
function wtcc_diagnostics_products() {
	$products_with = array();
	$products_without = array();
	$products = wc_get_products( array( 'limit' => 50, 'status' => 'publish' ) );
	if ( $products ) {
		foreach ( $products as $product ) {
			if ( $product->get_weight() > 0 ) {
				$products_with[] = $product;
			} else {
				$products_without[] = $product;
			}
		}
	}
	?>
	<div class="postbox">
		<h2 class="hndle"><?php echo wtcc_section_heading( __( 'Product Shipping Status', 'wtc-shipping' ) ); ?></h2>
		<div class="inside">
			<p><span class="dashicons dashicons-yes-alt wtcc-status-success"></span> <strong><?php echo count( $products_with ); ?></strong> <?php esc_html_e( 'products have weight', 'wtc-shipping' ); ?></p>
			<?php if ( ! empty( $products_without ) ) : ?>
			<p><span class="dashicons dashicons-warning wtcc-status-warning"></span> <strong><?php echo count( $products_without ); ?></strong> <?php esc_html_e( 'need weight:', 'wtc-shipping' ); ?></p>
			<table class="wp-list-table widefat striped">
				<thead><tr><th><?php esc_html_e( 'Product', 'wtc-shipping' ); ?></th><th><?php esc_html_e( 'SKU', 'wtc-shipping' ); ?></th><th><?php esc_html_e( 'Action', 'wtc-shipping' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( array_slice( $products_without, 0, 10 ) as $product ) : ?>
					<tr>
						<td><?php echo esc_html( $product->get_name() ); ?></td>
						<td><?php echo esc_html( $product->get_sku() ?: '—' ); ?></td>
						<td><a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'wtc-shipping' ); ?></a></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( count( $products_without ) > 10 ) : ?>
			<p class="description">...<?php esc_html_e( 'and', 'wtc-shipping' ); ?> <?php echo count( $products_without ) - 10; ?> <?php esc_html_e( 'more.', 'wtc-shipping' ); ?></p>
			<?php endif; ?>
			<?php else : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'All products have weight configured!', 'wtc-shipping' ); ?></p></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
