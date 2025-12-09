<?php
/**
 * Admin Page: Features
 *
 * @package WTC_Shipping_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle features form save.
 */
function wtcc_handle_features_save() {
	if ( ! isset( $_POST['wtcc_features_nonce'] ) || ! wp_verify_nonce( $_POST['wtcc_features_nonce'], 'wtcc_save_features' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$options = array(
		'show_delivery_estimates'    => ! empty( $_POST['wtcc_features']['show_delivery_estimates'] ) ? 1 : 0,
		'enable_address_validation'  => ! empty( $_POST['wtcc_features']['enable_address_validation'] ) ? 1 : 0,
		'enable_order_auto_complete' => ! empty( $_POST['wtcc_features']['enable_order_auto_complete'] ) ? 1 : 0,
		'enable_tracking_display'    => ! empty( $_POST['wtcc_features']['enable_tracking_display'] ) ? 1 : 0,
		'enable_debug_overlay'       => ! empty( $_POST['wtcc_features']['enable_debug_overlay'] ) ? 1 : 0,
	);

	update_option( 'wtcc_features_options', $options );

	add_settings_error( 'wtcc_features', 'settings_updated', __( 'Features saved.', 'wtc-shipping' ), 'updated' );
}
add_action( 'admin_init', 'wtcc_handle_features_save' );

/**
 * Get feature options with defaults.
 */
function wtcc_get_features_options() {
	return get_option( 'wtcc_features_options', array(
		'show_delivery_estimates'    => 1,
		'enable_address_validation'  => 0,
		'enable_order_auto_complete' => 0,
		'enable_tracking_display'    => 1,
		'enable_debug_overlay'       => 0,
	) );
}

/**
 * Render the Features admin page.
 */
function wtcc_shipping_features_page() {
	$options = wtcc_get_features_options();
	$is_pro  = function_exists( 'wtcc_is_pro' ) ? wtcc_is_pro() : true;
	?>
	<div class="wrap">
		<?php wtcc_admin_header( __( 'Features & Documentation', 'wtc-shipping' ) ); ?>
		<?php settings_errors( 'wtcc_features' ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				
				<!-- Main Content -->
				<div id="post-body-content">
					
					<!-- Toggle Features -->
					<form method="post" action="">
						<?php wp_nonce_field( 'wtcc_save_features', 'wtcc_features_nonce' ); ?>
						
						<div class="postbox">
							<h2 class="hndle"><span class="dashicons dashicons-admin-settings"></span><?php esc_html_e( 'Feature Toggles', 'wtc-shipping' ); ?></h2>
							<div class="inside">
								<p class="description"><?php esc_html_e( 'Enable or disable features for your store. Changes take effect immediately.', 'wtc-shipping' ); ?></p>
								
								<table class="form-table">
									<tr>
										<th scope="row"><?php esc_html_e( 'Delivery Estimates', 'wtc-shipping' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wtcc_features[show_delivery_estimates]" value="1" <?php checked( ! empty( $options['show_delivery_estimates'] ) ); ?>>
												<?php esc_html_e( 'Show estimated delivery days at checkout', 'wtc-shipping' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'Displays "Est. 2-3 business days" based on USPS service standards.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Address Validation', 'wtc-shipping' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wtcc_features[enable_address_validation]" value="1" <?php checked( ! empty( $options['enable_address_validation'] ) ); ?>>
												<?php esc_html_e( 'Validate addresses with USPS at checkout', 'wtc-shipping' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'Warns customers if their address cannot be verified. Reduces failed deliveries.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Order Auto-Complete', 'wtc-shipping' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wtcc_features[enable_order_auto_complete]" value="1" <?php checked( ! empty( $options['enable_order_auto_complete'] ) ); ?>>
												<?php esc_html_e( 'Auto-complete orders when label is printed', 'wtc-shipping' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'Automatically marks orders as "Completed" after printing a shipping label.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Tracking Display', 'wtc-shipping' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wtcc_features[enable_tracking_display]" value="1" <?php checked( ! empty( $options['enable_tracking_display'] ) ); ?>>
												<?php esc_html_e( 'Show tracking info in customer emails & account', 'wtc-shipping' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'Displays tracking number and link in order confirmation emails and My Account.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Debug Overlay', 'wtc-shipping' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="wtcc_features[enable_debug_overlay]" value="1" <?php checked( ! empty( $options['enable_debug_overlay'] ) ); ?>>
												<?php esc_html_e( 'Show debug overlay on checkout (Admins only)', 'wtc-shipping' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'Shows rate calculation details at checkout. Only visible to admins.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>

									<?php if ( ! $is_pro ) : ?>
										<tr>
											<th scope="row"><?php esc_html_e( 'Upgrade to Pro', 'wtc-shipping' ); ?></th>
											<td>
												<p>
													<?php esc_html_e( 'You are currently using the free edition. Live checkout rates, label printing, tracking, presets, and automation are part of Inkfinit USPS Shipping Engine Pro.', 'wtc-shipping' ); ?>
												</p>
												<p>
													<a href="https://inkfinit.pro/pricing" class="button button-primary" target="_blank" rel="noopener noreferrer">
														<?php esc_html_e( 'View Pro Plans', 'wtc-shipping' ); ?>
													</a>
												</p>
											</td>
										</tr>
									<?php endif; ?>
								</table>
								
								<?php submit_button( __( 'Save Features', 'wtc-shipping' ) ); ?>
							</div>
						</div>
					</form>

					<!-- Plugin Capabilities -->
					<div class="postbox">
						<h2 class="hndle"><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Plugin Capabilities', 'wtc-shipping' ); ?></h2>
						<div class="inside">
							<p><?php esc_html_e( 'Here\'s what Inkfinit Shipping Core can do for your store:', 'wtc-shipping' ); ?></p>
							
							<h4><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Live USPS Rates', 'wtc-shipping' ); ?></h4>
							<ul class="ul-disc">
								<li><?php esc_html_e( 'Ground Advantage rates', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Priority Mail rates', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Priority Mail Express rates', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Flat Rate box support', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Regional Rate boxes', 'wtc-shipping' ); ?></li>
							</ul>

							<h4><span class="dashicons dashicons-media-default"></span> <?php esc_html_e( 'Label Printing', 'wtc-shipping' ); ?></h4>
							<ul class="ul-disc">
								<li><?php esc_html_e( 'Print USPS labels from orders', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Bulk label printing', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Automatic tracking numbers', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Packing slips generation', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Label printer integration', 'wtc-shipping' ); ?></li>
							</ul>

							<h4><span class="dashicons dashicons-archive"></span> <?php esc_html_e( 'Smart Box Packing', 'wtc-shipping' ); ?></h4>
							<ul class="ul-disc">
								<li><?php esc_html_e( 'Custom box inventory', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( '3D bin packing algorithm', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Auto-selects best box', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Multi-package support', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Split shipments', 'wtc-shipping' ); ?></li>
							</ul>

							<h4><span class="dashicons dashicons-products"></span> <?php esc_html_e( 'Product Management', 'wtc-shipping' ); ?></h4>
							<ul class="ul-disc">
								<li><?php esc_html_e( 'Shipping presets for products', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Dimension recommendations', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Dimension alerts & warnings', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Bulk variation manager', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Purchase quantity limits', 'wtc-shipping' ); ?></li>
							</ul>

							<h4><span class="dashicons dashicons-randomize"></span> <?php esc_html_e( 'Rules & Pricing', 'wtc-shipping' ); ?></h4>
							<ul class="ul-disc">
								<li><?php esc_html_e( 'Rate adjustment rules', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Free shipping rules', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Flat rate overrides', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Zone-based pricing', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Handling fees', 'wtc-shipping' ); ?></li>
							</ul>

							<h4><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Pickup Scheduling', 'wtc-shipping' ); ?></h4>
							<ul class="ul-disc">
								<li><?php esc_html_e( 'Schedule USPS pickups', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'One-time pickups', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Recurring schedules', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Pickup confirmations', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Manage from dashboard', 'wtc-shipping' ); ?></li>
							</ul>
						</div>
					</div>

					<!-- Admin Pages Guide -->
					<div class="postbox">
						<h2 class="hndle"><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Admin Pages Guide', 'wtc-shipping' ); ?></h2>
						<div class="inside">
							<table class="widefat striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Page', 'wtc-shipping' ); ?></th>
										<th><?php esc_html_e( 'Purpose', 'wtc-shipping' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td><strong><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Setup & Configuration', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Configure shipping rate groups, price adjustments, and which zones each service ships to. This is where you set up your core shipping logic.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-welcome-write-blog"></span> <?php esc_html_e( 'Preset Editor', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Create and manage shipping presets that can be assigned to products. Presets define default dimensions, weights, and shipping rules.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-archive"></span> <?php esc_html_e( 'Box Inventory', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Define your available box sizes. The plugin uses these to calculate the best box(es) for each order.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-calculator"></span> <?php esc_html_e( 'Rate Rules', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Create rules to modify shipping rates based on conditions like cart total, weight, product categories, etc.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Flat Rate', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Set up flat rate shipping options that bypass USPS calculations. Good for local delivery or fixed pricing.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-rest-api"></span> <?php esc_html_e( 'USPS API', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Enter your USPS API credentials (Consumer Key & Secret) and set your origin ZIP code. Required for live rates.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Security', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'View security status, rate limiting stats, and manage security features to protect against abuse.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Diagnostics', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Run API tests, view debug logs, and troubleshoot issues. Shows USPS connection status and recent errors.', 'wtc-shipping' ); ?></td>
									</tr>
									<tr>
										<td><strong><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'User Guide', 'wtc-shipping' ); ?></strong></td>
										<td><?php esc_html_e( 'Complete documentation for setting up and using the plugin. Start here if you\'re new.', 'wtc-shipping' ); ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- Workflow Tips -->
					<div class="postbox">
						<h2 class="hndle"><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'Workflow Tips for Your Team', 'wtc-shipping' ); ?></h2>
						<div class="inside">
								
							<h4><?php esc_html_e( '📦 Processing Orders', 'wtc-shipping' ); ?></h4>
							<ol>
								<li><?php esc_html_e( 'Go to WooCommerce → Orders', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Click on an order to view details', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Use "Print Label" in the sidebar', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Tracking number auto-saves to order', 'wtc-shipping' ); ?></li>
							</ol>

							<h4><?php esc_html_e( '🏷️ Adding Products', 'wtc-shipping' ); ?></h4>
							<ol>
								<li><?php esc_html_e( 'Set weight & dimensions on each product', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Or assign a shipping preset', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Check dimension alerts in product list', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Use bulk manager for variations', 'wtc-shipping' ); ?></li>
							</ol>

							<h4><?php esc_html_e( '🔧 Troubleshooting', 'wtc-shipping' ); ?></h4>
							<ol>
								<li><?php esc_html_e( 'Check Diagnostics page first', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Verify USPS API status is green', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Run test rate calculation', 'wtc-shipping' ); ?></li>
								<li><?php esc_html_e( 'Check debug log for errors', 'wtc-shipping' ); ?></li>
							</ol>
						</div>
					</div>

				</div><!-- /post-body-content -->

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					<div class="meta-box-sortables">
						
						<!-- Quick Stats -->
						<div class="postbox">
							<h2 class="hndle"><span class="dashicons dashicons-chart-bar"></span><?php esc_html_e( 'Quick Stats', 'wtc-shipping' ); ?></h2>
							<div class="inside">
								<?php
								$api_options = get_option( 'wtcc_usps_api_options', array() );
								$has_api = ! empty( $api_options['consumer_key'] ) && ! empty( $api_options['consumer_secret'] );
								$boxes = get_option( 'wtcc_shipping_boxes', array() );
								$presets = get_option( 'wtcc_shipping_presets', array() );
								$rules = get_option( 'wtcc_shipping_rules', array() );
								?>
								<ul>
									<li>
										<?php if ( $has_api ) : ?>
											<span>●</span> <?php esc_html_e( 'USPS API: Connected', 'wtc-shipping' ); ?>
										<?php else : ?>
											<span>●</span> <?php esc_html_e( 'USPS API: Not configured', 'wtc-shipping' ); ?>
										<?php endif; ?>
									</li>
									<li><strong><?php echo count( $boxes ); ?></strong> <?php esc_html_e( 'boxes defined', 'wtc-shipping' ); ?></li>
									<li><strong><?php echo count( $presets ); ?></strong> <?php esc_html_e( 'shipping presets', 'wtc-shipping' ); ?></li>
									<li><strong><?php echo count( $rules ); ?></strong> <?php esc_html_e( 'rate rules', 'wtc-shipping' ); ?></li>
								</ul>
							</div>
						</div>

						<!-- USPS Services -->
						<div class="postbox">
							<h2 class="hndle"><span class="dashicons dashicons-email"></span><?php esc_html_e( 'Supported Services', 'wtc-shipping' ); ?></h2>
							<div class="inside">
								<ul>
									<li><strong><?php esc_html_e( 'Ground Advantage', 'wtc-shipping' ); ?></strong><br><small><?php esc_html_e( '2-5 days, economical', 'wtc-shipping' ); ?></small></li>
									<li><strong><?php esc_html_e( 'Priority Mail', 'wtc-shipping' ); ?></strong><br><small><?php esc_html_e( '1-3 days, includes insurance', 'wtc-shipping' ); ?></small></li>
									<li><strong><?php esc_html_e( 'Priority Mail Express', 'wtc-shipping' ); ?></strong><br><small><?php esc_html_e( 'Overnight to 2 days', 'wtc-shipping' ); ?></small></li>
									<li><strong><?php esc_html_e( 'Flat Rate Boxes', 'wtc-shipping' ); ?></strong><br><small><?php esc_html_e( 'Fixed price, any weight', 'wtc-shipping' ); ?></small></li>
								</ul>
							</div>
						</div>

						<!-- Quick Links -->
						<div class="postbox">
							<h2 class="hndle"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'Quick Links', 'wtc-shipping' ); ?></h2>
							<div class="inside">
								<ul>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-shipping-presets' ) ); ?>"><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Setup & Config', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-shipping-boxes' ) ); ?>"><span class="dashicons dashicons-archive"></span> <?php esc_html_e( 'Box Inventory', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-shipping-usps-api' ) ); ?>"><span class="dashicons dashicons-rest-api"></span> <?php esc_html_e( 'USPS API Settings', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-shipping-diagnostics' ) ); ?>"><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Diagnostics', 'wtc-shipping' ); ?></a></li>
									<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-shipping-user-guide' ) ); ?>"><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'User Guide', 'wtc-shipping' ); ?></a></li>
								</ul>
							</div>
						</div>

						<!-- Need Help -->
						<div class="postbox">
							<h2 class="hndle"><span class="dashicons dashicons-sos"></span><?php esc_html_e( 'External Resources', 'wtc-shipping' ); ?></h2>
							<div class="inside">
								<ul>
									<li><a href="https://developer.usps.com/" target="_blank" rel="noopener"><?php esc_html_e( 'USPS Developer Portal', 'wtc-shipping' ); ?> ↗</a></li>
									<li><a href="https://www.usps.com/business/web-tools-apis/" target="_blank" rel="noopener"><?php esc_html_e( 'USPS Web Tools', 'wtc-shipping' ); ?> ↗</a></li>
									<li><a href="https://postcalc.usps.com/" target="_blank" rel="noopener"><?php esc_html_e( 'USPS Rate Calculator', 'wtc-shipping' ); ?> ↗</a></li>
								</ul>
							</div>
						</div>

						<!-- Version Info -->
						<div class="postbox">
							<h2 class="hndle"><span class="dashicons dashicons-info"></span><?php esc_html_e( 'Plugin Info', 'wtc-shipping' ); ?></h2>
							<div class="inside">
								<ul>
									<li><strong><?php esc_html_e( 'Version:', 'wtc-shipping' ); ?></strong> <?php echo defined( 'WTCC_SHIPPING_VERSION' ) ? esc_html( WTCC_SHIPPING_VERSION ) : '1.0.0'; ?></li>
									<li><strong><?php esc_html_e( 'PHP:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( PHP_VERSION ); ?></li>
									<li><strong><?php esc_html_e( 'WP:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( get_bloginfo( 'version' ) ); ?></li>
									<li><strong><?php esc_html_e( 'WC:', 'wtc-shipping' ); ?></strong> <?php echo defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : 'N/A'; ?></li>
								</ul>
							</div>
						</div>

					</div>
				</div><!-- /postbox-container-1 -->

			</div><!-- /post-body -->
			<br class="clear">
		</div><!-- /poststuff -->

	</div><!-- /wrap -->
	<?php
}
