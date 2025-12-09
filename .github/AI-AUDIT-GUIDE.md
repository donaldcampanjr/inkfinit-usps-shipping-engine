# AI Developer Audit Guide

> **Last Audit Date:** December 9, 2025  
> **Auditor:** GitHub Copilot (Claude Opus 4.5)  
> **Status:** ✅ COMPLETE - All issues fixed

---

## Purpose

This document guides AI developers through systematic code review of the Inkfinit USPS Shipping Engine. Follow this checklist when making changes or performing audits.

---

## Quick Reference

### License Tiers
| Tier | Key Required | Features |
|------|-------------|----------|
| FREE | No | Calculator only |
| PRO | Yes (IUSE-PRO-*) | All shipping features |
| ENTERPRISE | Yes (IUSE-ENT-*) | Pro + white-label + bulk tools |

### Key Files
| File | Purpose |
|------|---------|
| `plugin.php` | Main entry, admin menu, hooks |
| `includes/license.php` | Client-side license validation |
| `includes/usps-api.php` | USPS API v3 OAuth integration |
| `license-server/` | Server plugin for inkfinit.pro |

---

## Security Checklist

Every file must pass these checks:

- [x] **Nonce Verification**: All form submissions use `wp_verify_nonce()`
- [x] **Capability Checks**: Admin functions use `current_user_can('manage_options')`
- [x] **Input Sanitization**: All `$_POST`, `$_GET`, `$_REQUEST` are sanitized
- [x] **Output Escaping**: All output uses `esc_html()`, `esc_attr()`, `esc_url()`
- [x] **SQL Safety**: All queries use `$wpdb->prepare()` or are parameterized
- [x] **AJAX Security**: All AJAX handlers verify nonce AND capability
- [x] **Direct Access Prevention**: Files start with `defined('ABSPATH') || exit;`

---

## File Audit Status

### Legend
- ✅ **NICE** - File passes all checks
- ⚠️ **FIXED** - Had issues, now fixed
- 🔍 **PENDING** - Not yet audited

---

## Core Files

| File | Status | Notes |
|------|--------|-------|
| `plugin.php` | ✅ NICE | Proper nonce, capability, sanitization |
| `uninstall.php` | ✅ NICE | Proper ABSPATH check, safe cleanup |
| `readme.txt` | ✅ NICE | Static content only |

---

## License System

| File | Status | Notes |
|------|--------|-------|
| `includes/license.php` | ✅ NICE | Secure test key handling, proper validation |
| `license-server/plugin.php` | ✅ NICE | Clean initialization |
| `license-server/includes/class-database.php` | ✅ NICE | Uses $wpdb->prepare() |
| `license-server/includes/class-key-generator.php` | ✅ NICE | Secure key generation with tier |
| `license-server/includes/class-license-manager.php` | ✅ NICE | Proper tier extraction |
| `license-server/includes/class-rest-api.php` | ✅ NICE | Proper REST permission callbacks |
| `license-server/includes/class-admin.php` | ✅ NICE | Capability checks present |
| `license-server/includes/hooks.php` | ✅ NICE | Proper SKU detection |

---

## Admin Pages

| File | Status | Notes |
|------|--------|-------|
| `includes/admin-page-boxes.php` | ✅ NICE | Nonce + capability verified |
| `includes/admin-page-features.php` | ✅ NICE | Secure form handling |
| `includes/admin-page-flat-rate.php` | ✅ NICE | Settings API used |
| `includes/admin-page-license.php` | ✅ NICE | Nonce + capability verified |
| `includes/admin-page-presets-editor.php` | ✅ NICE | AJAX has nonce + capability |
| `includes/admin-page-presets.php` | ✅ NICE | Secure form handling |
| `includes/admin-page-rates.php` | ✅ NICE | Proper sanitization |
| `includes/admin-page-user-guide.php` | ✅ NICE | Display only |
| `includes/admin-diagnostics.php` | ✅ NICE | Capability checks present |
| `includes/admin-features.php` | ✅ NICE | Secure toggles |
| `includes/admin-notices.php` | ✅ NICE | AJAX has nonce verification |
| `includes/admin-pickup-scheduling.php` | ✅ NICE | AJAX secured |
| `includes/admin-presets-wc-integration.php` | ✅ NICE | Bulk actions secure |
| `includes/admin-security-dashboard.php` | ✅ NICE | Display only |
| `includes/admin-settings-usps-api.php` | ✅ NICE | Settings API used |
| `includes/admin-split-shipments.php` | ✅ NICE | All AJAX handlers secured |
| `includes/admin-ui-helpers.php` | ✅ NICE | Helper functions only |
| `includes/admin-usps-api.php` | ✅ NICE | Credential handling secure |

