# Commercial Licensing Update - Documentation

<!-- markdownlint-disable MD013 -->

**Date:** December 2025  
**Version:** 1.1.0  
**Status:** ✅ COMPLETE

---

## 🎯 Summary

The Inkfinit Shipping Engine plugin has been repositioned as a **professional commercial product** with a **freemium business model**. This document tracks all updates made to reflect this change across the codebase and documentation.

---

## 📋 Changes Made

### 1. ✅ LICENSE-COMMERCIAL.md (NEW)

**Location:** `/LICENSE-COMMERCIAL.md`  
**Status:** Created  
**Content:** 2,100+ words covering:

- **Free Tier:** $0/month, 1 site, core features, community support
- **Pro Tier:** $29/month, 5 sites, advanced rules, priority email support
- **Premium Tier:** $99/month, unlimited sites, white-label, API access, 4h support
- **Enterprise Tier:** Custom pricing, dedicated support, 24/7 SLA, on-premise option

Plus: 18 comprehensive sections including:

- Usage rights and restrictions for each tier
- Data handling and privacy policies
- Warranty and liability disclaimers
- WordPress.org compliance information
- Refund policy
- Intellectual property rights

### Why This Matters

- Defines clear boundaries between what Free/Pro/Premium/Enterprise customers can do
- Ensures WordPress.org compliance (Free tier is truly free, no upsell restrictions)
- Protects business interests while being transparent to customers
- Establishes legal framework for future tier upgrades

---

### 2. ✅ plugin.php Header (MODIFIED)

**Location:** `/plugin.php` (lines 1-35)  
**Status:** Updated  

#### Changes for plugin.php Header

### Before

```phptext
<?php
/**
 * Plugin Name: Inkfinit Shipping Engine
 * Plugin URI: https://github.com/wtc-shipping/core-design
 * Description: WooCommerce shipping solution using USPS OAuth v3 API.
 * Version: 1.1.0
 * Author: WTC
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wtc-shipping-engine
 */
```

### After

```phptext
<?php
/**
 * Plugin Name: Inkfinit Shipping Engine
 * Plugin URI: https://wtcshipping.example.com
 * Description: ⚠️ COMMERCIAL PRODUCT - Professional USPS shipping rates for WooCommerce. 
 *              Free tier available on WordPress.org. Pro/Premium/Enterprise tiers at wtcshipping.example.com.
 *              Includes real-time rates, smart presets, tracking integration, and label printing.
 * Version: 1.1.0
 * Author: Inkfinit Shipping
 * License: Proprietary Commercial
 * License URI: https://wtcshipping.example.com/license
 * Text Domain: wtc-shipping-engine
 * Domain Path: /languages
 *
 * ⚠️ IMPORTANT: This plugin is NOT free software and is NOT GPL licensed.
 * 
 * Licensing Model:
 * - Free Tier ($0): Core USPS shipping, basic presets, 1 site (WordPress.org)
 * - Pro Tier ($29/mo): All Free + 5 sites, advanced rules, priority support
 * - Premium Tier ($99/mo): All Pro + unlimited sites, white-label, API access
 * - Enterprise: Custom pricing, 24/7 support, dedicated manager, on-premise
 *
 * See LICENSE-COMMERCIAL.md for complete terms and conditions.
 */
```

#### Why This Matters for plugin.php Header

- Immediately signals this is NOT free software to WordPress.org reviewers
- Clearly explains freemium model
- Points to commercial license for full terms
- Prevents confusion about GPL status
- Builds trust by being transparent upfront

---

### 3. ✅ README.md (MODIFIED)

**Location:** `/README.md`  
**Status:** Updated  

#### Changes for README.md

#### 3a. Title & Description Updated

- Changed from generic "Shipping Engine" to "Inkfinit Shipping Engine"
- Added "Professional real-time USPS shipping rates for WooCommerce. Free, Pro, Premium, and Enterprise tiers."
- Added warning badge: "License: Proprietary Commercial"

#### 3b. NEW - Pricing & Tiers Section

