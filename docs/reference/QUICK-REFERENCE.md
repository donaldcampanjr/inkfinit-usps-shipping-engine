# Inkfinit Shipping Engine - Quick Reference Card

<!-- markdownlint-disable MD013 -->

### Print this or keep it open while coding

---

## 🎯 THE 5-MINUTE SUMMARY

**What is it?** WordPress plugin that replaces WooCommerce Shipping with automatic rate calculation + smart box packing + complete order lifecycle.

**Tech Stack:** WordPress 5.8+, WooCommerce 8.0+, PHP 8.0+, USPS OAuth v3 API

**Key Power:** Admin sets 2 numbers (base cost + per-oz rate), system auto-calculates everything

**Load Order:** Matters! See plugin.php for sequence

**UI:** Native WordPress only (no custom CSS in PHP, no frameworks)

**Security:** 520+ lines of hardening (sanitize, escape, nonce, capability checks)

---

## 📁 CRITICAL FILES

| File | Purpose | Key Functions |
| ------ | --------- | ---------------- |
| `plugin.php` | ENTRY POINT | Load order critical |
| `core-functions.php` | Validation | `wtcc_shipping_sanitize_amount()` |
| `rule-engine.php` | THE BRAIN | `wtcc_shipping_calculate_cost_auto()` |
| `presets.php` | Product templates | `wtcc_shipping_get_presets()` |
| `box-packing.php` | Smart boxes | `wtcc_shipping_select_best_box()` |
| `shipping-methods.php` | WooCommerce integration | `WTC_Shipping_Method` class |
| `security-hardening.php` | Security | `wtcc_validate_zip_code()` |
| `admin-ui-helpers.php` | Admin UI | `wtcc_admin_header()` |

---

## 🔄 THE CALCULATION FLOW

```text
Customer Address + Order Weight → Rule Engine
                                     ↓
                            Get zone multiplier
                                     ↓
                            Get base cost + per-oz
                                     ↓
                        Formula: (base + weight × rate) × zone
                                     ↓
                            Return cost to WooCommerce
                                     ↓
                            Display rate option at checkout
```

---

## 💾 KEY OPTIONS (WordPress wp_options)

```phptext
get_option('wtcc_usps_consumer_key')           // USPS OAuth ID
get_option('wtcc_usps_consumer_secret')        // USPS OAuth secret
get_option('wtcc_shipping_rates_config')       // All rates + zones
get_option('wtc_enabled_methods')              // Which methods active
get_option('wtcc_shipping_boxes')              // Custom boxes
```

---

## 🔐 SECURITY CHECKLIST

Before submitting ANY code:

- [ ] All `$_POST` / `$_GET` sanitized with `sanitize_*()` function
- [ ] All output escaped with `esc_html()` / `esc_url()` / `esc_attr()`
- [ ] All admin pages verify `current_user_can('manage_woocommerce')`
- [ ] All form submissions verify nonce with `wp_verify_nonce()`
- [ ] No inline styles in PHP (use CSS files)
- [ ] No `eval()` or `create_function()`
- [ ] No direct `$wpdb` queries (use WordPress functions)

---

## 📝 FUNCTION TEMPLATE

```phptext
/**
 * Short description
 *
 * Longer description explaining what it does.
 *
 * @param string $param1 Description of param1.
 * @param int    $param2 Description of param2.
 * @return array Array of results.
 *
 * @since 1.1.0
 */
function wtcc_my_function( $param1, $param2 = 0 ) {
    // Validate
    $param1 = sanitize_text_field( $param1 );
    $param2 = intval( $param2 );
    
    // Logic
    $result = array();
    
    // Return
    return $result;
}
```

---

## 🎨 ADMIN UI TEMPLATE

```phptext
// CORRECT - Native WordPress
?>
<div class="wrap">
    <h1><?php esc_html_e( 'My Admin Page', 'wtc-shipping' ); ?></h1>
    
    <div class="postbox">
        <div class="postbox-header">
            <h2><span class="dashicons dashicons-archive"></span> Section Title</h2>
        </div>
        <div class="inside">
            <form method="post">
                <?php wp_nonce_field( 'my_action', 'my_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="setting">Setting Name</label></th>
                        <td>
                            <input type="text" name="setting" id="setting">
                            <p class="description">Description of setting</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
</div>
<?php
```

---

## 🧪 TESTING CHECKLIST

```text
BEFORE marking task complete:

[ ] Feature works (intended purpose)
[ ] No PHP errors/warnings in debug.log
[ ] No JavaScript console errors
[ ] Checkout still works
[ ] Rates calculate correctly
[ ] All inputs sanitized
[ ] All outputs escaped
[ ] Admin capability verified (if admin)
[ ] Nonce verified (if form)
[ ] Database data intact
[ ] Performance acceptable (no 30+ sec loads)
```

---

## 🚨 COMMON MISTAKES

| ❌ WRONG | ✅ CORRECT |
| --------- | ---------- |
| `echo $_POST['field']` | `echo esc_html(sanitize_text_field($_POST['field']))` |
| `echo '<div style="color:red">'` | Use CSS file, add class `<div class="error-box">` |
| File added to plugin.php without testing | Test checkout + admin after adding |
| New preset not in presets.php filter | Use `wtcc_shipping_presets` filter for extensions |
| Hardcoded URL | Use `admin_url()`, `plugin_url()`, etc. |
| `$result = $wpdb->get_results("SELECT...")` | Use `get_option()` or `get_post_meta()` |
| `var_dump()` in production | Use `error_log()` only if `WP_DEBUG` enabled |
| CSS in PHP `echo '<div style="...">'` | Create CSS file and enqueue |
| Modify global $GLOBALS variable | Use `get_option()` / `update_option()` |

