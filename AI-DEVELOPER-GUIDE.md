# Inkfinit Shipping Engine - AI Developer Workflow & Scope Clarification

<!-- markdownlint-disable MD013 -->

## For - AI Systems Beginning Development Work

**Updated:** December 2, 2025

---

## 🎯 PHASE 0 - SCOPE CLARIFICATION (BEFORE ANY CODE)

### Questions to Ask If Scope Is Unclear

Before modifying anything, clarify these items with the project owner:

#### 1. SHIPPING METHODS & MAIL CLASSES

- ❓ Should we support Media Mail, Library Mail, Cubic rates?
- ❓ Do we need Parcel Select Bound Printed Matter?
- ❓ Should we auto-pack items or just display recommendation?

#### 3. DELIVERY ESTIMATES

- ❓ Show estimated delivery date at checkout?
- ❓ Use USPS API or manual estimates?
- ❓ Show different estimates for different shipping methods?

#### 4. CUSTOMER FEATURES

- ❓ Tracking widget on order page?
- ❓ Auto-send tracking emails?
- ❓ Display in customer account?

#### 5. ADMIN FEATURES

- ❓ Bulk label printing?
- ❓ Batch shipping (multiple orders)?
- ❓ USPS pickup scheduling?
- ❓ Packing slips?
- ❓ Shipping history reporting?

#### 6. SECURITY & COMPLIANCE

- ❓ PCI compliance requirements?
- ❓ GDPR (store tracking data)?
- ❓ Export tracking data?

#### 7. THIRD-PARTY INTEGRATIONS

- ❓ Label printing service (ShipStation, Shippo, EasyPost)?
- ❓ Accounting software (QuickBooks, Xero)?
- ❓ Email service (for tracking notifications)?

---

## 🏗️ TECH STACK REQUIREMENT (NON-NEGOTIABLE)

### MUST USE

- ✅ **WordPress native functions only**
  - `get_option()`, `update_option()`, `get_transient()`, etc.
  - NO custom database tables (use wp_options)
  - NO external PHP libraries (no Composer packages)

- ✅ **WooCommerce native APIs only**
  - `WC_Shipping_Method` class
  - `WC_Shipping_Zones`
  - `WC_Order`, `WC_Product`
  - `woocommerce_shipping_init` hook
  - NO WooCommerce Shipping & Tax plugin (we replace it)

- ✅ **WordPress native UI only**
  - `.wrap`, `.postbox`, `.notice`, `.form-table`
  - Dashicons for icons
  - WordPress color palette
  - NO custom CSS in PHP (all in assets/)
  - NO CSS frameworks (Bootstrap, Tailwind, etc.)

- ✅ **JavaScript**
  - jQuery (WordPress core provides this)
  - Vanilla JavaScript ES6
  - NO frontend frameworks (no React, Vue, etc.)
  - NO npm packages (unless absolutely necessary and documented)

### CANNOT USE

- ❌ Composer / Packagist packages
- ❌ External APIs except USPS official
- ❌ Database tables outside wp_*
- ❌ Custom post types for settings (use wp_options)
- ❌ React, Vue, Angular, or any JS frameworks
- ❌ SCSS/LESS (plain CSS only)
- ❌ Inline styles in PHP (CSS files only)
- ❌ Custom REST endpoints (use WordPress native)
- ❌ JavaScript bundlers (no webpack, Vite, etc.)

---

## 📋 DEVELOPMENT WORKFLOW

### STEP 1 - UNDERSTAND BEFORE CODING

1. **Read the System Architecture document** (the one we just created)
2. **Study the code load order** in `plugin.php`
3. **Run the plugin locally** and test:
   - Add product to cart
   - Go to checkout
   - Verify shipping rates display
   - Create account and place test order
4. **Review security-hardening.php** - understand security model
5. **Check admin pages** - see how UI is built
6. **Test with different browsers** - especially for accessibility

### STEP 2 - IDENTIFY THE FILE(S) TO MODIFY

1. **Locate the relevant include file(s)**
   - Shipping calculation? → `rule-engine.php`
   - Product UI? → `product-preset-picker.php` or `product-scan.php`
   - Admin page? → `admin-page-*.php`
   - USPS integration? → `usps-api.php`
   - Frontend display? → `customer-tracking-display.php`

2. **Verify file dependencies**
   - Does it require other includes?
   - Is load order correct in plugin.php?
   - Does it depend on functions from core-functions.php?

3. **Check for existing filters/actions**
   - Can you hook instead of modifying code directly?
   - Use filters for extensibility first
   - Only modify files if hook points don't exist

### STEP 3 - CODE WITH STANDARDS

### Always follow this template

