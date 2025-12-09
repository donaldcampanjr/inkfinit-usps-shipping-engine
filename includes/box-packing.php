<?php
/**
 * Smart Box Packing Algorithm
 * 
 * Intelligently packs products into predefined boxes for accurate USPS shipping rates.
 * Handles multiple items of varying sizes, splits into multiple packages when needed.
 * 
 * @package WTC_Shipping_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get available box inventory with dimensions and max weight capacity
 * Dimensions in inches, weight in ounces
 * 
 * @return array Box definitions
 */
function wtcc_shipping_get_box_inventory() {
    $default_boxes = array(
        'poly_mailer_small' => array(
            'name'       => 'Poly Mailer (Small)',
            'length'     => 10,
            'width'      => 13,
            'height'     => 2,
            'max_weight' => 160, // 10 lbs in oz
            'tare_weight'=> 0.5, // packaging weight
            'type'       => 'soft', // soft packaging - good for shirts, soft items
        ),
        'poly_mailer_large' => array(
            'name'       => 'Poly Mailer (Large)',
            'length'     => 14.5,
            'width'      => 19,
            'height'     => 3,
            'max_weight' => 160,
            'tare_weight'=> 1,
            'type'       => 'soft',
        ),
        'box_small' => array(
            'name'       => 'Small Box',
            'length'     => 8,
            'width'      => 6,
            'height'     => 4,
            'max_weight' => 320, // 20 lbs
            'tare_weight'=> 4,
            'type'       => 'rigid',
        ),
        'box_medium' => array(
            'name'       => 'Medium Box',
            'length'     => 12,
            'width'      => 10,
            'height'     => 8,
            'max_weight' => 480, // 30 lbs
            'tare_weight'=> 8,
            'type'       => 'rigid',
        ),
        'box_large' => array(
            'name'       => 'Large Box',
            'length'     => 18,
            'width'      => 14,
            'height'     => 12,
            'max_weight' => 800, // 50 lbs
            'tare_weight'=> 16,
            'type'       => 'rigid',
        ),
        'box_xl' => array(
            'name'       => 'Extra Large Box',
            'length'     => 24,
            'width'      => 18,
            'height'     => 18,
            'max_weight' => 1120, // 70 lbs - USPS max
            'tare_weight'=> 24,
            'type'       => 'rigid',
        ),
    );
    
    // Filter to only enabled default boxes
    $enabled_defaults = get_option('wtcc_shipping_enabled_default_boxes', array_keys($default_boxes));
    if (!empty($enabled_defaults)) {
        $default_boxes = array_intersect_key($default_boxes, array_flip($enabled_defaults));
    }
    
    // Get custom boxes (already filtered by enabled status when saved)
    $custom_boxes = get_option('wtcc_shipping_custom_boxes', array());
    
    // Filter custom boxes to only enabled ones
    if (!empty($custom_boxes)) {
        $custom_boxes = array_filter($custom_boxes, function($box) {
            return !empty($box['enabled']);
        });
    }
    
    // Merge custom boxes with defaults
    $boxes = array_merge($default_boxes, $custom_boxes);
    
    // Allow customization via filter
    $boxes = apply_filters('wtcc_shipping_box_inventory', $boxes);
    
    return $boxes;
}

/**
 * Calculate volume of a box or item
 * 
 * @param float $length
 * @param float $width
 * @param float $height
 * @return float Volume in cubic inches
 */
function wtcc_shipping_calculate_volume($length, $width, $height) {
    return $length * $width * $height;
}

/**
 * Check if an item fits in a box (considering all rotations)
 * 
 * @param array $item Item dimensions [length, width, height]
 * @param array $box  Box dimensions [length, width, height]
 * @return bool|array False if doesn't fit, or best orientation if it does
 */
function wtcc_shipping_item_fits_in_box($item, $box) {
    // All possible rotations of the item
    $rotations = array(
        array($item['length'], $item['width'], $item['height']),
        array($item['length'], $item['height'], $item['width']),
        array($item['width'], $item['length'], $item['height']),
        array($item['width'], $item['height'], $item['length']),
        array($item['height'], $item['length'], $item['width']),
        array($item['height'], $item['width'], $item['length']),
    );
    
    foreach ($rotations as $rotation) {
        if ($rotation[0] <= $box['length'] && 
            $rotation[1] <= $box['width'] && 
            $rotation[2] <= $box['height']) {
            return array(
                'length' => $rotation[0],
                'width'  => $rotation[1],
                'height' => $rotation[2],
            );
        }
    }
    
    return false;
}

/**
 * Find the smallest box that fits an item
 * 
 * @param array $item Item with length, width, height, weight
 * @return string|false Box key or false if no box fits
 */
