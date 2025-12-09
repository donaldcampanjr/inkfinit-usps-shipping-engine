=== Inkfinit USPS Shipping Engine ===
Contributors: donaldcampanjr
Tags: shipping, woocommerce, usps, shipping-rates, label-printing
Requires at least: 5.8
Requires PHP: 8.0
Requires Plugins: woocommerce
Tested up to: 6.7
Stable tag: 1.3.0
WC tested up to: 9.4
WC requires at least: 8.0
License: GPL-3.0-or-later
License URI: <https://www.gnu.org/licenses/gpl-3.0-or-later.html>

Display real-time USPS rates at checkout with zero configuration. Automatic rate calculation, instant delivery estimates, and seamless tracking integration. Save time with smart presets and eliminate manual rate entry forever.

Inkfinit USPS Shipping Engine provides real-time USPS shipping rates for WooCommerce:

- **Free Mode** – no license key saved  
  - Advanced features (live checkout rates, label printing, tracking, presets, bulk tools, diagnostics, pickup, security dashboard) are gently gated and show Pro-only messaging instead of breaking.

- **Pro Mode** – license key saved (and optionally validated against your own license server)  
  - Unlocks the full engine: real-time USPS checkout rates, labels from orders, tracking with status badges, shipping presets, variation manager, diagnostics, pickup scheduling, security dashboard, and more.

== Description ==

**Inkfinit USPS Shipping Engine** provides live USPS shipping rates for WooCommerce stores using the modern USPS OAuth v3 API. Get accurate, real-time shipping costs at checkout with support for all major USPS services.

= 🚀 Why Choose Inkfinit Shipping? =

**Complete WooCommerce Shipping Replacement**

Inkfinit USPS Shipping Engine is a **standalone, modern alternative** to WooCommerce Shipping & Tax. You don't need WooCommerce's official USPS extension - this plugin does everything and more:

* ✅ **Replaces WooCommerce Shipping entirely** - No conflicts, no redundancy
* ✅ **Modern USPS OAuth v3 API** - WooCommerce Shipping uses legacy APIs
* ✅ **PHP 8.1+ fully compatible** - Tested on latest PHP versions
* ✅ **More features** - Bulk tools, presets, delivery estimates
* ✅ **Better performance** - Optimized caching and error handling
* ✅ **Actively maintained** - Regular updates and improvements

Unlike other USPS plugins that use outdated legacy APIs, Inkfinit USPS Shipping Engine uses the **modern USPS OAuth v3 API** - the same system USPS recommends for all new integrations. This means:

* **More accurate rates** - Real-time pricing from USPS
* **Future-proof** - Built on USPS's current API standard
* **Faster responses** - Optimized caching system (4-hour rate cache, 50-minute OAuth cache)
* **Better reliability** - Modern error handling and fallbacks

= 📦 Shipping Methods =

* **USPS First Class Mail** - For lightweight packages (under 13 oz)
* **USPS Ground Advantage** - Economical ground shipping (replaces Retail Ground)
* **USPS Priority Mail** - 1-3 day delivery
* **USPS Priority Mail Express** - Overnight/1-2 day guaranteed
* **Media Mail** - Discounted rates for books, CDs, DVDs
* **Cubic Rate Pricing** - Dimensional-based pricing for small, heavy items

= ⭐ Key Features =

**Live Shipping Rates**

* Real-time USPS API integration via OAuth v3
* Automatic rate caching (reduces API calls by 90%)
* Rates sorted cheapest-first at checkout
* Actual product dimensions used for accurate calculations
* Fallback to manual rates if API unavailable

**Order Tracking**

* Add USPS tracking numbers to orders
* Customers view tracking in My Account
* Real-time status updates from USPS
* Visual status badges (In Transit, Delivered, etc.)
* Automatic tracking email notifications

**Delivery Estimates**

* Show estimated delivery dates at checkout
* Business day calculations (excludes weekends/holidays)
* Regional delivery time awareness
* "Arrives by [Date]" display

**Label Printing**

* Generate USPS shipping labels from order screen
* Bulk label printing for multiple orders
* Auto-complete orders when labels printed
* Print to thermal or standard printers