---

## USPS API Integration

| File | Status | Notes |
|------|--------|-------|
| `includes/usps-api.php` | ✅ NICE | OAuth flow secure |
| `includes/usps-label-api.php` | ✅ NICE | Download handlers secured |
| `includes/usps-pickup-api.php` | ✅ NICE | Proper validation |
| `includes/usps-enhanced-features.php` | ⚠️ FIXED | Added nonce to AJAX tracking lookup |
| `includes/usps-status-badges.php` | ✅ NICE | Display functions only |

---

## Shipping Logic

| File | Status | Notes |
|------|--------|-------|
| `includes/class-shipping-method.php` | ✅ NICE | WC integration secure |
| `includes/shipping-methods.php` | ✅ NICE | Registration only |
| `includes/presets.php` | ✅ NICE | Proper sanitization |
| `includes/box-packing.php` | ✅ NICE | Pure calculation logic |
| `includes/flat-rate-boxes.php` | ✅ NICE | Data functions only |
| `includes/delivery-estimates.php` | ✅ NICE | AJAX has nonce |
| `includes/rule-engine.php` | ✅ NICE | Robust fallback logic |
| `includes/country-zones.php` | ✅ NICE | Data lookup only |
| `includes/additional-mail-classes.php` | ✅ NICE | Settings API used |

---

## Security & Validation

| File | Status | Notes |
|------|--------|-------|
| `includes/security-hardening.php` | ✅ NICE | Comprehensive validation functions |
| `includes/address-validation.php` | ✅ NICE | AJAX nonce + capability verified |

---

## Frontend

| File | Status | Notes |
|------|--------|-------|
| `includes/simple-calculator.php` | ✅ NICE | AJAX secured with nonce + capability |
| `includes/customer-tracking-display.php` | ⚠️ FIXED | Added nonce to tracking save |
| `includes/product-preset-picker.php` | ⚠️ FIXED | Added capability check to AJAX |
| `includes/debug-overlay.php` | ✅ NICE | Admin capability gated |

---

## Utility Files

| File | Status | Notes |
|------|--------|-------|
| `includes/core-functions.php` | ✅ NICE | AJAX has nonce + capability |
| `includes/bulk-license-import.php` | ✅ NICE | AJAX fully secured |
| `includes/bulk-variation-manager.php` | ✅ NICE | All 3 AJAX handlers secured |
| `includes/changelog-display.php` | ✅ NICE | Display with nonce on dismiss |
| `includes/debug-export.php` | ✅ NICE | AJAX secured |
| `includes/default-shipping-method.php` | ✅ NICE | Settings API used |
| `includes/label-printer-settings.php` | ✅ NICE | Settings API used |
| `includes/label-printing.php` | ✅ NICE | AJAX secured |
| `includes/order-auto-complete.php` | ✅ NICE | Hooks only |
| `includes/packing-slips.php` | ✅ NICE | Print key security |
| `includes/preset-product-sync.php` | ✅ NICE | Proper sanitization |
| `includes/product-dimension-alerts.php` | ✅ NICE | Display only |
| `includes/product-dimension-recommender.php` | ✅ NICE | AJAX secured |
| `includes/product-purchase-limits.php` | ✅ NICE | Proper validation |
| `includes/product-scan.php` | ✅ NICE | Internal functions |
| `includes/quick-links.php` | ✅ NICE | Proper escaping |
| `includes/self-test.php` | ✅ NICE | AJAX secured + tier check |
| `includes/white-label.php` | ✅ NICE | Settings API with sanitization |

---

## Assets (JS/CSS)

