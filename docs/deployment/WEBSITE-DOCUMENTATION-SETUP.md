# Website & Documentation Setup for Inkfinit Shipping

## Overview

This guide covers setting up a professional website and documentation for Inkfinit Shipping that supports both free and commercial tiers, built entirely on GitHub.

---

## Part 1: GitHub Pages for Free Tier

### Setup

Since you're using GitHub for the free tier, you can host your documentation and landing page at:

- **Main Site:** `https://donaldcampanjr.github.io/inkfinit-shipping`
- **Docs:** `https://donaldcampanjr.github.io/inkfinit-shipping/docs`
- **WordPress.org:** `https://wordpress.org/plugins/inkfinit-shipping`

### Repository Configuration

1. **Create `gh-pages` Branch**

```bash
cd inkfinit-shipping
git checkout --orphan gh-pages
```

2. **Directory Structure**

```
gh-pages branch:
├── index.html                 # Landing page
├── docs/                      # Documentation
│   ├── getting-started/
│   ├── guides/
│   ├── reference/
│   └── faq/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── _config.yml               # Jekyll config
```

3. **Enable GitHub Pages**

In repository settings:
- Go to: Settings → Pages
- Source: Deploy from a branch
- Branch: `gh-pages` / `/(root)`
- Save

---

## Part 2: Landing Page Template

### Create `index.html` in `gh-pages` branch

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkfinit Shipping - Professional USPS Shipping for WooCommerce</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Real-time USPS shipping rates for WooCommerce. Free on WordPress.org, Pro/Premium/Enterprise tiers available.">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <strong>Inkfinit Shipping</strong>
            </div>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="docs">Documentation</a></li>
                <li><a href="https://wordpress.org/plugins/inkfinit-shipping/" class="btn-primary">Get Plugin</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>Professional USPS Shipping for WooCommerce</h1>
            <p class="lead">Real-time rates, smart presets, tracking integration—and your store keeps selling.</p>
            <div class="hero-buttons">
                <a href="https://wordpress.org/plugins/inkfinit-shipping/" class="btn btn-primary btn-lg">Free on WordPress.org</a>
                <a href="#pricing" class="btn btn-secondary btn-lg">Upgrade to Pro</a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="features">
        <div class="container">
            <h2>Everything You Need</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3>⚡ Live USPS Rates</h3>
                    <p>Real-time shipping costs from USPS OAuth v3 API. Always accurate.</p>
                </div>
                
                <div class="feature-card">
                    <h3>📦 Smart Presets</h3>
                    <p>Pre-configured product templates. Save time, eliminate manual entry.</p>
                </div>
                
                <div class="feature-card">
                    <h3>🚚 Tracking Integration</h3>
                    <p>Add USPS tracking numbers. Customers get real-time delivery updates.</p>
                </div>
                
                <div class="feature-card">
                    <h3>📊 Admin Dashboard</h3>
                    <p>System diagnostics, API testing, product audits. Everything in one place.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="pricing">
        <div class="container">
            <h2>Simple, Transparent Pricing</h2>
            
            <div class="pricing-grid">
                <div class="pricing-card">
                    <h3>Free</h3>
                    <p class="price">$0<span>/month</span></p>
                    <ul>
                        <li>✅ Core USPS shipping</li>
                        <li>✅ Basic presets</li>
                        <li>✅ Live rates & tracking</li>
                        <li>✅ Community support</li>
                        <li>✅ Unlimited sites</li>
                    </ul>
                    <a href="https://wordpress.org/plugins/inkfinit-shipping/" class="btn btn-outline">Get Free</a>
                </div>
                
                <div class="pricing-card featured">
                    <div class="badge">Popular</div>
                    <h3>Pro</h3>
                    <p class="price">$29<span>/month</span></p>
                    <ul>
                        <li>✅ Everything in Free</li>
                        <li>✅ Bulk Variation Manager</li>
                        <li>✅ Advanced rate rules</li>
                        <li>✅ Email support (24-48h)</li>
                        <li>✅ 5 sites</li>
                    </ul>
                    <a href="https://inkfinit.pro/purchase?tier=pro" class="btn btn-primary">Upgrade to Pro</a>
                </div>
                
                <div class="pricing-card">
                    <h3>Premium</h3>
                    <p class="price">$99<span>/month</span></p>
                    <ul>
                        <li>✅ Everything in Pro</li>
                        <li>✅ Label printing</li>
                        <li>✅ White-label options</li>
                        <li>✅ API access</li>
                        <li>✅ Unlimited sites</li>
                    </ul>
                    <a href="https://inkfinit.pro/purchase?tier=premium" class="btn btn-outline">Upgrade to Premium</a>
                </div>
                
                <div class="pricing-card">
                    <h3>Enterprise</h3>
                    <p class="price">Custom</p>
                    <ul>
                        <li>✅ Everything in Premium</li>
                        <li>✅ Dedicated account manager</li>
                        <li>✅ 24/7 phone support</li>
                        <li>✅ Custom development</li>
                        <li>✅ SLA guarantees</li>
                    </ul>
                    <a href="https://inkfinit.pro/contact" class="btn btn-outline">Contact Sales</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Simplify Shipping?</h2>
            <p>Get started free, upgrade anytime.</p>
            <a href="https://wordpress.org/plugins/inkfinit-shipping/" class="btn btn-primary btn-lg">Install Free Plugin Now</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2025 Inkfinit LLC. All rights reserved.</p>
            <p>
                <a href="docs">Documentation</a> |
                <a href="https://github.com/donaldcampanjr/inkfinit-shipping">GitHub</a> |
                <a href="https://wordpress.org/plugins/inkfinit-shipping/">WordPress.org</a>
            </p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