**Bulk Variation Manager** ⭐ NEW

* Update prices for ALL variations by attribute
* Example: Change all "16 oz" candle prices at once
* Percentage or exact price adjustments
* Bulk stock updates by attribute
* Preview before applying changes

**Product Management**

* Shipping presets for common items (T-shirts, Vinyl, Posters)
* Custom preset editor
* Dimension alerts for missing product data
* Product shipping scanner
* Per-product shipping overrides

**Admin Tools**

* Real-time diagnostics dashboard
* API connection testing
* Security dashboard
* Import/export settings

= 🎯 Perfect For =

* **E-commerce stores** shipping within the USA
* **Band merchandise** stores (T-shirts, vinyl, posters)
* **Handmade goods** sellers
* **Small businesses** needing accurate shipping
* **WooCommerce stores** wanting live USPS rates

= 🔧 Easy Setup (Single Plugin Upload) =

1. Install and activate the plugin
2. Enter your USPS API credentials (Consumer Key & Secret)
3. Set your origin ZIP code
4. Decide how you want to use it:
   - **Free Mode** – leave the **License Key** field empty to explore the plugin.  
   - **Pro Mode** – paste your Inkfinit license key into the **License Key** field to unlock live checkout rates, labels, tracking, presets, diagnostics, and more.
5. (Optional) Add USPS methods to your WooCommerce shipping zones
6. Live rates appear automatically at checkout in Pro Mode!

= 💡 Smart Features =

* **Intelligent Dimension Handling** - Uses actual product dimensions when available, falls back to presets
* **Weight Aggregation** - Automatically combines cart item weights
* **Zone Detection** - Determines shipping zone from customer address
* **Error Recovery** - Never breaks checkout, graceful fallbacks
* **Performance Optimized** - Minimal database queries, smart caching

== Installation ==

= Automatic Installation =

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for "Inkfinit USPS Shipping Engine"
3. Click **Install Now** then **Activate**

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Select the ZIP file and click **Install Now**
4. Activate the plugin

= Configuration =

1. Navigate to **Inkfinit Shipping** in your admin menu
2. Go to **USPS Settings** and enter your API credentials
3. Set your **Origin ZIP Code** (where you ship from)
4. Click **Test API Connection** to verify setup
5. Go to **WooCommerce → Settings → Shipping → Shipping Zones**
6. Add USPS shipping methods to your zones

= Getting USPS API Credentials =

