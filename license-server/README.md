# Inkfinit License Server

Automatic license key generation and validation for Inkfinit USPS Shipping Engine.

## Quick Start (5 Minutes)

### Step 1: Install on inkfinit.pro

1. Upload `license-server/` folder to `wp-content/plugins/` on inkfinit.pro
2. Go to WordPress Admin → Plugins
3. Activate "Inkfinit License Server"
4. You'll see "License Keys" under WooCommerce menu

### Step 2: Create License Products in WooCommerce

Create WooCommerce products with these **exact SKUs**:

| Product Name | SKU | Tier | Price |
|--------------|-----|------|-------|
| USPS Shipping Pro - 1 Year | `INK-PRO-1Y` | Pro | $129 |
| USPS Shipping Pro - Lifetime | `INK-PRO-LT` | Pro | $299 |
| USPS Shipping Enterprise - 1 Year | `INK-ENT-1Y` | Enterprise | $499 |
| USPS Shipping Enterprise - Lifetime | `INK-ENT-LT` | Enterprise | $999 |

**CRITICAL**: The SKU must start with `INK-PRO-` or `INK-ENT-` for license keys to generate!

### Step 3: Test It

1. Make a test purchase
2. When order status → "Processing" or "Completed":
   - License key auto-generates
   - Customer gets email with key
3. Check WooCommerce → License Keys to see all keys

---

## How It Works

### Purchase Flow

```
Customer Buys License Product (SKU: INK-PRO-1Y)
                ↓
        Order Completes
                ↓
    License Key Generated
    (Format: IUSE-PRO-675789AB-A1B2C3-F1)
                ↓
    Email Sent to Customer
                ↓
Customer Enters Key in Plugin
                ↓
    Plugin Validates via REST API
                ↓
       Pro Features Unlock! ✅
```

### License Key Format

```
IUSE-{TIER}-{TIMESTAMP}-{RANDOM}-{ORDER_ID}
```

Examples:
- `IUSE-PRO-675789AB-A1B2C3-F1` (Pro license)
- `IUSE-ENT-675789AB-D4E5F6-2A` (Enterprise license)

### Tiers

| Tier | SKU Prefix | Features |
|------|------------|----------|
| Free | (no key) | Calculator only |
| Pro | `INK-PRO-*` | All features, USPS API, labels, tracking |
| Enterprise | `INK-ENT-*` | Pro + White-label + Bulk import |

---

## REST API

### Endpoint: Validate License

**POST** `https://inkfinit.pro/wp-json/inkfinit/v1/license/validate`

**Request:**
```json
{
  "license_key": "IUSE-PRO-675789AB-A1B2C3-F1",
  "site_url": "https://customer-site.com"
}
```

**Response (Valid):**
```json
{
  "valid": true,
  "license_key": "IUSE-PRO-675789AB-A1B2C3-F1",
  "tier": "pro",
  "order_id": 123,
  "customer": "John Doe",
  "expires_at": "2026-12-09 12:00:00",
  "validations": 42
}
```

**Response (Invalid):**
```json
{
  "valid": false,
  "reason": "not_found"
}
```

Reason codes: `not_found`, `revoked`, `expired`, `invalid_format`

---

## Admin Panel

Go to **WooCommerce → License Keys** to:
- View all generated licenses
- See customer info and validation count
- **Revoke** licenses (refunds, abuse)
- **Reactivate** revoked licenses

---

## Database

Creates table `wp_bils_license_keys`:
- `license_key` - Unique key
- `order_id` - WooCommerce order
- `customer_email`, `customer_name`
- `tier` - pro, enterprise
- `status` - active, revoked, expired
- `expires_at` - Expiration date (1 year from purchase)
- `validation_count` - How many times validated

---

## Troubleshooting

### License key not generating

1. Check product SKU starts with `INK-PRO-` or `INK-ENT-`
2. Verify order status is "Processing" or "Completed"
3. Check WooCommerce → Status → Logs for errors

### Customer didn't receive email

1. Test WordPress email: install "Check Email" plugin
2. Verify SMTP settings if using mail service
3. Key is still generated - check WooCommerce → License Keys

### Validation endpoint not working

Test with cURL:
```bash
curl -X POST https://inkfinit.pro/wp-json/inkfinit/v1/license/validate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"IUSE-PRO-12345-ABCDE-F"}'
```

---

## Files

```
license-server/
├── plugin.php                    # Main plugin file
├── README.md                     # This file
└── includes/
    ├── class-database.php        # Database table management
    ├── class-key-generator.php   # License key generation
    ├── class-license-manager.php # Create/validate/revoke
    ├── class-rest-api.php        # REST endpoint
    ├── class-admin.php           # Admin UI
    └── hooks.php                 # WooCommerce order hooks
```