function wtcc_shipping_find_smallest_box_for_item($item) {
    $boxes = wtcc_shipping_get_box_inventory();
    $best_box = null;
    $best_volume = PHP_INT_MAX;
    
    foreach ($boxes as $box_key => $box) {
        // Check if item fits dimensionally
        if (!wtcc_shipping_item_fits_in_box($item, $box)) {
            continue;
        }
        
        // Check weight capacity
        if ($item['weight'] > $box['max_weight']) {
            continue;
        }
        
        // Calculate box volume
        $volume = wtcc_shipping_calculate_volume($box['length'], $box['width'], $box['height']);
        
        if ($volume < $best_volume) {
            $best_volume = $volume;
            $best_box = $box_key;
        }
    }
    
    return $best_box;
}

/**
 * Main box packing algorithm - First Fit Decreasing (FFD)
 * 
 * @param array $items Array of items with length, width, height, weight, quantity
 * @return array Packed boxes with their contents and dimensions
 */
function wtcc_shipping_pack_items($items) {
    if (empty($items)) {
        return array();
    }
    
    $boxes = wtcc_shipping_get_box_inventory();
    $packed_boxes = array();
    
    // Expand items by quantity and add volume for sorting
    $all_items = array();
    foreach ($items as $index => $item) {
        $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
        $item['volume'] = wtcc_shipping_calculate_volume(
            $item['length'], 
            $item['width'], 
            $item['height']
        );
        
        for ($i = 0; $i < $qty; $i++) {
            $item['original_index'] = $index;
            $item['instance'] = $i;
            $all_items[] = $item;
        }
    }
    
    // Sort by volume (largest first) - First Fit Decreasing heuristic
    usort($all_items, function($a, $b) {
        return $b['volume'] <=> $a['volume'];
    });
    
    // Try to pack each item
    foreach ($all_items as $item) {
        $packed = false;
        
        // First, try to fit in an existing packed box
        foreach ($packed_boxes as $box_index => &$packed_box) {
            if (wtcc_shipping_can_add_to_box($item, $packed_box)) {
                wtcc_shipping_add_item_to_box($item, $packed_box);
                $packed = true;
                break;
            }
        }
        unset($packed_box);
        
        // If couldn't fit in existing boxes, start a new box
        if (!$packed) {
            $new_box = wtcc_shipping_start_new_box($item);
            if ($new_box) {
                $packed_boxes[] = $new_box;
            } else {
                // Item is too large for any box - ship as-is with padding
                $packed_boxes[] = wtcc_shipping_create_oversized_package($item);
            }
        }
    }
    
    // Finalize box dimensions
    foreach ($packed_boxes as &$packed_box) {
        wtcc_shipping_finalize_box_dimensions($packed_box);
    }
    
    return $packed_boxes;
}

/**
 * Check if an item can be added to an existing packed box
 * 
 * @param array $item      Item to add
 * @param array $packed_box Existing packed box
 * @return bool
 */
function wtcc_shipping_can_add_to_box($item, $packed_box) {
    $box_def = wtcc_shipping_get_box_inventory()[$packed_box['box_type']] ?? null;
    if (!$box_def) {
        return false;
    }
    
    // Check weight
    $new_weight = $packed_box['current_weight'] + $item['weight'];
    if ($new_weight > $box_def['max_weight']) {
        return false;
    }
    
    // Check if item fits dimensionally
    if (!wtcc_shipping_item_fits_in_box($item, $box_def)) {
        return false;
    }
    
    // Check remaining volume (with 80% packing efficiency factor)
    $box_volume = wtcc_shipping_calculate_volume(
        $box_def['length'], 
        $box_def['width'], 
        $box_def['height']
    );
    $usable_volume = $box_volume * 0.8;
    $new_used_volume = $packed_box['used_volume'] + $item['volume'];
    
    if ($new_used_volume > $usable_volume) {
        return false;
    }
    
    // Check stacking height
    $new_height = $packed_box['stacked_height'] + $item['height'];
    if ($new_height > $box_def['height']) {
        // Try if item can be laid flat
        $min_item_dim = min($item['length'], $item['width'], $item['height']);
        if ($packed_box['stacked_height'] + $min_item_dim > $box_def['height']) {
            return false;
        }
    }
    
    return true;
}

/**
 * Add an item to an existing packed box
 * 
 * @param array $item       Item to add
 * @param array &$packed_box Packed box to modify
 */
function wtcc_shipping_add_item_to_box($item, &$packed_box) {
    $packed_box['items'][] = $item;
    $packed_box['current_weight'] += $item['weight'];
    $packed_box['used_volume'] += $item['volume'];
    
    // Update stacked height (items stack on top)
    $min_height = min($item['length'], $item['width'], $item['height']);
    $packed_box['stacked_height'] += $min_height;
}