```phptext
<?php
/**
 * Module Description
 * What problem this solves, key functions
 * 
 * @package WTC_Shipping_Core
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;  // Exit if accessed directly
}

/**
 * Main function with complete PHPDoc
 *
 * Longer description explaining what it does,
 * what it receives, what it returns.
 *
 * @param string $param1 Description of param1.
 * @param int    $param2 Description of param2.
 * @return array Return value description.
 *
 * @since 1.1.0
 */
function wtcc_my_function( $param1, $param2 = 0 ) {
    // Validate input
    $param1 = sanitize_text_field( $param1 );
    $param2 = intval( $param2 );
    
    // Perform logic
    $result = array();
    
    // Return result
    return $result;
}

/**
 * Second function if needed
 */
function wtcc_another_function() {
    // Code here
}
```

### STEP 4 - TEST THOROUGHLY

### Before considering work complete

1. **Functional Test**
   - Does the feature work as intended?
   - Does it work on all shipping methods?
   - Does it work in all zones?

2. **Integration Test**
   - Does it break any existing features?
   - Do rates still calculate correctly?
   - Does checkout still work?

3. **Security Test**
   - Are inputs sanitized?
   - Are outputs escaped?
   - Is capability checked if admin?
   - Is nonce verified if form?

4. **Data Test**
   - Does it store data correctly?
   - Does it retrieve data correctly?
   - Does it handle missing data gracefully?

5. **Edge Case Test**
   - Empty cart?
   - Very heavy items (> 70 lbs)?
   - International shipping?
   - Different currencies?
   - Low stock/unavailable items?

### STEP 5 - DOCUMENT CHANGES

### If modifying core files

1. **Add code comments** explaining what changed and why
2. **Update version** if major change (currently 1.1.0)
3. **Add to CHANGELOG** (if exists)
4. **Update PHPDoc** if function signature changed
5. **Test documentation examples** - do they work?

---

## 🔄 COMMON DEVELOPMENT PATTERNS

### Pattern 1 - Adding a New Admin Setting

### File - `admin-page-presets.php`

```phptext
// 1. Add form field to settings form
?>
<table class="form-table">
    <tr>
        <th><label for="my_setting">My Setting</label></th>
        <td>
            <input type="text" 
                   name="my_setting" 
                   id="my_setting"
                   value="<?php echo esc_attr( get_option( 'my_setting' ) ); ?>">
            <p class="description">Description of setting</p>
        </td>
    </tr>
</table>
<?php

// 2. Add saving logic in wtcc_shipping_handle_settings_save()
if ( isset( $_POST['my_setting'] ) ) {
    $value = sanitize_text_field( $_POST['my_setting'] );
    update_option( 'my_setting', $value );
}

// 3. Use it elsewhere
$setting_value = get_option( 'my_setting', 'default_value' );
```

### Pattern 2 - Adding a Filter/Hook Point

### File - `rule-engine.php`

```phptext
// BEFORE: Hardcoded cost
$cost = $base_cost + ( $weight_oz * $per_oz_rate );

// AFTER: Allow customization
$cost = apply_filters(
    'wtcc_shipping_calculated_cost',
    $cost,
    $group,
    $package,
    $zone
);

// Third-party code can now modify:
add_filter( 'wtcc_shipping_calculated_cost', function( $cost, $group ) {
    if ( $group === 'express' ) {
        $cost += 5.00;  // Add handling fee
    }
    return $cost;
}, 10, 4 );
```

### Pattern 3 - Adding Input Validation

### File - `security-hardening.php`

```phptext
/**
 * Validate and sanitize custom field
 *
 * @param string $value Value to validate.
 * @return string|false Sanitized value or false if invalid.
 */
function wtcc_validate_my_field( $value ) {
    // Remove dangerous characters
    $value = sanitize_text_field( $value );
    
    // Check required format
    if ( ! preg_match( '/^[a-zA-Z0-9-]+$/', $value ) ) {
        return false;
    }
    
    // Check length
    if ( strlen( $value ) > 100 ) {
        return false;
    }
    
    return $value;
}

// Use in forms
$validated = wtcc_validate_my_field( $_POST['field'] );
if ( false === $validated ) {
    add_settings_error( 'my_setting', 'invalid', 'Invalid format.' );
} else {
    update_option( 'my_setting', $validated );
}
```

### Pattern 4 - Adding Frontend Feature

### File - Create `includes/my-feature.php`