```

### Create `assets/css/style.css`

```css
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #0073aa;
    --secondary: #23282d;
    --light: #f5f5f5;
    --text: #333;
    --border: #ddd;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: var(--text);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Navigation */
.navbar {
    background: white;
    border-bottom: 1px solid var(--border);
    padding: 15px 0;
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 18px;
    font-weight: 600;
}

.nav-links {
    display: flex;
    list-style: none;
    gap: 30px;
    align-items: center;
}

.nav-links a {
    text-decoration: none;
    color: var(--text);
    transition: color 0.3s;
}

.nav-links a:hover {
    color: var(--primary);
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    border: 1px solid transparent;
    transition: all 0.3s;
    cursor: pointer;
    font-weight: 500;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #005a87;
}

.btn-secondary {
    background: var(--secondary);
    color: white;
}

.btn-secondary:hover {
    background: #1a1e23;
}

.btn-outline {
    border: 1px solid var(--primary);
    color: var(--primary);
    background: white;
}

.btn-outline:hover {
    background: var(--light);
}

.btn-lg {
    padding: 15px 30px;
    font-size: 16px;
}

/* Hero */
.hero {
    background: linear-gradient(135deg, var(--primary) 0%, #0088cc 100%);
    color: white;
    padding: 80px 20px;
    text-align: center;
}

.hero h1 {
    font-size: 48px;
    margin-bottom: 20px;
    line-height: 1.2;
}

.hero .lead {
    font-size: 20px;
    margin-bottom: 40px;
    opacity: 0.95;
}

.hero-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

/* Features */
.features {
    padding: 80px 20px;
    background: white;
}

.features h2 {
    font-size: 36px;
    text-align: center;
    margin-bottom: 50px;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.feature-card {
    padding: 30px;
    background: var(--light);
    border-radius: 8px;
    text-align: center;
}

.feature-card h3 {
    font-size: 20px;
    margin-bottom: 15px;
}

/* Pricing */
.pricing {
    padding: 80px 20px;
    background: var(--light);
}

.pricing h2 {
    font-size: 36px;
    text-align: center;
    margin-bottom: 50px;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.pricing-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    border: 1px solid var(--border);
    text-align: center;
    position: relative;
}

.pricing-card.featured {
    border-color: var(--primary);
    box-shadow: 0 4px 20px rgba(0, 115, 170, 0.1);
    transform: scale(1.05);
}

.pricing-card .badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--primary);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.pricing-card h3 {
    font-size: 22px;
    margin-bottom: 15px;
}

.pricing-card .price {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--primary);
}

