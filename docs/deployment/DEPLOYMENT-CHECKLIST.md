# Deployment Checklist

<!-- markdownlint-disable MD013 -->

Step-by-step instructions for deploying the Inkfinit Shipping Engine to production.

---

## Pre-Deployment (Do This First)

- [ ] Read `READY-TO-DEPLOY.md` - Understand what changed
- [ ] Review `FILES-CHANGED.md` - Know exactly which files were modified
- [ ] Backup current production `includes/` and `assets/` folders
- [ ] Backup WordPress database
- [ ] Verify you have all 6 files ready to upload

---

## File Upload

### Modified Files (4)

Upload these files to your server:

```texttext
plugin.php
includes/product-preset-picker.php
includes/admin-ui-helpers.php
assets/admin-style.css
```

**Upload method:** FTP, SFTP, SSH, or WordPress File Manager

### Path on server

```texttext
/wp-content/plugins/wtc-shipping-core-design/
```

### New Files (2)

Create and upload these new files:

```texttext
includes/admin-presets-wc-integration.php
assets/preset-picker.js
```

**Same path:** `/wp-content/plugins/wtc-shipping-core-design/`

---

## Post-Upload Steps

1. **Clear WordPress cache** (if using caching plugin)
   - Go to Settings → Caching Plugin (e.g., WP Super Cache)
   - Click "Delete Cache"

2. **Clear browser cache**
   - Tell users to do Ctrl+Shift+R (hard refresh) in admin

3. **Reload plugin**
   - Go to Plugins page
   - Deactivate Inkfinit Shipping Engine
   - Reactivate Inkfinit Shipping Engine

4. **Verify no errors**
   - Go to Dashboard → check for error messages
   - Go to WP Admin → check logs for PHP errors

---

## Verification

Run the tests in `VERIFICATION-TESTS.md`:

- [ ] Test 1: Product Preset Auto-Fill
- [ ] Test 2: Presets in WC Settings
- [ ] Test 3: Footer Display
- [ ] Test 4: Section Titles & Layout

**If any test fails:** See "Rollback" section below

---

## Rollback (If Something Goes Wrong)

1. **Delete new files:**

   ```
   rm includes/admin-presets-wc-integration.php
   rm assets/preset-picker.js
   ```

2. **Restore original files from backup:**

   ```
   Restore from your backup:
   - plugin.php
   - includes/product-preset-picker.php
   - includes/admin-ui-helpers.php
   - assets/admin-style.css
   ```

3. **Deactivate & reactivate plugin**

4. **Verify old version works**

---

## Success Criteria

✅ All tests in VERIFICATION-TESTS.md pass
✅ No PHP errors in error logs
✅ Presets auto-fill when selected on products
✅ Presets visible in WC Shipping settings
✅ Footer only shows on dashboard
✅ Section titles properly aligned

---

## Post-Deployment

1. **Document deployment:**
   - Date deployed
   - Deployed by: [Your name]
   - Method: FTP/SFTP/SSH/etc
   - Verification: PASSED/FAILED

2. **Notify team:**
   - Preset auto-fill now active
   - Presets visible in WooCommerce settings
   - Admin pages have improved layout

3. **Monitor:**
   - Watch error logs for 24 hours
   - Check product creation (presets)
   - Check shipping calculations

---

## Troubleshooting

**Problem:** New files don't upload

- **Solution:** Check file permissions (755 for files, 755 for directories)
- Verify FTP path is correct
- Try uploading via WordPress File Manager instead

**Problem:** Tests fail after upload

- **Solution:** Go to Plugins → Deactivate → Reactivate Inkfinit Shipping
- Clear browser cache (Ctrl+Shift+R)
- Check error logs at wp-content/debug.log

**Problem:** Old code still running

- **Solution:** Clear object cache if using Redis/Memcached
- Restart PHP-FPM if running PHP-FPM
- Wait 5 minutes for WordPress to reload files

**Problem:** Preset auto-fill not working

- **Solution:** Verify `assets/preset-picker.js` was uploaded
- Check JavaScript console for errors (F12)
- Verify `product-preset-picker.php` was uploaded

---

## Support

If deployment fails:

1. Check `docs/deployment/VERIFICATION-TESTS.md` for specific tests
2. Review `docs/guides/CODE-FLOW-VERIFICATION.md` to understand code flow
3. Check that all 6 files were uploaded correctly

---

### Good luck! 🚀
