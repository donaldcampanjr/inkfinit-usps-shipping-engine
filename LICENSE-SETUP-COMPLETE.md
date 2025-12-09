# Inkfinit USPS Shipping Engine - Complete Setup Guide

## The Big Picture

You now have **two separate WordPress installations**:

1. **Your Sales Site** (`https://inkfinit.pro`)
   - Where customers **buy** the Pro license
   - Runs the License Server plugin
   - Auto-generates keys on purchase

2. **Customer Sites** (their individual WooCommerce stores)
   - Where customers **install** the shipping plugin
   - Paste the license key to unlock Pro features
   - Optionally validate with your sales site

---

## Phase 1: Set Up License Server (on inkfinit.pro)

### Step 1.1: Upload License Server Plugin

1. On your computer, find the `license-server` folder
2. Zip it (right-click → Compress)
3. Go to `https://inkfinit.pro/wp-admin`
4. Click **Plugins → Add New → Upload Plugin**
5. Choose the zipped license-server file
6. Click **Install Now**
7. Click **Activate Plugin**

**You should now see "License Keys" under the WooCommerce menu.**

### Step 1.2: Create the Pro License Product

1. Go to **WooCommerce → Products → Add New**
2. Fill in these fields:

   | Field | Value |
   |-------|-------|
   | **Product name** | Inkfinit USPS Shipping Engine Pro License |
   | **Price** | 129 |
   | **SKU** | IUSE-PRO-1Y |

3. In **Description**, write something like:

   > Unlock all Pro features for one year:
   > - Live USPS rates at checkout
   > - Label printing and generation
   > - Package tracking with status
   > - Shipping presets and bulk manager
   > - Diagnostics dashboard
   > - Priority support
   > 
   > One license key per site. Renew annually for continued support.

4. Click **Publish**

That's it. **Your sales site is done.**

When a customer buys this product:
- A license key is automatically generated
- Emailed to them automatically
- Stored in your License Keys table for admin viewing

---

## Phase 2: Install Shipping Plugin (on Customer Sites)

### For Each Customer:

1. **They download** the shipping plugin zip from you
2. **They install** it:
   - WP Admin → Plugins → Add New → Upload
   - Upload the shipping engine zip
   - Activate

3. **They enter USPS API credentials** under:
   - Inkfinit Shipping → USPS API
   - Enter Consumer Key, Consumer Secret, Origin ZIP

4. **They paste the license key** (you emailed it to them):
   - Still on Inkfinit Shipping → USPS API
   - Scroll to "Advanced" / "Licensing"
   - **License Key**: Paste the key you emailed
   - **License Server URL**: `https://inkfinit.pro/wp-json/inkfinit/v1/license/validate`
   - Click Save

5. **Pro unlocks automatically**

The shipping plugin will now call your license server to validate the key. If valid, Pro features are active.

---

## What Happens Automatically

### When Customer Buys (on Your Sales Site)

```
Order placed
    ↓
Payment processed
    ↓
Order status → Processing / Completed
    ↓
License Server plugin:
  - Generates unique key (e.g., IUSE-67ABC123-DEF456-1234)
  - Stores in database with customer email & name
  - Emails key to customer
  - Adds note to order
```

### When Customer Uses Key (on Their Site)

```
Customer enters key in License Key field
    ↓
Plugin saves key to their WordPress database
    ↓
Plugin calls your license server periodically:
  POST https://inkfinit.pro/wp-json/inkfinit/v1/license/validate
  JSON: { "license_key": "IUSE-..." }
    ↓
Your server responds:
  { "valid": true, ... } 
    OR
  { "valid": false, "reason": "revoked" }
    ↓
Customer site unlocks Pro or reverts to Free
```

