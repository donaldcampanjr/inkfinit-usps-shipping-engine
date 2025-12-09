<?php
/**
 * WTC Preset Registry
 * Central place to define and extend presets.
 *
 * Use the filter `wtcc_shipping_presets` to add/modify presets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all presets with metadata.
 *
 * @return array
 */
function wtcc_shipping_get_presets() {
	$presets = array(
		'tee' => array(
			'label'         => 'T-Shirt (0.5 oz)',
			'weight'        => 0.5,
			'unit'          => 'oz',
			'length'        => 12,
			'width'         => 10,
			'height'        => 2,
			'dimensions_unit' => 'in',
			'max_weight'    => 8,
			'class'         => 't-shirt',
			'class_label'   => 'T-Shirt',
			'class_desc'    => 'Band tee up to 8 oz',
		),
		'hoodie' => array(
			'label'         => 'Hoodie (1.5 lb)',
			'weight'        => 1.5,
			'unit'          => 'lb',
			'length'        => 14,
			'width'         => 12,
			'height'        => 3,
			'dimensions_unit' => 'in',
			'max_weight'    => 48,
			'class'         => 'hoodie',
			'class_label'   => 'Hoodie',
			'class_desc'    => 'Heavy apparel and hoodies',
		),
		'vinyl' => array(
			'label'         => 'Vinyl (0.5 lb)',
			'weight'        => 0.5,
			'unit'          => 'lb',
			'length'        => 13,
			'width'         => 13,
			'height'        => 1.5,
			'dimensions_unit' => 'in',
			'max_weight'    => 16,
			'class'         => 'vinyl',
			'class_label'   => 'Vinyl',
			'class_desc'    => 'Vinyl record mailers',
		),
		'hat' => array(
			'label'         => 'Hat (0.25 oz)',
			'weight'        => 0.25,
			'unit'          => 'oz',
			'length'        => 10,
			'width'         => 10,
			'height'        => 6,
			'dimensions_unit' => 'in',
			'max_weight'    => 3,
			'class'         => 'hat',
			'class_label'   => 'Hat/Cap',
			'class_desc'    => 'Hats, caps, beanies',
		),
		'patch' => array(
			'label'         => 'Patch (0.1 oz)',
			'weight'        => 0.1,
			'unit'          => 'oz',
			'length'        => 6,
			'width'         => 4,
			'height'        => 0.5,
			'dimensions_unit' => 'in',
			'max_weight'    => 1,
			'class'         => 'patch',
			'class_label'   => 'Patch',
			'class_desc'    => 'Patches and small add-ons',
		),
		'sticker' => array(
			'label'         => 'Sticker (0.05 oz)',
			'weight'        => 0.05,
			'unit'          => 'oz',
			'length'        => 5,
			'width'         => 3,
			'height'        => 0.1,
			'dimensions_unit' => 'in',
			'max_weight'    => 0.5,
			'class'         => 'sticker',
			'class_label'   => 'Sticker',
			'class_desc'    => 'Stickers and flats',
		),
		'bundle' => array(
			'label'         => 'Bundle (2.0 lb)',
			'weight'        => 2.0,
			'unit'          => 'lb',
			'length'        => 16,
			'width'         => 12,
			'height'        => 4,
			'dimensions_unit' => 'in',
			'max_weight'    => 64,
			'class'         => 'bundle',
			'class_label'   => 'Bundle',
			'class_desc'    => 'Multi-item bundles',
		),
		'mousepad' => array(
			'label'         => 'Mousepad (6 oz)',
			'weight'        => 6,
			'unit'          => 'oz',
			'length'        => 11,
			'width'         => 9.5,
			'height'        => 0.5,
			'dimensions_unit' => 'in',
			'max_weight'    => 16,
			'class'         => 'mousepad',
			'class_label'   => 'Mousepad',
			'class_desc'    => 'Mousepads and soft desk mats',
		),
	);

	/**
	 * Filter to add/modify presets.
	 *
	 * Each preset requires:
	 * - label (string)
	 * - weight (float)
	 * - unit (oz|lb|g|kg)
	 * - length, width, height (float)
	 * - dimensions_unit (in|cm)
	 * - max_weight (float - in oz for consistency)
	 * - class (shipping class slug)
	 * Optional:
	 * - class_label (string)
	 * - class_desc (string)
	 */
	return apply_filters( 'wtcc_shipping_presets', $presets );
}

/**
 * Retrieve stored custom presets.
 *
 * @return array
 */
function wtcc_shipping_get_custom_presets() {
	$custom = get_option( 'wtc_custom_presets', array() );
	return is_array( $custom ) ? $custom : array();
}

