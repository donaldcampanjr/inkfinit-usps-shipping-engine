# GitHub Repository Protection & Commercial License Setup

## Overview

This guide explains how to protect your plugin from unauthorized distribution while still allowing:
- Legitimate customers to access paid tiers
- WordPress.org community access to free tier
- Secure license verification
- Automated updates for licensed customers

---

## Part 1: Free Tier (Public - WordPress.org)

### Repository Setup

```
Repository Name: inkfinit-shipping
Visibility: Public
License: GPL-3.0-or-later
Description: Free tier available on WordPress.org
```

### GitHub Configuration

1. **Main Branch Settings**
   - Require pull request reviews: No (free tier is in WordPress.org)
   - Dismiss stale PRs: Yes
   - Allow auto-merge: No
   - Default branch: `main`

2. **Protection Rules**
   - Set `main` branch protection
   - Require status checks to pass
   - Require up-to-date branches
   - Include administrators

3. **Actions & Permissions**
   - Allow Actions from pull requests: Yes
   - Allow auto-generated dependabot PRs

---

## Part 2: Pro Tier (Private - Commercial)

### Repository Setup

```
Repository Name: inkfinit-shipping-pro
Visibility: Private
License: All-Rights-Reserved (proprietary)
Description: Commercial Pro/Premium/Enterprise tiers
```

### Access Control

**Team Members with Access:**
- Code owners: Write
- Security team: Admin
- Support team: Read-only
- Developers: Write
- Customers: Download only (via release/license system)

### Branch Protection

```yaml
main:
  require_pull_requests: true
  required_reviewers: 1
  dismiss_stale_reviews: true
  require_status_checks: true
  required_checks:
    - continuous-integration
    - security-scan
    - code-quality
  restrict_to_admins: true

develop:
  require_pull_requests: true
  required_reviewers: 1
```

---

## Part 3: License Verification System

### License Key Format

```
BISH-XXXX-XXXX-XXXX-XXXX
├─ BISH = Product code (Inkfinit Shipping)
├─ Year = License year (2025, 2026, etc.)
├─ Type = License tier (PRO, PRM, ENT)
└─ Random = 16 random characters
```

Example: `BISH-2025-PRO-A7F2K9Q2R5M8B1N3`

### License Verification in Plugin

**File:** `includes/license-verification.php`

```php
<?php
/**
 * License verification system
 * Validates Pro/Premium/Enterprise licenses
 */

function bish_verify_license( $license_key ) {
    // Check format
    if ( ! preg_match( '/^BISH-\d{4}-[A-Z]{3}-[A-Z0-9]{16}$/i', $license_key ) ) {
        return false;
    }

    // Check against master license file
    $valid_licenses = bish_get_master_licenses();
    
    // Verify checksum
    $checksum = bish_calculate_license_checksum( $license_key );
    if ( ! in_array( $checksum, $valid_licenses, true ) ) {
        return false;
    }

    // Check license hasn't expired
    $expiry = bish_get_license_expiry( $license_key );
    if ( $expiry < time() ) {
        return false;
    }

    return true;
}
```

### Master License Database

**File:** Store in encrypted option or external API

```
API Endpoint: https://license.inkfinit.pro/api/verify
Method: POST
Body: {
  "license_key": "BISH-2025-PRO-...",
  "domain": "example.com",
  "plugin_version": "1.2.0"
}

Response: {
  "valid": true,
  "tier": "pro",
  "expires": "2026-01-01",
  "domains": 5,
  "domains_used": 3
}
```

---

## Part 4: Download Protection

### GitHub Releases

**Public Releases (Free Tier):**
- Available in WordPress.org (automatic)
- Also available in GitHub releases for developers
- Tag format: `v1.2.0`
- Asset: `inkfinit-shipping-1.2.0.zip`
- Public download link

**Private Releases (Pro Tier):**
- Tagged as `pro-v1.2.0`
- Private release notes
- Asset: `inkfinit-shipping-pro-1.2.0.zip`
- Download only via:
  - License verification
  - GitHub API token (customers only)
  - License management dashboard

### Repository `.gitignore` Modifications

```bash
# Don't commit license data
*.license
license-keys.json
license-verification-cache/

# Don't commit customer data
customer-licenses.php
license-database.json

# Don't commit payment processing keys
.env.local
payment-keys.json

# Generated files
/dist/
/build/
/coverage/
```

---

## Part 5: Distribution Methods

### Free Tier Distribution

```
User Path 1: WordPress.org (Recommended)
├─ Search for "Inkfinit Shipping"
├─ Click "Install Now"
├─ Activate plugin
└─ Optionally upgrade to Pro tier

User Path 2: GitHub Public Repo
├─ Visit github.com/donaldcampanjr/inkfinit-shipping
├─ Click "Releases"
├─ Download latest release ZIP
├─ Upload to WordPress manually
└─ Activate plugin
```

### Pro/Premium/Enterprise Tier Distribution

```
User Path 1: License Dashboard (Recommended)
├─ Customer purchases license
├─ Dashboard generates license key
├─ Dashboard provides download link
├─ Download includes license embedded
├─ Plugin auto-activates with license
└─ Auto-updates via license

User Path 2: GitHub Private Access (Developers)
├─ Customer receives GitHub org invitation
├─ Add SSH key to GitHub account
├─ git clone git@github.com:donaldcampanjr/inkfinit-shipping-pro.git
├─ License key in environment file
└─ Manual updates from private repo
```

---

## Part 6: Anti-Piracy Measures

### License Enforcement in Code

