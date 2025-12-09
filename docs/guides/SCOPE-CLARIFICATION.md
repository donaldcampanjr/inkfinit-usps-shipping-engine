# Inkfinit Shipping Engine - Scope Clarification Form

<!-- markdownlint-disable MD013 -->

### Complete this form to establish clear requirements before development begins

---

## 📋 PROJECT INFORMATION

**Plugin Name:** Inkfinit Shipping Engine  
**Current Version:** 1.1.0  
**Status:** Production-ready  
**Developer:** Don Campan  
**Last Updated:** December 2, 2025  

**Questions to Clarify Scope:** (Check all that apply to your needs)

---

## 🚚 SHIPPING METHODS & CARRIERS

### Primary Question - What carriers do we support?

- [ ] **USPS Only** (Current state - Ground, Priority, Express)
- [ ] **USPS + FedEx** (Requires FedEx API integration)
- [ ] **USPS + UPS** (Requires UPS API integration)
- [ ] **USPS + DHL** (Requires DHL API integration)
- [ ] **All Major Carriers** (Complex, long implementation)

### Sub-Question - What USPS mail classes?

- [ ] **Ground Advantage** ✅ (Included)
- [ ] **Priority Mail** ✅ (Included)
- [ ] **Priority Mail Express** ✅ (Included)
- [ ] **First Class Mail** (For parcels under 13 oz - Included)
- [ ] **Media Mail** ✅ (Books, CDs, DVDs - Included)
- [ ] **Library Mail** ✅ (Library materials - Included)
- [ ] **Parcel Select Bound Printed Matter** (Not included)
- [ ] **USPS Cubic Rate** ✅ (Included)
- [ ] **First Class International** (Not included)
- [ ] **Priority Mail International** (Not included)
- [ ] **Priority Mail Express International** (Not included)

### International Shipping

- [ ] **USA Only** (Skip international)
- [ ] **USA + Canada** (Simplified)
- [ ] **USA + Canada + EU** (Standard)
- [ ] **All Countries** (Complex)
- [ ] **Custom zone list** (Define which countries)

### If yes to international, which zones need custom rates?

```text
[  ] Africa
[  ] Asia
[  ] Central America
[  ] Europe
[  ] Middle East
[  ] South America
[  ] Other: ________________
```

---

## 📦 BOX PACKING & PACKAGING

### Question - How should we handle box selection?

- [ ] **Automatic** ✅ (Current - system picks best box)
- [ ] **Manual** (Merchant selects for each order)
- [ ] **Hybrid** (Auto with manual override option)
- [ ] **No box packing** (Use flat rate only)

### Question - Do we handle multi-package orders?

- [ ] **Yes** ✅ (Auto-split if > 70 lbs)
- [ ] **No** (Prevent orders > 70 lbs)
- [ ] **Yes, but different limit** (Max weight: _____ lbs)

### Question - Do we track dimensional weight?

- [ ] **Yes** (Use dimensional weight for pricing)
- [ ] **No** ✅ (Use actual weight only)

### Question - What box types do we support?

- [ ] **Default boxes only** (Poly mailers, small/medium/large/XL)
- [ ] **Default + Custom boxes** ✅ (Current)
- [ ] **USPS Flat Rate Boxes** ✅ (Included)
- [ ] **Custom box inventory system** (Detailed box management)

---

## 📅 DELIVERY ESTIMATES

### Question - Should we show delivery date estimates?

- [ ] **Yes** ✅ (Show "Arrives Dec 8-11")
- [ ] **No** (Remove delivery estimate feature)
- [ ] **Optional** (Admin toggle)

### Question - If yes, which method of estimation?

- [ ] **USPS Service Standards API** ✅ (Current - real data)
- [ ] **Manual lookup table** (Admin enters estimates)
- [ ] **Both** (Prefer API, fallback to manual)

### Question - Where should estimates display?

- [ ] **Checkout shipping method list** ✅ (Recommended)
- [ ] **Cart page**
- [ ] **Product page**
- [ ] **Order confirmation email**
- [ ] **Customer account**
- [ ] **All of above**

---

## 👥 CUSTOMER-FACING FEATURES

### Question - Tracking display for customers?