/**
 * Start a new box for an item
 * 
 * @param array $item First item to put in box
 * @return array|null New packed box or null if item too large
 */
function wtcc_shipping_start_new_box($item) {
    $boxes = wtcc_shipping_get_box_inventory();
    
    // Sort boxes by volume to find smallest that fits
    uasort($boxes, function($a, $b) {
        $vol_a = wtcc_shipping_calculate_volume($a['length'], $a['width'], $a['height']);
        $vol_b = wtcc_shipping_calculate_volume($b['length'], $b['width'], $b['height']);
        return $vol_a <=> $vol_b;
    });
    
    foreach ($boxes as $box_key => $box) {
        // Check if item fits
        if (!wtcc_shipping_item_fits_in_box($item, $box)) {
            continue;
        }
        
        // Check weight
        if ($item['weight'] > $box['max_weight']) {
            continue;
        }
        
        // Create new packed box
        return array(
            'box_type'       => $box_key,
            'box_name'       => $box['name'],
            'box_dimensions' => array(
                'length' => $box['length'],
                'width'  => $box['width'],
                'height' => $box['height'],
            ),
            'items'          => array($item),
            'current_weight' => $item['weight'],
            'tare_weight'    => $box['tare_weight'],
            'used_volume'    => $item['volume'],
            'stacked_height' => min($item['length'], $item['width'], $item['height']),
        );
    }
    
    return null;
}

/**
 * Create package for oversized item that doesn't fit any box
 * 
 * @param array $item Oversized item
 * @return array Package definition
 */
function wtcc_shipping_create_oversized_package($item) {
    // Add padding around the item
    $padding = 2; // 2 inches on each side
    
    return array(
        'box_type'       => 'custom',
        'box_name'       => 'Custom Package',
        'box_dimensions' => array(
            'length' => $item['length'] + $padding,
            'width'  => $item['width'] + $padding,
            'height' => $item['height'] + $padding,
        ),
        'items'          => array($item),
        'current_weight' => $item['weight'],
        'tare_weight'    => 8, // Estimate for custom box materials
        'used_volume'    => $item['volume'],
        'stacked_height' => $item['height'],
        'is_oversized'   => true,
    );
}

/**
 * Finalize box dimensions - optimize to actual used space
 * 
 * @param array &$packed_box Packed box to finalize
 */
function wtcc_shipping_finalize_box_dimensions(&$packed_box) {
    // Calculate total weight including tare weight
    $packed_box['total_weight'] = $packed_box['current_weight'] + $packed_box['tare_weight'];
    
    // Round up to nearest ounce
    $packed_box['total_weight'] = ceil($packed_box['total_weight']);
    
    // For custom packages, dimensions are already set
    if ($packed_box['box_type'] === 'custom') {
        return;
    }
    
    // Keep the box dimensions as-is (we're using actual box sizes)
    // This gives USPS accurate dimensions for rate calculation
}

/**
 * Get shipping packages for USPS API
 * 
 * @param array $packed_boxes Result from wtcc_shipping_pack_items()
 * @return array Packages formatted for USPS API
 */
function wtcc_shipping_get_packages_for_usps($packed_boxes) {
    $packages = array();
    
    foreach ($packed_boxes as $index => $packed_box) {
        $packages[] = array(
            'package_id'  => $index + 1,
            'box_name'    => $packed_box['box_name'],
            'length'      => $packed_box['box_dimensions']['length'],
            'width'       => $packed_box['box_dimensions']['width'],
            'height'      => $packed_box['box_dimensions']['height'],
            'weight_oz'   => $packed_box['total_weight'],
            'weight_lbs'  => round($packed_box['total_weight'] / 16, 2),
            'item_count'  => count($packed_box['items']),
            'is_oversized'=> !empty($packed_box['is_oversized']),
        );
    }
    
    return $packages;
}

/**
 * Get packing summary for display
 * 
 * @param array $packed_boxes Result from wtcc_shipping_pack_items()
 * @return string Human-readable summary
 */
function wtcc_shipping_get_packing_summary($packed_boxes) {
    if (empty($packed_boxes)) {
        return 'No items to pack';
    }
    
    $total_packages = count($packed_boxes);
    $total_items = 0;
    $total_weight = 0;
    
    foreach ($packed_boxes as $box) {
        $total_items += count($box['items']);
        $total_weight += $box['total_weight'];
    }
    
    $summary = sprintf(
        '%d item(s) in %d package(s), total weight: %.1f lbs',
        $total_items,
        $total_packages,
        $total_weight / 16
    );
    
    return $summary;
}