```php
// Check license on plugin init
add_action( 'plugins_loaded', 'bish_verify_pro_features', 5 );
function bish_verify_pro_features() {
    $license_key = get_option( 'bish_pro_license_key' );
    
    if ( ! bish_verify_license( $license_key ) ) {
        // Disable Pro features
        define( 'BISH_PRO_ACTIVE', false );
        
        // Show upgrade notice
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo 'Your Inkfinit Shipping Pro license is invalid or expired. ';
            echo '<a href="https://inkfinit.pro/renew-license">Renew Now</a>';
            echo '</p></div>';
        });
    } else {
        define( 'BISH_PRO_ACTIVE', true );
    }
}
```

### Phone Home System

```php
/**
 * Weekly license verification
 * Verifies license is still active
 */
add_action( 'wp_loaded', 'bish_phone_home_check' );
function bish_phone_home_check() {
    $last_check = get_option( 'bish_last_license_check' );
    
    // Check weekly
    if ( $last_check && ( time() - $last_check ) < WEEK_IN_SECONDS ) {
        return;
    }
    
    // Verify with server
    $license_key = get_option( 'bish_pro_license_key' );
    $response = wp_remote_post( 'https://license.inkfinit.pro/verify', array(
        'body' => array(
            'license_key' => $license_key,
            'domain' => get_option( 'siteurl' ),
            'version' => BISH_VERSION,
        ),
    ));
    
    if ( ! is_wp_error( $response ) ) {
        $result = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! $result['valid'] ) {
            update_option( 'bish_pro_license_invalid', true );
        }
    }
    
    update_option( 'bish_last_license_check', time() );
}
```

### Disable Pro Features If License Invalid

```php
/**
 * Hook into Pro features
 * Only allow if license is active
 */
if ( ! defined( 'BISH_PRO_ACTIVE' ) || ! BISH_PRO_ACTIVE ) {
    // Hide Pro menu items
    add_filter( 'bish_show_pro_features', '__return_false' );
    
    // Disable bulk variation manager
    remove_action( 'admin_menu', 'bish_add_variation_manager_menu' );
    
    // Disable advanced rules
    remove_action( 'admin_menu', 'bish_add_rules_editor_menu' );
    
    // Disable label printing
    remove_action( 'woocommerce_order_actions', 'bish_add_print_label_action' );
}
```

---

## Part 7: GitHub Actions for Security

### Automated Code Scanning

Create `.github/workflows/security.yml`:

```yaml
name: Security Scan

on: [push, pull_request]

jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Run Semgrep Security Scan
        uses: returntocorp/semgrep-action@v1
        with:
          config: >-
            p/security-audit
            p/php
      
      - name: Upload Results
        uses: github/codeql-action/upload-sarif@v2
        with:
          sarif_file: semgrep.sarif
```

### Version Check Action

Create `.github/workflows/version-check.yml`:

```yaml
name: Version Consistency

on: [push, pull_request]

jobs:
  version-check:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Check Version Consistency
        run: |
          PLUGIN_VERSION=$(grep -oP "Version: \K.*" plugin.php)
          README_VERSION=$(grep -oP "Stable tag: \K.*" readme.txt)
          
          if [ "$PLUGIN_VERSION" != "$README_VERSION" ]; then
            echo "Version mismatch!"
            exit 1
          fi
```

---

## Part 8: Release Process

### Creating a Release

**For Free Tier (Public):**

```bash
# Update version numbers
# Update CHANGELOG.md
# Commit changes

git tag -a v1.2.0 -m "Release v1.2.0 - Free tier"
git push origin v1.2.0

# GitHub automatically creates release
# WordPress.org syncs from SVN tag
```

**For Pro Tier (Private):**

```bash
# On private repo
git tag -a pro-v1.2.0 -m "Release v1.2.0 - Pro tier"
git push origin pro-v1.2.0

# Upload to license dashboard
# Generate download link
# Email customers
```

---

## Part 9: Communication with Customers

### License Verification Email

```
Subject: Your Inkfinit Shipping License

Hello [Customer],

Your Pro license has been successfully activated!

License Key: BISH-2025-PRO-A7F2K9Q2R5M8B1N3
Tier: Pro
Valid Until: January 1, 2026
Domains Allowed: 5

Installation:

1. Download: https://dashboard.inkfinit.pro/license/download/[TOKEN]
2. Upload to WordPress Plugins
3. Activate in WordPress
4. Go to Inkfinit Shipping → License
5. Paste your license key
6. Click "Verify License"

All Pro features are now unlocked!

Support: support@inkfinit.pro
Docs: https://docs.inkfinit.pro/pro

Best regards,
Boundless Ink Team
```

### Expiration Warning

```
Subject: Your License Expires Soon

Your Inkfinit Shipping Pro license expires in 30 days!

Current License: BISH-2025-PRO-...
Expires: January 1, 2026

Renew Now: https://inkfinit.pro/renew?license=BISH-2025-PRO-...

Without renewal, Pro features will become unavailable.

This will NOT affect your free tier functionality.

Renew Now: [Button]
```

---

## Summary: Repository Protection Strategy

### Free Tier (Public)
✅ Open source on WordPress.org  
✅ Available on GitHub public  
✅ Anyone can download/use  
✅ No license verification  
✅ Community contributions welcome  

### Pro Tier (Private)
🔒 Closed source on private GitHub  
🔒 License key required for access  
🔒 Encrypted downloads  
🔒 License verification + phone home  
🔒 DMCA-protected (proprietary)  
🔒 Customers-only private org membership  

### Result
✨ **Your plugin is protected from theft**  
✨ **Customers get secure access**  
✨ **Revenue model sustainable**  
✨ **Community gets free tier**  
✨ **WordPress.org happy (GPL-free tier)**  

---

**Last Updated:** 2025-12-03  
**Author:** Inkfinit LLC