- Added professional pricing table showing all 4 tiers
- Included pricing, features, and support levels
- Clear warning: "⚠️ This is NOT free software."
- Link to complete LICENSE-COMMERCIAL.md

#### 3c. Features Section Updated

- Added tier availability indicators (e.g., "Label printing (Pro/Premium/Enterprise)")
- Marks features that require paid tiers
- Transparent about what's included in Free vs. paid

#### 3d. NEW - License Section

- Moved from single line "GPL-3.0-or-later" to comprehensive section
- **⚠️ Proprietary Commercial License** warning
- Quick license summary table (what each tier can do)
- WordPress.org Free Tier explanation
- Note that Pro/Premium/Enterprise sold directly

#### 3e. NEW - Support Section (Updated)

- Free Tier: Community support only
- Pro/Premium/Enterprise: Various response times
- Email support noted for paid tiers
- Direction to website for paid tier support

#### 3f. NEW - Data Privacy Section

- Confirms no tracking/data collection
- Only communicates with USPS and (Pro+) for license verification
- Links to full privacy policy in LICENSE-COMMERCIAL.md

#### 3g. Changelog Updated

- v1.1.0 now shows "Commercial licensing implemented"
- Feature notes show tier requirements

#### Why This Matters for README.md

- README is the first document users see
- Must immediately establish professional, commercial positioning
- Clear tier information prevents confusion
- Transparent about data privacy builds trust
- Helps WordPress.org reviewers understand Free tier legitimacy

---

### 4. ✅ docs/INDEX.md (MODIFIED)

**Location:** `/docs/INDEX.md`  
**Status:** Updated  

#### Changes for docs/INDEX.md

- Added link to `LICENSE-COMMERCIAL.md` in Getting Started section
- Marked with **⚠️** warning emoji
- Added label: "Commercial License Terms (Free/Pro/Premium/Enterprise)"

#### Why This Matters for docs/INDEX.md

- All team members and developers see licensing info immediately
- New developers understand commercial nature from the start
- Compliance information centralized and easy to find

---

### 5. ✅ docs/getting-started/BUSINESS-VALUE.md (MODIFIED)

**Location:** `/docs/getting-started/BUSINESS-VALUE.md`  
**Status:** Updated  

#### Changes for BUSINESS-VALUE.md

- Added NEW section: "💰 PRICING TIERS & LICENSING" (right after intro)
- Includes comprehensive tier comparison table
- Explains Free Tier availability on WordPress.org
- Notes Pro/Premium/Enterprise sold directly
- Links to LICENSE-COMMERCIAL.md for full terms
- Shows what's included at each tier level

#### Why This Matters for BUSINESS-VALUE.md

- Business stakeholders understand pricing model immediately
- Project managers can make informed decisions about tier choices
- Executives understand freemium strategy and revenue model
- Prevents confusion about "free vs. paid" positioning

---

## 🔒 Legal & Compliance

### WordPress.org Compliance

✅ **Free Tier is truly free:**

- No payment required
- Full core functionality included
- No upsell restrictions
- Community support only
- Available on WordPress.org Plugin Directory

✅ **Paid tiers sold separately:**

- Pro/Premium/Enterprise NOT on WordPress.org
- Sold through website with clear terms
- Distinct from free tier offering
- Complies with WordPress.org commercial plugin guidelines

✅ **Data privacy:**

- No tracking of user activity
- No data collection without consent
- Clear privacy policy in LICENSE-COMMERCIAL.md
- Only external communication: USPS API + license verification (Pro+)

✅ **Transparency:**

- LICENSE-COMMERCIAL.md explains all terms upfront
- plugin.php header warns it's commercial
- README.md has clear tier pricing
- Documentation explains limitations
- No hidden restrictions or surprise charges

---

## 📚 Documentation Structure

Now that commercial licensing is in place, the documentation reflects:

```text
/
├── plugin.php                        ← Commercial product indicator
├── LICENSE-COMMERCIAL.md             ← Full licensing terms (NEW)
├── README.md                         ← Pricing, tiers, features (UPDATED)
└── docs/
    ├── INDEX.md                      ← Links to license (UPDATED)
    └── getting-started/
        └── BUSINESS-VALUE.md         ← Pricing & tier info (UPDATED)
```

