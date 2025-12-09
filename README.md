# Inkfinit USPS Shipping Engine

**Professional USPS shipping rates, labels, and tracking for WooCommerce.**

[![WordPress](https://img.shields.io/badge/WordPress-5.8+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-6.0+-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)

---

## Overview

Inkfinit USPS Shipping Engine delivers accurate USPS shipping rates directly in your WooCommerce checkout using the modern USPS OAuth API v3. Works immediately with a free shipping calculator, with Pro/Enterprise licenses unlocking live API rates, label printing, tracking, and advanced features.

### Key Features

| Feature | Free | Pro | Enterprise |
|---------|------|-----|------------|
| Shipping Calculator Widget | ✅ | ✅ | ✅ |
| Per-Ounce Rate Fallback | ✅ | ✅ | ✅ |
| Live USPS API Rates | ❌ | ✅ | ✅ |
| Shipping Label Printing | ❌ | ✅ | ✅ |
| Package Tracking | ❌ | ✅ | ✅ |
| Customer Tracking Portal | ❌ | ✅ | ✅ |
| Product Presets | ❌ | ✅ | ✅ |
| Bulk Variation Manager | ❌ | ✅ | ✅ |
| Split Shipments | ❌ | ❌ | ✅ |
| Multi-Origin Support | ❌ | ❌ | ✅ |
| Priority Support | ❌ | ✅ | ✅ |

---

## Quick Start

### Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 8.0+ (8.1+ recommended)
- SSL certificate (HTTPS required for USPS API)

### Installation

1. **Download** the plugin ZIP file
2. **Upload** via WordPress Admin → Plugins → Add New → Upload Plugin
3. **Activate** the plugin
4. **Navigate** to WooCommerce → Inkfinit Shipping

### Initial Setup

1. **Free Mode** - Works immediately with per-ounce rate calculator
2. **Pro/Enterprise** - Enter your license key in Inkfinit Shipping → License
3. **USPS API** - Configure credentials in Inkfinit Shipping → USPS Settings (Pro/Enterprise)

---

## Configuration

### USPS API Setup (Pro/Enterprise)

1. Create a [USPS Business Customer Gateway](https://gateway.usps.com/) account
2. Apply for API credentials (Web Tools API)
3. Enter credentials in Inkfinit Shipping → USPS Settings:
   - Consumer Key
   - Consumer Secret
   - Origin ZIP Code
4. Click "Test Connection" to verify

### Shipping Calculator (All Tiers)

The frontend calculator provides instant shipping estimates:

```
Rate Formula: Base Rate + (Weight × Per-Ounce Rate) + Handling Fee
```

Configure in WooCommerce → Settings → Shipping → Inkfinit USPS:
- Base rate per service class
- Per-ounce multiplier
- Handling fees
- Enabled shipping services

### Product Presets (Pro/Enterprise)

Save time with reusable shipping configurations:

1. Go to Inkfinit Shipping → Presets
2. Create preset with dimensions, weight, and packaging
3. Assign to products via product edit screen sidebar

### Label Printing (Pro/Enterprise)

Supports multiple printer types and formats:

| Printer Type | Formats | Label Sizes |
|--------------|---------|-------------|
| Zebra | ZPL, PDF | 4x6" |
| Rollo | PDF | 4x6" |
| DYMO | PDF | 4x6" |
| Brother | PDF | 4x6" |
| Standard | PDF | 4x6", 8.5x11" |

Configure in Inkfinit Shipping → Label Settings.

---

## File Structure

```
inkfinit-usps-shipping-engine/
├── plugin.php                    # Main plugin file
├── uninstall.php                 # Cleanup on uninstall
├── readme.txt                    # WordPress.org readme
├── README.md                     # GitHub readme (this file)
│
├── assets/                       # CSS/JS assets
│   ├── admin-*.js/css           # Admin panel assets
│   ├── frontend-*.js/css        # Customer-facing assets
│   └── images/                   # Plugin images
│
├── includes/                     # Core PHP classes
│   ├── class-shipping-method.php # Main WooCommerce shipping method
│   ├── usps-api.php             # USPS OAuth API integration
│   ├── license.php              # License validation
│   ├── label-printing.php       # Label generation
│   ├── presets.php              # Product presets system
│   └── ...                      # Additional modules
│
├── docs/                         # Documentation
│   └── INDEX.md                 # Documentation hub
│
└── license-server/               # License server reference
    └── README.md                # Integration guide
```

---

## Migration Guide

### From WTC Custom Shipping

This plugin is the rebranded successor to WTC Custom Shipping:

| Old (WTC) | New (Inkfinit) |
|-----------|----------------|
| `wtc_` function prefix | `wtcc_` function prefix |
| `wtc-shipping` slug | `inkfinit-usps-shipping` |
| WTC Custom Shipping menu | Inkfinit Shipping menu |

**Migration Steps:**
1. Deactivate WTC Custom Shipping
2. Install Inkfinit USPS Shipping Engine
3. Re-enter license key if applicable
4. Settings migrate automatically

---

## Troubleshooting

### Common Issues

**"USPS API Connection Failed"**
- Verify API credentials are correct
- Ensure SSL is active on your site
- Check if USPS API is experiencing downtime

**"No shipping rates found"**
- Verify origin ZIP code is set
- Check product weights and dimensions
- Ensure shipping zones are configured

**Rates not showing at checkout**
- Clear WooCommerce transients
- Verify customer address is complete
- Check shipping class assignments

### Diagnostics

Access built-in diagnostics: Inkfinit Shipping → Diagnostics
- System health check
- API connection test
- Configuration validation
- Debug log export

---

## Developer Reference

### Hooks & Filters

```php
// Modify shipping rates before display
add_filter('wtcc_shipping_rates', function($rates, $package) {
    return $rates;
}, 10, 2);

// After label generation
add_action('wtcc_after_label_generated', function($label_id, $order_id) {
    // Custom processing
}, 10, 2);

// Modify calculator output
add_filter('wtcc_calculator_result', function($result) {
    return $result;
});
```

### License Functions

```php
// Check license status
$is_pro = wtcc_is_pro();
$is_enterprise = wtcc_is_enterprise();
$tier = wtcc_get_license_tier(); // 'free', 'pro', 'enterprise'

// Feature gating
if (wtcc_is_pro()) {
    // Pro+ feature code
}
```

### REST API Endpoints

```
GET  /wp-json/wtcc/v1/rates         # Get shipping rates
POST /wp-json/wtcc/v1/labels        # Generate label
GET  /wp-json/wtcc/v1/tracking/{id} # Get tracking info
```

---

## Support

- **Documentation:** See `/docs/INDEX.md`
- **Issues:** GitHub Issues
- **Pro Support:** support@inkfinit.com

---

## License

- **Free Edition:** GPLv2 or later
- **Pro/Enterprise:** Commercial license (see LICENSE-COMMERCIAL.md)

Copyright © 2025 Inkfinit / WoodsToll Company

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for full version history.

### Recent Updates

**v1.3.2** (Current)
- Enhanced security hardening
- Improved thermal printer support
- Pro/Enterprise tier gating refinements

**v1.3.1**
- USPS OAuth v3 API migration
- New diagnostics dashboard
- Bulk variation manager