/**
 * Merge user-defined presets into the registry.
 */
function wtcc_shipping_extend_with_custom_presets( $presets ) {
	$custom = wtcc_shipping_get_custom_presets();
	if ( ! empty( $custom ) && is_array( $custom ) ) {
		$presets = array_merge( $presets, $custom );
	}
	return $presets;
}
add_filter( 'wtcc_shipping_presets', 'wtcc_shipping_extend_with_custom_presets', 5 );

/**
 * Get preset data by shipping class slug
 *
 * @param string $class_slug
 * @return array|false
 */
function wtcc_get_preset_by_class( $class_slug ) {
	$presets = wtcc_shipping_get_presets();
	foreach ( $presets as $preset ) {
		if ( isset( $preset['class'] ) && $preset['class'] === $class_slug ) {
			return $preset;
		}
	}
	return false;
}

/**
 * Get shipping class data with dimensions and max weight
 * Stored in wp_term_meta so it syncs with WooCommerce shipping classes
 *
 * @param int|string $class_id_or_slug Shipping class term ID or slug
 * @return array
 */
function wtcc_get_shipping_class_data( $class_id_or_slug ) {
	$term = null;
	
	if ( is_numeric( $class_id_or_slug ) ) {
		$term = get_term( intval( $class_id_or_slug ), 'product_shipping_class' );
	} else {
		$term = get_term_by( 'slug', $class_id_or_slug, 'product_shipping_class' );
	}
	
	if ( ! $term || is_wp_error( $term ) ) {
		return array();
	}
	
	$data = array(
		'term_id'     => $term->term_id,
		'slug'        => $term->slug,
		'name'        => $term->name,
		'description' => $term->description,
	);
	
	// Get dimensions and weight from term meta
	$length    = get_term_meta( $term->term_id, 'wtcc_class_length', true );
	$width     = get_term_meta( $term->term_id, 'wtcc_class_width', true );
	$height    = get_term_meta( $term->term_id, 'wtcc_class_height', true );
	$dims_unit = get_term_meta( $term->term_id, 'wtcc_class_dimensions_unit', true );
	$max_wt    = get_term_meta( $term->term_id, 'wtcc_class_max_weight', true );
	
	$data['length']           = $length ? floatval( $length ) : 0;
	$data['width']            = $width ? floatval( $width ) : 0;
	$data['height']           = $height ? floatval( $height ) : 0;
	$data['dimensions_unit']  = $dims_unit ?: 'in';
	$data['max_weight']       = $max_wt ? floatval( $max_wt ) : 0;
	
	// Also check if there's a linked preset
	$preset_key = get_term_meta( $term->term_id, 'wtcc_preset_key', true );
	if ( $preset_key ) {
		$presets = wtcc_shipping_get_presets();
		if ( isset( $presets[ $preset_key ] ) ) {
			$data['preset'] = $presets[ $preset_key ];
		}
	}
	
	return $data;
}

/**
 * Update shipping class data (dimensions and max weight)
 * Stores in term meta for sync with WooCommerce
 *
 * @param int $term_id Shipping class term ID
 * @param array $data Data to update
 * @return bool
 */
function wtcc_update_shipping_class_data( $term_id, $data ) {
	if ( empty( $term_id ) || ! is_numeric( $term_id ) ) {
		return false;
	}
	
	$term = get_term( intval( $term_id ), 'product_shipping_class' );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}
	
	if ( isset( $data['length'] ) ) {
		update_term_meta( $term_id, 'wtcc_class_length', floatval( $data['length'] ) );
	}
	
	if ( isset( $data['width'] ) ) {
		update_term_meta( $term_id, 'wtcc_class_width', floatval( $data['width'] ) );
	}
	
	if ( isset( $data['height'] ) ) {
		update_term_meta( $term_id, 'wtcc_class_height', floatval( $data['height'] ) );
	}
	
	if ( isset( $data['dimensions_unit'] ) ) {
		update_term_meta( $term_id, 'wtcc_class_dimensions_unit', sanitize_text_field( $data['dimensions_unit'] ) );
	}
	
	if ( isset( $data['max_weight'] ) ) {
		update_term_meta( $term_id, 'wtcc_class_max_weight', floatval( $data['max_weight'] ) );
	}
	
	if ( isset( $data['preset_key'] ) ) {
		update_term_meta( $term_id, 'wtcc_preset_key', sanitize_key( $data['preset_key'] ) );
	}
	
	return true;
}

/**
 * Sync preset data to shipping classes on plugin initialization
 * Ensures shipping classes always have the latest preset dimensions and weights
 */
