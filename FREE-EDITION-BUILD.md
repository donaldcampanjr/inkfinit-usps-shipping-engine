## Inkfinit Shipping – Free Edition

This codebase already contains everything needed to build a **free edition** that gates the Pro features (live checkout rates, labels, bulk tools, diagnostics, security dashboard, pickup).

The logic for free vs Pro lives in `includes/license.php` via:

- `wtcc_get_edition()` – returns `pro` or `free`.
- `wtcc_is_pro()` – convenience helper used throughout the plugin.
- A constant override: if `WTCC_FORCE_EDITION` is defined as `'free'`, the whole plugin runs in free mode.

In **free mode** (`WTCC_FORCE_EDITION` = `free`):

- Checkout shipping methods do not add live rates.
- Label printing integration is not exposed to other plugins.
- Bulk Variation Manager, Diagnostics, Security Dashboard, Carrier Pickup, and other heavy tools show a Pro-only notice with an upgrade link instead of running.

### How to build the free edition plugin

1. **Create a new plugin folder**

   On your machine, create a new folder for the free plugin, for example:

   - `inkfinit-usps-free`

   Copy the entire contents of this repository into that folder so the free plugin has the same files (`plugin.php`, `includes/`, `assets/`, etc.).

2. **Rename the main plugin file and header**

   Inside the free plugin folder, open `plugin.php` and change the header block at the top to something like:

   ```php
   /**
    * Plugin Name: Inkfinit USPS Shipping Engine (Free)
    * Description: Free USPS shipping integration for WooCommerce with an upgrade path to Inkfinit USPS Shipping Engine Pro.
    * Version: 1.0.0
    * Author: Inkfinit LLC
    * Text Domain: wtc-shipping
    * Requires at least: 5.8
    * Requires PHP: 8.0
    * Requires Plugins: woocommerce
    */
   ```

3. **Force the free edition in this build**

   Still in that `plugin.php`, just **after** the `if ( ! defined( 'ABSPATH' ) ) { exit; }` line, add:

   ```php
   if ( ! defined( 'WTCC_FORCE_EDITION' ) ) {
   	define( 'WTCC_FORCE_EDITION', 'free' );
   }
   ```

   This tells the shared code in `includes/license.php` to run in **free mode** for this plugin build.

4. **Zip the free plugin folder for distribution**

   - Make sure the plugin folder name (e.g., `inkfinit-usps-free`) contains `plugin.php`, `includes/`, `assets/`, etc.
   - Zip that **folder** and upload it to your WordPress site via **Plugins → Add New → Upload Plugin**.

   On sites using the free edition, the admin UI will:

   - Display Pro-only notices for advanced features with an upgrade link to your Pro product.
   - Allow users to explore the admin interface and understand the plugin capabilities.

5. **Important: Do not run free and Pro from the same folder**

   The free and Pro plugins must live in **different folders** in `wp-content/plugins` (e.g., `inkfinit-usps-free` for free, `inkfinit-shipping-engine` for Pro). They share the same internal code structure, but should never be combined in one directory or activated together on the same site.