.pricing-card .price span {
    font-size: 14px;
    color: #999;
}

.pricing-card ul {
    list-style: none;
    margin-bottom: 30px;
    text-align: left;
}

.pricing-card li {
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}

.pricing-card li:last-child {
    border: none;
}

/* CTA */
.cta {
    background: var(--primary);
    color: white;
    padding: 80px 20px;
    text-align: center;
}

.cta h2 {
    font-size: 36px;
    margin-bottom: 15px;
}

.cta p {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.95;
}

/* Footer */
footer {
    background: var(--secondary);
    color: white;
    padding: 40px 20px;
    text-align: center;
}

footer a {
    color: white;
    text-decoration: none;
    margin: 0 15px;
}

footer a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 768px) {
    .hero h1 {
        font-size: 32px;
    }

    .hero .lead {
        font-size: 16px;
    }

    .nav-links {
        gap: 15px;
        flex-direction: column;
    }

    .pricing-card.featured {
        transform: scale(1);
    }
}
```

---

## Part 3: Documentation Hub

### Create Documentation Directory

```
docs/
├── index.md                    # Doc homepage
├── getting-started.md          # Quick start
├── features.md                 # Feature list
├── faq.md                      # FAQs
├── installation.md             # Install guide
├── configuration.md            # Setup guide
├── troubleshooting.md          # Common issues
└── support.md                  # Support info
```

### Example: `docs/index.md`

```markdown
# Inkfinit Shipping Documentation

## Getting Started

- [Installation](installation.md)
- [Configuration](configuration.md)
- [Features](features.md)

## Using the Plugin

- [Shipping Methods](shipping-methods.md)
- [Product Presets](product-presets.md)
- [Order Management](order-management.md)

## Support

- [FAQ](faq.md)
- [Troubleshooting](troubleshooting.md)
- [Contact Support](support.md)

## Upgrade Information

- [Free vs Pro](free-vs-pro.md)
- [Pricing Plans](pricing.md)
- [Upgrade Guide](upgrade-guide.md)
```

---

## Part 4: Commercial Tier Website

### Separate Domain/Subdomain

For Pro/Premium/Enterprise, use:

```
Option A: https://pro.inkfinit.pro
Option B: https://inkfinit.pro/pro
Option C: https://cart.boundlessink.com (for licensing)
```

### Required Pages

```
/pro/
├── index.html          # Pro tier landing
├── pricing.html        # Tier comparison
├── purchase.html       # License purchase
├── account.html        # Customer dashboard (authenticated)
├── license-manage.html # License activation
├── download.html       # License downloads
└── support.html        # Priority support
```

### License Management Dashboard

Build a simple PHP/Node dashboard:

```
Features:
- Display purchased licenses
- Generate license keys
- Download plugin versions
- Check license status
- Renew/upgrade licenses
- Manage sites per license
- View support tickets
```

---

## Part 5: WordPress.org Plugin Page

Your plugin page will auto-generate from `readme.txt`:

```
https://wordpress.org/plugins/inkfinit-shipping/

Sections Auto-Generated:
✓ Plugin name & description (from readme.txt)
✓ Screenshots (upload in directory)
✓ Installation instructions
✓ FAQ
✓ Reviews & ratings
✓ Changelog
✓ Support forum
```

### How to Maximize Your Plugin Page

1. **Add Screenshots**
   - 4 screenshots maximum
   - 1200×900px each
   - PNG or JPG format
   - Name: `screenshot-1.png`, etc.
   - Place in plugin root

2. **Create Compelling Copy**
   - Write for non-technical users
   - Lead with benefits, not features
   - Include clear CTA to upgrade

3. **Monitor Reviews**
   - Respond to all reviews
   - Address issues quickly
   - Thank positive reviewers

---

## Part 6: Linking Everything Together

### From WordPress.org Plugin Page

In `readme.txt`:

```
== Support ==