```phptext
<?php
/**
 * My New Frontend Feature
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display feature on checkout
 */
add_action( 'woocommerce_review_order_before_shipping', 'wtcc_display_my_feature' );
function wtcc_display_my_feature() {
    ?>
    <div class="wtc-my-feature">
        <p><?php esc_html_e( 'My Feature Content', 'wtc-shipping' ); ?></p>
    </div>
    <?php
}

/**
 * Enqueue styles and scripts
 */
add_action( 'wp_enqueue_scripts', 'wtcc_my_feature_enqueue' );
function wtcc_my_feature_enqueue() {
    if ( is_checkout() ) {
        wp_enqueue_style(
            'wtcc-my-feature',
            WTCC_SHIPPING_PLUGIN_URL . 'assets/my-feature.css',
            array(),
            WTCC_SHIPPING_VERSION
        );
        wp_enqueue_script(
            'wtcc-my-feature',
            WTCC_SHIPPING_PLUGIN_URL . 'assets/my-feature.js',
            array( 'jquery' ),
            WTCC_SHIPPING_VERSION,
            true
        );
    }
}
```

### Then add to plugin.php load order

```phptext
require_once WTCC_SHIPPING_PLUGIN_DIR . 'includes/my-feature.php';
```

### Pattern 5 - Adding WooCommerce Hook

```phptext
// Use these hooks for WooCommerce integration:

// Checkout
add_action( 'woocommerce_review_order_before_shipping', $function );
add_action( 'woocommerce_review_order_after_shipping', $function );
add_action( 'woocommerce_checkout_process', $function );

// Product page
add_action( 'woocommerce_product_meta_end', $function );
add_action( 'woocommerce_product_data_tabs', $function );
add_action( 'woocommerce_product_data_panels', $function );

// Order
add_action( 'woocommerce_order_details_after_order_table', $function );
add_action( 'woocommerce_thankyou', $function );

// Admin order
add_action( 'woocommerce_admin_order_data_after_shipping_address', $function );
add_action( 'woocommerce_admin_order_actions_end', $function );

// Shipping
add_filter( 'woocommerce_shipping_methods', $function );
add_filter( 'woocommerce_package_rates', $function );
add_filter( 'woocommerce_available_shipping_methods', $function );
```

---

## 🧪 TESTING CHECKLIST

Use this checklist before marking any task complete:

```texttext
FUNCTIONAL TESTING
☐ Feature works as described
☐ Works with all shipping methods (Ground, Priority, Express)
☐ Works with all zones (USA, Canada, International)
☐ Works with different order weights
☐ Works with different product types
☐ Works with product variations
☐ Works with bundled products

ADMIN TESTING
☐ Settings page loads without errors
☐ Can save settings
☐ Settings persist after page reload
☐ Validation works (errors show)
☐ Nonces are verified
☐ Capability check works (non-admin can't access)

CHECKOUT TESTING
☐ Checkout loads without JavaScript errors
☐ Rates calculate correctly
☐ Correct rates for each method
☐ Correct rates for each zone
☐ Rates update when address changes
☐ No duplicate rates display

SECURITY TESTING
☐ All inputs sanitized (check $_POST, $_GET)
☐ All outputs escaped (check echo, print)
☐ All admin pages check capability
☐ All form submissions verify nonce
☐ SQL injection not possible
☐ XSS not possible

PERFORMANCE TESTING
☐ Checkout doesn't show excessive API calls
☐ Rates cache working (same rate within 4 hours)
☐ Tokens cache working (same token within 7 hours)
☐ No 30+ second page loads
☐ No memory warnings in logs

BROWSER TESTING
☐ Chrome/Edge (latest)
☐ Firefox (latest)
☐ Safari (latest)
☐ Mobile Safari (iOS)
☐ Chrome (Android)

ACCESSIBILITY TESTING
☐ Can tab through all fields
☐ Screen reader announces all labels
☐ Color contrast meets WCAG AA
☐ Icons have alt text or aria-label
☐ Forms have proper ARIA attributes

DATABASE TESTING
☐ Options saved to wp_options (not custom tables)
☐ No duplicate options
☐ Options deleted on uninstall
☐ Product meta using proper functions
☐ No data left after plugin deactivation
```

---

## 📊 BEFORE/AFTER COMPARISON

### Before Starting Work

### Gather baseline

```bashtext
# Check error log
wp plugin test wtc-shipping-core

# Verify installation
wp option get wtcc_shipping_rates_config

# Test checkout (check browser console)
# Add item to cart, go to checkout, verify rates show
```

### After Completing Work

### Run verification

```bashtext
# Check for errors
wp debug-bar  # Use this or check debug.log

# Test same checkout
# Verify still works, rates still show

# Check data integrity
# Verify settings still there, rates still correct
```

---

## 🚨 CRITICAL GOTCHAS

### Gotcha 1 - Load Order Matters

If you add `require_once` to plugin.php, it must go AFTER its dependencies.

```phptext
// WRONG - presets used before loaded
require_once 'rule-engine.php';       // uses presets
require_once 'presets.php';           // defines presets

// CORRECT
require_once 'presets.php';           // define presets first
require_once 'rule-engine.php';       // then use presets
```

### Gotcha 2 - WooCommerce Not Always Available

Some files run on frontend where WooCommerce classes may not be loaded yet.