---

## 🔌 HOOKS YOU CAN USE

```phptext
// Modify calculated cost
apply_filters('wtcc_shipping_calculated_cost', $cost, $group, $package, $zone)

// Add custom presets
apply_filters('wtcc_shipping_presets', $presets)

// Modify box inventory
apply_filters('wtcc_shipping_box_inventory', $boxes)

// Add custom zones
apply_filters('wtcc_country_zones', $zones)

// Custom shipment data
apply_filters('wtcc_shipping_shipment_data', $data, $order_id)

// Before label generation
do_action('wtcc_before_label_generation', $order_id, $shipment_data)

// After label generated
do_action('wtcc_after_label_generated', $order_id, $label_data)
```

---

## 📊 MOST USED FUNCTIONS

```phptext
// GET DATA
$config = wtcc_shipping_get_rates_config();
$presets = wtcc_shipping_get_presets();
$zones = wtcc_shipping_get_country_zones();
$cost = wtcc_shipping_calculate_cost_auto($group, $weight_oz, $zone);

// SET DATA
update_option('wtcc_shipping_rates_config', $config);
wtcc_shipping_set_product_preset($product_id, $preset_key);

// VALIDATE
$zip = wtcc_validate_zip_code($_POST['zip']);
$country = wtcc_validate_country_code($_POST['country']);
$tracking = wtcc_validate_tracking_number($_POST['tracking']);

// ADMIN UI
wtcc_admin_header('Page Title');
wtcc_admin_footer();
echo '<span class="dashicons ' . wtcc_get_section_icon('shipping') . '"></span>';

// API
$token = wtcc_shipping_get_oauth_token($credentials);
$estimate = wtcc_get_delivery_estimate($from_zip, $to_zip, $mail_class);
```

---

## 📱 WooCommerce HOOKS

```phptext
// Checkout
add_action('woocommerce_review_order_before_shipping', 'my_function');

// Product page
add_action('woocommerce_product_meta_end', 'my_function');

// Order received page
add_action('woocommerce_thankyou', 'my_function');

// Admin order
add_action('woocommerce_admin_order_data_after_shipping_address', 'my_function');

// Shipping methods
add_filter('woocommerce_shipping_methods', 'my_function');

// Package rates
add_filter('woocommerce_package_rates', 'my_function', 100, 2);
```

---

## 💡 QUICK ANSWERS

### Q - How do I add a new shipping method?
A: Extend `WTC_Shipping_Method` class, register via `woocommerce_shipping_methods` filter

### Q - How do I modify the rate calculation?
A: Use `wtcc_shipping_calculated_cost` filter in your code

### Q - How do I add a new preset?
A: Use `wtcc_shipping_presets` filter, don't modify presets.php

### Q - Where do I store plugin settings?
A: Use `get_option()` / `update_option()`, not custom DB tables

### Q - How do I access product data?
A: Use `wc_get_product()`, `get_post_meta()` for custom fields

### Q - Where should CSS go?
A: Create file in `/assets/`, enqueue with `wp_enqueue_style()`

### Q - How do I debug?
A: Use `error_log()` with `defined('WP_DEBUG')` check, check debug.log

### Q - How do I verify user permissions?
A: Use `current_user_can('manage_woocommerce')`

### Q - How do I prevent CSRF?
A: Use `wp_nonce_field()` in forms, verify with `wp_verify_nonce()`

### Q - How do I cache data?
A: Use `get_transient()` / `set_transient()` with expiration time

---

## 📖 DOCUMENTATION FILES

| File | Purpose |
| ------ | --------- |
| `SYSTEM-ARCHITECTURE.md` | **START HERE** - Complete system overview |
| `AI-DEVELOPER-GUIDE.md` | Development workflow, patterns, testing |
| `BUSINESS-VALUE.md` | Why this plugin is powerful (non-tech) |
| `USER-GUIDE.md` | For store owners (non-tech) |
| `README.md` | Public-facing documentation |
| `plugin.php` | Code entry point, load order |

---

## 🚀 START HERE CHECKLIST

1. [ ] Read SYSTEM-ARCHITECTURE.md (15 min)
2. [ ] Review plugin.php load order (5 min)
3. [ ] Understand rule-engine.php (10 min)
4. [ ] Review security-hardening.php (10 min)
5. [ ] Test checkout locally (10 min)
6. [ ] Pick your task from scope list
7. [ ] Read this Quick Reference (you are here)
8. [ ] Code with standards above
9. [ ] Run full test checklist
10. [ ] Submit with confidence

---

## ⏱️ TIME ESTIMATES

| Task | Time |
| ------ | ------ |
| Understand system | 30-45 min |
| Fix small bug | 15-30 min |
| Add new admin setting | 30-45 min |
| Add new shipping method | 45-60 min |
| Add new preset | 15-20 min |
| Fix security issue | 30-45 min |
| Optimize performance | 60-90 min |
| Write documentation | 30-60 min |

---

### Print this card or bookmark it. Reference constantly while coding.

Last Updated: December 2, 2025