function wtcc_sync_presets_to_shipping_classes() {
	$presets = wtcc_shipping_get_presets();
	
	foreach ( $presets as $preset_key => $preset ) {
		if ( ! isset( $preset['class'] ) ) {
			continue;
		}
		
		$term = get_term_by( 'slug', $preset['class'], 'product_shipping_class' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}
		
		// Update dimensions and max weight
		$data = array(
			'length'           => $preset['length'] ?? 0,
			'width'            => $preset['width'] ?? 0,
			'height'           => $preset['height'] ?? 0,
			'dimensions_unit'  => $preset['dimensions_unit'] ?? 'in',
			'max_weight'       => $preset['max_weight'] ?? 0,
			'preset_key'       => $preset_key,
		);
		
		wtcc_update_shipping_class_data( $term->term_id, $data );
	}
}
add_action( 'wp_loaded', 'wtcc_sync_presets_to_shipping_classes', 15 );

/**
 * Get default/built-in presets (from wtcc_shipping_get_presets before custom merge)
 *
 * @return array
 */
function wtcc_shipping_get_default_presets() {
	$presets = array(
		'tee' => array(
			'label'         => 'T-Shirt (0.5 oz)',
			'weight'        => 0.5,
			'unit'          => 'oz',
			'length'        => 12,
			'width'         => 10,
			'height'        => 2,
			'dimensions_unit' => 'in',
			'max_weight'    => 8,
			'class'         => 't-shirt',
			'class_label'   => 'T-Shirt',
			'class_desc'    => 'Band tee up to 8 oz',
		),
		'hoodie' => array(
			'label'         => 'Hoodie (1.5 lb)',
			'weight'        => 1.5,
			'unit'          => 'lb',
			'length'        => 14,
			'width'         => 12,
			'height'        => 3,
			'dimensions_unit' => 'in',
			'max_weight'    => 48,
			'class'         => 'hoodie',
			'class_label'   => 'Hoodie',
			'class_desc'    => 'Heavy apparel and hoodies',
		),
		'vinyl' => array(
			'label'         => 'Vinyl (0.5 lb)',
			'weight'        => 0.5,
			'unit'          => 'lb',
			'length'        => 13,
			'width'         => 13,
			'height'        => 1.5,
			'dimensions_unit' => 'in',
			'max_weight'    => 16,
			'class'         => 'vinyl',
			'class_label'   => 'Vinyl',
			'class_desc'    => 'Vinyl record mailers',
		),
		'hat' => array(
			'label'         => 'Hat (0.25 oz)',
			'weight'        => 0.25,
			'unit'          => 'oz',
			'length'        => 10,
			'width'         => 10,
			'height'        => 6,
			'dimensions_unit' => 'in',
			'max_weight'    => 3,
			'class'         => 'hat',
			'class_label'   => 'Hat/Cap',
			'class_desc'    => 'Hats, caps, beanies',
		),
		'patch' => array(
			'label'         => 'Patch (0.1 oz)',
			'weight'        => 0.1,
			'unit'          => 'oz',
			'length'        => 6,
			'width'         => 4,
			'height'        => 0.5,
			'dimensions_unit' => 'in',
			'max_weight'    => 1,
			'class'         => 'patch',
			'class_label'   => 'Patch',
			'class_desc'    => 'Patches and small add-ons',
		),
		'sticker' => array(
			'label'         => 'Sticker (0.05 oz)',
			'weight'        => 0.05,
			'unit'          => 'oz',
			'length'        => 5,
			'width'         => 3,
			'height'        => 0.1,
			'dimensions_unit' => 'in',
			'max_weight'    => 0.5,
			'class'         => 'sticker',
			'class_label'   => 'Sticker',
			'class_desc'    => 'Stickers and flats',
		),
		'bundle' => array(
			'label'         => 'Bundle (2.0 lb)',
			'weight'        => 2.0,
			'unit'          => 'lb',
			'length'        => 16,
			'width'         => 12,
			'height'        => 4,
			'dimensions_unit' => 'in',
			'max_weight'    => 64,
			'class'         => 'bundle',
			'class_label'   => 'Bundle',
			'class_desc'    => 'Multi-item bundles',
		),
		'mousepad' => array(
			'label'         => 'Mousepad (6 oz)',
			'weight'        => 6,
			'unit'          => 'oz',
			'length'        => 11,
			'width'         => 9.5,
			'height'        => 0.5,
			'dimensions_unit' => 'in',
			'max_weight'    => 16,
			'class'         => 'mousepad',
			'class_label'   => 'Mousepad',
			'class_desc'    => 'Mousepads and soft desk mats',
		),
	);
	return $presets;
}