```phptext
// WRONG - Will crash if WooCommerce not loaded
class My_Class extends WC_Shipping_Method {
    // ...
}

// CORRECT - Check first
if ( class_exists( 'WC_Shipping_Method' ) ) {
    class My_Class extends WC_Shipping_Method {
        // ...
    }
}
```

### Gotcha 3 - Transients Aren't Always Saved

Transients might not save in some hosting environments. Always have a fallback.

```phptext
// WRONG - Assumes transient saved
$token = get_transient( 'my_token' );
return $token;  // Will return false if transient failed

// CORRECT - Handle fallback
$token = get_transient( 'my_token' );
if ( false === $token ) {
    // Get fresh token, don't just fail
    $token = get_new_token();
}
return $token;
```

### Gotcha 4 - CSS Caching

When modifying CSS, browsers cache old version. Always:

1. Update version number in enqueue
2. Tell user to hard refresh (Ctrl+Shift+R)

```phptext
// Update version number to bust cache
wp_enqueue_style(
    'wtcc-admin-style',
    WTCC_SHIPPING_PLUGIN_URL . 'assets/admin-style.css',
    array(),
    WTCC_SHIPPING_VERSION . '.' . time()  // Add timestamp
);
```

### Gotcha 5 - JavaScript jQuery Required

This plugin uses jQuery heavily. jQuery must load first.

```phptext
// WRONG - jQuery might not be loaded yet
wp_enqueue_script( 'my-script', 'my-script.js' );

// CORRECT - Depend on jQuery
wp_enqueue_script(
    'my-script',
    'my-script.js',
    array( 'jquery' )  // jQuery loads first
);
```

---

## 📞 WHEN TO ASK FOR CLARIFICATION

### Ask before proceeding if

- [ ] Scope is ambiguous
- [ ] Technical requirements conflict with "native WordPress" requirement
- [ ] Change requires breaking existing functionality
- [ ] Change requires new database tables
- [ ] Change requires external dependencies/APIs
- [ ] Change significantly impacts performance
- [ ] Security implications are unclear
- [ ] File dependencies are unclear

### Do NOT

- ❌ Assume what features should do
- ❌ Add features not explicitly requested
- ❌ Modify files without understanding consequences
- ❌ Use non-native tech stack without approval
- ❌ Make breaking changes to public APIs
- ❌ Add security measures without testing

---

## 🎓 REFERENCE - WordPress/WooCommerce Functions You'll Use Most

```phptext
// OPTIONS (Most Common)
get_option( $option, $default )
update_option( $option, $value )
delete_option( $option )
add_option( $option, $value )

// TRANSIENTS (Caching)
get_transient( $transient )
set_transient( $transient, $value, $expiration )
delete_transient( $transient )

// POST META (Product Data)
get_post_meta( $post_id, $key, $single )
update_post_meta( $post_id, $key, $value )
delete_post_meta( $post_id, $key, $value )

// USERS/CAPABILITIES
current_user_can( $capability )
get_current_user_id()
is_user_logged_in()

// SECURITY
wp_verify_nonce( $nonce, $action )
wp_create_nonce( $action )
wp_nonce_field( $action, $name )

// SANITIZATION
sanitize_text_field( $str )
sanitize_email( $email )
intval( $value )
floatval( $value )
absint( $value )
wp_parse_args( $args, $defaults )

// ESCAPING
esc_html( $text )
esc_url( $url )
esc_attr( $attr )
wp_json_encode( $data )

// WooCommerce
wc_get_order( $id )
wc_get_product( $id )
WC()->cart
WC()->session
WC()->countries

// URLS & ADMIN
admin_url( $path )
plugin_dir_url( __FILE__ )
plugin_dir_path( __FILE__ )
add_admin_page()
add_submenu_page()

// LOGGING
error_log( $message )  // Only if WP_DEBUG enabled
wp_remote_get/post()   // HTTP requests

// ACTIONS/FILTERS (Extending)
add_action( $hook, $function )
add_filter( $hook, $function )
do_action( $hook )
apply_filters( $hook, $value )
```

---

## ✅ COMPLETION CHECKLIST

When you complete ANY task, verify:

- [ ] Code follows all standards (PHPDoc, escaping, sanitization, etc.)
- [ ] File is in correct location
- [ ] Load order is maintained (if new file)
- [ ] All tests pass (functional, security, performance)
- [ ] No PHP warnings/errors in debug.log
- [ ] No JavaScript console errors
- [ ] Checkout still works
- [ ] Admin pages still work
- [ ] Previous features not broken
- [ ] Documentation updated if needed
- [ ] Version number updated (if major change)

---

### End of AI Developer Workflow Document

Use this alongside SYSTEM-ARCHITECTURE.md for complete reference.

Questions? Review both documents + code comments.
