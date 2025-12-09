# Copilot Instructions for Inkfinit USPS Shipping Engine

## Project Overview
- **Purpose:** Professional USPS shipping rates, labels, and tracking for WooCommerce stores.
- **Modes:**
  - *Free Mode*: Pro features gated.
  - *Pro Mode*: License unlocks full engine (live rates, labels, tracking, presets, diagnostics, bulk tools, security dashboard).
- **Architecture:**
  - Main plugin logic in `plugin.php` and `includes/`
  - Admin/Frontend assets in `assets/`
  - License server integration in `license-server/`
  - Documentation in `docs/`

## Key Components & Patterns
- **Shipping Methods:** Defined in `includes/class-shipping-method.php` and registered via `shipping-methods.php`.
- **USPS API:** Modern OAuth v3 integration in `includes/usps-api.php`.
- **Presets:** Product shipping presets in `includes/presets.php` and admin UI.
- **Bulk Tools:** Variation manager and bulk operations in `includes/bulk-variation-manager.php`.
- **Diagnostics:** System health and API testing in `includes/admin-diagnostics.php`.
- **Security:** All forms use nonces, capability checks, and input sanitization. See `includes/security-hardening.php`.
- **License Validation:** REST API calls to license server (see `license-server/README.md`).

## Developer Workflows
- **Install:** Place plugin in `wp-content/plugins/` and activate via WordPress admin.
- **Configure:** Enter USPS API credentials in admin panel (`Inkfinit Shipping → USPS Settings`).
- **Testing:**
  - Test API connection in admin (`Test API Connection` button).
  - Check system health in Diagnostics panel.
- **Bulk Updates:** Use Variation Manager for mass price/stock changes by attribute.
- **Debugging:**
  - Enable WordPress debug mode for error logs.
  - Use built-in diagnostics for plugin-specific issues.

## Conventions & Integration
- **No WooCommerce Shipping/Services:** This plugin replaces those; deactivate to avoid conflicts.
- **Presets:** Use sidebar in product edit for preset assignment; fallback logic if dimensions/weight missing.
- **Manual Rate Fallback:** If USPS API fails, per-ounce rates are used (see README for formula).
- **License Server:** License keys are validated via REST API; see `license-server/README.md` for flow.
- **Security:** All admin actions require nonce and capability checks; sanitize all inputs.

## External Dependencies
- **USPS Business Customer Gateway** for API credentials.
- **WooCommerce** (required).
- **PHP 8.0+** (8.1+ recommended).

## Documentation
- Main: `README.md`
- License: `LICENSE-COMMERCIAL.md`
- User Guide: `USER-GUIDE.md`
- Developer Docs: `docs/`

## Example Patterns
- **Shipping Method Registration:**
  ```php
  // includes/shipping-methods.php
  add_filter('woocommerce_shipping_methods', function($methods) {
      $methods['inkfinit_usps'] = 'Inkfinit_Shipping_Method';
      return $methods;
  });
  ```
- **Preset Assignment:**
  ```php
  // includes/presets.php
  $preset = get_product_shipping_preset($product_id);
  ```
- **License Validation:**
  ```php
  // license-server/README.md
  POST /wp-json/inkfinit-license/v1/validate
  ```

---

**For unclear workflows or missing conventions, ask the user for clarification or examples.**
