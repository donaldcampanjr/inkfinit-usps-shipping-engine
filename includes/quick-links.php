<?php
/**
 * Quick Support and Documentation Links.
 *
 * Adds helpful links throughout the admin UI for fast access
 * to documentation and support resources.
 *
 * @package Inkfinit_Shipping_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define support and documentation URLs.
 * These can be overridden in wp-config.php if needed.
 */
if ( ! defined( 'WTCC_DOCS_URL' ) ) {
	define( 'WTCC_DOCS_URL', 'https://github.com/donaldcampanjr/inkfinit-usps-shipping-engine/blob/main/docs/INDEX.md' );
}
if ( ! defined( 'WTCC_SUPPORT_URL' ) ) {
	define( 'WTCC_SUPPORT_URL', 'https://github.com/donaldcampanjr/inkfinit-usps-shipping-engine/issues' );
}
if ( ! defined( 'WTCC_CHANGELOG_URL' ) ) {
	define( 'WTCC_CHANGELOG_URL', 'https://github.com/donaldcampanjr/inkfinit-usps-shipping-engine/blob/main/CHANGELOG.md' );
}
if ( ! defined( 'WTCC_UPGRADE_URL' ) ) {
	define( 'WTCC_UPGRADE_URL', 'https://inkfinit.pro/pricing' );
}

/**
 * Add plugin action links (appears on Plugins page).
 */
add_filter( 'plugin_action_links_' . plugin_basename( WTCC_SHIPPING_PLUGIN_DIR . 'plugin.php' ), 'wtcc_add_plugin_action_links' );

/**
 * Add action links to plugin listing.
 *
 * @param array $links Existing links.
 * @return array Modified links.
 */
