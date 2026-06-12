# YourPropFirm UI Addon — Production Setup Guide

This add-on replaces the YourPropFirm checkout with the **FUNDEDBIT 2-step design**
(Challenge → Information). It is a **pure override layer**: it styles and re-arranges
the checkout the main plugin already renders. **It never modifies the main plugin** —
everything below is standard WooCommerce / YourPropFirm configuration plus one small
admin field this add-on adds.

> **Requirement:** the **YourPropFirm Plugin** must be installed and active. If it isn't,
> the add-on shows an admin notice and does nothing.

---

## 1. Install

1. Upload & activate the **YourPropFirm UI Addon** plugin (alongside the main plugin).
2. That's it — the checkout (`/checkout/`) is automatically restyled. No settings page
   for the add-on itself.

The compiled stylesheet (`dist/css/checkout.css`) is committed and shipped — you do **not**
need Node/Tailwind in production. (Only rebuild if you change `src/css/checkout.css`; see
[Development](#development).)

---

## 2. Product structure (how Step 1 is built)

Step 1 ("Choose Your Challenge") is driven **entirely by your WooCommerce categories and
products** — nothing is hardcoded. The model is:

| Design element | Comes from | Where you set it |
|---|---|---|
| **Evaluation Type** card (e.g. "1-Step") | a Product **Category** name | Products → Categories |
| Card **subtitle** ("Single Phase Evaluation") | the Category **Description** | Products → Categories → Description |
| Card **badge** ("Best Value" / "Most Popular") | the Category **Checkout Badge** | Products → Categories → **Checkout Badge** (added by this add-on) |
| **Account Balance** pill (e.g. "$100,000") | a **Product's Account Size** | the product → Product data → **Account Size** |
| Balance **currency** ($, €, …) | the **Product's Account Currency** | the product → **Account Currency** |
| **Price** (Base / Total) | the **Product price** | the product → Regular price |
| **Trading Platform** options | the product's **Trading Options** | the product → Trading Options |

**So, to add an evaluation type:** create a product **Category** (name + description + badge),
then add one **Product per account size** inside it (set Account Size, Account Currency,
Trading Options, Program, and price on each).

Everything is live: rename a category, change a description/badge, add a product, change a
price — the checkout reflects it (see the [cache note](#notes--gotchas)).

---

## 3. Checkout settings

**WP Admin → YourPropFirm → Product Setup:**

- **Enable Product Selection** — ✅
- **Product Categories** — select your evaluation-type categories (these become the cards)
- **Display Product as Radio Buttons** — ✅ (renders the account-size **cards**; turn off for a dropdown)
- **Display Account Size** — ✅ (shows the account balance instead of the product name)
- *(Optional)* **Overwrite Product Category Label** → level 0 = `Select Evaluation Type`

**YourPropFirm → General Settings:**

- **Terms of Service Link** and **Privacy Policy Link** — set these; the consent text on
  Step 2 links to them.

---

## 4. Badges (the "Checkout Badge" field)

This add-on adds a **Checkout Badge** field to the category editor:

> **Products → Categories → (edit a category) → Checkout Badge**

Type e.g. `Best Value` or `Most Popular` and Update. Leave empty for no badge. It also
appears on the **Add new category** screen.

---

## 5. Payment

The checkout uses **whatever payment gateways you enable in WooCommerce** — nothing is
hardcoded to a specific provider.

- Enable gateways in **WooCommerce → Settings → Payments** (Stripe, bank transfer, Confirmo,
  NMI, …).
- Step 2's **Payment method** dropdown is populated **dynamically** from your enabled gateways.
- Selecting a gateway renders **that provider's own fields** (e.g. Stripe's card form).
- **Proceed to Payment** places the order through the selected gateway via WooCommerce's
  native checkout — the add-on does no payment processing of its own.

No gateway-specific configuration is needed in the add-on.

---

## 6. Terms & conditions

The design uses **consent text** ("By placing your order, you agree to our Terms / Privacy
Policy") instead of a checkbox. The form submits a hidden, always-accepted `terms` field so
the main plugin's terms validation passes. Just make sure the **TOS / Privacy links** are set
(Section 3).

---

## 7. Currency model (important)

Two **independent** currencies, by design:

- **Account balance** (the pills + the summary's *Account* row) → the **product's Account
  Currency** (e.g. a `$100,000` USD account).
- **Price** (*Base Price / Sub Total / Total*, and the order) → the **WooCommerce store
  currency** (WooCommerce → Settings → General → Currency).

So a `$100,000` (USD) account can be sold for `€489` (EUR store) — the account size shows `$`,
the price shows `€`. This is intentional and correct.

---

## Notes & gotchas

- **Caching:** the main plugin caches the checkout data for ~5 minutes. After editing
  products/categories, changes may take up to 5 minutes (or one extra reload) to appear.
- **Order line-item names** are a snapshot taken at purchase (standard WooCommerce) — renaming
  a product later does not change names on existing orders.
- The add-on overrides templates only when a matching file exists under
  `templates/woocommerce/`; anything else falls through to the main plugin unchanged.

---

## Development

Only needed if you edit styles/markup in this repo.

- **Rebuild CSS** after editing `src/css/checkout.css`:
  ```bash
  cd build && npm run build      # outputs dist/css/checkout.css
  ```
- **Local test data:** `local-dev/seed-category-demo.php` builds a category-driven demo
  (eval categories + account-size products). Run it in your WP-CLI/container:
  ```bash
  wp eval-file wp-content/plugins/yourpropfirm-ui-addon/local-dev/seed-category-demo.php
  ```
- The add-on is **add-on only** — never edit the main plugin to make a change work; it must
  function against the stock main plugin.