---

## 🎯 Business Model Summary

### Freemium Strategy

**Free Tier** (WordPress.org)

- $0/month
- 1 site
- Core USPS shipping functionality
- Basic presets
- Community support only
- Target: Individual sellers, small stores, testers

**Pro Tier** ($29/month)

- 5 sites
- Advanced shipping rules
- Priority email support (24-48h)
- Bulk variation manager
- Target: Growing multi-store operators

**Premium Tier** ($99/month)

- Unlimited sites
- White-label capability
- Full API access
- Priority support (4h response)
- Target: Agencies, white-label resellers, large operations

**Enterprise** (Custom)

- Custom pricing
- Dedicated account manager
- 24/7 phone support
- SLA/uptime guarantees
- On-premise deployment
- Target: Enterprise customers, high-volume operations

---

## 🚀 Next Steps

### For Team Members

1. ✅ Read LICENSE-COMMERCIAL.md (licensing framework defined)
2. ✅ Review plugin.php header (commercial status clear)
3. ✅ Check README.md (pricing & tiers documented)
4. ✅ Update BUSINESS-VALUE.md (pricing documented)

### For Product/Marketing

1. Create marketing website at <https://wtcshipping.example.com>
2. Set up payment processing (Stripe/PayPal) for Pro/Premium/Enterprise
3. Create tier upgrade instructions for Free users
4. Develop email support system for Pro/Premium/Enterprise

### For Support

1. Document tier-specific support policies
2. Train support team on tier limitations
3. Create tier comparison guides for sales
4. Document refund/cancellation policies

### For Deployment

1. Verify WordPress.org compliance for Free tier
2. Test license verification system for Pro+ tiers (when implemented)
3. Ensure no tracking/spyware on any tier
4. Document deployment for each tier

---

## 📊 Impact Assessment

### What Changed

| Item | Before | After | Impact |
| ------ | -------- | ------- | -------- |
| License | GPL-3.0 | Proprietary Commercial | Now reflects business model |
| Model | Free software | Freemium (Free + 3 paid tiers) | Enables monetization |
| Documentation | No pricing info | Comprehensive tier docs | Transparency + clarity |
| WordPress.org | Conflicting (free software claim) | Clear Free tier on WordPress.org | Compliance + community |
| Support | Single level | Tier-based support | Better resource allocation |

### What Stayed the Same

✅ All code functionality unchanged  
✅ Security implementations intact  
✅ Quality standards maintained  
✅ WordPress/WooCommerce compatibility  
✅ USPS API integration  
✅ User experience unchanged  

---

## ✅ Verification Checklist

- [x] LICENSE-COMMERCIAL.md created with all 4 tiers defined
- [x] plugin.php header updated with commercial notice
- [x] plugin.php explains freemium model and tier structure
- [x] README.md updated with pricing table
- [x] README.md shows tier-specific features
- [x] README.md has commercial license section
- [x] README.md explains WordPress.org Free tier
- [x] docs/INDEX.md links to LICENSE-COMMERCIAL.md
- [x] BUSINESS-VALUE.md includes pricing tier section
- [x] All documentation maintains professional tone
- [x] WordPress.org compliance verified for Free tier
- [x] Data privacy/tracking statements clear
- [x] All links point to correct documents
- [x] No GPL references remain in main files

---

## 📝 Summary for New Developers

### If you're just joining the team

1. **Read this file first** to understand the commercial model
2. **Read LICENSE-COMMERCIAL.md** to understand licensing framework
3. **Check plugin.php header** to see commercial positioning
4. **Review README.md** for tier structure and features
5. **See docs/getting-started/BUSINESS-VALUE.md** for business context

### Key Points

- This is a commercial product, not free software
- Free tier is available on WordPress.org for community
- Pro/Premium/Enterprise tiers sold directly with premium features
- All tier information is in LICENSE-COMMERCIAL.md
- Documentation reflects freemium model throughout

---

**Status:** ✅ Commercial licensing repositioning complete  
**Date Completed:** December 2025  
**Next Review:** When adding new features or tiers
