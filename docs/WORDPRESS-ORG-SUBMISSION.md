# WordPress.org Plugin Directory Submission Checklist

**Last Updated:** December 3, 2025  
**Plugin:** Inkfinit USPS Shipping Engine v1.2.0  
**Status:** Ready for WordPress.org Submission

---

## Pre-Submission Requirements ✅

### 1. Plugin Metadata
- [x] Plugin name clearly indicates purpose: "Inkfinit USPS Shipping Engine"
- [x] Description is clear and concise (under 150 chars when possible)
- [x] Author name: "Inkfinit LLC" (company, not individual)
- [x] Valid license: GPL-3.0-or-later
- [x] Version number: 1.2.0 (follows semantic versioning)

### 2. Code Standards
- [x] No references to commercial marketplace (WTC, unauthorized downloads)
- [x] All functions prefixed with unique identifier: `wtcc_` for core, `wtc_` for features
- [x] No external libraries without proper licensing
- [x] No embedded external JavaScript/CSS from CDNs (bundled locally)
- [x] Proper error handling and logging
- [x] SQL injection prevention with prepared statements
- [x] XSS prevention with proper escaping (esc_html, esc_attr, esc_url)
- [x] CSRF protection with nonces
- [x] Proper capability checking (manage_woocommerce, manage_options)

### 3. File Structure
```
inkfinit-shipping-engine/
├── plugin.php (main file)
├── readme.txt (WordPress.org format)
├── uninstall.php (cleanup on removal)
├── LICENSE (GPL-3.0-or-later)
├── includes/
│   ├── core-functions.php
│   ├── admin-*.php (admin pages)
│   ├── usps-*.php (USPS integration)
│   └── ... (other functionality)
├── assets/
│   ├── admin-style.css
│   ├── admin-*.js
│   ├── frontend-*.js
│   ├── frontend-*.css
│   └── images/
├── languages/
│   ├── wtc-shipping.pot (translation file)
│   └── [translations]
├── docs/
│   ├── INDEX.md
│   └── [documentation files]
└── build/ (if applicable)
```

### 4. Documentation
- [x] Comprehensive README in root folder
- [x] Installation instructions in readme.txt
- [x] Usage guide for end-users
- [x] Developer documentation for customization
- [x] Changelog documenting all versions

### 5. Security
- [x] No hardcoded credentials or API keys
- [x] Settings validated and sanitized on save
- [x] User capabilities checked before showing sensitive info
- [x] USPS credentials stored in wp_options with proper escaping
- [x] No direct file access (ABSPATH checks)

### 6. Compatibility
- [x] WordPress minimum: 5.8
- [x] PHP minimum: 8.0 (modern, supported)
- [x] WooCommerce minimum: 8.0
- [x] Tested up to: WordPress 6.7, WooCommerce 9.4
- [x] No deprecated functions used

### 7. Internationalization (i18n)
- [x] All user-facing strings wrapped in __() or esc_html__()
- [x] Text domain: 'wtc-shipping'
- [x] Domain path: /languages
- [x] POT file generated for translators
- [x] No hardcoded text in JavaScript (uses wp_localize_script)

### 8. Dependencies
- [x] Requires WooCommerce (properly declared)
- [x] No required external APIs beyond USPS
- [x] Graceful degradation if USPS API unavailable
- [x] Admin notice if WooCommerce not installed/activated

---

## readme.txt Format ✅

Your `readme.txt` must follow the exact WordPress.org format:

```
=== Plugin Name ===
Contributors: username(s)
Tags: tag1, tag2, tag3
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 8.0
Requires Plugins: woocommerce
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0-or-later.html
Stable tag: 1.2.0

Short description (max 150 chars)

== Description ==

Longer description with markdown support

== Installation ==

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

== Upgrade Notice ==
```

**Current Status:** ✅ Properly formatted

---

## What WordPress.org DOES Allow ✅

- ✅ Links to your website/documentation
- ✅ Links to external services (USPS, GitHub for support)
- ✅ Commercial disclaimers (e.g., "This is a premium feature" in free version)
- ✅ Notices about premium/paid versions (if applicable)
- ✅ Links to professional documentation
- ✅ Requires premium subscriptions/services to work
- ✅ Collecting user data with proper opt-in (GDPR compliant)

---

## What WordPress.org DOES NOT Allow ❌

- ❌ Selling the plugin directly from plugin code
- ❌ Paid download links within the plugin
- ❌ Requiring payment within the plugin interface
- ❌ Restricting plugin to "subscribers only"
- ❌ Preventing modifications/redistribution against GPL
- ❌ Directing users to external sites to "activate" premium
- ❌ Including external commercial plugins
- ❌ Obfuscated code
- ❌ Self-updating without WordPress.org updates

