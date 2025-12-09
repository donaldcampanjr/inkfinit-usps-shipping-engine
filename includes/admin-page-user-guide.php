<?php
/**
 * User Guide Admin Page
 * 
 * Comprehensive step-by-step instructions for using all plugin features
 * 
 * @package WTC_Shipping
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue assets for the user guide page.
 */
function wtcc_user_guide_enqueue_assets( $hook ) {
    // The hook for this page is 'shipping-engine_page_wtc-user-guide'
    if ( 'shipping-engine_page_wtc-user-guide' !== $hook ) {
        return;
    }

	wp_enqueue_style(
		'wtc-admin-style',
		plugin_dir_url( __FILE__ ) . '../assets/admin-style.css',
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . '../assets/admin-style.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'wtcc_user_guide_enqueue_assets' );

/**
 * Get all user guide sections
 */
function wtcc_get_user_guide_sections() {
	return array(
		'getting_started' => array(
			'title' => __( 'Getting Started', 'wtc-shipping' ),
			'icon'  => 'dashicons-flag',
			'sections' => array(
				array(
					'title' => __( 'Step 1: Configure USPS API Credentials', 'wtc-shipping' ),
					'content' => __( 'Before you can get live shipping rates, you need USPS API credentials.', 'wtc-shipping' ),
					'steps' => array(
						__( 'Go to Inkfinit Shipping → USPS Settings', 'wtc-shipping' ),
						__( 'Enter your USPS Consumer Key (from USPS Web Tools)', 'wtc-shipping' ),
						__( 'Enter your USPS Consumer Secret', 'wtc-shipping' ),
						__( 'Enter your Origin ZIP Code (where you ship from)', 'wtc-shipping' ),
						__( 'Click "Save Changes"', 'wtc-shipping' ),
						__( 'Click "Test API Connection" to verify everything works', 'wtc-shipping' ),
					),
					'tip' => __( 'Don\'t have USPS credentials? Visit usps.com/business to register for a free Web Tools account.', 'wtc-shipping' ),
				),
				array(
					'title' => __( 'Step 2: Add Shipping Zones in WooCommerce', 'wtc-shipping' ),
					'content' => __( 'WooCommerce needs shipping zones to know where to offer your shipping methods.', 'wtc-shipping' ),
					'steps' => array(
						__( 'Go to WooCommerce → Settings → Shipping → Shipping Zones', 'wtc-shipping' ),
						__( 'Click "Add shipping zone"', 'wtc-shipping' ),
						__( 'Name it (e.g., "United States")', 'wtc-shipping' ),
						__( 'Add zone regions (e.g., United States)', 'wtc-shipping' ),
						__( 'Click "Add shipping method"', 'wtc-shipping' ),
						__( 'Add each USPS method you want to offer', 'wtc-shipping' ),
					),
				),
				array(
					'title' => __( 'Step 3: Assign Shipping Presets to Products', 'wtc-shipping' ),
					'content' => __( 'Presets define default dimensions and weights for quick setup.', 'wtc-shipping' ),
					'steps' => array(
						__( 'Edit any product', 'wtc-shipping' ),
						__( 'Look for the "Shipping Preset" dropdown in the sidebar', 'wtc-shipping' ),
						__( 'Select the preset that matches your product', 'wtc-shipping' ),
						__( 'The preset will set default dimensions if the product doesn\'t have them', 'wtc-shipping' ),
						__( 'For custom products, set actual dimensions in Product Data → Shipping', 'wtc-shipping' ),
					),
					'tip' => __( 'Products with actual dimensions always use their real measurements. Presets are only used as fallbacks.', 'wtc-shipping' ),
				),
			),
		),
		'bulk_variation_manager' => array(
			'title' => __( 'Bulk Variation Manager', 'wtc-shipping' ),
			'icon'  => 'dashicons-edit',
			'sections' => array(
				array(
					'title' => __( 'What is the Bulk Variation Manager?', 'wtc-shipping' ),
					'content' => __( 'The Bulk Variation Manager lets you update prices or stock for ALL product variations that share a specific attribute value. For example, update the price of every "16 oz" candle variation across your entire catalog in one click.', 'wtc-shipping' ),
				),
				array(
					'title' => __( 'Example: Update All "16 oz" Candle Prices', 'wtc-shipping' ),
					'content' => __( 'Let\'s say you sell 50 different candle scents, each available in 8oz, 12oz, and 16oz sizes. You want to increase all 16oz prices from $24.99 to $29.99.', 'wtc-shipping' ),
					'steps' => array(
						__( 'Go to Inkfinit Shipping → Variation Manager', 'wtc-shipping' ),
						__( 'Select Attribute: "Size"', 'wtc-shipping' ),
						__( 'Select Value: "16 oz"', 'wtc-shipping' ),
						__( 'Click "Preview Variations"', 'wtc-shipping' ),
						__( 'Select the "Update Prices" tab', 'wtc-shipping' ),
						__( 'Choose "Regular Price" and "Set to exact price"', 'wtc-shipping' ),
						__( 'Enter "29.99" in the Amount field', 'wtc-shipping' ),
						__( 'Click "Apply Changes"', 'wtc-shipping' ),
					),
					'tip' => __( 'Always preview before applying! The preview shows exactly which variations will be affected.', 'wtc-shipping' ),
				),
			),
		),
		'shipping_presets' => array(
			'title' => __( 'Shipping Presets', 'wtc-shipping' ),
			'icon'  => 'dashicons-archive',
			'sections' => array(
				array(
					'title' => __( 'Understanding Shipping Presets', 'wtc-shipping' ),
					'content' => __( 'Presets are pre-configured shipping profiles that define typical dimensions and weights for common product types.', 'wtc-shipping' ),
				),
				array(
					'title' => __( 'Built-in Presets', 'wtc-shipping' ),
					'content' => __( 'The plugin includes presets for common merchandise:', 'wtc-shipping' ),
					'list' => array(
						__( 'T-Shirt – Standard apparel dimensions', 'wtc-shipping' ),
						__( 'Hoodie – Heavier apparel', 'wtc-shipping' ),
						__( 'Vinyl Record – 12" LP dimensions', 'wtc-shipping' ),
						__( 'CD/DVD – Jewel case dimensions', 'wtc-shipping' ),
						__( 'Poster (Rolled) – Tube dimensions', 'wtc-shipping' ),
						__( 'Sticker Pack – Small flat items', 'wtc-shipping' ),
						__( 'Book – Standard paperback', 'wtc-shipping' ),
						__( 'USPS Flat Rate Boxes – All official sizes', 'wtc-shipping' ),
					),
				),
				array(
					'title' => __( 'Creating Custom Presets', 'wtc-shipping' ),
					'content' => __( 'Need a preset for your unique products? Create your own!', 'wtc-shipping' ),
					'steps' => array(
						__( 'Go to Inkfinit Shipping → Preset Editor', 'wtc-shipping' ),
						__( 'Click "Add New Preset"', 'wtc-shipping' ),
						__( 'Enter a name (e.g., "Large Candle")', 'wtc-shipping' ),
						__( 'Set default dimensions (length, width, height)', 'wtc-shipping' ),
						__( 'Set maximum weight for this product type', 'wtc-shipping' ),
						__( 'Click "Save Preset"', 'wtc-shipping' ),
					),
				),
			),
		),
		'live_rates' => array(
			'title' => __( 'Live USPS Rates', 'wtc-shipping' ),
			'icon'  => 'dashicons-money-alt',
			'sections' => array(
				array(
					'title' => __( 'How Live Rates Work', 'wtc-shipping' ),
					'content' => __( 'When a customer enters their shipping address at checkout, the plugin:', 'wtc-shipping' ),
					'list' => array(
						__( 'Calculates total cart weight from product weights', 'wtc-shipping' ),
						__( 'Determines package dimensions from products or presets', 'wtc-shipping' ),
						__( 'Sends request to USPS API', 'wtc-shipping' ),
						__( 'Receives real-time rates for all enabled shipping methods', 'wtc-shipping' ),
						__( 'Displays rates sorted cheapest to most expensive', 'wtc-shipping' ),
						__( 'Caches rates for 4 hours to reduce API calls', 'wtc-shipping' ),
					),
				),
				array(
					'title' => __( 'Troubleshooting: No Rates Showing', 'wtc-shipping' ),
					'content' => __( 'If shipping rates aren\'t appearing, check these common issues:', 'wtc-shipping' ),
					'list' => array(
						__( 'API credentials not configured', 'wtc-shipping' ),
						__( 'Origin ZIP code not set', 'wtc-shipping' ),
						__( 'No shipping methods added to zone', 'wtc-shipping' ),
						__( 'Products missing weight', 'wtc-shipping' ),
						__( 'Customer address outside shipping zone', 'wtc-shipping' ),
					),
					'tip' => __( 'Use the Diagnostics page to test your API connection and identify issues.', 'wtc-shipping' ),
				),
			),
		),
		'tracking' => array(
			'title' => __( 'Order Tracking', 'wtc-shipping' ),
			'icon'  => 'dashicons-location',
			'sections' => array(
				array(
					'title' => __( 'Adding Tracking Numbers', 'wtc-shipping' ),
					'content' => __( 'Add USPS tracking numbers to orders so customers can track their packages.', 'wtc-shipping' ),
					'steps' => array(
						__( 'Go to WooCommerce → Orders', 'wtc-shipping' ),
						__( 'Click on an order to edit it', 'wtc-shipping' ),
						__( 'Look for the "Shipping & Tracking" meta box', 'wtc-shipping' ),
						__( 'Enter the USPS tracking number', 'wtc-shipping' ),
						__( 'Check "Send tracking email to customer" if desired', 'wtc-shipping' ),
						__( 'Click "Update"', 'wtc-shipping' ),
					),
				),
				array(
					'title' => __( 'Customer Tracking Portal', 'wtc-shipping' ),
					'content' => __( 'Customers can track their orders from their account:', 'wtc-shipping' ),
					'list' => array(
						__( 'Customer logs into My Account', 'wtc-shipping' ),
						__( 'Goes to Orders or dedicated Tracking tab', 'wtc-shipping' ),
						__( 'Clicks on order with tracking', 'wtc-shipping' ),
						__( 'Sees real-time USPS tracking status', 'wtc-shipping' ),
						__( 'Visual status badges show delivery progress', 'wtc-shipping' ),
					),
				),
			),
		),
		'diagnostics' => array(
			'title' => __( 'Diagnostics & Troubleshooting', 'wtc-shipping' ),
			'icon'  => 'dashicons-admin-tools',
			'sections' => array(
				array(
					'title' => __( 'Using the Diagnostics Page', 'wtc-shipping' ),
					'content' => __( 'The Diagnostics page shows the health of your shipping setup at a glance.', 'wtc-shipping' ),
					'list' => array(
						__( 'Green indicators = working correctly', 'wtc-shipping' ),
						__( 'Yellow indicators = warnings to review', 'wtc-shipping' ),
						__( 'Red indicators = errors needing attention', 'wtc-shipping' ),
					),
				),
				array(
					'title' => __( 'Common Issues & Solutions', 'wtc-shipping' ),
					'content' => __( 'Quick fixes for common problems:', 'wtc-shipping' ),
					'list' => array(
						__( '"API credentials invalid" → Double-check Consumer Key and Secret', 'wtc-shipping' ),
						__( '"No shipping methods available" → Add methods to your shipping zone', 'wtc-shipping' ),
						__( '"Rates not updating" → Clear transients or wait for cache to expire', 'wtc-shipping' ),
						__( '"Dimensions missing" → Check products flagged in Dimension Alerts', 'wtc-shipping' ),
						__( '"Rates too high" → Verify product weights are in correct units', 'wtc-shipping' ),
					),
				),
			),
		),
	);
}

/**
 * Render the User Guide page
 */
function wtcc_render_user_guide_page() {
	$sections = wtcc_get_user_guide_sections();
	?>
	<div class="wrap">
		<?php wtcc_admin_header( __( 'User Guide & Documentation', 'wtc-shipping' ) ); ?>
		
		<p class="description">
			<?php esc_html_e( 'Complete step-by-step instructions for setting up and using all features of Inkfinit USPS Shipping Engine.', 'wtc-shipping' ); ?>
		</p>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				
				<!-- Main Content -->
				<div id="post-body-content">
					<?php
					$first_sections = array_slice( $sections, 0, 3, true );
					foreach ( $first_sections as $key => $section ) :
						?>
						<div class="postbox">
							<h2 class="hndle"><span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>"></span><?php echo esc_html( $section['title'] ); ?></h2>
							<div class="inside">
								<?php wtcc_render_guide_section( $section['sections'] ); ?>
							</div>
						</div>
						<?php
					endforeach;
					?>
				</div>

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">
					<div class="meta-box-sortables ui-sortable">
						<?php
						$sidebar_sections = array_slice( $sections, 3 );
						foreach ( $sidebar_sections as $key => $section ) :
							?>
							<div class="postbox">
								<h2 class="hndle"><span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>"></span><?php echo esc_html( $section['title'] ); ?></h2>
								<div class="inside">
									<?php wtcc_render_guide_section( $section['sections'] ); ?>
								</div>
							</div>
							<?php
						endforeach;
						?>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
	</div>
	<?php
}

/**
 * Render a guide section's content
 */
function wtcc_render_guide_section( $subsections ) {
	foreach ( $subsections as $subsection ) : ?>
		<div class="wtc-guide-subsection">
			<h3>
				<?php echo esc_html( $subsection['title'] ); ?>
			</h3>

			<?php if ( ! empty( $subsection['content'] ) ) : ?>
				<p>
					<?php echo wp_kses_post( $subsection['content'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $subsection['steps'] ) ) : ?>
				<ol class="wtc-guide-steps">
					<?php foreach ( $subsection['steps'] as $step ) : ?>
						<li>
							<?php echo esc_html( $step ); ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<?php if ( ! empty( $subsection['list'] ) ) : ?>
				<ul class="wtc-guide-list">
					<?php foreach ( $subsection['list'] as $item ) : ?>
						<li>
							<?php echo esc_html( $item ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $subsection['tip'] ) ) : ?>
				<div class="notice notice-warning inline">
					<p><strong>💡 <?php esc_html_e( 'Pro Tip:', 'wtc-shipping' ); ?></strong> <?php echo esc_html( $subsection['tip'] ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	<?php endforeach;
}
