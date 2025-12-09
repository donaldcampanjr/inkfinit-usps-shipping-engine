# Inkfinit License Server - Complete AI Developer Guide

> **Purpose**: This document provides comprehensive, AI-followable instructions for implementing, maintaining, and troubleshooting the license key sales integration between the Inkfinit USPS Shipping Engine plugin and the inkfinit.pro WooCommerce store.

## Table of Contents

1. [System Architecture Overview](#1-system-architecture-overview)
2. [License Tiers and Features](#2-license-tiers-and-features)
3. [License Server Plugin (inkfinit.pro)](#3-license-server-plugin-inkfinitpro)
4. [Client Plugin Integration](#4-client-plugin-integration)
5. [REST API Specification](#5-rest-api-specification)
6. [Database Schema](#6-database-schema)
7. [License Key Format](#7-license-key-format)
8. [Validation Flow](#8-validation-flow)
9. [Feature Gating Implementation](#9-feature-gating-implementation)
10. [Testing and Debugging](#10-testing-and-debugging)
11. [Security Considerations](#11-security-considerations)
12. [Troubleshooting Guide](#12-troubleshooting-guide)

---

## 1. System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        SALES SITE (inkfinit.pro)                        │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    inkfinit-license-server plugin                │   │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │   │
│  │  │ Key Generation  │  │ Key Storage     │  │ REST Validation │  │   │
│  │  │ (on purchase)   │  │ (wp_bils_keys)  │  │ Endpoint        │  │   │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                    │                                    │
│                                    │ REST API                           │
│                                    ▼                                    │
└─────────────────────────────────────────────────────────────────────────┘
                                     │
                                     │ HTTPS
                                     ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      CUSTOMER SITE (any WordPress)                       │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                 Inkfinit USPS Shipping Engine                    │   │
│  │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │   │
│  │  │ License Key     │  │ Remote          │  │ Feature         │  │   │
│  │  │ Input (admin)   │  │ Validation      │  │ Gating          │  │   │
│  │  └─────────────────┘  └─────────────────┘  └─────────────────┘  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

### Components

1. **Sales Site (inkfinit.pro)**
   - WooCommerce store selling license products
   - `inkfinit-license-server` plugin for key generation and validation
   - REST API endpoint for remote validation

2. **Customer Site (any WordPress + WooCommerce)**
   - `inkfinit-usps-shipping-engine` plugin
   - Stores license key locally
   - Validates against sales site periodically
   - Gates features based on license status

---

## 2. License Tiers and Features

### Tier Definitions

| Tier | Constant | Features |
|------|----------|----------|
| `free` | Default | Calculator only, Dashboard, User Guide, License page |
| `pro` | `WTCC_EDITION_PRO` | All features, USPS API, Labels, Tracking |
| `premium` | `WTCC_EDITION_PREMIUM` | Pro + White-Label settings |
| `enterprise` | `WTCC_EDITION_ENTERPRISE` | Premium + Bulk Import, Multi-site |

### Feature Matrix

```php
// FREE TIER (No license key)
- Dashboard (with upgrade prompts)
- Rate Calculator (simple, no API)
- User Guide
- License activation page

// PRO TIER (Any valid license)
- Everything in Free +
- USPS API credentials entry
- Live rates at checkout
- Label printing
- Package tracking
- Box inventory management
- Shipping presets
- Preset editor
- Flat rate boxes
- Packing slips
- Variation manager
- Shipping rules
- Diagnostics
- Changelog

// PREMIUM/ENTERPRISE TIER
- Everything in Pro +
- White-Label settings
- Bulk license import
```

### WooCommerce Product SKUs

| SKU | Tier | Description |
|-----|------|-------------|
| `IUSE-PRO-1Y` | pro | Pro License - 1 Year |
| `IUSE-PRO-LIFETIME` | pro | Pro License - Lifetime |
| `IUSE-PREMIUM-1Y` | premium | Premium License - 1 Year |
| `IUSE-ENTERPRISE-1Y` | enterprise | Enterprise License - 1 Year |

---

## 3. License Server Plugin (inkfinit.pro)

### File Structure

```
license-server/
├── inkfinit-license-server.php    # Main plugin file
├── README.md                       # Documentation
└── includes/
    ├── class-license-table.php    # Database table management
    ├── class-rest-api.php         # REST endpoint handlers
    ├── class-admin-page.php       # Admin UI for managing keys
    └── hooks.php                  # WooCommerce order hooks
```

### Main Plugin File

```php
<?php
/**
 * Plugin Name: Inkfinit License Server
 * Description: License key generation and validation for Inkfinit products
 * Version: 1.0.0
 * Author: Inkfinit
 * Text Domain: inkfinit-license-server
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'INKFINIT_LICENSE_SERVER_VERSION', '1.0.0' );
define( 'INKFINIT_LICENSE_SERVER_DIR', plugin_dir_path( __FILE__ ) );

// Load dependencies
require_once INKFINIT_LICENSE_SERVER_DIR . 'includes/class-license-table.php';
require_once INKFINIT_LICENSE_SERVER_DIR . 'includes/class-rest-api.php';
require_once INKFINIT_LICENSE_SERVER_DIR . 'includes/class-admin-page.php';
require_once INKFINIT_LICENSE_SERVER_DIR . 'includes/hooks.php';

// Activation hook
register_activation_hook( __FILE__, array( 'Inkfinit_License_Table', 'create_table' ) );
```

### Key Generation Logic

```php
/**
 * Generate a unique license key
 * 
 * @param int    $order_id WooCommerce order ID
 * @param string $tier     License tier (pro, premium, enterprise)
 * @return string Generated license key
 */
function inkfinit_generate_license_key( $order_id, $tier = 'pro' ) {
    // Format: IUSE-{TIER}-{TIMESTAMP_HEX}-{RANDOM_HEX}
    $tier_prefix = strtoupper( substr( $tier, 0, 3 ) ); // PRO, PRE, ENT
    $timestamp   = dechex( time() );
    $random      = bin2hex( random_bytes( 4 ) );
    $order_hex   = dechex( $order_id );
    
    return sprintf(
        'IUSE-%s-%s-%s-%s',
        $tier_prefix,
        strtoupper( $timestamp ),
        strtoupper( $random ),
        strtoupper( $order_hex )
    );
}
```

### WooCommerce Order Hook

```php
/**
 * Generate license key when order is completed
 */
add_action( 'woocommerce_order_status_completed', 'inkfinit_generate_key_on_purchase' );
add_action( 'woocommerce_order_status_processing', 'inkfinit_generate_key_on_purchase' );

function inkfinit_generate_key_on_purchase( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;
    
    // Check if key already generated for this order
    $existing_key = get_post_meta( $order_id, '_inkfinit_license_key', true );
    if ( ! empty( $existing_key ) ) return;
    
    // Check for license product in order
    $tier = null;
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) continue;
        
        $sku = $product->get_sku();
        if ( strpos( $sku, 'IUSE-' ) === 0 ) {
            // Determine tier from SKU
            if ( strpos( $sku, 'ENTERPRISE' ) !== false ) {
                $tier = 'enterprise';
            } elseif ( strpos( $sku, 'PREMIUM' ) !== false ) {
                $tier = 'premium';
            } else {
                $tier = 'pro';
            }
            break;
        }
    }
    
    if ( ! $tier ) return; // No license product in order
    
    // Generate and store key
    $license_key = inkfinit_generate_license_key( $order_id, $tier );
    
    // Save to order meta
    update_post_meta( $order_id, '_inkfinit_license_key', $license_key );
    update_post_meta( $order_id, '_inkfinit_license_tier', $tier );
    
    // Save to license table
    global $wpdb;
    $table = $wpdb->prefix . 'bils_license_keys';
    
    $wpdb->insert( $table, array(
        'license_key'    => $license_key,
        'order_id'       => $order_id,
        'customer_email' => $order->get_billing_email(),
        'customer_name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'tier'           => $tier,
        'status'         => 'active',
        'created_at'     => current_time( 'mysql' ),
        'expires_at'     => date( 'Y-m-d H:i:s', strtotime( '+1 year' ) ),
    ), array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
    
    // Send email to customer
    inkfinit_send_license_email( $order, $license_key, $tier );
    
    // Add order note
    $order->add_order_note( sprintf(
        'License key generated: %s (Tier: %s)',
        $license_key,
        ucfirst( $tier )
    ) );
}
```

---

## 4. Client Plugin Integration

### Key Files in Shipping Plugin

| File | Purpose |
|------|---------|
| `includes/license.php` | Core license validation logic |
| `includes/admin-page-license.php` | License settings admin page |
| `plugin.php` | Menu gating and feature loading |

### License Constants (plugin.php)

```php
// License tier constants
if ( ! defined( 'WTCC_EDITION_FREE' ) ) {
    define( 'WTCC_EDITION_FREE', 'free' );
}
if ( ! defined( 'WTCC_EDITION_PRO' ) ) {
    define( 'WTCC_EDITION_PRO', 'pro' );
}
if ( ! defined( 'WTCC_EDITION_PREMIUM' ) ) {
    define( 'WTCC_EDITION_PREMIUM', 'premium' );
}
if ( ! defined( 'WTCC_EDITION_ENTERPRISE' ) ) {
    define( 'WTCC_EDITION_ENTERPRISE', 'enterprise' );
}

// Default license server URL
if ( ! defined( 'WTCC_LICENSE_SERVER_URL' ) ) {
    define( 'WTCC_LICENSE_SERVER_URL', 'https://inkfinit.pro/wp-json' );
}
```

### License Validation Functions (includes/license.php)

```php
<?php
/**
 * License validation functions
 * 
 * @package Inkfinit_USPS_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get stored license key
 * 
 * @return string License key or empty string
 */
function wtcc_get_license_key() {
    return trim( get_option( 'wtcc_license_key', '' ) );
}

/**
 * Get stored license data (cached validation result)
 * 
 * @return array License data with keys: valid, tier, expires, last_check
 */
function wtcc_get_license_data() {
    $data = get_option( 'wtcc_license_data', array() );
    return wp_parse_args( $data, array(
        'valid'      => false,
        'tier'       => 'free',
        'expires'    => '',
        'last_check' => 0,
    ) );
}

/**
 * Get current license edition/tier
 * 
 * Priority:
 * 1. Development test keys (INKDEV-*)
 * 2. Cached validation result
 * 3. Remote validation (if cache expired)
 * 4. Graceful fallback to 'free'
 * 
 * @return string Edition: 'free', 'pro', 'premium', or 'enterprise'
 */
function wtcc_get_edition() {
    $license_key = wtcc_get_license_key();
    
    // No key = free edition
    if ( empty( $license_key ) ) {
        return WTCC_EDITION_FREE;
    }
    
    // Development/test keys (bypass remote validation)
    if ( strpos( $license_key, 'INKDEV-' ) === 0 ) {
        return wtcc_parse_dev_key_tier( $license_key );
    }
    
    // Check cached validation
    $license_data = wtcc_get_license_data();
    $cache_valid  = ( time() - $license_data['last_check'] ) < 12 * HOUR_IN_SECONDS;
    
    if ( $cache_valid && ! empty( $license_data['tier'] ) ) {
        return $license_data['valid'] ? $license_data['tier'] : WTCC_EDITION_FREE;
    }
    
    // Remote validation needed
    $validation = wtcc_validate_license_remote( $license_key );
    
    // Update cache
    update_option( 'wtcc_license_data', array(
        'valid'      => $validation['valid'],
        'tier'       => $validation['tier'],
        'expires'    => $validation['expires'] ?? '',
        'last_check' => time(),
    ) );
    
    return $validation['valid'] ? $validation['tier'] : WTCC_EDITION_FREE;
}

/**
 * Parse development test key tier
 * 
 * Format: INKDEV-{TIER}-{RANDOM}
 * Examples: INKDEV-PRO-12345, INKDEV-ENT-ABCDE
 * 
 * @param string $key License key
 * @return string Tier
 */
function wtcc_parse_dev_key_tier( $key ) {
    $parts = explode( '-', $key );
    if ( count( $parts ) < 2 ) {
        return WTCC_EDITION_PRO;
    }
    
    $tier_code = strtoupper( $parts[1] );
    
    switch ( $tier_code ) {
        case 'ENT':
        case 'ENTERPRISE':
            return WTCC_EDITION_ENTERPRISE;
        case 'PRE':
        case 'PREMIUM':
            return WTCC_EDITION_PREMIUM;
        case 'PRO':
        default:
            return WTCC_EDITION_PRO;
    }
}

/**
 * Validate license key against remote server
 * 
 * @param string $license_key License key to validate
 * @return array Validation result: valid, tier, expires, reason
 */
function wtcc_validate_license_remote( $license_key ) {
    $server_url = defined( 'WTCC_LICENSE_SERVER_URL' ) 
        ? WTCC_LICENSE_SERVER_URL 
        : 'https://inkfinit.pro/wp-json';
    
    $endpoint = trailingslashit( $server_url ) . 'inkfinit/v1/license/validate';
    
    $response = wp_remote_post( $endpoint, array(
        'timeout' => 15,
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( array(
            'license_key' => $license_key,
            'site_url'    => home_url(),
            'plugin'      => 'inkfinit-usps-shipping',
            'version'     => WTCC_SHIPPING_VERSION,
        ) ),
    ) );
    
    // Network error - fail gracefully (allow Pro to continue)
    if ( is_wp_error( $response ) ) {
        return array(
            'valid'  => true,  // Graceful - don't break customer sites
            'tier'   => 'pro', // Assume Pro on network failure
            'reason' => 'network_error',
        );
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    
    // Invalid response - fail gracefully
    if ( ! is_array( $data ) ) {
        return array(
            'valid'  => true,
            'tier'   => 'pro',
            'reason' => 'invalid_response',
        );
    }
    
    // Server says invalid
    if ( empty( $data['valid'] ) ) {
        return array(
            'valid'  => false,
            'tier'   => 'free',
            'reason' => $data['reason'] ?? 'invalid',
        );
    }
    
    // Valid license
    return array(
        'valid'   => true,
        'tier'    => $data['tier'] ?? 'pro',
        'expires' => $data['expires_at'] ?? '',
        'reason'  => 'valid',
    );
}

/**
 * Check if current edition is Pro or higher
 * 
 * @return bool True if Pro, Premium, or Enterprise
 */
function wtcc_is_pro() {
    $edition = wtcc_get_edition();
    return in_array( $edition, array( 'pro', 'premium', 'enterprise' ), true );
}

/**
 * Check if current edition is Premium or higher
 * 
 * @return bool True if Premium or Enterprise
 */
function wtcc_is_premium() {
    $edition = wtcc_get_edition();
    return in_array( $edition, array( 'premium', 'enterprise' ), true );
}

/**
 * Check if current edition is Enterprise
 * 
 * @return bool True if Enterprise
 */
function wtcc_is_enterprise() {
    return wtcc_get_edition() === 'enterprise';
}
```

---

## 5. REST API Specification

### Endpoint: Validate License

**URL**: `POST /wp-json/inkfinit/v1/license/validate`

**Request Headers**:
```
Content-Type: application/json
```

**Request Body**:
```json
{
    "license_key": "IUSE-PRO-67ABC123-DEF456-F201",
    "site_url": "https://customer-site.com",
    "plugin": "inkfinit-usps-shipping",
    "version": "1.3.0"
}
```

**Response (Valid)**:
```json
{
    "valid": true,
    "license_key": "IUSE-PRO-67ABC123-DEF456-F201",
    "tier": "pro",
    "order_id": 123,
    "customer": "John Doe",
    "expires_at": "2026-01-15T00:00:00Z",
    "validations": 42
}
```

**Response (Invalid)**:
```json
{
    "valid": false,
    "reason": "not_found"
}
```

**Reason Codes**:
| Code | Description |
|------|-------------|
| `not_found` | License key does not exist |
| `revoked` | License has been revoked by admin |
| `expired` | License has expired |
| `invalid_format` | Key format is not recognized |

### REST API Implementation (License Server)

```php
<?php
/**
 * REST API for license validation
 */

add_action( 'rest_api_init', 'inkfinit_register_license_routes' );

function inkfinit_register_license_routes() {
    register_rest_route( 'inkfinit/v1', '/license/validate', array(
        'methods'             => 'POST',
        'callback'            => 'inkfinit_validate_license_endpoint',
        'permission_callback' => '__return_true',
        'args'                => array(
            'license_key' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'site_url' => array(
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
            ),
            'plugin' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'version' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
}

function inkfinit_validate_license_endpoint( WP_REST_Request $request ) {
    global $wpdb;
    
    $license_key = $request->get_param( 'license_key' );
    $site_url    = $request->get_param( 'site_url' );
    
    // Validate key format
    if ( ! preg_match( '/^IUSE-[A-Z]{3}-[A-F0-9]+-[A-F0-9]+-[A-F0-9]+$/i', $license_key ) ) {
        return rest_ensure_response( array(
            'valid'  => false,
            'reason' => 'invalid_format',
        ) );
    }
    
    // Look up in database
    $table  = $wpdb->prefix . 'bils_license_keys';
    $license = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$table} WHERE license_key = %s",
        $license_key
    ) );
    
    if ( ! $license ) {
        return rest_ensure_response( array(
            'valid'  => false,
            'reason' => 'not_found',
        ) );
    }
    
    // Check status
    if ( $license->status === 'revoked' ) {
        return rest_ensure_response( array(
            'valid'  => false,
            'reason' => 'revoked',
        ) );
    }
    
    // Check expiration
    if ( ! empty( $license->expires_at ) && strtotime( $license->expires_at ) < time() ) {
        return rest_ensure_response( array(
            'valid'  => false,
            'reason' => 'expired',
        ) );
    }
    
    // Update validation stats
    $wpdb->update(
        $table,
        array(
            'last_validated_at' => current_time( 'mysql' ),
            'validation_count'  => $license->validation_count + 1,
            'last_site_url'     => $site_url,
        ),
        array( 'id' => $license->id ),
        array( '%s', '%d', '%s' ),
        array( '%d' )
    );
    
    // Return success
    return rest_ensure_response( array(
        'valid'       => true,
        'license_key' => $license->license_key,
        'tier'        => $license->tier ?? 'pro',
        'order_id'    => (int) $license->order_id,
        'customer'    => $license->customer_name,
        'expires_at'  => $license->expires_at,
        'validations' => (int) $license->validation_count + 1,
    ) );
}
```

---

## 6. Database Schema

### License Keys Table

```sql
CREATE TABLE {prefix}bils_license_keys (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    license_key VARCHAR(100) NOT NULL,
    order_id BIGINT(20) UNSIGNED NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    tier VARCHAR(20) NOT NULL DEFAULT 'pro',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    expires_at DATETIME DEFAULT NULL,
    last_validated_at DATETIME DEFAULT NULL,
    last_site_url VARCHAR(255) DEFAULT NULL,
    validation_count INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY license_key (license_key),
    KEY order_id (order_id),
    KEY customer_email (customer_email),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### PHP Table Creation

```php
function inkfinit_create_license_table() {
    global $wpdb;
    
    $table_name      = $wpdb->prefix . 'bils_license_keys';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        license_key varchar(100) NOT NULL,
        order_id bigint(20) unsigned NOT NULL,
        customer_email varchar(100) NOT NULL,
        customer_name varchar(100) NOT NULL,
        tier varchar(20) NOT NULL DEFAULT 'pro',
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL,
        expires_at datetime DEFAULT NULL,
        last_validated_at datetime DEFAULT NULL,
        last_site_url varchar(255) DEFAULT NULL,
        validation_count int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY license_key (license_key),
        KEY order_id (order_id),
        KEY customer_email (customer_email),
        KEY status (status)
    ) $charset_collate;";
    
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
```

---

## 7. License Key Format

### Production Keys

```
IUSE-{TIER}-{TIMESTAMP_HEX}-{RANDOM_HEX}-{ORDER_HEX}
```

| Component | Description | Example |
|-----------|-------------|---------|
| `IUSE` | Product identifier (Inkfinit USPS Shipping Engine) | `IUSE` |
| `{TIER}` | 3-letter tier code | `PRO`, `PRE`, `ENT` |
| `{TIMESTAMP_HEX}` | Unix timestamp in hex | `67ABC123` |
| `{RANDOM_HEX}` | 8 random hex characters | `DEF456AB` |
| `{ORDER_HEX}` | Order ID in hex | `F201` |

**Examples**:
- `IUSE-PRO-67ABC123-DEF456AB-F201` (Pro license, Order #61953)
- `IUSE-ENT-67ABC456-12345678-A1B2` (Enterprise license)

### Development/Test Keys

```
INKDEV-{TIER}-{RANDOM}
```

| Component | Description | Example |
|-----------|-------------|---------|
| `INKDEV` | Development key prefix | `INKDEV` |
| `{TIER}` | Tier code | `PRO`, `PRE`, `ENT` |
| `{RANDOM}` | Random string | `12345ABC` |

**Examples**:
- `INKDEV-PRO-12345ABC` (Test Pro license)
- `INKDEV-ENT-TESTKEY1` (Test Enterprise license)

**Important**: Development keys bypass remote validation entirely and are detected by the `INKDEV-` prefix.

---

## 8. Validation Flow

### Flow Diagram

```
┌─────────────────┐
│ Plugin Loads    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ wtcc_get_edition()
└────────┬────────┘
         │
         ▼
┌─────────────────┐    No key
│ Get License Key │──────────────────────┐
└────────┬────────┘                      │
         │ Has key                        │
         ▼                               │
┌─────────────────┐    Yes               │
│ Is INKDEV-* ?   │───────────────┐      │
└────────┬────────┘               │      │
         │ No                     ▼      │
         │              ┌─────────────┐  │
         │              │ Parse Tier  │  │
         │              │ from Key    │  │
         │              └──────┬──────┘  │
         ▼                     │         │
┌─────────────────┐            │         │
│ Cache Valid?    │            │         │
│ (< 12 hours)    │            │         │
└────────┬────────┘            │         │
    Yes  │  No                 │         │
         │                     │         │
    ┌────┴────┐                │         │
    │         │                │         │
    ▼         ▼                │         │
┌───────┐ ┌─────────────┐      │         │
│ Use   │ │ Remote      │      │         │
│ Cache │ │ Validation  │      │         │
└───┬───┘ └──────┬──────┘      │         │
    │            │             │         │
    │     ┌──────┴──────┐      │         │
    │     │             │      │         │
    │     ▼             ▼      │         │
    │  ┌──────┐    ┌────────┐  │         │
    │  │Valid │    │Invalid │  │         │
    │  └──┬───┘    └───┬────┘  │         │
    │     │            │       │         │
    └─────┼────────────┼───────┼─────────┤
          │            │       │         │
          ▼            ▼       ▼         ▼
    ┌───────────────────────────────────────┐
    │         Return Edition                 │
    │  pro/premium/enterprise   OR   free    │
    └───────────────────────────────────────┘
```

### Caching Strategy

1. **Cache Duration**: 12 hours (`12 * HOUR_IN_SECONDS`)
2. **Storage**: `wtcc_license_data` WordPress option
3. **Cache Contents**: `valid`, `tier`, `expires`, `last_check`
4. **Invalidation**: Cache cleared on license key change

### Graceful Degradation

If the license server is unreachable:
1. Plugin logs the error but does NOT break
2. Returns `valid: true` with `tier: pro`
3. Customer site continues working
4. Next check in 12 hours will retry

---

## 9. Feature Gating Implementation

### Admin Menu Gating (plugin.php)

```php
function wtcc_shipping_admin_menu() {
    // Check license status
    $is_licensed = function_exists( 'wtcc_is_pro' ) && wtcc_is_pro();
    $is_premium  = function_exists( 'wtcc_is_premium' ) && wtcc_is_premium();
    
    // === FREE TIER PAGES (Always visible) ===
    add_submenu_page( ... 'Dashboard' ... );
    add_submenu_page( ... 'Rate Calculator' ... );
    add_submenu_page( ... 'License' ... );
    add_submenu_page( ... 'User Guide' ... );
    
    // === PRO TIER PAGES ===
    if ( $is_licensed ) {
        add_submenu_page( ... 'USPS API' ... );
        add_submenu_page( ... 'Presets' ... );
        add_submenu_page( ... 'Box Inventory' ... );
        // ... more Pro features
    }
    
    // === PREMIUM/ENTERPRISE TIER PAGES ===
    if ( $is_premium ) {
        add_submenu_page( ... 'White-Label' ... );
        add_submenu_page( ... 'Bulk Import' ... );
    }
}
```

### Page-Level Gating

```php
function wtcc_shipping_usps_api_page() {
    // STRICT LICENSE CHECK
    $is_licensed = function_exists( 'wtcc_is_pro' ) && wtcc_is_pro();
    
    if ( ! $is_licensed ) {
        ?>
        <div class="wrap">
            <div class="notice notice-error">
                <h3>🔒 Pro License Required</h3>
                <p>USPS API credentials require a Pro license.</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=wtc-core-shipping-license'); ?>" 
                       class="button button-primary">Activate License</a>
                    <a href="https://inkfinit.pro/plugins/usps-shipping/" 
                       target="_blank" class="button">Get Pro License</a>
                </p>
            </div>
        </div>
        <?php
        return;
    }
    
    // ... render full page for licensed users
}
```

### Shipping Method Gating

```php
// In class-shipping-method.php
public function calculate_shipping( $package = array() ) {
    // Free users get fallback rates only
    if ( ! wtcc_is_pro() ) {
        $this->add_rate( array(
            'id'    => 'wtc_fallback',
            'label' => 'Standard Shipping',
            'cost'  => $this->get_fallback_rate( $package ),
        ) );
        return;
    }
    
    // Pro users get live USPS rates
    // ... API call logic
}
```

---

## 10. Testing and Debugging

### Test Keys

Use these development keys for testing (bypass remote validation):

| Key | Edition | Use Case |
|-----|---------|----------|
| `INKDEV-PRO-TEST001` | Pro | Standard Pro testing |
| `INKDEV-PRE-TEST001` | Premium | Premium feature testing |
| `INKDEV-ENT-TEST001` | Enterprise | Enterprise feature testing |

### Debug Mode

Add to `wp-config.php`:

```php
define( 'WTCC_DEBUG', true );
define( 'WTCC_LICENSE_DEBUG', true );
```

Debug output appears in:
- WordPress debug.log
- Browser console (if WP_DEBUG_DISPLAY)

### Test cURL Command

```bash
# Test license validation
curl -X POST https://inkfinit.pro/wp-json/inkfinit/v1/license/validate \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "IUSE-PRO-67ABC123-DEF456-F201",
    "site_url": "https://test-site.com",
    "plugin": "inkfinit-usps-shipping",
    "version": "1.3.0"
  }'
```

### Unit Test (PHP)

```php
/**
 * Test license validation
 */
function test_license_validation() {
    // Test dev key parsing
    $tier = wtcc_parse_dev_key_tier( 'INKDEV-ENT-TEST123' );
    assert( $tier === 'enterprise' );
    
    // Test free edition (no key)
    delete_option( 'wtcc_license_key' );
    delete_option( 'wtcc_license_data' );
    assert( wtcc_get_edition() === 'free' );
    assert( wtcc_is_pro() === false );
    
    // Test pro edition (dev key)
    update_option( 'wtcc_license_key', 'INKDEV-PRO-TEST123' );
    delete_option( 'wtcc_license_data' );
    assert( wtcc_get_edition() === 'pro' );
    assert( wtcc_is_pro() === true );
    assert( wtcc_is_premium() === false );
    
    // Test enterprise edition
    update_option( 'wtcc_license_key', 'INKDEV-ENT-TEST123' );
    delete_option( 'wtcc_license_data' );
    assert( wtcc_get_edition() === 'enterprise' );
    assert( wtcc_is_enterprise() === true );
}
```

---

## 11. Security Considerations

### Input Validation

1. **License Key**: Sanitize with `sanitize_text_field()`, validate format with regex
2. **Site URL**: Sanitize with `esc_url_raw()`
3. **All inputs**: Never trust, always sanitize

### Rate Limiting (License Server)

```php
// Add to REST endpoint
function inkfinit_check_rate_limit( $ip ) {
    $transient_key = 'inkfinit_rate_' . md5( $ip );
    $requests      = get_transient( $transient_key ) ?: 0;
    
    if ( $requests > 100 ) { // Max 100 requests per hour
        return new WP_Error( 'rate_limited', 'Too many requests', array( 'status' => 429 ) );
    }
    
    set_transient( $transient_key, $requests + 1, HOUR_IN_SECONDS );
    return true;
}
```

### SSL Enforcement

```php
// Ensure HTTPS for license validation
if ( strpos( WTCC_LICENSE_SERVER_URL, 'https://' ) !== 0 ) {
    // Log warning, but still proceed for development
    error_log( 'Warning: License server should use HTTPS' );
}
```

### Key Revocation

Admin can revoke keys immediately:
- Sets `status = 'revoked'` in database
- Next validation returns `valid: false`
- Customer site degrades to Free within 12 hours

---

## 12. Troubleshooting Guide

### Issue: License key not validating

**Symptoms**: 
- Key entered but features still locked
- "Free Edition" shown despite valid key

**Solutions**:
1. Check key format (should start with `IUSE-` or `INKDEV-`)
2. Clear validation cache: Delete `wtcc_license_data` option
3. Verify license server URL is correct
4. Test endpoint with cURL
5. Check WordPress debug.log for errors

### Issue: Remote validation failing

**Symptoms**:
- Network timeout errors
- `unknown` status

**Solutions**:
1. Verify license server is accessible
2. Check for firewall blocking outbound connections
3. Verify SSL certificate on license server
4. Increase timeout in `wp_remote_post()` call

### Issue: License key not generating on purchase

**Symptoms**:
- Order completed but no key
- No email sent to customer

**Solutions**:
1. Verify product SKU starts with `IUSE-`
2. Check order status (must be "Processing" or "Completed")
3. Look for errors in WordPress debug.log
4. Manually check order meta for `_inkfinit_license_key`

### Issue: Menu items still showing for free users

**Symptoms**:
- Pro features visible without license

**Solutions**:
1. Verify `wtcc_is_pro()` function exists
2. Check `wtcc_shipping_admin_menu()` has proper gating
3. Clear WordPress cache and transients
4. Verify license.php is loaded before menu registration

### Debug Checklist

```
□ License key format is valid
□ License key is stored in wtcc_license_key option
□ License server URL is correct
□ REST endpoint returns valid JSON
□ Database table exists on license server
□ License record exists with 'active' status
□ License has not expired
□ Validation cache is not stale
□ No PHP errors in debug.log
□ SSL certificate is valid
```

---

## Appendix: Quick Reference

### WordPress Options (Client Plugin)

| Option | Description | Type |
|--------|-------------|------|
| `wtcc_license_key` | Stored license key | string |
| `wtcc_license_data` | Cached validation result | array |
| `wtcc_license_server_url` | Custom server URL (optional) | string |

### WordPress Options (License Server)

| Option | Description | Type |
|--------|-------------|------|
| `inkfinit_license_db_version` | Database schema version | string |

### Key Functions

| Function | Purpose | Returns |
|----------|---------|---------|
| `wtcc_get_license_key()` | Get stored license key | string |
| `wtcc_get_edition()` | Get current tier | string |
| `wtcc_is_pro()` | Check Pro+ access | bool |
| `wtcc_is_premium()` | Check Premium+ access | bool |
| `wtcc_is_enterprise()` | Check Enterprise access | bool |
| `wtcc_validate_license_remote()` | Validate against server | array |

### Hooks (License Server)

| Hook | Purpose |
|------|---------|
| `woocommerce_order_status_completed` | Generate key on order complete |
| `woocommerce_order_status_processing` | Generate key on order processing |
| `rest_api_init` | Register validation endpoint |

---

**Document Version**: 1.0.0  
**Last Updated**: 2024-01-15  
**Author**: Inkfinit Development Team

> This document is designed to be followed by AI systems for implementation, debugging, and maintenance of the license key integration system.
