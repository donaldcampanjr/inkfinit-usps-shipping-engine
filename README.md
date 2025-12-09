# Inkfinit USPS Shipping Engine

<!-- markdownlint-disable MD013 -->

**Professional real-time USPS shipping rates for WooCommerce**

**Developed by:** Inkfinit LLC  
**Purpose:** Simplifying shipping operations for e-commerce stores worldwide

**Display real-time USPS rates at checkout with zero configuration. Automatic
rate calculation, instant delivery estimates, and seamless tracking integration.
Save time with smart presets and eliminate manual rate entry forever.**

Inkfinit USPS Shipping Engine provides real-time USPS shipping rates for WooCommerce:

- **Free Mode** – no license key saved  
  - Advanced features (live checkout rates, label printing, tracking, presets, bulk tools, diagnostics, pickup, security dashboard) are gently gated and show Pro-only messaging instead of breaking.

- **Pro Mode** – license key saved (and optionally validated against your own license server)  
  - Unlocks the full engine: real-time USPS checkout rates, labels from orders, tracking with status badges, shipping presets, variation manager, diagnostics, pickup scheduling, security dashboard, and more.

You always upload a **single plugin zip** – behavior is controlled by whether a license key is present in the settings.

![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-purple)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-96588A)
![License](https://img.shields.io/badge/License-GPL--3.0--or--later-green)

---

## 💳 Support & Licensing

| Tier | Cost | Features | Support |
| -------------- | --------- | -------------------------------------------------- | -------------- |
| **Free** | $0 | Pro features gated in admin | WordPress.org forums |
| **Professional** | $149/year | Full Pro engine (live rates, labels, tracking, presets, diagnostics, bulk tools) | Email (24–48hr response) |
| **Enterprise** | Custom | Dedicated support + custom development | Phone + Email + SLA |

**📝 License:** GPL-3.0-or-later
- Free to use, modify, and redistribute per GPL
- Official support and services available for purchase
- See [LICENSE](LICENSE) for full GPL text

---

---

## 🚀 Features

### Replaces WooCommerce Shipping

- ✅ **Complete replacement** - No need for WooCommerce Shipping & Tax
- ✅ **Modern API** - USPS OAuth v3 (WooCommerce Shipping uses legacy APIs)
- ✅ **PHP 8.1+ compatible** - Fully tested on latest PHP versions
- ✅ **Standalone** - All USPS functionality built-in

### Live USPS Rates

- **OAuth v3 API** - Modern USPS API (not legacy Web Tools)
- **Real-time pricing** - Accurate rates at checkout
- **Smart caching** - 4-hour rate cache, 50-minute OAuth cache
- **Graceful fallbacks** - Never breaks checkout

### Shipping Methods

- ✅ USPS First Class Mail (under 13 oz)
- ✅ USPS Ground Advantage (replaces Retail Ground)
- ✅ USPS Priority Mail (1-3 days)
- ✅ USPS Priority Mail Express (overnight)
- ✅ Media Mail (books, CDs, DVDs)
- ✅ Cubic Rate Pricing

### Order Management

- 📍 **Tracking integration** - Add tracking numbers to orders
- 📧 **Customer notifications** - Automatic tracking emails
- 🎫 **Label printing** - Generate USPS labels (Pro/Premium/Enterprise)
- ✅ **Auto-complete orders** - Mark complete on label print

### Product Tools

- 📦 **Shipping presets** - T-shirts, vinyl, posters, etc.
- 📐 **Dimension handling** - Actual dimensions or preset fallback
- ⚠️ **Dimension alerts** - Flag products missing shipping data
- 🔍 **Product scanner** - Audit shipping configuration

### Bulk Operations

- 📊 **Variation Manager** - Update all variations by attribute (Pro+)
- 💰 **Bulk pricing** - Percentage or exact price changes (Pro+)
- 📦 **Bulk stock** - Update inventory by attribute (Pro+)

### Admin Dashboard

- 🔧 **Diagnostics** - Real-time system health
- 🧪 **API testing** - Verify USPS connection
- 🛡️ **Security dashboard** - Monitor security status

## 📋 Requirements

- WordPress 5.8+ (tested up to 6.4)
- WooCommerce 8.0+ (required)
- PHP 8.0+ (PHP 8.1+ recommended)
- USPS Business Customer Gateway account (free)

### Not Required

- ❌ WooCommerce Shipping & Tax - This plugin replaces it
- ❌ WooCommerce Services - Not needed
- ❌ Other USPS rate plugins - Conflicts will occur

## 🔄 Migrating from WooCommerce Shipping

### Before installing

1. **Deactivate** WooCommerce Shipping & Tax
2. **Deactivate** WooCommerce Services (if installed)
3. **Remove** any other USPS rate plugins
4. Note your current USPS credentials
5. Install Inkfinit USPS Shipping Engine

### After installation

- Your shipping zones remain intact
- Replace old USPS methods with Inkfinit methods
- Configure USPS API credentials (same as before)
- Test checkout flow

## ⚡ Quick Start

### 1. Install & Activate

```bashtext
# Upload to plugins directory
wp-content/plugins/wtc-shipping-core/

# Or install via WordPress admin
Plugins → Add New → Upload Plugin
```

### 2. Configure USPS API

1. Go to **Inkfinit Shipping → USPS Settings**
2. Enter your USPS Consumer Key
3. Enter your USPS Consumer Secret
4. Set your Origin ZIP Code
5. Click **Test API Connection**

### 3. Add Shipping Methods

1. Go to **WooCommerce → Settings → Shipping → Shipping Zones**
2. Edit or create a zone (e.g., "United States")
3. Click **Add shipping method**
4. Add: First Class, Ground Advantage, Priority, Express

### 4. Assign Presets (Optional)

1. Edit a product
2. Select a **Shipping Preset** from the sidebar
3. Preset provides default dimensions/weight if product doesn't have them

## 📁 File Structure

```text
wtc-shipping-core/
├── plugin.php                     # Main plugin file
├── uninstall.php                  # Cleanup on uninstall
├── readme.txt                     # WordPress.org readme
```

├── assets/ │ ├── admin-style.css # Admin styles │ └── frontend-style.css #
Frontend styles └── includes/ ├── class-shipping-method.php # Base shipping
method ├── shipping-methods.php # Method registration ├── usps-api.php # USPS
OAuth API ├── usps-enhanced-features.php # Extended USPS features ├──
rule-engine.php # Rate calculation logic ├── presets.php # Shipping presets ├──
admin-page-rates.php # Rates admin page ├── admin-page-presets.php # Presets
admin page ├── admin-page-features.php # Features showcase ├──
admin-page-user-guide.php # User documentation ├── admin-diagnostics.php #
System diagnostics ├── admin-usps-api.php # USPS settings page ├──
admin-security-dashboard.php # Security monitor ├── bulk-variation-manager.php #
Bulk variation editing ├── delivery-estimates.php # Delivery date estimates ├──
customer-tracking-display.php # My Account tracking ├── label-printing.php #
USPS label generation ├── security-hardening.php # Security layer └── ... (35+
files total)

