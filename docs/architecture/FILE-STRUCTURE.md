# File Structure

<!-- markdownlint-disable MD013 -->

How the 45 plugin files are organized and what each does.

## Load Order (from plugin.php)

**Important:** Files load in specific order. Dependencies are defined in `plugin.php` line by line.

### Core Files (Always Loaded)

```text
includes/core-functions.php          - Utility functions used everywhere
includes/presets.php                 - Preset system (core feature)
includes/country-zones.php           - Country/zone configuration
includes/box-packing.php             - Box packing algorithm
includes/product-scan.php            - Product data scanning
includes/usps-api.php                - USPS OAuth API integration
includes/usps-enhanced-features.php  - USPS extended services
includes/rule-engine.php             - Shipping rules & calculation
includes/shipping-methods.php        - WooCommerce shipping method
includes/label-printing.php          - USPS label generation
includes/usps-label-api.php          - Label API integration
includes/label-printer-settings.php  - Label printer configuration
includes/usps-pickup-api.php         - USPS pickup scheduling
includes/address-validation.php      - Address validation
```

### Security

```text
includes/security-hardening.php      - 520+ lines of security (loaded early)
```

### Frontend Features

```text
includes/customer-tracking-display.php - Order tracking widget
includes/delivery-estimates.php      - Delivery estimate display
includes/additional-mail-classes.php - Media Mail, Cubic, Library Mail
includes/default-shipping-method.php - Auto-select shipping method
includes/order-auto-complete.php     - Auto-complete orders on label print
includes/flat-rate-boxes.php         - USPS flat rate boxes (frontend)
```

### Admin-Only Files (if is_admin())

```text
includes/admin-ui-helpers.php        - Page headers, footers, helpers
includes/admin-features.php          - Feature toggles
includes/usps-status-badges.php      - Status displays
includes/admin-page-presets.php      - Preset configuration page
includes/admin-page-presets-editor.php - Preset editor
includes/admin-page-features.php     - Feature toggles page
includes/admin-page-user-guide.md    - User guide page
includes/admin-page-rates.php        - Rates configuration page
includes/admin-page-boxes.php        - Box configuration page
includes/admin-usps-api.php          - USPS API settings
includes/admin-diagnostics.php       - System diagnostics
includes/product-preset-picker.php   - Product editor preset selector (NEW)
includes/preset-product-sync.php     - Sync presets to products
includes/product-dimension-recommender.php - Dimension suggestions
includes/debug-overlay.php           - Debug information display
includes/admin-security-dashboard.md - Security status
includes/product-dimension-alerts.md - Dimension warnings
includes/admin-messaging.md          - Admin notifications
includes/bulk-variation-manager.md   - Bulk product updates
includes/admin-pickup-scheduling.md  - Pickup scheduling UI
includes/admin-split-shipments.md    - Split shipment management
includes/packing-slips.md            - Packing slip generation
includes/admin-flat-rate.md          - Flat rate configuration
```

### NEW Integration (Admin-only)

```text
includes/admin-presets-wc-integration.php - WooCommerce Shipping integration (NEW)
```

### Frontend Status Badges (if !is_admin())

```text
includes/usps-status-badges.php      - Status displays on storefront
```

### Product Limits (Frontend + Admin)

```text
includes/product-purchase-limits.php - Max quantity per order
```

## Asset Files

```text
assets/
├── admin-style.css                 - Admin UI styling
├── admin-clean.css                 - Alternate admin theme
├── frontend-style.css              - Customer-facing CSS
├── admin.js                        - Admin JavaScript utilities
├── preset-picker.js                - Auto-fill dropdown (NEW)
└── images/
    └── wtc-logo.png               - WTC logo
```

- `includes/admin-presets-wc-integration.php` - WC Shipping integration
- `assets/preset-picker.js` - Auto-fill JavaScript

## Documentation Files

```text
docs/
├── INDEX.md                        - Master routing hub (start here!)
├── getting-started/
│   ├── README.md
│   ├── START-HERE.md
│   ├── AI-DEVELOPER-GUIDE.md
│   └── BUSINESS-VALUE.md
├── architecture/
│   ├── README.md
│   ├── SYSTEM-ARCHITECTURE.md
│   ├── FILE-STRUCTURE.md (you are here)
│   └── DATABASE-SCHEMA.md
├── guides/
│   ├── README.md
│   ├── CODE-FLOW-VERIFICATION.md
│   ├── USER-EXPERIENCE-NOW.md
│   ├── WHAT-DOES-THIS-DO.md
│   └── SCOPE-CLARIFICATION.md
├── deployment/
│   ├── README.md
│   ├── READY-TO-DEPLOY.md
│   ├── DEPLOYMENT-CHECKLIST.md
│   ├── VERIFICATION-TESTS.md
│   └── FILES-CHANGED.md
└── reference/
    ├── README.md
    ├── QUICK-REFERENCE.md
    ├── FIXES-SUMMARY.md
    ├── FIXES-COMPLETED.md
    └── ... (other references)
```

## How Files Are Used

### Preset System Flow

```text
plugin.php
  ↓
includes/presets.php (defines preset storage)
  ↓
includes/product-preset-picker.php (lets users select on products)
  ↓
includes/admin-presets-wc-integration.php (shows in WC settings)
  ↓
assets/preset-picker.js (AJAX handler for auto-fill)
```

### Shipping Calculation Flow

```text
plugin.php
  ↓
includes/shipping-methods.php (WC integration)
  ↓
includes/rule-engine.php (applies rules)
  ↓
includes/usps-api.php (gets rates)
  ↓
frontend shows rate at checkout
```

### Label Printing Flow

```text
includes/label-printing.php
  ↓
includes/usps-label-api.php
  ↓
includes/usps-pickup-api.php (optional pickup)
  ↓
includes/order-auto-complete.php (marks order complete)
```

### Admin UI Flow

```text
plugin.php
  ↓
includes/admin-ui-helpers.php (helpers)
  ↓
includes/admin-features.php (feature toggles)
  ↓
includes/admin-page-*.php (admin pages)
```

### Packing Slip Flow

```text
includes/packing-slips.php
  ↓
includes/usps-label-api.php
  ↓
includes/order-auto-complete.php (marks order complete)
```

## Adding New Features

1. **If UI related:** Add code to `includes/admin-page-*.php`
2. **If shipping related:** Modify `includes/rule-engine.php` or `includes/shipping-methods.php`
3. **If USPS related:** Modify `includes/usps-api.php`
4. **If product data:** Modify `includes/product-*.php`
5. **Create admin files:** Name as `includes/admin-[feature].php`
6. **Add to plugin.php:** Include in appropriate section with comment
7. **Add to docs:** Document in `/docs/architecture/SYSTEM-ARCHITECTURE.md`

---

### See `docs/INDEX.md` for the master documentation hub
