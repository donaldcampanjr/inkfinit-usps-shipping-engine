<?php
/**
 * Admin pages and functionality.
 *
 * @package InkfinitLicenseServer
 */

defined( 'ABSPATH' ) || exit;

class BILS_Admin {

	/**
	 * Initialize admin functionality.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
	}

	/**
	 * Add admin menu.
	 */
	public static function add_menu() {
		add_submenu_page(
			'woocommerce',
			'License Keys',
			'License Keys',
			'manage_options',
			'bils-licenses',
			array( __CLASS__, 'render_licenses_page' )
		);
	}

	/**
	 * Handle admin actions (revoke/activate).
	 */
	public static function handle_actions() {
		if ( ! isset( $_GET['page'] ) || 'bils-licenses' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['action'] ) && isset( $_GET['license_id'] ) ) {
			$action = sanitize_text_field( $_GET['action'] );
			$license_id = intval( $_GET['license_id'] );

			if ( 'revoke' === $action ) {
				BILS_License_Manager::revoke( $license_id );
				wp_safe_remote_post(
					admin_url( 'admin.php?page=bils-licenses&revoked=1' )
				);
			} elseif ( 'activate' === $action ) {
				BILS_License_Manager::activate( $license_id );
				wp_safe_remote_post(
					admin_url( 'admin.php?page=bils-licenses&activated=1' )
				);
			}
		}
	}

	/**
	 * Render the licenses admin page.
	 */
	public static function render_licenses_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
		$limit = 25;
		$offset = ( $page - 1 ) * $limit;

		$licenses = BILS_Database::get_all( $limit, $offset );
		$total = BILS_Database::count_all();
		$pages = ceil( $total / $limit );

		?>
		<div class="wrap">
			<h1>Inkfinit License Keys</h1>

			<?php if ( isset( $_GET['revoked'] ) ) : ?>
				<div class="notice notice-success"><p>License revoked.</p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['activated'] ) ) : ?>
				<div class="notice notice-success"><p>License activated.</p></div>
			<?php endif; ?>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>License Key</th>
						<th>Customer</th>
						<th>Email</th>
						<th>Order ID</th>
						<th>Status</th>
						<th>Created</th>
						<th>Expires</th>
						<th>Validations</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $licenses as $license ) : ?>
						<tr>
							<td><code><?php echo esc_html( $license->license_key ); ?></code></td>
							<td><?php echo esc_html( $license->customer_name ); ?></td>
							<td><?php echo esc_html( $license->customer_email ); ?></td>
							<td>#<?php echo intval( $license->order_id ); ?></td>
							<td>
								<span style="color: <?php echo 'active' === $license->status ? 'green' : 'red'; ?>;">
									<?php echo esc_html( $license->status ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $license->created_at ); ?></td>
							<td><?php echo esc_html( $license->expires_at ?? '—' ); ?></td>
							<td><?php echo intval( $license->validation_count ); ?></td>
							<td>
								<?php if ( 'active' === $license->status ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'revoke', 'license_id' => $license->id ), admin_url( 'admin.php?page=bils-licenses' ) ), 'bils_action' ) ); ?>" class="button button-small" onclick="return confirm('Revoke this license?');">Revoke</a>
								<?php else : ?>
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'license_id' => $license->id ), admin_url( 'admin.php?page=bils-licenses' ) ), 'bils_action' ) ); ?>" class="button button-small button-primary">Activate</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '?paged=%#%',
								'current' => $page,
								'total'   => $pages,
							)
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
