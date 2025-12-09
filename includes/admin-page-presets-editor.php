<?php
/**
 * Preset Editor - Clean WordPress Native UI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all presets from database
 */
function wtcc_get_all_presets() {
	$presets = get_option( 'wtcc_shipping_presets', array() );
	
	if ( empty( $presets ) ) {
		$presets = wtcc_get_default_presets();
		update_option( 'wtcc_shipping_presets', $presets );
	}
	
	return $presets;
}

/**
 * Get default presets
 */
function wtcc_get_default_presets() {
	return array(
		'envelope_small' => array(
			'id'            => 'envelope_small',
			'name'          => 'Small Envelope',
			'description'   => 'Documents, photos, small flat items',
			'default_length' => 10,
			'default_width'  => 7,
			'default_height' => 0.5,
			'default_weight' => 1,
			'max_weight'    => 3,
			'dimension_unit' => 'in',
			'weight_unit'   => 'oz',
			'active'        => true,
			'is_default'    => true,
		),
		'envelope_large' => array(
			'id'            => 'envelope_large',
			'name'          => 'Large Envelope',
			'description'   => 'Large documents, magazines',
			'default_length' => 15,
			'default_width'  => 12,
			'default_height' => 1,
			'default_weight' => 8,
			'max_weight'    => 13,
			'dimension_unit' => 'in',
			'weight_unit'   => 'oz',
			'active'        => true,
			'is_default'    => true,
		),
		'box_small' => array(
			'id'            => 'box_small',
			'name'          => 'Small Box',
			'description'   => 'Small products, accessories',
			'default_length' => 6,
			'default_width'  => 6,
			'default_height' => 4,
			'default_weight' => 8,
			'max_weight'    => 16,
			'dimension_unit' => 'in',
			'weight_unit'   => 'oz',
			'active'        => true,
			'is_default'    => true,
		),
		'box_medium' => array(
			'id'            => 'box_medium',
			'name'          => 'Medium Box',
			'description'   => 'Standard products',
			'default_length' => 12,
			'default_width'  => 10,
			'default_height' => 8,
			'default_weight' => 24,
			'max_weight'    => 48,
			'dimension_unit' => 'in',
			'weight_unit'   => 'oz',
			'active'        => true,
			'is_default'    => true,
		),
		'box_large' => array(
			'id'            => 'box_large',
			'name'          => 'Large Box',
			'description'   => 'Large products, bulk orders',
			'default_length' => 18,
			'default_width'  => 14,
			'default_height' => 12,
			'default_weight' => 2,
			'max_weight'    => 70,
			'dimension_unit' => 'in',
			'weight_unit'   => 'lb',
			'active'        => true,
			'is_default'    => true,
		),
		'tube_roll' => array(
			'id'            => 'tube_roll',
			'name'          => 'Tube/Roll',
			'description'   => 'Posters, documents, rolled items',
			'default_length' => 24,
			'default_width'  => 4,
			'default_height' => 4,
			'default_weight' => 16,
			'max_weight'    => 48,
			'dimension_unit' => 'in',
			'weight_unit'   => 'oz',
			'active'        => true,
			'is_default'    => true,
		),
	);
}

/**
 * Save preset
 */
function wtcc_save_preset( $preset ) {
	if ( empty( $preset['id'] ) ) {
		return false;
	}
	
	$presets = wtcc_get_all_presets();
	
	$sanitized = array(
		'id'            => sanitize_key( $preset['id'] ),
		'name'          => sanitize_text_field( $preset['name'] ),
		'description'   => sanitize_textarea_field( $preset['description'] ?? '' ),
		'default_length' => floatval( $preset['default_length'] ?? 0 ),
		'default_width'  => floatval( $preset['default_width'] ?? 0 ),
		'default_height' => floatval( $preset['default_height'] ?? 0 ),
		'default_weight' => floatval( $preset['default_weight'] ?? 0 ),
		'max_weight'    => floatval( $preset['max_weight'] ?? 70 ),
		'dimension_unit' => sanitize_key( $preset['dimension_unit'] ?? 'in' ),
		'weight_unit'   => sanitize_key( $preset['weight_unit'] ?? 'oz' ),
		'active'        => ! empty( $preset['active'] ),
		'is_default'    => ! empty( $preset['is_default'] ),
	);
	
	$presets[ $sanitized['id'] ] = $sanitized;
	
	return update_option( 'wtcc_shipping_presets', $presets );
}