| File | Status | Notes |
|------|--------|-------|
| `assets/admin.js` | ✅ NICE | Uses localized nonces |
| `assets/admin-style.css` | ✅ NICE | CSS only |
| `assets/admin-bulk-variation-manager.js` | ✅ NICE | Uses localized nonces |
| `assets/admin-dashboard-drag.js` | ✅ NICE | UI only |
| `assets/admin-diagnostics.js` | ✅ NICE | Uses localized nonces |
| `assets/admin-pickup-scheduling.js` | ✅ NICE | Uses localized nonces |
| `assets/admin-presets.js` | ✅ NICE | Uses localized nonces |
| `assets/admin-split-shipments.js` | ✅ NICE | Uses localized nonces |
| `assets/admin-usps-api.js` | ✅ NICE | Uses localized nonces |
| `assets/simple-calculator.js` | ✅ NICE | Uses localized nonces |
| `assets/preset-picker.js` | ✅ NICE | Uses localized nonces |
| `assets/frontend-style.css` | ✅ NICE | CSS only |
| `assets/frontend-tracking.css` | ✅ NICE | CSS only |
| `assets/frontend-delivery-estimates.css` | ✅ NICE | CSS only |
| `assets/frontend-product-preset-picker.css` | ✅ NICE | CSS only |
| `assets/frontend-debug.css` | ✅ NICE | CSS only |
| `assets/admin-split-shipments.css` | ✅ NICE | CSS only |

---

## Common Issues to Fix

### 1. Missing Nonce Verification

```php
// ❌ WRONG
if ($_POST['action'] === 'save') {
    update_option('key', $_POST['value']);
}

// ✅ CORRECT
if (isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'save_action')) {
        wp_die('Security check failed');
    }
    update_option('key', sanitize_text_field($_POST['value']));
}
```

### 2. Missing Capability Check
```php
// ❌ WRONG
function my_admin_page() {
    // renders admin content
}

// ✅ CORRECT
function my_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    // renders admin content
}
```

### 3. Unsanitized Input
```php
// ❌ WRONG
$weight = $_POST['weight'];

// ✅ CORRECT
$weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
```

### 4. Unescaped Output
```php
// ❌ WRONG
echo "<input value='{$user_input}'>";

// ✅ CORRECT
echo "<input value='" . esc_attr($user_input) . "'>";
```

### 5. Unsafe SQL
```php
// ❌ WRONG
$wpdb->query("SELECT * FROM table WHERE id = {$_GET['id']}");

// ✅ CORRECT
$wpdb->query($wpdb->prepare("SELECT * FROM table WHERE id = %d", intval($_GET['id'])));
```

---

## License Flow Verification

### Client Side (Customer's WooCommerce Site)
1. User enters license key in `Inkfinit Shipping → License`
2. `includes/license.php` sends POST to license server
3. Server validates and returns `{ valid: true, tier: 'pro' }`
4. Client stores license data in options
5. `wtcc_get_license_tier()` returns tier for feature gating

### Server Side (inkfinit.pro)
1. WooCommerce order completed with SKU `IUSE-PRO-*` or `IUSE-ENT-*`
2. `license-server/includes/hooks.php` detects SKU
3. `class-key-generator.php` creates key: `IUSE-{TIER}-{TIMESTAMP}-{RANDOM}-{ORDER}`
4. Key stored in database with tier
5. Email sent to customer with key

---

## Testing Checklist

Before marking any file as ✅ NICE:

1. [ ] Read entire file
2. [ ] Check all form handlers for nonce + capability
3. [ ] Check all AJAX handlers for nonce + capability
4. [ ] Check all $_POST/$_GET/$_REQUEST are sanitized
5. [ ] Check all output is escaped
6. [ ] Check all SQL uses prepare()
7. [ ] Verify logic flow is correct
8. [ ] Verify license tier gating is enforced where needed
9. [ ] Verify error handling is appropriate
10. [ ] Check for debug code that should be removed

---

## Issues Found Log

Document all issues found during audit:

### CRITICAL (Must Fix Before Release)

None - All critical issues fixed.

### HIGH (Fix Soon)

None - All high priority issues fixed.

### MEDIUM (Should Fix)

None - All medium priority issues fixed.

### LOW (Nice to Have)

None - All low priority issues fixed.

---

## Issues Fixed in This Audit

| File | Issue | Fix Applied |
|------|-------|-------------|
| `usps-enhanced-features.php` | AJAX handler missing nonce | Added `wp_verify_nonce()` check |
| `product-preset-picker.php` | AJAX handler missing capability | Added `current_user_can('edit_products')` |
| `customer-tracking-display.php` | Save handler missing nonce | Added `wp_nonce_field()` + verification |

---

## Update History

| Date | Auditor | Changes |
|------|---------|---------|
| 2025-12-09 | GitHub Copilot (Claude Opus 4.5) | Complete security audit - 3 issues found and fixed |
| 2025-12-09 | GitHub Copilot (Claude Opus 4.5) | Initial audit guide created |