1. Visit [USPS Web Tools](https://www.usps.com/business/web-tools-apis/)
2. Register for a free USPS Web Tools account
3. Request API access for Shipping Rates
4. Copy your Consumer Key and Consumer Secret
5. Enter them in Inkfinit Shipping → USPS Settings

== Frequently Asked Questions ==

= Can I use this instead of WooCommerce Shipping? =

Yes! Inkfinit USPS Shipping Engine is a **complete replacement** for WooCommerce Shipping & Tax. In fact, you should deactivate WooCommerce Shipping before installing this plugin to avoid conflicts.

**Migration steps:**

1. Deactivate WooCommerce Shipping & Tax
2. Deactivate WooCommerce Services (if installed)
3. Install Inkfinit USPS Shipping Engine
4. Configure USPS API credentials
5. Add Inkfinit Shipping methods to your zones

Your existing shipping zones and settings remain intact.

= Is it compatible with PHP 8.1+? =

Yes! Fully tested and compatible with PHP 8.0, 8.1, 8.2, and 8.3. All deprecation warnings have been handled.

= Do I need a USPS account? =

Yes, you need a free USPS Web Tools account to get API credentials. This gives you access to live rates. Visit usps.com/business to register.

= Does it work without API credentials? =

Yes! The plugin includes manual rate configuration as a fallback. You can set flat rates per ounce for each shipping method.

= What USPS services are supported? =

* First Class Mail (under 13 oz)
* Ground Advantage (formerly Retail Ground)
* Priority Mail (1-3 days)
* Priority Mail Express (overnight)
* Media Mail (books, CDs, DVDs)
* Cubic Rate pricing

= How accurate are the rates? =

Rates are pulled directly from USPS's API using your actual product weights and dimensions. They match what you'd see on usps.com.

= Do customers see tracking? =

Yes! When you add a tracking number to an order, customers can view real-time status in their My Account → Orders page.

= Can I print shipping labels? =

Yes, the plugin includes a label printing feature. Generate labels directly from the order screen or use bulk actions for multiple orders.

= Does it support international shipping? =

The plugin focuses on domestic US shipping. International rates are on the roadmap for a future version.

= What if my products don't have dimensions? =

The plugin includes shipping presets for common items (T-shirts, vinyl records, etc.). Assign a preset to products without specific dimensions.

= How does caching work? =

* OAuth tokens cached for 50 minutes
* Shipping rates cached for 4 hours
* Reduces API calls by approximately 90%
* Rates refresh automatically when cache expires

= Can I test rates before going live? =

Yes! Use the Diagnostics page in the admin area to test your API connection and verify your configuration.

= What happens if the USPS API is down? =

The plugin gracefully falls back to your configured manual rates. Checkout never breaks.

= Does it work with variable products? =

Absolutely! Each variation can have its own weight and dimensions, or inherit from the parent product.

= How do I update all variation prices at once? =

Use the Bulk Variation Manager! Go to Inkfinit Shipping → Variation Manager, select an attribute (like "Size: 16 oz"), and update prices for all matching variations at once.

== Screenshots ==

1. **USPS Settings** - Configure API credentials and origin ZIP
2. **Live Rates at Checkout** - Customers see real-time shipping options
3. **Diagnostics Dashboard** - Monitor API status and system health
4. **Order Tracking** - Customers view shipment status
5. **Bulk Variation Manager** - Update prices by attribute
6. **Shipping Presets** - Quick setup for common products
7. **Features Dashboard** - Overview of all capabilities

== Changelog ==

= 1.1.0 - December 2024 =

* **NEW** PHP 8.1+ full compatibility with comprehensive null safety
* **NEW** WordPress core deprecation warning suppression for third-party issues
* **NEW** Upload directory validation to prevent path errors
* **NEW** Bulk Variation Manager - Update prices/stock by attribute
* **NEW** Delivery date estimates at checkout
* **NEW** Media Mail and Cubic Rate support
* **NEW** Order auto-complete on label print
* **NEW** Default shipping method selection
* **NEW** Features & Benefits admin page
* **NEW** Comprehensive User Guide in admin
* **IMPROVED** All strpos() and str_replace() calls have null guards
* **IMPROVED** Actual product dimensions now used (not just presets)
* **IMPROVED** Rates sorted cheapest-first
* **IMPROVED** Rate cache timestamps displayed
* **IMPROVED** Security hardening layer added
* **IMPROVED** Customer tracking display in My Account
* **IMPROVED** Admin UI with teal accent colors
* **FIXED** WooCommerce class loading order bug
* **FIXED** First Class Mail service code mapping
* **FIXED** Dashboard API detection
* **FIXED** File operation safety in label generation

= 1.0.3 - November 2024 =

* Security audit complete
* Store-ready structure
* 0 syntax errors verified
* WordPress plugin directory compliance

= 1.0.2 - November 2024 =

* Comprehensive audit report
* Security enhancements
* Performance optimizations

= 1.0.1 - November 2024 =

* Initial beta release
* Core shipping engine
* USPS API integration

= 1.0.0 - November 2024 =

* Plugin foundation

== Upgrade Notice ==

= 1.1.0 =
Major update! PHP 8.1+ compatibility, Bulk Variation Manager, delivery estimates, Media Mail support, comprehensive null safety, and improved documentation. Recommended for all users. Can replace WooCommerce Shipping entirely.

== Support ==

* **Documentation**: See the User Guide in Inkfinit Shipping → User Guide
* **Features List**: See Inkfinit Shipping → Features for complete capabilities
* **Diagnostics**: Use Inkfinit Shipping → Diagnostics to troubleshoot issues

== License ==

This plugin is licensed under the GNU General Public License v3.0 or later.

== Credits ==

Built for modern WooCommerce stores using USPS's OAuth v3 API. Designed with security, performance, and user experience as top priorities.