/**
 * Delete preset
 */
function wtcc_delete_preset( $preset_id ) {
	$presets = wtcc_get_all_presets();
	
	if ( isset( $presets[ $preset_id ] ) ) {
		unset( $presets[ $preset_id ] );
		return update_option( 'wtcc_shipping_presets', $presets );
	}
	
	return false;
}

/**
 * AJAX save preset
 */
add_action( 'wp_ajax_wtcc_save_preset', 'wtcc_ajax_save_preset' );
function wtcc_ajax_save_preset() {
	if ( ! check_ajax_referer( 'wtcc_preset_editor', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
	}
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
	}
	
	$preset = isset( $_POST['preset'] ) ? (array) $_POST['preset'] : array();
	
	if ( empty( $preset['id'] ) || empty( $preset['name'] ) ) {
		wp_send_json_error( array( 'message' => 'ID and name are required' ) );
	}
	
	if ( wtcc_save_preset( $preset ) ) {
		wp_send_json_success( array( 'message' => 'Preset saved' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Failed to save' ) );
	}
}

/**
 * AJAX delete preset
 */
add_action( 'wp_ajax_wtcc_delete_preset', 'wtcc_ajax_delete_preset' );
function wtcc_ajax_delete_preset() {
	if ( ! check_ajax_referer( 'wtcc_preset_editor', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
	}
	
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
	}
	
	$preset_id = sanitize_key( $_POST['preset_id'] ?? '' );
	
	if ( wtcc_delete_preset( $preset_id ) ) {
		wp_send_json_success( array( 'message' => 'Preset deleted' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Failed to delete' ) );
	}
}

/**
 * Render preset editor page
 */
function wtcc_render_preset_editor_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'wtc-shipping' ) );
	}

	// Handle form submission for non-JS fallback
	if ( isset( $_POST['wtcc_preset_action'] ) && check_admin_referer( 'wtcc_preset_editor_action' ) ) {
		$action = sanitize_key( $_POST['wtcc_preset_action'] );
		
		if ( 'save' === $action && ! empty( $_POST['preset'] ) ) {
			wtcc_save_preset( wp_unslash( (array) $_POST['preset'] ) );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Preset saved.', 'wtc-shipping' ) . '</p></div>';
		} elseif ( 'delete' === $action && ! empty( $_POST['preset_id'] ) ) {
			wtcc_delete_preset( sanitize_key( $_POST['preset_id'] ) );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Preset deleted.', 'wtc-shipping' ) . '</p></div>';
		}
	}
	
	$presets = wtcc_get_all_presets();
	$editing_preset = null;
	$is_new = isset( $_GET['edit'] ) && $_GET['edit'] === 'new';
	
	if ( isset( $_GET['edit'] ) && $_GET['edit'] !== 'new' ) {
		$edit_id = sanitize_key( $_GET['edit'] );
		if ( isset( $presets[ $edit_id ] ) ) {
			$editing_preset = $presets[ $edit_id ];
		}
	}
	
	// If editing or creating, show the edit form
	if ( $is_new || $editing_preset ) {
		wtcc_render_preset_form( $editing_preset );
		return;
	}
	
	// Otherwise show the list
	?>
	<div class="wrap">
		<?php wtcc_admin_header(__( 'Shipping Presets', 'wtc-shipping' ), 'Add New', admin_url( 'admin.php?page=wtc-core-shipping-presets-editor&edit=new' )); ?>
		
		<p class="description">
			<?php esc_html_e( 'Presets define default package dimensions and weights for products. They are the core of the shipping engine.', 'wtc-shipping' ); ?>
		</p>

		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-primary"><?php esc_html_e( 'Name', 'wtc-shipping' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Dimensions (L×W×H)', 'wtc-shipping' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Max Weight', 'wtc-shipping' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php if ( empty( $presets ) ) : ?>
					<tr>
						<td colspan="4"><?php esc_html_e( 'No presets found.', 'wtc-shipping' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $presets as $preset ) : ?>
					<tr>
						<td class="column-primary">
							<strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets-editor&edit=' . $preset['id'] ) ); ?>" class="row-title"><?php echo esc_html( $preset['name'] ); ?></a></strong>
							<?php if ( ! empty( $preset['is_default'] ) ) : ?>
								<span class="dashicons dashicons-star-filled wtc-shipping-preset-star" title="<?php esc_attr_e( 'Default Preset', 'wtc-shipping' ); ?>"></span>
							<?php endif; ?>
							<div class="row-actions visible">
								<span class="edit"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets-editor&edit=' . $preset['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'wtc-shipping' ); ?></a> | </span>
								<span class="trash">
									<form method="post" class="wtc-shipping-delete-form" style="display:inline;">
										<?php wp_nonce_field( 'wtcc_preset_editor_action' ); ?>
										<input type="hidden" name="wtcc_preset_action" value="delete">
										<input type="hidden" name="preset_id" value="<?php echo esc_attr( $preset['id'] ); ?>">
										<button type="submit" class="button-link" style="color:#b32d2e;text-decoration:none;padding:0;cursor:pointer;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this preset?', 'wtc-shipping' ); ?>');"><?php esc_html_e( 'Delete', 'wtc-shipping' ); ?></button>
									</form>
								</span>
							</div>
							<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'wtc-shipping' ); ?></span></button>
						</td>
						<td data-colname="<?php esc_attr_e( 'Dimensions', 'wtc-shipping' ); ?>">
							<?php echo esc_html( ($preset['default_length'] ?? '') . ' × ' . ($preset['default_width'] ?? '') . ' × ' . ($preset['default_height'] ?? '') . ' ' . ($preset['dimension_unit'] ?? '') ); ?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Max Weight', 'wtc-shipping' ); ?>">
							<?php echo esc_html( ($preset['max_weight'] ?? '') . ' ' . ($preset['weight_unit'] ?? '') ); ?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Status', 'wtc-shipping' ); ?>">
							<?php if ( ! empty( $preset['active'] ) ) : ?>
								<span style="color: #2271b1;"><?php esc_html_e( 'Active', 'wtc-shipping' ); ?></span>
							<?php else : ?>
								<span><?php esc_html_e( 'Inactive', 'wtc-shipping' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render the preset edit form
 */
function wtcc_render_preset_form( $preset = null ) {
	$is_new = empty( $preset );
	$page_title = $is_new ? __( 'Add New Preset', 'wtc-shipping' ) : __( 'Edit Preset', 'wtc-shipping' );
	?>
	<div class="wrap">
		<?php wtcc_admin_header($page_title, 'Back to List', admin_url( 'admin.php?page=wtc-core-shipping-presets-editor' )); ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wtc-core-shipping-presets-editor' ) ); ?>">
			<?php wp_nonce_field( 'wtcc_preset_editor_action' ); ?>
			<input type="hidden" name="wtcc_preset_action" value="save">
			<?php if (!$is_new): ?>
				<input type="hidden" name="preset[id]" value="<?php echo esc_attr( $preset['id'] ?? '' ); ?>">
			<?php endif; ?>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<!-- Sidebar -->
					<div id="postbox-container-1" class="postbox-container">
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading(__( 'Publish', 'wtc-shipping' )); ?></h2>
							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="major-publishing-actions">
										<div id="delete-action">
											<?php if ( ! $is_new ) : ?>
												<a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wtc-core-shipping-presets-editor&action=delete&preset_id=' . ( $preset['id'] ?? '' ) ), 'wtcc_preset_editor_action' ) ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this preset?', 'wtc-shipping' ); ?>');">
													<?php esc_html_e( 'Delete', 'wtc-shipping' ); ?>
												</a>
											<?php endif; ?>
										</div>
										<div id="publishing-action">
											<span class="spinner"></span>
											<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Save Preset', 'wtc-shipping' ); ?>">
										</div>
										<div class="clear"></div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Main Content -->
					<div id="post-body-content">
						<div class="postbox">
							<h2 class="hndle"><?php echo wtcc_section_heading(__( 'Preset Details', 'wtc-shipping' )); ?></h2>
							<div class="inside">
								<table class="form-table">
									<tr>
										<th scope="row"><label for="preset_name"><?php esc_html_e( 'Name', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="text" id="preset_name" name="preset[name]" 
												value="<?php echo esc_attr( $preset['name'] ?? '' ); ?>" 
												class="regular-text" required>
										</td>
									</tr>
									<?php if ($is_new): ?>
									<tr>
										<th scope="row"><label for="preset_id"><?php esc_html_e( 'ID', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="text" id="preset_id" name="preset[id]" 
												value="<?php echo esc_attr( $preset['id'] ?? '' ); ?>" 
												class="regular-text" 
												pattern="[a-z0-9_]+"
												required>
											<p class="description"><?php esc_html_e( 'Lowercase letters, numbers, and underscores only. Cannot be changed later.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<?php endif; ?>
									<tr>
										<th scope="row"><label for="preset_description"><?php esc_html_e( 'Description', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="text" id="preset_description" name="preset[description]" 
												value="<?php echo esc_attr( $preset['description'] ?? '' ); ?>" 
												class="large-text">
											<p class="description"><?php esc_html_e( 'A short description for internal reference.', 'wtc-shipping' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Dimensions', 'wtc-shipping' ); ?></th>
										<td>
											<input type="number" step="0.01" min="0" name="preset[default_length]" 
												value="<?php echo esc_attr( $preset['default_length'] ?? '' ); ?>" 
												class="small-text" placeholder="<?php esc_attr_e( 'L', 'wtc-shipping' ); ?>"> ×
											<input type="number" step="0.01" min="0" name="preset[default_width]" 
												value="<?php echo esc_attr( $preset['default_width'] ?? '' ); ?>" 
												class="small-text" placeholder="<?php esc_attr_e( 'W', 'wtc-shipping' ); ?>"> ×
											<input type="number" step="0.01" min="0" name="preset[default_height]" 
												value="<?php echo esc_attr( $preset['default_height'] ?? '' ); ?>" 
												class="small-text" placeholder="<?php esc_attr_e( 'H', 'wtc-shipping' ); ?>">
											<select name="preset[dimension_unit]">
												<option value="in" <?php selected( ( $preset['dimension_unit'] ?? 'in' ), 'in' ); ?>><?php esc_html_e( 'in', 'wtc-shipping' ); ?></option>
												<option value="cm" <?php selected( ( $preset['dimension_unit'] ?? 'in' ), 'cm' ); ?>><?php esc_html_e( 'cm', 'wtc-shipping' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><label for="preset_max_weight"><?php esc_html_e( 'Max Weight', 'wtc-shipping' ); ?></label></th>
										<td>
											<input type="number" step="0.01" min="0" id="preset_max_weight" name="preset[max_weight]" 
												value="<?php echo esc_attr( $preset['max_weight'] ?? '70' ); ?>" 
												class="small-text">
											<select name="preset[weight_unit]">
												<option value="oz" <?php selected( ( $preset['weight_unit'] ?? 'oz' ), 'oz' ); ?>><?php esc_html_e( 'oz', 'wtc-shipping' ); ?></option>
												<option value="lb" <?php selected( ( $preset['weight_unit'] ?? 'oz' ), 'lb' ); ?>><?php esc_html_e( 'lb', 'wtc-shipping' ); ?></option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></th>
										<td>
											<fieldset>
												<legend class="screen-reader-text"><span><?php esc_html_e( 'Status', 'wtc-shipping' ); ?></span></legend>
												<label>
													<input type="checkbox" name="preset[active]" value="1" 
														<?php checked( $preset['active'] ?? true ); ?>>
													<?php esc_html_e( 'Active', 'wtc-shipping' ); ?>
												</label>
											</fieldset>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</form>
	</div>
	<?php
}