If your server is unreachable, the plugin **stays Pro** (doesn't break their store).

---

## Admin Tasks You Can Do

### View All License Keys

1. Go to **WooCommerce → License Keys**
2. See all keys, customer names, emails, order IDs, status, validation counts

### Revoke a License (if customer abuses or requests refund)

1. Find the key in the License Keys table
2. Click **Revoke**
3. Next time that customer's site validates, it gets `"valid": false`
4. Pro features lock; they fall back to Free edition

### Reactivate a Revoked License

1. Find the key
2. Click **Activate**
3. Pro unlocks again on their next validation check

### Monitor Usage

Each row shows:
- **Validations**: How many times that site checked the key
- **Last Validated**: When they last connected
- Helps you see which customers are actively using the plugin

---

## Real-World Example

### Customer: John's Coffee Shop

1. **John finds your plugin** on WordPress.org or your site
2. **He downloads** the free edition and tests it
3. **He's impressed**, so he buys a Pro license from `https://inkfinit.pro`
4. **An email arrives** with:
   ```
   License Key: IUSE-67ABC123-DEF456-1A2B
   
   Setup instructions:
   1. Download the plugin
   2. Paste this key into License Key field
   3. Set License Server URL to https://inkfinit.pro/wp-json/inkfinit/v1/license/validate
   ```
5. **John installs** the shipping plugin on his site
6. **John enters** his USPS credentials
7. **John pastes** the license key
8. **Pro features unlock** automatically
9. **John's customers** now see live USPS rates, label printing, tracking, etc.

**You didn't do anything manual. Everything auto-generated.**

---

## Troubleshooting

### "Email didn't arrive"

Check:
1. Is WordPress sending email? (Test with a test order)
2. Did the order reach "Completed" or "Processing" status?
3. Check WooCommerce → License Keys to see if key was at least generated
4. Check your SMTP/mail logs

### "Key says invalid but I just bought it"

1. Go to WooCommerce → License Keys
2. Confirm the key is there and status is "active"
3. Have the customer check their License Server URL field:
   - Should be: `https://inkfinit.pro/wp-json/inkfinit/v1/license/validate`
4. Have them clear their WordPress cache if using caching plugin
5. Wait a few moments for first validation

### "Customer wants a refund"

1. Go to WooCommerce → License Keys
2. Find their key
3. Click **Revoke**
4. Refund the order normally
5. Next validation = Pro locks, they get Free edition

---

## Your License Server REST API

**Endpoint:** `POST https://inkfinit.pro/wp-json/inkfinit/v1/license/validate`

**Request:**
```json
{
  "license_key": "IUSE-67ABC123-DEF456-1A2B",
  "site_url": "https://john-coffee.com",
  "plugin": "inkfinit-shipping-engine",
  "version": "1.2.0"
}
```

**Valid Response:**
```json
{
  "valid": true,
  "license_key": "IUSE-67ABC123-DEF456-1A2B",
  "order_id": 1234,
  "customer": "John Doe",
  "expires_at": "2026-12-03",
  "validations": 42
}
```

**Invalid Response:**
```json
{
  "valid": false,
  "reason": "revoked"
}
```

---

## Timeline

- **Right now**: Sales site ready, license server installed, Pro product created
- **Customer buys**: Key auto-generates + emails (instant)
- **Customer installs**: Plugin + pastes key (5 minutes)
- **Pro unlocks**: Automatic on save (instant)

**Zero manual intervention per customer after setup.**

---

## Next Steps

1. ✅ Confirm License Server plugin is active at inkfinit.pro
2. ✅ Confirm Pro License product exists and is published
3. ✅ Test: Make a test purchase from your own product
4. ✅ Check that you received the auto-generated key via email
5. ✅ Install shipping plugin on a test site, paste the key, confirm Pro unlocks
6. Go to GitHub and push your shipping plugin code
7. Launch your first marketing push for the free edition

---

## Support & Questions

If anything doesn't work:
- Check WooCommerce → License Keys for generated keys
- Check WordPress error logs for PHP errors
- Test the REST API directly with cURL if needed
- Review the README.md in the license-server folder for more details

**You're done with setup. Everything else is automatic.**