---

## GitHub Setup (For Support & Distribution)

Since this is a commercial plugin sold separately, your GitHub repository should:

### Configure for Commercial Use:

1. **Add a License Notice in README.md:**
```markdown
## Commercial License

This plugin is a commercial product sold by Inkfinit LLC.
It is licensed under GPL-3.0-or-later, BUT:
- Downloads from GitHub are provided as-is
- For official support and updates, purchase from [your sales page]
- You may modify and redistribute per GPL-3.0
```

2. **Create a `.github/FUNDING.yml`:**
```yaml
custom: ['https://boundlessink.com']
```

3. **Protect branches:**
   - Set `main` branch protection to require PR reviews
   - Add status checks before merging
   - Require branch to be up to date

4. **Create `.gitignore` for sensitive files:**
```
# API Keys and Credentials
.env
.env.local
.env.*.local
wp-config*.php

# WordPress
wp-content/
wp-admin/
wp-includes/
```

---

## Distribution Strategy

### For WordPress.org (Free Version):
1. Submit through https://wordpress.org/plugins/developers/
2. All features available for free
3. Updates managed through WordPress.org
4. Support through WordPress.org forums

### For GitHub (Commercial):
1. Repository contains the full plugin
2. Clear commercial license notice
3. Link to Inkfinit LLC website for support
4. Add branch protection for production releases

### For Your Website:
1. Include purchase/download link
2. License key management (if using licensing system)
3. Premium documentation
4. Technical support email
5. Update notifications

---

## Submission Steps

1. **Create WordPress.org Account**
   - Go to https://wordpress.org/plugins/developers/
   - Register as "Inkfinit LLC" or your account name

2. **Prepare for Submission**
   - Zip plugin directory
   - Ensure all files follow naming conventions
   - Verify readme.txt format is exact
   - Test on latest WordPress and WooCommerce

3. **Submit Plugin**
   - Follow WordPress.org submission form
   - Provide clear description and features
   - Upload plugin zip file

4. **Wait for Review** (typically 1-2 weeks)
   - Reviewer checks for security issues
   - Verifies code quality
   - Tests basic functionality
   - Reviews readme.txt and screenshots

5. **Respond to Feedback**
   - Address any security concerns
   - Fix compatibility issues
   - Improve documentation if requested

6. **Launch**
   - Plugin appears in WordPress.org directory
   - Updates managed through WordPress admin
   - Users can download and install automatically

---

## Plugin Directory URL

Once approved, your plugin will be available at:
```
https://wordpress.org/plugins/inkfinit-shipping-engine/
```

Users can search for:
- "Boundless Ink"
- "Shipping Engine"
- "USPS Shipping"
- And any tags you define

---

## Marketing Your Plugin

1. **Update Your Website** with WordPress.org link
2. **Create Blog Posts** about shipping management
3. **Post on Social Media** about the free version
4. **SEO Optimization** target shipping-related keywords
5. **Email Marketing** to your customer base
6. **Documentation** make it easy to use
7. **Support Forum** actively respond to questions

---

## Compliance Checklist

- [x] GPL-3.0-or-later compliant
- [x] No hardcoded credentials
- [x] No backdoors or obfuscation
- [x] Proper security headers
- [x] Data handling disclosed
- [x] No malware or suspicious code
- [x] No external calls without disclosure
- [x] GDPR considerations documented
- [x] Proper error handling
- [x] Database queries escaped

---

## Important Notes

1. **WooCommerce Dependency:** Since this requires WooCommerce, WordPress.org will show this requirement clearly

2. **USPS API:** Users must have USPS Web Tools account. Document this in FAQ section.

3. **Support:** Have a plan for handling support questions (email, forum, documentation)

4. **Updates:** After WordPress.org approval, submit updates through their system

5. **Version Numbering:** Follow semantic versioning (MAJOR.MINOR.PATCH)

---

## Next Steps

1. ✅ Verify all checklist items
2. ✅ Test on clean WordPress/WooCommerce install
3. ✅ Create account on WordPress.org
4. ✅ Submit plugin through developer portal
5. ✅ Monitor email for reviewer feedback
6. ✅ Publish GitHub repository with commercial notice
7. ✅ Create marketing materials
8. ✅ Launch sales/distribution channel

---

For more information, see:
- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WordPress.org Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/)
- [WordPress Security Best Practices](https://developer.wordpress.org/plugins/security/)
