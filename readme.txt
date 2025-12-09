=== Inkfinit USPS Shipping Engine ===
Contributors: inkfinitllc
Donate link: https://inkfinit.pro
Tags: shipping, woocommerce, usps, shipping-rates, label-printing, tracking, ecommerce
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.3.2
WC requires at least: 8.0
WC tested up to: 9.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Professional USPS shipping rates, label printing, and tracking for WooCommerce. Real-time rates using modern USPS OAuth v3 API.

== Description ==

**Inkfinit USPS Shipping Engine** is the modern, professional solution for USPS shipping in WooCommerce. Get accurate real-time shipping rates at checkout using the official USPS OAuth v3 API.

= 🚀 Why Inkfinit USPS Shipping Engine? =

* **Modern API** – Uses USPS OAuth v3 (not legacy XML APIs)
* **Real-Time Rates** – Live pricing from USPS at checkout
* **Smart Caching** – Reduces API calls by 90%
* **Label Printing** – Generate labels directly from orders
* **Order Tracking** – Real-time tracking for you and customers
* **Thermal Printer Support** – Works with Zebra, Rollo, DYMO, and more
* **PHP 8.1+ Ready** – Fully tested on modern PHP versions

= 📦 Supported Shipping Methods =

* USPS Ground Advantage (replaces Retail Ground)
* USPS Priority Mail (1-3 days)
* USPS Priority Mail Express (overnight)
* USPS First Class Mail (under 13 oz)
* Media Mail (books, CDs, DVDs)
* Cubic Rate Pricing

= ⭐ Free vs Pro Features =

**FREE Edition:**
* Rate Calculator – Estimate shipping costs for any package
* Basic Documentation
* Community Support

**PRO Edition ($99/year):**
* Live checkout rates from USPS API
* Shipping label generation
* Customer tracking display
* Thermal printer support (ZPL format)
* Shipping presets (T-shirts, vinyl, posters, etc.)
* Bulk variation manager
* Delivery date estimates
* Email tracking notifications
* Diagnostics & API testing
* Priority email support

**ENTERPRISE Edition ($299/year):**
* Everything in Pro
* White-label branding
* Bulk license import
* Multi-site support
* Dedicated support

[Get Pro License →](https://inkfinit.pro)

= 🖨️ Thermal Printer Support =

Perfect for high-volume shipping operations:

* **Zebra** – ZP450, ZP500, ZP505, GK420d (ZPL native)
* **Rollo** – All models (ZPL format)
* **DYMO** – 4XL (PDF format)
* **Brother** – QL series (PDF format)
* Any thermal printer supporting 4x6 labels

= 🔒 Security First =

* All AJAX handlers secured with nonces
* Capability checks on all admin functions
* Input sanitization throughout
* No sensitive data exposure
* WordPress coding standards compliant

== Installation ==

= Automatic Installation =

1. Go to **Plugins → Add New** in WordPress admin
2. Search for "Inkfinit USPS Shipping Engine"
3. Click **Install Now**, then **Activate**
4. Go to **Inkfinit Shipping** in your admin menu

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

= Configuration =

1. Go to **Inkfinit Shipping → License** and enter your license key (Pro users)
2. Go to **Inkfinit Shipping → USPS API** and enter your USPS API credentials
3. Configure your origin ZIP code
4. Start shipping!

= Getting USPS API Credentials =

1. Visit [USPS Business Customer Gateway](https://gateway.usps.com/)
2. Register for a business account
3. Request Web Tools API access
4. Copy your Consumer Key and Consumer Secret
5. Enter them in **Inkfinit Shipping → USPS API**

== Frequently Asked Questions ==

= Do I need a USPS account? =

Yes, you need a free USPS Business Customer Gateway account to get API credentials. This is required for live shipping rates.

= Does this work with WooCommerce Shipping? =

Inkfinit USPS Shipping Engine is a complete replacement for WooCommerce Shipping. We recommend deactivating WooCommerce Shipping to avoid conflicts.

= What PHP version do I need? =

PHP 8.0 minimum, PHP 8.1+ recommended. We're fully tested on PHP 8.2.

= Can I use this with other shipping plugins? =

Yes, but we recommend using only one USPS rate provider to avoid confusion at checkout.

= Do you support international shipping? =

Currently focused on domestic US shipping. International support is planned for a future release.

= What thermal printers are supported? =

Any thermal printer that accepts 4x6 labels. We support ZPL format (Zebra, Rollo) and PDF format (DYMO, Brother). See the Printer Settings for configuration.

= How do I get support? =

Free users: WordPress.org support forums
Pro users: Email support@inkfinit.pro

== Screenshots ==

1. Dashboard overview with quick stats and status
2. Rate Calculator - estimate shipping costs instantly
3. USPS API configuration page
4. Live checkout rates display
5. Order tracking with status badges
6. Label printing interface
7. Shipping presets editor
8. Bulk variation manager

== Changelog ==

= 1.3.2 =
* Security: Added nonce verification to all AJAX handlers
* Security: Added capability checks to preset picker
* Security: Added nonce to tracking save handler
* Improved: Customer tracking now Pro/Enterprise only
* Improved: Better thermal printer documentation
* Fixed: Various minor bug fixes

= 1.3.1 =
* Added: Simple rate calculator for free users
* Added: License tier gating in admin menu
* Fixed: Dashboard display for free vs licensed users
* Improved: License server integration

= 1.3.0 =
* Added: Bulk Variation Manager
* Added: White-label settings (Enterprise)
* Added: Self-test diagnostics
* Added: Quick links dashboard
* Improved: USPS API error handling
* Improved: Rate caching performance

= 1.2.0 =
* Added: Thermal printer support (ZPL format)
* Added: Label size options (4x6, 6x4, 8.5x11)
* Added: Auto-print option
* Added: Pickup scheduling
* Improved: Label printing metabox

= 1.1.0 =
* Added: Customer tracking display
* Added: Tracking in order emails
* Added: Delivery date estimates
* Added: Status badges
* Improved: Order tracking column

= 1.0.0 =
* Initial release
* Real-time USPS rates via OAuth v3
* Support for all major USPS services
* Shipping presets
* Basic label integration

== Upgrade Notice ==

= 1.3.2 =
Security update. All users should update immediately.

= 1.3.0 =
Major feature release with Bulk Variation Manager and improved diagnostics.

== Additional Info ==

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 8.0 or higher
* PHP 8.0 or higher (8.1+ recommended)
* USPS Business Customer Gateway account

= Support =

* [Documentation](https://inkfinit.pro/docs)
* [Support Forum](https://wordpress.org/support/plugin/inkfinit-usps-shipping-engine)
* [Pro Support](mailto:support@inkfinit.pro)

= Credits =

Developed by [Inkfinit LLC](https://inkfinit.pro)

