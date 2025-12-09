# Shipping Engine - User Guide

<!-- markdownlint-disable MD013 -->

### Display real-time USPS rates at checkout with zero configuration. Automatic rate calculation, instant delivery estimates, and seamless tracking integration.

---

## What Does This Plugin Do?

Automatically calculates shipping costs for your merch based on weight and where customers live. You set up products once, and the plugin handles everything at checkout.

---

## Quick Start (3 Steps)

### Step 1 - Configure Base Shipping Costs (One Time)

1. Go to **WordPress Admin → Inkfinit Shipping → Setup & Configuration**
2. Set your base costs for each shipping method:
   - **First Class Mail**: Cheapest, up to 13 oz
   - **Ground Shipping**: Standard speed, heavier items
   - **Priority Mail**: Faster delivery
   - **Express Mail**: Next-day priority
3. Click **Save Configuration**

### Example

- First Class: $4.50 base + $0.15 per oz
- Ground: $8.00 base + $0.20 per oz

### Step 2 - Add Products with Weights

This is where the magic happens. Two ways to do it:

#### **Option A - Use Existing Preset** (Fastest)

1. Edit any product
2. Click **"Shipping Setup"** tab
3. Choose from dropdown:
   - T-Shirt
   - Hoodie
   - Vinyl
   - Hat
   - Sticker
   - etc.
4. Weight auto-fills instantly
5. **Optional:** Set max quantity per order (e.g., "5" for limited editions)
6. Save product → Done

#### **Option B - Enter Custom Weight** (Creates Preset for Next Time)

1. Edit product
2. Click **"Shipping Setup"** tab
3. Enter weight (e.g., "8 oz")
4. Leave "Save as reusable preset" checked ✅
5. Give it a name (e.g., "Poster 11x17")
6. **Optional:** Set purchase limit (e.g., "2" for rare items)
7. Save product

### What Happens

- Product gets weight
- New preset created automatically
- Purchase limit enforced at checkout (if set)
- Next similar product: Your preset appears in dropdown

### Step 3 - Test at Checkout

1. Add product to cart
2. Enter shipping address
3. Shipping costs calculate automatically
4. Customer sees all available methods with prices

---

## Real-World Examples

### Example 1 - Setting Up T-Shirts (Existing Preset)

**You:** "I need to add 20 different t-shirt designs"

### Steps

1. Edit first t-shirt
2. Shipping Setup tab
3. Select **"T-Shirt (0.5 oz)"** from dropdown
4. Save
5. Repeat for other 19 shirts (same preset)

**Time:** 30 seconds per product

---

### Example 2 - New Product Type (Auto-Creates Preset)

**You:** "We're selling 18x24 posters now. Never sold these before."

### Steps

1. Edit poster product
2. Shipping Setup tab
3. Enter weight: **"6"** oz
4. Name preset: **"Large Poster"**
5. Save

### What Happens

- This poster gets 6 oz weight
- "Large Poster (6 oz)" preset is created
- Next poster: Select "Large Poster" from dropdown (1 click)

### Time

- First poster: 45 seconds
- Every poster after: 10 seconds

---

### Example 3 - Bundle/Variety Pack

**You:** "We have a merch bundle: 1 tee + 1 vinyl + sticker pack"

### Steps

1. Edit bundle product
2. Shipping Setup tab
3. Calculate total weight:
   - Tee: 8 oz
   - Vinyl: 8 oz
   - Stickers: 1 oz
   - **Total: 17 oz**
4. Enter **"17 oz"**
5. Name: **"Standard Merch Bundle"**
6. Save

**Result:** Bundle ships correctly, preset saved for future bundles

---

## Common Scenarios

### "I Have 5 Different Hoodie Designs"

✅ Use the **"Hoodie (1.5 lb)"** preset for all 5

- Takes 10 seconds per product
- All hoodies ship at same cost

### "My Hoodies Are Different Weights"

✅ Enter custom weight for each:

1. Lightweight hoodie: 18 oz → Creates "Lightweight Hoodie" preset
2. Heavy hoodie: 24 oz → Creates "Heavy Hoodie" preset
3. Future hoodies: Pick from your saved presets

### "I Made a Mistake on Weight"

✅ Edit the product:

1. Go to Shipping Setup tab
2. Enter correct weight
3. Uncheck "Save as preset" (if you don't want to save it)
4. Save

### "I Want to Limit How Many Customers Can Buy"

✅ Set purchase limit:

1. Edit product
2. Go to Shipping Setup tab
3. Scroll to "Max Quantity Per Order"
4. Enter limit (e.g., "2" for limited editions)
5. Save

**Result:** Customers can't add more than your limit to cart. Clear error message shows if they try.

### "Customer Complains Shipping Is Too High"

✅ Check these:

1. **Product weight correct?** (Edit product → Shipping Setup tab)
2. **Base costs too high?** (Inkfinit Shipping → Setup & Configuration)
3. **Zone multipliers?** (International costs 1.5x-3x more, that's normal)

### "I Want to Test Shipping Costs"

✅ Use the built-in calculator:

1. Go to **Inkfinit Shipping → Setup & Configuration**
2. Scroll to **"Shipping Calculator"** card
3. Enter weight and destination
4. Click "Calculate Rates"
5. See costs for all methods instantly

---

## Product Tab Breakdown

When you click **"Shipping Setup"** on any product, you see:

```text
┌─────────────────────────────────────────┐
│  SELECT EXISTING PRESET                 │
│  ↓ Choose a preset or create new below │
│  [Dropdown: T-Shirt, Hoodie, Vinyl...]  │
│  → Auto-fills weight instantly          │
└─────────────────────────────────────────┘
                  OR
┌─────────────────────────────────────────┐
│  ENTER CUSTOM WEIGHT                    │
│  [8.5] [oz ↓]                          │
│  ☑ Save as reusable preset             │
│  [Preset name: "Medium Poster"]         │
│  → Creates new preset automatically     │
└─────────────────────────────────────────┘
             Purchase Limits
┌─────────────────────────────────────────┐
│  MAX QUANTITY PER ORDER                 │
│  [5] ← Leave empty for unlimited        │
│  → Enforced at checkout automatically   │
└─────────────────────────────────────────┘
```

**That's it.** Pick one method, optionally set a limit, save, done.

---

## Admin Pages Overview

### 1. **Dashboard** (Overview)

- System status
- Quick stats (products, presets, zones)
- Current rate table

### 2. **Setup & Configuration** (Main Settings)

- Enable/disable shipping methods
- Set base costs per method
- Test calculator
- Create custom presets
- International zone multipliers

### 3. **USPS API** (Optional - Real-Time Rates)

- Connect to USPS for live rates
- Alternative to manual base costs
- Requires USPS account

### 4. **Diagnostics** (Troubleshooting)

- View all shipping zones
- Check which products have weights
- See configuration status

---

## USPS Priority Mail Flat Rate

### What Is Flat Rate Shipping?

USPS Priority Mail Flat Rate means **"If it fits, it ships"** at one fixed price:

- Same price anywhere in the USA
- Up to 70 lbs per box
- No zone calculations needed
- **FREE boxes from USPS** (order at usps.com or pick up at Post Office)

### Why Use Flat Rate?

Flat rate is typically **better for heavy items**:

- Vinyl records
- Books and magazines
- Electronics
- Multiple items bundled
- Anything over ~3-4 lbs

**Example:** A 10 lb package to California from New York:

- Calculated rate: ~$25-35 (varies by zone)
- Large Flat Rate Box: $23.00 (fixed)
- **Savings: $2-12!**

### Setting Up Flat Rate

1. Go to **Shipping Engine → Flat Rate Boxes**
2. Configure your settings:

#### Global Settings

| Setting | Description |
| --------- | ------------- |
| **Enable Flat Rate** | Turn flat rate shipping on/off |
| **Pricing Type** | Retail (Post Office), Commercial (online), or Business (volume discount) |
| **Preference** | Use cheaper option, always flat rate, or never flat rate |
| **Markup/Discount** | Add handling fee or give discount |

#### Pricing Types Explained

- **Retail:** Full price if dropping off at Post Office counter
- **Commercial:** Discounted price when shipping online via USPS Click-N-Ship (recommended)
- **Business:** Additional discount if you have a USPS Business Rate Card (high-volume shippers)

### Available Flat Rate Options

#### Envelopes (for flat items)

| Envelope | Size | Retail | Commercial |
| ---------- | ------ | -------- | ------------ |
| Standard Envelope | 12.5" × 9.5" × 0.75" | $10.45 | $8.45 |
| Padded Envelope | 12.5" × 9.5" × 1" | $10.90 | $8.85 |
| Legal Envelope | 15" × 9.5" × 0.75" | $10.75 | $8.70 |
| Small Envelope | 10" × 6" × 0.5" | $9.35 | $7.55 |

#### Boxes (up to 70 lbs)

| Box | Inside Dimensions | Retail | Commercial |
| ----- | ------------------- | -------- | ------------ |
| Small Box | 8.69" × 5.44" × 1.75" | $10.90 | $9.00 |
| Medium Box (Top Load) | 11.25" × 8.75" × 6" | $17.60 | $14.55 |
| Medium Box (Side Load) | 14" × 12" × 3.5" | $17.60 | $14.55 |
| Large Box | 12.25" × 12.25" × 6" | $23.00 | $19.60 |

### How It Works at Checkout

1. Customer adds items to cart
2. Plugin measures if items fit in a flat rate box
3. Compares flat rate price vs. calculated weight/zone price
4. Shows the cheaper option (or flat rate only, based on your settings)
5. Customer selects shipping method
6. Order records which flat rate box to use

### Overriding Flat Rate on Orders

You can manually assign a flat rate box to any order:

1. Edit the order in WooCommerce
2. Scroll to **Shipping Address** section
3. Find **"Override Flat Rate Box"** dropdown
4. Select the box you want to use
5. Update order

This is useful when:

- You know items fit in a smaller box than calculated
- Customer requested a specific box type
- You want to use a flat rate box for cost savings

### Enabling/Disabling Specific Boxes

Don't sell items that fit certain boxes? Disable them:

1. Go to **Shipping Engine → Flat Rate Boxes**
2. Uncheck boxes you don't want to offer
3. Save settings

Disabled boxes won't appear as shipping options at checkout.

### Price Overrides

Need to adjust USPS prices? (e.g., to account for packaging costs)

1. Go to **Shipping Engine → Flat Rate Boxes**
2. Find the box in the pricing table
3. Enter your custom price in the Retail or Commercial field
4. Leave blank to use default USPS prices
5. Save settings

### Getting Free USPS Boxes

USPS provides Flat Rate boxes for **FREE**:

1. Visit [store.usps.com](https://store.usps.com/store/category/shipping-supplies/priority-mail)
2. Order boxes (free shipping too!)
3. Or pick up at your local Post Office

**Tip:** Order a variety pack to have all sizes on hand.

### Best Practices for Flat Rate

✅ **DO:**

- Use flat rate for heavy items (over 3-4 lbs)
- Stock multiple box sizes
- Set pricing to "Commercial" if you ship online
- Let the plugin auto-compare rates

❌ **DON'T:**

- Force flat rate for lightweight items (often more expensive)
- Forget to order free boxes
- Overstuff boxes (items must fit without forcing)

### Flat Rate vs. Calculated - When to Use Each

| Situation | Best Option |
| ----------- | ------------- |
| Single light t-shirt | Calculated (First Class) |
| Heavy hoodie | Flat Rate (may be cheaper) |
| Vinyl records | Flat Rate (usually cheaper) |
| Multiple items bundled | Flat Rate (almost always cheaper) |
| International orders | Calculated (flat rate is domestic only) |

---

## Tips for Band Members

### ✅ DO

- **Use presets whenever possible** - Faster and consistent
- **Name presets clearly** - "Large Poster 18x24" not "Poster1"
- **Set purchase limits on rare items** - Prevents bulk buying/reselling
- **Test checkout before launch** - Make sure shipping calculates
- **Check product weights** - Green checkmark = ready to ship

### ❌ DON'T

- **Skip weights** - Products with 0 weight won't ship correctly
- **Guess weights** - Use a kitchen scale, be accurate
- **Set limits too low** - Customer frustration if legit buyers can't order enough
- **Forget international costs** - Zone multipliers handle this automatically
- **Panic if costs seem high** - International shipping IS expensive

---

## Troubleshooting

### "Shipping not showing at checkout"

1. Check product has weight set (Edit product → Shipping Setup)
2. Check shipping zones exist (WooCommerce → Settings → Shipping)
3. Check at least one method enabled (Inkfinit Shipping → Setup)

### "Shipping costs are $0.00"

- Product weight is probably 0
- Go to product → Shipping Setup → Pick preset or enter weight

### "I need to change all t-shirt prices"

- You can't bulk-edit presets (yet)
- Edit base costs instead: Inkfinit Shipping → Setup & Configuration
- Increase "First Class" base cost or per-oz rate

### "Where do I see all my presets?"

- Inkfinit Shipping → Setup & Configuration
- Scroll to "Custom Product Presets" card
- Your auto-created presets are listed there

---

## Workflow Summary

### For Existing Products

```text
Edit Product → Shipping Setup Tab → Select Preset → Save (10 sec)
```

### For New Product Types

```text
Edit Product → Shipping Setup Tab → Enter Weight + Name → Save (30 sec)
Next Product → Shipping Setup Tab → Select Your Preset → Save (10 sec)
```

### Result

- Every product has accurate weight
- Checkout calculates shipping automatically
- Customers see real costs instantly
- You never think about shipping math again

---

## Manager Notes

### Time Investment

- **Initial setup:** 15 minutes (base costs, enable methods)
- **Per product (existing preset):** 10 seconds
- **Per product (new preset):** 30 seconds first time, 10 seconds after

### Cost Accuracy

- Plugin calculates based on YOUR base costs
- You control pricing entirely
- Zone multipliers handle international (customizable)
- Test calculator shows exact costs before going live

### Maintenance

- Update base costs anytime (takes 2 minutes)
- No per-product updates needed when costs change
- Presets persist across products

### Support

- Diagnostics page shows configuration status
- Green = good, yellow = needs attention
- All settings have clear descriptions

---

## Quick Reference Card

| Task | Location | Time |
| ------ | ---------- | ------ |
| Set base shipping costs | Inkfinit Shipping → Setup & Configuration | 5 min (one time) |
| Configure flat rate boxes | Inkfinit Shipping → Flat Rate Boxes | 3 min (one time) |
| Add product with preset | Product → Shipping Setup → Select preset | 10 sec |
| Add product with limit | Product → Shipping Setup → Enter max qty | 5 sec |
| Create new preset | Product → Shipping Setup → Enter weight | 30 sec |
| Test shipping costs | Inkfinit Shipping → Calculator | 15 sec |
| Override flat rate on order | Edit Order → Override Flat Rate Box | 10 sec |
| View all presets | Inkfinit Shipping → Setup & Configuration | - |
| Check product status | Edit product (green/yellow box shows) | 5 sec |
| Troubleshoot | Inkfinit Shipping → Diagnostics | 1 min |

---

## Bottom Line

### Old Way

- Calculate shipping manually per order
- Guess costs
- Customer surprised at checkout
- 5+ minutes per order

### New Way

1. Set product weight once (10-30 seconds)
2. Plugin does math automatically
3. Customer sees costs instantly
4. Zero manual work per order

**You do:** Set weights  
**Plugin does:** Everything else

---

**Questions?** Check Diagnostics page or contact support.