function wtcc_add_plugin_action_links( $links ) {
	$custom_links = array(
		'<a href="' . esc_url( admin_url( 'admin.php?page=wtc-features' ) ) . '">' . esc_html__( 'Settings', 'wtc-shipping' ) . '</a>',
	);

	// Dynamic license tier link with upgrade promotion
	// Tiers: Free → Pro → Premium → Enterprise
	$edition = function_exists( 'wtcc_get_edition' ) ? wtcc_get_edition() : 'free';
	
	switch ( $edition ) {
		case 'enterprise':
			$custom_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ) . '"><strong>Enterprise ✓</strong></a>';
			break;
			
		case 'premium':
			$custom_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ) . '"><strong>Premium ✓</strong></a>';
			$custom_links[] = '<a href="' . esc_url( WTCC_UPGRADE_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Get Enterprise', 'wtc-shipping' ) . '</a>';
			break;
			
		case 'pro':
			$custom_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wtc-core-shipping-license' ) ) . '"><strong>Pro ✓</strong></a>';
			$custom_links[] = '<a href="' . esc_url( WTCC_UPGRADE_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Get Premium', 'wtc-shipping' ) . '</a>';
			break;
			
		default:
			$custom_links[] = '<a href="' . esc_url( WTCC_UPGRADE_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Get PRO', 'wtc-shipping' ) . '</a>';
			break;
	}

	return array_merge( $custom_links, $links );
}

/**
 * Add plugin row meta links (appears below description on Plugins page).
 */
add_filter( 'plugin_row_meta', 'wtcc_add_plugin_row_meta', 10, 2 );

/**
 * Add row meta links to plugin listing.
 *
 * @param array  $links Existing links.
 * @param string $file  Plugin file.
 * @return array Modified links.
 */
function wtcc_add_plugin_row_meta( $links, $file ) {
	if ( plugin_basename( WTCC_SHIPPING_PLUGIN_DIR . 'plugin.php' ) !== $file ) {
		return $links;
	}

	$custom_links = array(
		'<a href="' . esc_url( WTCC_SUPPORT_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'wtc-shipping' ) . '</a>',
		'<a href="' . esc_url( WTCC_CHANGELOG_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Changelog', 'wtc-shipping' ) . '</a>',
	);

	return array_merge( $links, $custom_links );
}

/**
 * Add help tab to plugin admin pages.
 */
add_action( 'current_screen', 'wtcc_add_help_tabs' );

/**
 * Add help tabs on plugin admin pages.
 */
function wtcc_add_help_tabs() {
	$screen = get_current_screen();

	if ( ! $screen ) {
		return;
	}

	// Only on our plugin pages.
	$our_pages = array(
		'woocommerce_page_inkfinit-shipping-features',
		'woocommerce_page_inkfinit-shipping-presets',
		'woocommerce_page_inkfinit-shipping-rates',
		'woocommerce_page_inkfinit-shipping-license',
	);

	if ( ! in_array( $screen->id, $our_pages, true ) ) {
		return;
	}

	// Getting Started tab.
	$screen->add_help_tab( array(
		'id'      => 'wtcc_help_getting_started',
		'title'   => __( 'Getting Started', 'wtc-shipping' ),
		'content' => '<h3>' . __( 'Getting Started with Inkfinit Shipping', 'wtc-shipping' ) . '</h3>' .
			'<p>' . __( 'Follow these steps to set up shipping:', 'wtc-shipping' ) . '</p>' .
			'<ol>' .
			'<li>' . __( 'Enter your USPS API credentials on the Features page', 'wtc-shipping' ) . '</li>' .
			'<li>' . __( 'Configure your origin address (ship from location)', 'wtc-shipping' ) . '</li>' .
			'<li>' . __( 'Set up shipping presets for your product types', 'wtc-shipping' ) . '</li>' .
			'<li>' . __( 'Assign presets to products in the product editor', 'wtc-shipping' ) . '</li>' .
			'</ol>',
	) );

	// Troubleshooting tab.
	$screen->add_help_tab( array(
		'id'      => 'wtcc_help_troubleshooting',
		'title'   => __( 'Troubleshooting', 'wtc-shipping' ),
		'content' => '<h3>' . __( 'Common Issues', 'wtc-shipping' ) . '</h3>' .
			'<p><strong>' . __( 'No rates showing:', 'wtc-shipping' ) . '</strong> ' .
			__( 'Check API credentials, verify product dimensions are set, ensure origin address is configured.', 'wtc-shipping' ) . '</p>' .
			'<p><strong>' . __( 'OAuth errors:', 'wtc-shipping' ) . '</strong> ' .
			__( 'Re-enter your Consumer Key and Secret. Make sure your site uses HTTPS.', 'wtc-shipping' ) . '</p>' .
			'<p><strong>' . __( 'Rates seem wrong:', 'wtc-shipping' ) . '</strong> ' .
			__( 'Double-check product weights and dimensions. USPS uses dimensional weight for large packages.', 'wtc-shipping' ) . '</p>',
	) );

	// Set help sidebar with links.
	$screen->set_help_sidebar(
		'<p><strong>' . __( 'Quick Links', 'wtc-shipping' ) . '</strong></p>' .
		'<p><a href="' . esc_url( WTCC_DOCS_URL ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Documentation', 'wtc-shipping' ) . '</a></p>' .
		'<p><a href="' . esc_url( WTCC_SUPPORT_URL ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Get Support', 'wtc-shipping' ) . '</a></p>' .
		'<p><a href="' . esc_url( WTCC_CHANGELOG_URL ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Changelog', 'wtc-shipping' ) . '</a></p>'
	);
}

/**
 * Add quick links section to admin footer on plugin pages.
 */
add_action( 'admin_footer', 'wtcc_add_footer_links' );

/**
 * Render footer links on plugin admin pages.
 */
function wtcc_add_footer_links() {
	$screen = get_current_screen();

	if ( ! $screen ) {
		return;
	}

	// Only on WooCommerce pages (our plugin pages are under WooCommerce).
	if ( strpos( $screen->id, 'inkfinit-shipping' ) === false ) {
		return;
	}

	?>
	<script>
	jQuery(document).ready(function($) {
		if ($('#wtcc-admin-footer-links').length === 0) {
			$('body.woocommerce_page_inkfinit-shipping-features .wrap, body.woocommerce_page_inkfinit-shipping-presets .wrap, body.woocommerce_page_inkfinit-shipping-rates .wrap, body.woocommerce_page_inkfinit-shipping-license .wrap').append(
				'<div id="wtcc-admin-footer-links" class="card">' +
				'<h4><?php echo esc_js( __( 'Need Help?', 'wtc-shipping' ) ); ?></h4>' +
				'<p>' +
				'<a href="<?php echo esc_url( WTCC_DOCS_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button"><span class="dashicons dashicons-book"></span> <?php echo esc_js( __( 'Documentation', 'wtc-shipping' ) ); ?></a> ' +
				'<a href="<?php echo esc_url( WTCC_SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button"><span class="dashicons dashicons-editor-help"></span> <?php echo esc_js( __( 'Get Support', 'wtc-shipping' ) ); ?></a> ' +
				'<a href="<?php echo esc_url( admin_url( 'admin.php?page=inkfinit-shipping-features&tab=diagnostics' ) ); ?>" class="button"><span class="dashicons dashicons-sos"></span> <?php echo esc_js( __( 'Diagnostics', 'wtc-shipping' ) ); ?></a>' +
				'</p>' +
				'</div>'
			);
		}
	});
	</script>
	<?php
}

/**
 * Render a support card widget for use in admin pages.
 */
function wtcc_render_support_card() {
	?>
	<div class="card">
		<h3><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Quick Links', 'wtc-shipping' ); ?></h3>
		<p>
			<a href="<?php echo esc_url( WTCC_DOCS_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button">
				<span class="dashicons dashicons-book"></span> <?php esc_html_e( 'Documentation', 'wtc-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( WTCC_SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button">
				<span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Get Support', 'wtc-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=inkfinit-shipping-features&tab=diagnostics' ) ); ?>" class="button">
				<span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Run Diagnostics', 'wtc-shipping' ); ?>
			</a>
			<a href="<?php echo esc_url( WTCC_CHANGELOG_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button">
				<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Changelog', 'wtc-shipping' ); ?>
			</a>
		</p>
		<?php if ( ! wtcc_is_pro() ) : ?>
		<div class="notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Unlock All Features', 'wtc-shipping' ); ?></strong><br>
				<?php esc_html_e( 'Get label printing, pickup scheduling, and priority support.', 'wtc-shipping' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( WTCC_UPGRADE_URL ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
					<?php esc_html_e( 'Go Pro →', 'wtc-shipping' ); ?>
				</a>
			</p>
		</div>
		<?php endif; ?>
	</div>
	<?php
}
