# ✅ Verification Tests

<!-- markdownlint-disable MD013 -->

After deployment, run these 4 tests to verify everything works.

---

## Test 1 - Product Preset Auto-Fill ✅

**What we're testing:** Presets auto-fill weight/dimensions on products

### Steps

1. Go to WordPress Admin
2. Click **Products**
3. Edit any product
4. Scroll down to find **"Shipping Preset"** tab
5. Open the dropdown and **select a preset** (e.g., "Small Shirt")

### Expected Result

- [ ] Dropdown shows list of presets
- [ ] Success alert appears: "✓ Preset applied & saved instantly"
- [ ] Page reloads after 1-2 seconds
- [ ] Product weight field shows value (in admin if you check)
- [ ] Dimensions fields populated (length, width, height)

### If It Fails

- Verify `assets/preset-picker.js` was uploaded
- Check browser console (F12 → Console) for JavaScript errors
- Verify `includes/product-preset-picker.php` was uploaded
- Try: Deactivate plugin → Reactivate → Hard refresh browser (Ctrl+Shift+R)

---

## Test 2 - Presets in WooCommerce Settings ✅

**What we're testing:** Presets visible in native WooCommerce Shipping interface

### Steps

1. Go to WordPress Admin
2. Click **WooCommerce**
3. Click **Settings**
4. Click **Shipping** tab
5. Click on any **Shipping Zone** (e.g., "United States")
6. Click **"Add shipping method"**
7. In the dropdown, you should see **"Inkfinit Shipping"**

### Expected Result

- [ ] "Inkfinit Shipping" is an option in the dropdown
- [ ] When selected, it adds the method to your zone
- [ ] Clicking "Edit" on the method shows preset options

### If It Fails

- Verify `includes/admin-presets-wc-integration.php` was uploaded
- Check `plugin.php` to ensure the new file is included
- Deactivate/reactivate plugin

---

## Test 3 - Admin UI Styling ✅

**What we're testing:** Minor UI improvements in admin settings

### Steps

1. Go to WordPress Admin
2. Click **WooCommerce**
3. Click **Settings**
4. Click **Shipping** tab
5. Click **"Inkfinit Shipping Engine"** at the top

### Expected Result

- [ ] The page title should be **"Inkfinit Shipping Engine"** (not "Shipping")
- [ ] The save button should be blue (not default gray)
- [ ] Section tabs (Rates, Boxes, etc.) should have a bottom border

### If It Fails

- Verify `assets/admin-style.css` was uploaded
- Clear browser cache (hard refresh)
- Check if another plugin is overriding the styles

---

## Test 4 - Plugin Header Update ✅

**What we're testing:** Plugin version and author info is updated

### Steps

1. Go to WordPress Admin
2. Click **Plugins**
3. Find **"Inkfinit Shipping Core Design"** in the list

### Expected Result

- [ ] Version number is **2.0.0**
- [ ] Author is **WTC, LLC**
- [ ] "View details" link shows the updated `readme.txt` info

### If It Fails

- Verify `plugin.php` was uploaded with the new header
- Check `readme.txt` to ensure it was also updated
- WordPress caches plugin data, so it might take a few minutes to show

---

## ✅ Expected Outcomes

- **All 4 tests pass** without any errors.
- The new preset picker works seamlessly.
- Admin UI is slightly improved.
- Plugin version and details are correct.
- No functionality is broken.

---

## ❌ Not In Scope

- **Frontend display:** No changes were made to the shipping calculator on the cart/checkout pages.
- **Rate calculation:** The core shipping rate logic was not modified.
- **Label printing:** No changes to label generation.
- **Box packing:** The packing algorithm remains the same.
- **Anything else not mentioned** in the 4 tests above.