/**
 * Calculate combined shipping rate for multiple packages
 * 
 * @param array  $packages      Packages from wtcc_shipping_get_packages_for_usps()
 * @param string $origin_zip    Origin ZIP code
 * @param string $dest_zip      Destination ZIP code
 * @param string $dest_country  Destination country code
 * @param string $service_type  USPS service type
 * @return float|false Combined rate or false on error
 */
function wtcc_shipping_calculate_multi_package_rate($packages, $origin_zip, $dest_zip, $dest_country = 'US', $service_type = 'USPS_GROUND_ADVANTAGE') {
    if (empty($packages)) {
        return false;
    }
    
    $total_rate = 0;
    
    foreach ($packages as $package) {
        // Build dimensions array for API call
        $dimensions = array(
            'length' => $package['length'],
            'width'  => $package['width'],
            'height' => $package['height'],
        );
        
        // Get rate for this package
        if (function_exists('wtcc_shipping_get_usps_rate')) {
            $rate = wtcc_shipping_get_usps_rate(
                $origin_zip,
                $dest_zip,
                $package['weight_oz'],
                $dest_country,
                $service_type,
                $dimensions
            );
            
            if ($rate === false) {
                // If API fails, use fallback calculation
                $rate = wtcc_shipping_calculate_fallback_rate($package, $dest_zip, $service_type);
            }
        } else {
            $rate = wtcc_shipping_calculate_fallback_rate($package, $dest_zip, $service_type);
        }
        
        if ($rate === false) {
            return false;
        }
        
        $total_rate += $rate;
    }
    
    return $total_rate;
}

/**
 * Fallback rate calculation when API is unavailable
 * 
 * @param array  $package     Package details
 * @param string $dest_zip    Destination ZIP
 * @param string $service_type Service type
 * @return float Estimated rate
 */
function wtcc_shipping_calculate_fallback_rate($package, $dest_zip, $service_type) {
    $weight_lbs = $package['weight_lbs'];
    
    // Base rates per service (rough estimates)
    $base_rates = array(
        'USPS_GROUND_ADVANTAGE' => 5.00,
        'PRIORITY_MAIL'         => 8.00,
        'PRIORITY_MAIL_EXPRESS' => 25.00,
    );
    
    $base = $base_rates[$service_type] ?? 5.00;
    
    // Add per-pound rate
    $per_lb_rates = array(
        'USPS_GROUND_ADVANTAGE' => 0.50,
        'PRIORITY_MAIL'         => 0.75,
        'PRIORITY_MAIL_EXPRESS' => 1.50,
    );
    
    $per_lb = $per_lb_rates[$service_type] ?? 0.50;
    
    // Calculate dimensional weight
    $dim_weight = ($package['length'] * $package['width'] * $package['height']) / 166;
    $billable_weight = max($weight_lbs, $dim_weight);
    
    return $base + ($billable_weight * $per_lb);
}

/**
 * Debug function to visualize box packing
 * 
 * @param array $packed_boxes Result from wtcc_shipping_pack_items()
 * @return string HTML representation
 */
function wtcc_shipping_visualize_packing($packed_boxes) {
    if (empty($packed_boxes)) {
        return '<p>No packages</p>';
    }
    
    $html = '<div class="wtcc-packing-visualization">';
    
    foreach ($packed_boxes as $index => $box) {
        $html .= sprintf(
            '<div class="wtcc-package">
                <h4>Package %d: %s</h4>
                <p><strong>Dimensions:</strong> %.1f" × %.1f" × %.1f"</p>
                <p><strong>Weight:</strong> %.1f oz (%.2f lbs)</p>
                <p><strong>Items:</strong> %d</p>
                <ul>%s</ul>
            </div>',
            $index + 1,
            esc_html($box['box_name']),
            $box['box_dimensions']['length'],
            $box['box_dimensions']['width'],
            $box['box_dimensions']['height'],
            $box['total_weight'],
            $box['total_weight'] / 16,
            count($box['items']),
            wtcc_shipping_list_box_items($box['items'])
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate list of items in a box
 * 
 * @param array $items Items in box
 * @return string HTML list items
 */
function wtcc_shipping_list_box_items($items) {
    $html = '';
    
    foreach ($items as $item) {
        $name = isset($item['name']) ? esc_html($item['name']) : 'Item';
        $html .= sprintf(
            '<li>%s (%.1f" × %.1f" × %.1f", %.1f oz)</li>',
            $name,
            $item['length'],
            $item['width'],
            $item['height'],
            $item['weight']
        );
    }
    
    return $html;
}