````text

## 🔧 Configuration

### USPS API Credentials

Get free credentials at [USPS Web Tools](https://www.usps.com/business/web-tools-apis/):

1. Register for USPS Web Tools account
2. Request API access
3. Copy Consumer Key & Secret
4. Enter in Inkfinit Shipping → USPS Settings

### Shipping Presets

Built-in presets for common products:

| Preset | Dimensions | Use Case |
| -------- | ------------ | ---------- |
| T-Shirt | 10×8×2" | Standard apparel |
| Hoodie | 14×10×3" | Heavy apparel |
| Vinyl Record | 13×13×0.5" | 12" LPs |
| CD/DVD | 6×5×0.5" | Jewel cases |
| Poster (Rolled) | 36×4×4" | Tube shipping |
| Sticker Pack | 6×4×0.25" | Small flat items |

### Manual Rate Fallback

If USPS API is unavailable, configure per-ounce rates:

```php
First Class:  Base $3.00 + $0.15/oz
Ground:       Base $4.50 + $0.10/oz
Priority:     Base $8.00 + $0.20/oz
Express:      Base $25.00 + $0.30/oz
````text

## 📊 Bulk Variation Manager

Update prices or stock for all variations sharing an attribute:

### Example - Update All "16 oz" Candle Prices

1. Go to **Inkfinit Shipping → Variation Manager**
2. Select Attribute: "Size"
3. Select Value: "16 oz"
4. Click **Preview Variations**
5. Choose **Update Prices** tab
6. Select "Set to exact price" → Enter "29.99"
7. Click **Apply Changes**

All 50+ variations updated instantly!

## 🛡️ Security

- ✅ All inputs sanitized
- ✅ Nonces verified on all forms
- ✅ Capability checks on all actions
- ✅ SQL injection prevention
- ✅ XSS prevention
- ✅ CSRF protection
- ✅ Rate limiting on API calls

## 🧪 Testing

### Verify API Connection

```bash
Inkfinit Shipping → USPS Settings → Test API Connection
```text

### Check System Health

```bash
Inkfinit Shipping → Diagnostics
```text

## 📝 Changelog

### v1.1.0 (Current)

- PHP 8.1+ full compatibility
- Comprehensive null safety checks
- WordPress core deprecation handling
- Upload directory validation
- Bulk Variation Manager (Pro+)
- Delivery date estimates (Pro+)
- Media Mail & Cubic Rate support
- Order auto-complete on label print (Pro/Premium/Enterprise)
- Features & User Guide pages
- Security hardening layer
- WooCommerce class loading fix
- **Commercial licensing implemented** - Free/Pro/Premium/Enterprise tiers

### v1.0.3

- Security audit complete
- WordPress.org compliance
- Zero syntax errors

### v1.0.0

- Initial release
- USPS OAuth v3 API
- Four shipping methods
- Shipping presets

## 📄 License

### ⚠️ Proprietary Commercial License

This plugin is **NOT free software** and is **NOT GPL licensed**.

See [LICENSE-COMMERCIAL.md](LICENSE-COMMERCIAL.md) for complete licensing terms,
including:

- Free Tier usage rights
- Pro/Premium/Enterprise licensing
- Restrictions and compliance requirements
- Warranty and liability disclaimers

## Quick License Summary

| What You Can Do | Free | Pro | Premium | Enterprise |
| ------------------ | ------ | ------- | --------- | ---------- |
| Use on own site | ✅ | ✅ | ✅ | ✅ |
| Client sites | 1 site | 5 sites | Unlimited | Unlimited |
| Source code access | ❌ | ❌ | Limited | Full |
| White-label | ❌ | ❌ | ✅ | ✅ |
| API access | ❌ | ❌ | ✅ | ✅ |
| Resell/SaaS | ❌ | ❌ | ❌ | ✅ |

### WordPress.org Free Tier

The **Free Tier** of this plugin is available on WordPress.org and includes:

- Core USPS shipping functionality
- Basic shipping presets
- Community support only
- Full USPS API integration

See the
[WordPress.org plugin page](https://wordpress.org/plugins/wtc-shipping-engine/)
for the free version.

**Pro, Premium, and Enterprise tiers** are sold exclusively through our website
and include additional features, priority support, and commercial use rights.

## 📚 Documentation

- **[docs/START.md](docs/START.md)** - New user/developer entry point
- **[docs/INDEX.md](docs/INDEX.md)** - Complete documentation index
- **[USER-GUIDE.md](USER-GUIDE.md)** - Complete user instructions
- **[LICENSE-COMMERCIAL.md](LICENSE-COMMERCIAL.md)** - Licensing & legal terms
- **docs/getting-started/** - Quick start guides
- **docs/deployment/** - Installation & troubleshooting
- **docs/reference/** - API and configuration reference

## 🤝 Support

### Free Tier Support

- Community forum (WordPress.org)
- Admin Panel documentation
- Built-in User Guide (Inkfinit Shipping → User Guide)
- Diagnostics tool (Inkfinit Shipping → Diagnostics)

### Pro/Premium/Enterprise Support

- Email support (included)
- Priority response times
- Direct assistance with configuration
- Custom implementation help (Enterprise)
- 24/7 support (Enterprise only)

For support inquiries, visit https://inkfinit.pro or email support@inkfinit.pro.

## 🔐 Data Privacy

This plugin:

- ✅ Does NOT collect site usage data
- ✅ Does NOT track users
- ✅ Does NOT sell data
- ✅ Only communicates with USPS API
- ✅ Only communicates with our servers for license verification (Pro+)

See [LICENSE-COMMERCIAL.md](LICENSE-COMMERCIAL.md) for complete data handling
policies.

---

## 👥 About

Built with ❤️ for WooCommerce stores using modern USPS APIs.

### Premium shipping simplified. Professional support included.

### Development

**Developed by:** Inkfinit LLC  
**Purpose:** Professional USPS shipping engine to make shipping operations easier for e-commerce stores and organizations worldwide.

This plugin was created to simplify shipping workflows, allowing organizations
to focus on what matters while we handle the logistics complexity.

---

### © 2025 Inkfinit LLC. All Rights Reserved.