For Free Tier support:
- Forums: https://wordpress.org/support/plugin/inkfinit-shipping/
- Docs: https://donaldcampanjr.github.io/inkfinit-shipping/

Upgrade to Pro/Premium/Enterprise:
- Learn more: https://inkfinit.pro/pro
- Pricing: https://inkfinit.pro/pricing
```

### From GitHub README

```markdown
## Free Tier

Available on WordPress.org:
https://wordpress.org/plugins/inkfinit-shipping/

## Documentation

Full docs: https://donaldcampanjr.github.io/inkfinit-shipping/

## Commercial Tiers

For Pro/Premium/Enterprise:
- Website: https://inkfinit.pro
- Pricing: https://inkfinit.pro/pricing
```

### From GitHub Pages Landing

```html
<a href="https://wordpress.org/plugins/inkfinit-shipping/">
  Free on WordPress.org
</a>

<a href="https://inkfinit.pro/pricing">
  Upgrade to Pro
</a>
```

---

## Part 7: SEO & Marketing

### Meta Tags for GitHub Pages

```html
<meta name="description" content="Real-time USPS shipping rates for WooCommerce. Free on WordPress.org, Pro/Premium/Enterprise tiers.">
<meta name="keywords" content="shipping, WooCommerce, USPS, plugin">
<meta name="author" content="Inkfinit LLC">
<meta property="og:title" content="Inkfinit Shipping - Professional USPS Shipping for WooCommerce">
<meta property="og:description" content="Real-time rates, smart presets, tracking integration">
<meta property="og:type" content="website">
<meta property="og:url" content="https://donaldcampanjr.github.io/inkfinit-shipping">
```

### XML Sitemap

Create `sitemap.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://donaldcampanjr.github.io/inkfinit-shipping/</loc>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://donaldcampanjr.github.io/inkfinit-shipping/docs/</loc>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://wordpress.org/plugins/inkfinit-shipping/</loc>
    <priority>0.9</priority>
  </url>
</urlset>
```

---

## Deployment Checklist

- [ ] Create `gh-pages` branch in free tier repo
- [ ] Add `index.html` landing page
- [ ] Add CSS and JavaScript files
- [ ] Configure GitHub Pages in settings
- [ ] Test landing page loads correctly
- [ ] Create `/docs` directory with documentation
- [ ] Add pricing/comparison pages
- [ ] Setup custom domain (optional)
- [ ] Create WordPress.org plugin page
- [ ] Upload plugin screenshots
- [ ] Link documentation in readme
- [ ] Setup commercial tier pages
- [ ] Create license dashboard (future)

---

## Example Project Structure Summary

```
inkfinit-shipping/                  # Free tier (public, GPL)
├── main branch                           # Plugin code
│   ├── plugin.php
│   ├── includes/
│   ├── assets/
│   └── docs/
└── gh-pages branch                       # Website
    ├── index.html                        # Landing
    ├── docs/                             # Documentation
    └── assets/                           # CSS/JS

inkfinit-shipping-pro/              # Pro tier (private, commercial)
├── main branch                           # Code
│   ├── plugin.php (with license check)
│   └── pro-features/
└── gh-pages branch
    └── (not used, pro site on separate domain)

boundlessink.com                         # Commercial website
├── / → landing page
├── /pricing → all tier comparison
├── /pro → pro tier info
├── /pro/purchase → license checkout
└── /pro/account → customer dashboard (auth required)
```

---

**Last Updated:** 2025-12-03  
**Author:** Inkfinit LLC

**Next Steps:**
1. Create GitHub Pages site with landing page
2. Setup `/docs` directory
3. Create WordPress.org plugin entry
4. Link all properties together
5. Plan commercial tier website/platform