- [ ] **Yes** ✅ (Show tracking number + link on order page)
- [ ] **No** (Don't show tracking)
- [ ] **Optional** (Admin toggle)

### Question - Auto-send tracking emails?

- [ ] **Yes** (Send when label generated)
- [ ] **No** (Manual email from admin)
- [ ] **Optional** (Admin toggle) ✅ (Recommended)

### Question - Pre-checkout rate estimate?

- [ ] **Yes** (Show rates before adding to cart)
- [ ] **No** (Show rates only at checkout)
- [ ] **Optional** (Merchant choice)

---

## 🏢 ADMIN FEATURES

### Question - What admin dashboard elements?

- [ ] **Configuration page** ✅ (Set rates)
- [ ] **Diagnostics dashboard** ✅ (System health)
- [ ] **Product audit** ✅ (Missing weights/dimensions)
- [ ] **Shipping history** (Track all shipments)
- [ ] **Reporting/Analytics** (Revenue by method)
- [ ] **Security audit** ✅ (API status, credentials)

### Question - Bulk operations?

- [ ] **Bulk preset assignment** ✅ (Assign preset to 100 products)
- [ ] **Bulk price update** ✅ (Change all rates by %)
- [ ] **Bulk stock update** (Change inventory)
- [ ] **Bulk label printing** (Print 50 labels at once)
- [ ] **Bulk shipping** (Ship 50 orders in batch)

### Question - USPS integration features?

- [ ] **OAuth credentials only** ✅ (Get rates)
- [ ] **Label printing** ✅ (Generate USPS labels)
- [ ] **Pickup scheduling** ✅ (Schedule carrier pickup)
- [ ] **Flat rate box ordering** (Order free boxes from USPS)
- [ ] **Account info** (View account balance/usage)

### Question - Label printing integration?

- [ ] **ShipStation** (Third-party service)
- [ ] **Shippo** (Third-party service)
- [ ] **EasyPost** (Third-party service)
- [ ] **Native USPS labels** ✅ (Built-in)
- [ ] **Multiple services**

---

## 🔐 SECURITY & COMPLIANCE

### Question - Data retention requirements?

- [ ] **Store all shipping data indefinitely**
- [ ] **Delete after 90 days** (Privacy)
- [ ] **Delete after 1 year** (GDPR compliant)
- [ ] **No tracking data stored** (Rates only)

### Question - PCI Compliance

- [ ] **Required** (We handle sensitive data)
- [ ] **Not required** (No credit card data)
- [ ] **Recommend** (Best practice)

### Question - GDPR Compliance

- [ ] **Required** (EU customers)
- [ ] **Not required** (US only)
- [ ] **Recommend** (Best practice)

### Question - Data export/deletion

- [ ] **Yes** (Allow merchants to export shipping data)
- [ ] **No** (No export feature)
- [ ] **WooCommerce native only** (Use WC data export)

---

## 🔌 INTEGRATIONS & EXTENSIONS

### Question - Third-party integrations needed?

- [ ] **None** (Standalone plugin)
- [ ] **Accounting** (QuickBooks, Xero, FreshBooks)
- [ ] **Email** (Mailchimp, SendGrid, Klaviyo)
- [ ] **Tracking** (Track & Trace, AfterShip)
- [ ] **Analytics** (Google Analytics, Mixpanel)
- [ ] **Custom integration** (Specify: ____________)

### Question - API access for partners?

- [ ] **No** (Internal use only)
- [ ] **Yes** (Expose REST API endpoints)
- [ ] **Webhooks** (Send events to external services)

### Question - Extensibility requirements?

- [ ] **Hooks/Filters only** ✅ (Current approach)
- [ ] **Custom database tables** (For complex data)
- [ ] **Custom admin pages** (Beyond current)
- [ ] **Full API** (Complete third-party access)

---

## 📊 REPORTING & ANALYTICS

### Question - What reports needed?

- [ ] **Shipping method usage** (Which methods most popular)
- [ ] **Zone revenue** (Which zones most profitable)
- [ ] **Weight distribution** (What's the average order weight)
- [ ] **Carrier comparison** (Performance metrics)
- [ ] **Customer analytics** (Shipping preferences)
- [ ] **Cost analysis** (Shipping costs vs revenue)
- [ ] **No reporting** (Basic stats only)

### Question - Report export formats?

- [ ] **CSV** (Excel compatible)
- [ ] **PDF** (Printable)
- [ ] **JSON** (API-compatible)
- [ ] **Dashboard view only** (No export)

---

## 🎨 USER INTERFACE

### Question - Admin UI style?

- [ ] **Native WordPress** ✅ (Current - no custom CSS)
- [ ] **Custom dashboard** (Requires design/CSS)
- [ ] **Minimalist** (Fewer options, simpler)
- [ ] **Advanced** (More options, more complex)

### Question - Accessibility requirements?

- [ ] **WCAG AA** ✅ (Screen readers, keyboard nav)
- [ ] **WCAG AAA** (Highest standard)
- [ ] **ADA Compliant** (US legal)
- [ ] **Basic** (No special requirements)

### Question - Mobile responsiveness?

- [ ] **Admin pages** (Tablet/mobile friendly)
- [ ] **Frontend only** (Checkout, product page)
- [ ] **Both** ✅ (Recommended)
- [ ] **Desktop only** (Not required)

---

## 🚀 PERFORMANCE & SCALE

### Question - Expected store size?

- [ ] **Small** (< 100 products, < 100 orders/month)
- [ ] **Medium** (100-1,000 products, 100-1,000 orders/month)
- [ ] **Large** (1,000-10,000 products, 1,000-10,000 orders/month)
- [ ] **Enterprise** (10,000+ products, 10,000+ orders/month)

### Question - Performance targets?

- [ ] **Checkout loads in < 2 seconds** (High performance)
- [ ] **Checkout loads in < 5 seconds** (Acceptable)
- [ ] **No specific target** (Works fine)

### Question - API call limits?

- [ ] **Unlimited** (No concerns)
- [ ] **Minimize API calls** (Use caching, batching)
- [ ] **Batch API calls** (Single API call per checkout)
- [ ] **Offline mode** (Support offline/fallback)

---

## 💰 PRICING & LICENSING

### Question - Plugin pricing model?

- [ ] **One-time purchase** (Current: $99)
- [ ] **Annual subscription** (Current: $99/year)
- [ ] **Monthly subscription** (Alternative: $9.99/mo)
- [ ] **Freemium** (Free + premium features)
- [ ] **Open source free**

### Question - Licensing?

- [ ] **GPL v3** ✅ (Current)
- [ ] **MIT** (More permissive)
- [ ] **Commercial** (Proprietary)
- [ ] **Dual license** (GPL + commercial)

### Question - Multi-site support?

- [ ] **Single site** (One license per site)
- [ ] **Multi-site** (One license for all sites)
- [ ] **Unlimited** (License as many as you want)

---

## 📝 SUPPORT & MAINTENANCE

### Question - Support level?

- [ ] **Community** (GitHub issues only)
- [ ] **Email support** (Response in 24 hours)
- [ ] **Priority support** (Response in 1 hour)
- [ ] **Dedicated support** (Assigned person)

### Question - Update frequency?

- [ ] **Bug fixes only** (As needed)
- [ ] **Monthly updates** (Features + fixes)
- [ ] **Quarterly updates** (Seasonal)
- [ ] **Continuous** (Weekly or more)

### Question - Backward compatibility?

- [ ] **Always compatible** (No breaking changes)
- [ ] **Deprecation warnings** (Warn before breaking)
- [ ] **Major versions only** (v1, v2, etc)

---

## 🎯 FINAL SUMMARY

### Based on your selections above, here's the scope

### ✅ INCLUDED (Will definitely have)

- [ ] USPS shipping integration (Ground, Priority, Express)
- [ ] Smart box packing & auto-selection
- [ ] Zone-based rate multipliers
- [ ] Product presets system
- [ ] Delivery date estimates
- [ ] Native WordPress admin UI
- [ ] Security hardening (520+ lines)
- [ ] Tracking display for customers
- [ ] Basic diagnostics dashboard
- [ ] USPS label printing integration

### ⚠️ OPTIONAL (May need)

- Multiple carriers (FedEx, UPS, DHL)
- Advanced reporting
- Bulk operations
- Pickup scheduling
- Flat rate box ordering

### ❌ OUT OF SCOPE (Not included)

- Custom carrier integrations
- Real-time inventory sync
- Machine learning price optimization
- International customs forms
- Multi-warehouse management

---

## 📞 NEXT STEPS

1. **Complete this form** - Check all that apply
2. **Review marked items** - Clarify any ambiguities
3. **Sign off on scope** - Project owner approves
4. **Share with team** - Developers know what to build
5. **Reference during development** - Keep this form visible

---

## 🗣️ QUESTIONS FOR PROJECT OWNER

### Ask these before starting

1. **What is the primary use case?**
   - Merch store? Creator economy? General ecommerce?

2. **What's the merchant skill level?**
   - Technical? Non-technical? Mix?

3. **What's the timeline?**
   - MVP in 2 weeks? Full product in 3 months?

4. **What's the success metric?**
   - X hours saved per month? Y% conversion increase?

5. **What's the budget?**
   - For development? For hosting? For support?

6. **What's the competitive landscape?**
   - Who else does this? What do they charge?

7. **What's the long-term vision?**
   - Sell on WordPress.org? GitHub? Own website?

8. **What's the risk tolerance?**
   - Can it break checkout? Break orders? Lose data?

---

**Document Version:** 1.0  
**Last Updated:** December 2, 2025  
**Status:** Ready for scope clarification

---

### END OF SCOPE CLARIFICATION FORM

Use this to establish clear requirements. Never code without answering these questions.
