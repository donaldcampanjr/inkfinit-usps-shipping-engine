<?php
/**
 * Admin Page: Shipping Rules Management
 * Clean WordPress native styling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue assets for the rates page.
 */
function wtcc_shipping_rates_enqueue_assets( $hook ) {
    // The hook for this page is 'shipping-engine_page_wtc-shipping-rules'
    if ( 'shipping-engine_page_wtc-shipping-rules' !== $hook ) {
        return;
    }

	wp_enqueue_style(
		'wtc-admin-style',
		plugin_dir_url( __FILE__ ) . '../assets/admin-style.css',
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . '../assets/admin-style.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'wtcc_shipping_rates_enqueue_assets' );


/**
 * Render shipping rules page
 */
function wtcc_shipping_rates_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'wtc-shipping' ) );
	}

	// Require Pro license for this feature.
	if ( function_exists( 'wtcc_require_license_tier' ) && wtcc_require_license_tier( __( 'Shipping Rules', 'wtc-shipping' ), 'pro' ) ) {
		return;
	}

	// Handle save
	if ( isset( $_POST['wtc_save_rules'] ) && check_admin_referer( 'wtc_rules_nonce' ) ) {
		wtcc_shipping_save_rules( $_POST );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule saved successfully.', 'wtc-shipping' ) . '</p></div>';
	}

	// Handle delete
	if ( isset( $_GET['delete_rule'] ) && check_admin_referer( 'wtc_delete_rule' ) ) {
		wtcc_shipping_delete_rule( (int) $_GET['delete_rule'] );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Rule deleted.', 'wtc-shipping' ) . '</p></div>';
	}

	$rules  = get_option( 'wtcc_shipping_rules', array() );
	$groups = wtcc_shipping_get_groups();
	$config = wtcc_shipping_get_rates_config();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Shipping Rules', 'wtc-shipping' ); ?></h1>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">

				<!-- Main Content -->
				<div id="post-body-content">

					<!-- Add New Rule -->
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Add New Rule', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<form method="post" action="">
								<?php wp_nonce_field( 'wtc_rules_nonce' ); ?>
								<input type="hidden" name="wtc_save_rules" value="1">

								<table class="form-table">
									<tr>
										<th scope="row"><label for="new_group"><?php esc_html_e( 'Shipping Method', 'wtc-shipping' ); ?></label></th>
										<td>
											<select name="new_rule[group]" id="new_group" required>
												<option value=""><?php esc_html_e( 'Select method...', 'wtc-shipping' ); ?></option>
												<?php foreach ( $groups as $key => $group ) : ?>
													<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $group['label'] ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label><?php esc_html_e( 'Weight Range (oz)', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="number" step="0.01" name="new_rule[min_weight]" placeholder="<?php esc_attr_e( 'Min', 'wtc-shipping' ); ?>" class="small-text">
											<span> &mdash; </span>
											<input type="number" step="0.01" name="new_rule[max_weight]" placeholder="<?php esc_attr_e( 'Max', 'wtc-shipping' ); ?>" class="small-text">
											<p class="description"><?php esc_html_e( 'Leave blank for any weight.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label><?php esc_html_e( 'Quantity Range', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="number" name="new_rule[min_qty]" placeholder="<?php esc_attr_e( 'Min', 'wtc-shipping' ); ?>" class="small-text">
											<span> &mdash; </span>
											<input type="number" name="new_rule[max_qty]" placeholder="<?php esc_attr_e( 'Max', 'wtc-shipping' ); ?>" class="small-text">
											<p class="description"><?php esc_html_e( 'Leave blank for any quantity.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="new_country"><?php esc_html_e( 'Country / Zone', 'wtc-shipping' ); ?></label></th>
										<td>
											<select name="new_rule[country]" id="new_country">
												<option value=""><?php esc_html_e( 'Any Country', 'wtc-shipping' ); ?></option>
												<option value="US"><?php esc_html_e( 'United States', 'wtc-shipping' ); ?></option>
												<option value="CA"><?php esc_html_e( 'Canada', 'wtc-shipping' ); ?></option>
												<option value="GB"><?php esc_html_e( 'United Kingdom', 'wtc-shipping' ); ?></option>
												<option value="AU"><?php esc_html_e( 'Australia', 'wtc-shipping' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="new_category"><?php esc_html_e( 'Product Category', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="text" name="new_rule[category]" id="new_category" placeholder="<?php esc_attr_e( 'e.g., clothing', 'wtc-shipping' ); ?>" class="regular-text">
											<p class="description"><?php esc_html_e( 'Category slug, leave blank for any.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="new_cost"><?php esc_html_e( 'Action / Cost', 'wtc-shipping' ); ?></label></th>
										<td>
											<select name="new_rule[action]" class="wtc-rule-action-select">
												<option value="fixed_cost"><?php esc_html_e( 'Set Fixed Cost', 'wtc-shipping' ); ?></option>
												<option value="flat_rate_box"><?php esc_html_e( 'Use Flat Rate Box', 'wtc-shipping' ); ?></option>
												<option value="disable_method"><?php esc_html_e( 'Disable Shipping Method', 'wtc-shipping' ); ?></option>
											</select>

											<span class="wtc-rule-action-field" id="action-fixed-cost-field">
												<input type="number" step="0.01" min="0" name="new_rule[cost]" placeholder="0.00" class="small-text">
											</span>

											<span class="wtc-rule-action-field" id="action-flat-rate-box-field" hidden>
												<select name="new_rule[flat_rate_box]">
													<option value=""><?php esc_html_e( 'Select a box...', 'wtc-shipping' ); ?></option>
													<?php
													$flat_rate_boxes = wtcc_get_flat_rate_boxes();
													foreach ( $flat_rate_boxes as $box_key => $box_details ) {
														echo '<option value="' . esc_attr( $box_key ) . '">' . esc_html( $box_details['name'] ) . '</option>';
													}
													?>
												</select>
											</span>
											<p class="description"><?php esc_html_e( 'Define what happens when this rule matches.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
								</table>

								<p class="submit">
									<button type="submit" class="button button-primary">
										<?php esc_html_e( 'Add Rule', 'wtc-shipping' ); ?>
									</button>
								</p>
							</form>

							<script>
							document.addEventListener('DOMContentLoaded', function() {
								const actionSelect = document.querySelector('.wtc-rule-action-select');
								const costField = document.getElementById('action-fixed-cost-field');
								const boxField = document.getElementById('action-flat-rate-box-field');

								function toggleFields() {
									if (actionSelect.value === 'flat_rate_box') {
										costField.style.display = 'none';
										boxField.style.display = 'inline';
									} else if (actionSelect.value === 'disable_method') {
										costField.style.display = 'none';
										boxField.style.display = 'none';
									} else {
										costField.style.display = 'inline';
										boxField.style.display = 'none';
									}
								}

								actionSelect.addEventListener('change', toggleFields);
								toggleFields(); // Run on page load
							});
							</script>
						</div>
					</div>

					<!-- Current Rules -->
					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Active Rules', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<p class="description">
								<?php esc_html_e( 'Rules are processed from top to bottom. The first matching rule wins.', 'wtc-shipping' ); ?>
							</p>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th scope="col"><?php esc_html_e( 'Group', 'wtc-shipping' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Weight (oz)', 'wtc-shipping' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Qty', 'wtc-shipping' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Country/Zone', 'wtc-shipping' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Category', 'wtc-shipping' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Action / Cost', 'wtc-shipping' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Actions', 'wtc-shipping' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $rules ) ) : ?>
										<tr>
											<td colspan="7">
												<?php esc_html_e( 'No rules configured. Your base rates will be applied automatically.', 'wtc-shipping' ); ?>
											</td>
										</tr>
									<?php else : ?>
										<?php foreach ( $rules as $idx => $rule ) : ?>
											<tr>
												<td>
													<?php
													$group_key = $rule['group'] ?? '';
													echo esc_html( $groups[ $group_key ]['label'] ?? $group_key );
													?>
												</td>
												<td>
													<?php
													if ( ! empty( $rule['min_weight'] ) || ! empty( $rule['max_weight'] ) ) {
														if ( ! empty( $rule['min_weight'] ) && ! empty( $rule['max_weight'] ) ) {
															echo esc_html( $rule['min_weight'] . ' - ' . $rule['max_weight'] );
														} elseif ( ! empty( $rule['min_weight'] ) ) {
															echo esc_html( '>= ' . $rule['min_weight'] );
														} else {
															echo esc_html( '<= ' . $rule['max_weight'] );
														}
													} else {
														echo '<span class="description">' . esc_html__( 'Any', 'wtc-shipping' ) . '</span>';
													}
													?>
												</td>
												<td>
													<?php
													if ( ! empty( $rule['min_qty'] ) || ! empty( $rule['max_qty'] ) ) {
														if ( ! empty( $rule['min_qty'] ) && ! empty( $rule['max_qty'] ) ) {
															echo esc_html( $rule['min_qty'] . '-' . $rule['max_qty'] );
														} elseif ( ! empty( $rule['min_qty'] ) ) {
															echo esc_html( '>= ' . $rule['min_qty'] );
														} else {
															echo esc_html( '<= ' . $rule['max_qty'] );
														}
													} else {
														echo '<span class="description">' . esc_html__( 'Any', 'wtc-shipping' ) . '</span>';
													}
													?>
												</td>
												<td>
													<?php
													if ( ! empty( $rule['country'] ) ) {
														echo esc_html( $rule['country'] );
													} else {
														echo '<span class="description">' . esc_html__( 'Any', 'wtc-shipping' ) . '</span>';
													}
													?>
												</td>
												<td>
													<?php echo esc_html( ! empty( $rule['category'] ) ? $rule['category'] : '—' ); ?>
												</td>
												<td>
													<?php
													$action = $rule['action'] ?? 'fixed_cost';
													if ( $action === 'fixed_cost' ) {
														echo '<strong>$' . esc_html( number_format( (float) ( $rule['cost'] ?? 0 ), 2 ) ) . '</strong>';
													} elseif ( $action === 'flat_rate_box' ) {
														$box_key = $rule['flat_rate_box'] ?? '';
														$box_name = wtcc_get_flat_rate_boxes()[ $box_key ]['name'] ?? 'N/A';
														echo '<em>' . esc_html( $box_name ) . '</em>';
													} elseif ( $action === 'disable_method' ) {
														echo '<span class="description">' . esc_html__( 'Disabled', 'wtc-shipping' ) . '</span>';
													}
													?>
												</td>
												<td>
													<?php
													$delete_url = add_query_arg( 'delete_rule', $idx );
													$nonce_delete_url = wp_nonce_url( $delete_url, 'wtc_delete_rule' );
													?>
													<a href="<?php echo esc_url( $nonce_delete_url ); ?>" 
													   class="button button-small"
													   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this rule?', 'wtc-shipping' ); ?>');">
														<?php esc_html_e( 'Delete', 'wtc-shipping' ); ?>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>

				</div><!-- #post-body-content -->

				<!-- Sidebar -->
				<div id="postbox-container-1" class="postbox-container">

					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'How to Use', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<p><strong><?php esc_html_e( 'Priority:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'Rules are processed top-to-bottom. The first match wins.', 'wtc-shipping' ); ?></p>
							<p><strong><?php esc_html_e( 'Conditions:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'Set weight, quantity, destination, or category to target specific orders.', 'wtc-shipping' ); ?></p>
							<p><strong><?php esc_html_e( 'Cost:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'This fixed cost overrides the base calculated rate.', 'wtc-shipping' ); ?></p>
							<p><strong><?php esc_html_e( 'Fallback:', 'wtc-shipping' ); ?></strong> <?php esc_html_e( 'If no rules match, the base rates are used.', 'wtc-shipping' ); ?></p>
						</div>
					</div>

					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Current Base Rates', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<p class="description"><?php esc_html_e( 'These rates apply when no rules match:', 'wtc-shipping' ); ?></p>
							<table class="widefat wtc-rates-sidebar-table">
								<?php foreach ( $groups as $key => $group ) : 
									$rate = $config[ $key ] ?? array();
									$base = $rate['base_cost'] ?? 0;
									$per_oz = $rate['per_oz'] ?? 0;
								?>
								<tr>
									<td><strong><?php echo esc_html( $group['label'] ); ?></strong></td>
									<td><?php echo esc_html( '$' . number_format( $base, 2 ) . ' + $' . number_format( $per_oz, 2 ) . '/oz' ); ?></td>
								</tr>
								<?php endforeach; ?>
							</table>
							<p class="wtc-rates-sidebar-table-container">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets' ) ); ?>" class="button button-secondary">
									<?php esc_html_e( 'Edit Base Rates', 'wtc-shipping' ); ?>
								</a>
							</p>
						</div>
					</div>

					<div class="postbox">
						<h2 class="hndle"><span><?php esc_html_e( 'Zone Multipliers', 'wtc-shipping' ); ?></span></h2>
						<div class="inside">
							<table class="widefat wtc-rates-sidebar-table">
								<?php 
								$multipliers = $config['zone_multipliers'] ?? array();
								$zones = array( 'usa' => 'USA', 'canada' => 'Canada', 'uk' => 'UK', 'eu1' => 'Europe' );
								foreach ( $zones as $key => $label ) : 
									$mult = $multipliers[ $key ] ?? 1.0;
								?>
								<tr>
									<td><?php echo esc_html( $label ); ?></td>
									<td><?php echo esc_html( $mult . 'x' ); ?></td>
								</tr>
								<?php endforeach; ?>
							</table>
						</div>
					</div>

				</div><!-- #postbox-container-1 -->

			</div><!-- #post-body -->
		</div><!-- #poststuff -->
	</div>
	<?php
}

/**
 * Save shipping rules
 */
function wtcc_shipping_save_rules( $data ) {
	$rules = get_option( 'wtcc_shipping_rules', array() );

	if ( ! empty( $data['new_rule']['group'] ) && isset( $data['new_rule']['action'] ) ) {
		$new_rule = array(
			'group'      => sanitize_key( $data['new_rule']['group'] ),
			'min_weight' => ! empty( $data['new_rule']['min_weight'] ) ? floatval( $data['new_rule']['min_weight'] ) : '',
			'max_weight' => ! empty( $data['new_rule']['max_weight'] ) ? floatval( $data['new_rule']['max_weight'] ) : '',
			'min_qty'    => ! empty( $data['new_rule']['min_qty'] ) ? intval( $data['new_rule']['min_qty'] ) : '',
			'max_qty'    => ! empty( $data['new_rule']['max_qty'] ) ? intval( $data['new_rule']['max_qty'] ) : '',
			'country'    => sanitize_text_field( $data['new_rule']['country'] ?? '' ),
			'category'   => sanitize_text_field( $data['new_rule']['category'] ?? '' ),
			'action'     => sanitize_key( $data['new_rule']['action'] ),
		);

		switch ( $new_rule['action'] ) {
			case 'fixed_cost':
				$new_rule['cost'] = isset( $data['new_rule']['cost'] ) ? floatval( $data['new_rule']['cost'] ) : 0;
				break;
			case 'flat_rate_box':
				$new_rule['flat_rate_box'] = sanitize_key( $data['new_rule']['flat_rate_box'] ?? '' );
				break;
		}

		$rules[] = $new_rule;
		update_option( 'wtcc_shipping_rules', $rules );
	}
}

/**
 * Delete shipping rule
 */
function wtcc_shipping_delete_rule( $index ) {
	$rules = get_option( 'wtcc_shipping_rules', array() );

	if ( isset( $rules[ $index ] ) ) {
		unset( $rules[ $index ] );
		$rules = array_values( $rules );
		update_option( 'wtcc_shipping_rules', $rules );
	}
}
