# TODO Before Launch

> **Last Updated:** December 9, 2025  
> **Status:** Pre-Launch Checklist

This file tracks tasks that must be completed before public release. Add items here as they come up during development.

---

## 🔴 CRITICAL (Must Complete)

### Security & Code Quality (From Devil's Advocate Audit)

- [x] ~~Set `$disable_test_keys_permanently = true` in license.php~~ ✅ FIXED
- [x] ~~Fix Plugin URI to marketing site~~ ✅ FIXED  
- [x] ~~Add index.php to includes/, assets/, assets/images/~~ ✅ FIXED
- [x] ~~Fix debug logging to not leak API tokens~~ ✅ FIXED
- [x] ~~Fix dashboard performance (was loading ALL products)~~ ✅ FIXED
- [x] ~~Add caching to wtcc_is_pro() calls~~ ✅ FIXED
- [ ] **Move inline styles to CSS files** (50+ instances - WordPress.org will reject)
- [ ] **Move inline JavaScript to external files** (address-validation.php)
- [ ] Add rate limiting to public AJAX endpoints (nopriv handlers)
- [ ] Add `wp_unslash()` to all $_POST/$_GET sanitization
- [ ] Audit all strings for proper i18n (some hardcoded English found)

### Screenshots for WordPress.org

- [ ] **Screenshot 1:** Dashboard overview (admin panel)
- [ ] **Screenshot 2:** Rate calculator in action
- [ ] **Screenshot 3:** USPS API settings page
- [ ] **Screenshot 4:** Checkout page with live rates
- [ ] **Screenshot 5:** Order tracking display
- [ ] **Screenshot 6:** Label printing interface
- [ ] **Screenshot 7:** Shipping presets editor
- [ ] **Screenshot 8:** Bulk variation manager

**Instructions:** Screenshots should be named `screenshot-1.png`, `screenshot-2.png`, etc. and placed in the `assets/` folder for WordPress.org submission.

### Banner and Icon Images

- [ ] **Plugin Banner:** 772x250 pixels (`banner-772x250.png`)
- [ ] **Plugin Banner HD:** 1544x500 pixels (`banner-1544x500.png`)
- [ ] **Plugin Icon:** 128x128 pixels (`icon-128x128.png`)
- [ ] **Plugin Icon HD:** 256x256 pixels (`icon-256x256.png`)

**Instructions:** Place in `assets/` folder. Use Inkfinit brand colors (navy #002868, green #0fb47e).

---

## 🟡 HIGH PRIORITY

### License Server Deployment

- [ ] Upload `license-server/` plugin to inkfinit.pro
- [ ] Create WooCommerce products:
  - [ ] `IUSE-PRO-MONTHLY` - Pro Monthly ($9.99/mo)
  - [ ] `IUSE-PRO-YEARLY` - Pro Yearly ($99/yr)
  - [ ] `IUSE-ENT-MONTHLY` - Enterprise Monthly ($29.99/mo)
  - [ ] `IUSE-ENT-YEARLY` - Enterprise Yearly ($299/yr)
- [ ] Test license key generation
- [ ] Test license validation from client plugin
- [ ] Verify email delivery with license keys

### WordPress.org Submission

- [ ] Run WordPress Plugin Check (PCP)
- [ ] Verify all text is internationalized
- [ ] Check for any hardcoded URLs
- [ ] Verify uninstall.php cleans up properly
- [ ] Test activation/deactivation hooks
- [ ] Submit to WordPress.org repository

### Documentation

- [ ] Record video tutorial (YouTube)
- [ ] Create help articles on inkfinit.pro
- [ ] FAQ page on website
- [ ] Support ticketing system setup

---

## 🟢 NICE TO HAVE

### Marketing

- [ ] Launch announcement blog post
- [ ] Social media graphics
- [ ] Email campaign to beta testers
- [ ] Product Hunt launch
- [ ] WordPress community outreach

### Features for v1.4

- [ ] Multi-carrier support (UPS, FedEx)
- [ ] International shipping zones
- [ ] Address book for frequent recipients
- [ ] Shipment insurance integration
- [ ] Returns label generation

---

## 📝 Notes

### Screenshot Guidelines (WordPress.org)

1. Screenshots must be PNG or JPEG
2. Max width: 1200px recommended
3. Should demonstrate actual functionality
4. Include diverse product examples
5. Show both admin and frontend views

### Testing Checklist

Before each release:

- [ ] PHP 8.0 compatibility
- [ ] PHP 8.1 compatibility
- [ ] PHP 8.2 compatibility
- [ ] WordPress 6.4 compatibility
- [ ] WordPress 6.5 compatibility
- [ ] WordPress 6.6 compatibility
- [ ] WordPress 6.7 compatibility
- [ ] WooCommerce 8.0+ compatibility
- [ ] WooCommerce HPOS compatibility
- [ ] Multisite compatibility

---

## Completed Items

Move items here when done:

- [x] Security audit completed (Dec 9, 2025)
- [x] License server plugin created
- [x] Simple calculator for free users
- [x] Admin menu license gating
- [x] Git repository setup
- [x] AI Developer Guide created

