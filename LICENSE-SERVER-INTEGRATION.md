## Inkfinit USPS Shipping Engine – License Server Integration

This file explains how to wire up **automatic license key validation** from this plugin to a central WooCommerce-powered sales site.

You DO NOT need to build this immediately to use the plugin. Without a license server URL configured, the plugin:

- Treats any non-empty license key as valid.
- Never calls a remote server.
- Still gates features based on whether a key is present (Free vs Pro).

When you are ready for full automation (keys per purchase, revocation, etc.), follow the steps below.

---

## 1. What the plugin already supports

In the shipping engine (this plugin):

- License key is stored in the WordPress option `wtcc_license_key`.
- License server URL is stored in `wtcc_license_server_url`.
- `includes/license.php` contains:
  - `wtcc_get_license_key()` – reads the key.
  - `wtcc_get_license_status()` – OPTIONAL remote validation:
    - If `wtcc_license_server_url` is empty → returns `'unknown'` and **does not break** the site.
    - If filled, it sends a JSON POST to your server with:

      ```json
      {
        "license_key": "the-key-from-settings",
        "site_url": "https://customer-site.com",
        "plugin": "inkfinit-shipping-engine",
        "version": "1.2.0"
      }
      ```

    - Expects a JSON response:

      ```json
      { "valid": true }
      ```

      or

      ```json
      { "valid": false }
      ```

    - If remote says valid → status `'valid'`, plugin runs Pro.
    - If remote says invalid → status `'invalid'`, plugin falls back to Free.
    - If there is any network/API error → status `'unknown'`, plugin **still runs Pro** (never breaks customer sites because your server is down).

- `wtcc_get_edition()` uses `wtcc_get_license_status()`:
  - `'valid'` or `'unknown'` with a key → edition `pro`.
  - `'invalid'` or no key → edition `free`.

---

## 2. What you need on the sales site (WooCommerce)

On your **sales site** (where people buy licenses), you need:

1. A WooCommerce product for your Pro license (e.g., "Inkfinit Shipping Pro License").
2. A small plugin/module that:
   - Generates a unique license key when that product is purchased.
   - Saves it with the order and customer.
   - Exposes a REST API endpoint:

     `POST /wp-json/inkfinit/v1/license/validate`

     which:

     - Accepts JSON `{ "license_key": "ABC123", "site_url": "https://example.com", ... }`.
     - Looks up that key in your database.
     - Returns `{ "valid": true }` or `{ "valid": false }`.

You can put this module in a separate plugin on your sales site, for example `inkfinit-license-server`.

---

## 3. Example license server endpoint (pseudo-code)

Below is a rough outline of what the endpoint handler on your sales site could look like.
This is NOT wired here yet, but gives your developer a clear contract:

```php
add_action( 'rest_api_init', function() {
	register_rest_route(
		'inkfinit/v1',
		'/license/validate',
		array(
			'methods'             => 'POST',
			'callback'            => 'inkfinit_validate_license_rest',
			'permission_callback' => '__return_true', // You can add a shared secret check here.
		)
	);
} );

function inkfinit_validate_license_rest( WP_REST_Request $request ) {
	$params      = $request->get_json_params();
	$license_key = isset( $params['license_key'] ) ? sanitize_text_field( $params['license_key'] ) : '';

	if ( '' === $license_key ) {
		return new WP_REST_Response( array( 'valid' => false ), 200 );
	}

	// TODO: look up $license_key in your store's license table/meta.
	$is_valid = inkfinit_is_license_valid( $license_key );

	return new WP_REST_Response( array( 'valid' => (bool) $is_valid ), 200 );
}
```

Your `inkfinit_is_license_valid()` would:

- Check a custom table, or
- Check order meta, user meta, or a custom post type,
- Optionally check status (active / revoked / expired).

## 4. How to wire the shipping plugin to your license server

Once the license server endpoint exists and returns `{ "valid": true/false }`, do this on EACH customer store:

1. In the store's WordPress admin, go to:

   `Inkfinit Shipping → USPS API`

2. Under **Advanced Settings**:

   - **License Key**: paste the key you generated for this customer.
   - **License Server URL**: set to the validation endpoint on your sales site, for example:

     `https://yourstore.com/wp-json/inkfinit/v1/license/validate`

3. Save changes.

From then on:

- The plugin will call your license server at most once every 12 hours to confirm the key.
- If the server says `valid: true` → Pro stays enabled.
- If you revoke the key on your sales site (so the server returns `valid: false`) → the plugin will move the site back to Free mode within 12 hours.
- If your server is down or unreachable → plugin treats status as `'unknown'` and continues to run Pro for safety.

---

## 5. Summary

- This plugin is fully usable **without** a license server; keys are local and any non-empty key means Pro.
- For "automatic" licensing (per-purchase keys and central revocation), you need:
  - A small license server module on your WooCommerce sales site following the JSON contract above.
  - The `wtcc_license_server_url` set on each customer site to point to that endpoint.

