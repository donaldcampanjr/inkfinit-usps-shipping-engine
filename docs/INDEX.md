# Inkfinit USPS Shipping Engine - Documentation Hub

> Professional USPS shipping rates, labels, and tracking for WooCommerce.

---

## Quick Navigation

| Document | Audience | Description |
|----------|----------|-------------|
| [README.md](../README.md) | Everyone | Overview, features, installation |
| [USER-GUIDE.md](../USER-GUIDE.md) | Store Owners | How to use the plugin |
| [CHANGELOG.md](../CHANGELOG.md) | Everyone | Version history |

### Architecture Documentation

| Document | Description |
|----------|-------------|
| [SYSTEM-ARCHITECTURE.md](architecture/SYSTEM-ARCHITECTURE.md) | Complete system design |
| [FILE-STRUCTURE.md](architecture/FILE-STRUCTURE.md) | File organization and load order |
| [QUICK-REFERENCE.md](reference/QUICK-REFERENCE.md) | Quick reference card for developers |

### License Server

| Document | Description |
|----------|-------------|
| [LICENSE-SERVER-SETUP.md](LICENSE-SERVER-SETUP.md) | License server deployment guide |
| [License Server README](../license-server/README.md) | Integration reference |

---

## For Store Owners

### Getting Started

1. **Install the plugin** from WordPress.org or upload manually
2. **Activate** in Plugins menu
3. **Get USPS API credentials** from [USPS Business Gateway](https://gateway.usps.com)
4. **Configure** in Inkfinit Shipping → USPS API
5. **Start shipping!**

### Feature Tiers

| Feature | Free | Pro | Enterprise |
|---------|:----:|:---:|:----------:|
| Rate Calculator | ✅ | ✅ | ✅ |
| Live Checkout Rates | ❌ | ✅ | ✅ |
| Label Printing | ❌ | ✅ | ✅ |
| Customer Tracking | ❌ | ✅ | ✅ |
| Shipping Presets | ❌ | ✅ | ✅ |
| Bulk Variation Manager | ❌ | ✅ | ✅ |
| Diagnostics | ❌ | ✅ | ✅ |
| White-Label Branding | ❌ | ❌ | ✅ |
| Bulk License Import | ❌ | ❌ | ✅ |

### Support

- **Free Users:** [WordPress.org Forums](https://wordpress.org/support/plugin/inkfinit-usps-shipping-engine)
- **Pro/Enterprise:** support@inkfinit.pro

---

## For Developers

### AI Developers

See [.github/copilot-instructions.md](../.github/copilot-instructions.md) for AI-specific guidance.

See [.github/AI-AUDIT-GUIDE.md](../.github/AI-AUDIT-GUIDE.md) for security audit checklist.

### Architecture

- **Main Plugin:** `plugin.php` - Entry point, hooks, admin menu
- **Includes:** `includes/` - All PHP functionality
- **Assets:** `assets/` - CSS and JavaScript
- **License Server:** `license-server/` - Separate plugin for inkfinit.pro

### Key Files

| File | Purpose |
|------|---------|
| `includes/license.php` | License validation (client) |
| `includes/usps-api.php` | USPS OAuth v3 integration |
| `includes/class-shipping-method.php` | WooCommerce shipping method |
| `includes/label-printing.php` | Label generation |
| `includes/customer-tracking-display.php` | Customer-facing tracking |

### License Key Format

```
IUSE-{TIER}-{TIMESTAMP}-{RANDOM}-{ORDER_HEX}

Examples:
- IUSE-PRO-675789AB-A1B2C3-F1
- IUSE-ENT-675789AB-X9Y8Z7-2A
```

### REST API Endpoints

**License Validation:**

```
POST https://inkfinit.pro/wp-json/inkfinit/v1/license/validate
Body: { "license_key": "IUSE-PRO-...", "site_url": "https://customer.com" }
```

---

## Architecture Docs

- [SYSTEM-ARCHITECTURE.md](architecture/SYSTEM-ARCHITECTURE.md) - Technical architecture
- [FILE-STRUCTURE.md](architecture/FILE-STRUCTURE.md) - Directory structure

---

## Deployment

See [TODO-BEFORE-LAUNCH.md](../TODO-BEFORE-LAUNCH.md) for pre-launch checklist.

---

## License

- Plugin: [GPL v3](../LICENSE)
- Commercial Terms: [LICENSE-COMMERCIAL.md](../LICENSE-COMMERCIAL.md)

