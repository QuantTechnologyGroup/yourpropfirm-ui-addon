# Template Reference

PHP template files for the YourPropFirm WooCommerce checkout plugin.

All WooCommerce override templates live under `woocommerce/checkout/`. Plugin-specific partials live under `public/partials/checkout/`.

---

## Main Templates

### `woocommerce/single-checkout.php`

Full HTML page wrapper. Renders the standalone checkout page with dark mode initialisation, iframe detection, logo, and decorative SVG background shapes.

**Responsibilities**

- Outputs the complete `<!doctype html>` document.
- Runs an inline script in `<head>` that reads `localStorage.theme` and `prefers-color-scheme`, then sets the `dark` or `light` class on `<html>` before first paint to prevent flash-of-wrong-theme.
- Runs a second inline script immediately after `<body>` opens that detects iframe embedding (`window.self !== window.top`) and adds `iframe-mode` to `<body>` when true.
- Renders the language switcher and dark-mode toggle button inside `.checkout-header-container`.
- Outputs the site logo: prefers `dark`/`light` variant URLs from `yourpropfirm_get_dashboard_asset_url()`, falls back to the WP custom logo, then a plain `<h1>`.
- Wraps all checkout content in `.yourpropfirm-checkout` and calls `the_content()`.
- Appends two absolutely-positioned SVG blobs (`.background-bottom-left`, `.background-top-right`) that use `--yourpropfirm-primary` via inline `stop-color` for the gradient fill.

| Key class / element | Purpose |
|---|---|
| `checkout-body` | Applied to `<body>` via `body_class()`. Scopes checkout-specific body styles. |
| `checkout-container` | Outermost page wrapper div. |
| `checkout-header-container` | Constrained-width header row holding logo and controls. Hidden in iframe mode via CSS. |
| `checkout-logo` | Logo image wrapper. |
| `dark-mode-toggle-container` | Wrapper for the language switcher and theme toggle button. |
| `dark-mode-toggle` | The `<button id="theme-toggle">` that calls `dark-mode.js`. |
| `yourpropfirm-checkout` | Flex wrapper that centres the WooCommerce checkout content output. |
| `background-placement background-bottom-left` | Decorative blurred SVG, bottom-left. Hidden in iframe mode. |
| `background-placement background-top-right` | Decorative blurred SVG, top-right. Hidden in iframe mode. |

---

### `woocommerce/checkout/form-checkout.php`

Main two-column checkout form. Overrides the WooCommerce core `form-checkout.php`. Renders the `<form>` tag and the two-column grid, then delegates every section to sub-templates via `wc_get_template()`.

**Structure**

```
<form.checkout.woocommerce-checkout>
  .checkout-grid#customer_details
    .checkout-form-left
      .checkout-card
        .billing-detail-container          ← billing fields (inline, not separate template)
        .container-product-selection-group
          .container-purchase-details      ← repurchase-detail.php
          .container-product-selection     ← form-product-selection.php   [hide-if-*]
          .container-trading-platform      ← form-trading-platform.php    [hide-if-*]
          .container-available-addons      ← form-available-addons.php    [hide-if-*]
          .container-bundle-packages       ← form-available-bundles.php   [hide-if-*]
    .checkout-form-right
      .order-summary.checkout-card
        .coupon-section                    ← inline coupon input
        .payment-section                   ← payment.php
        review-order.php (direct include)
        .terms-conditions-section          ← terms.php
        .place-order-section               ← submit button + agreement text + secure indicator
```

**Notes**

- WooCommerce notices are moved after the `<form>` so payment-gateway JS errors appear below, not above, the form.
- The coupon input (`#coupon_code`, `#apply_coupon_btn`) is rendered inline within this template, not through `form-coupon.php` (which is suppressed).
- The "Proceed to Payment" submit button (`#place_order`) lives inside `.place-order-section`.

| Key class | Purpose |
|---|---|
| `checkout-grid` | CSS Grid parent; creates the two-column layout. |
| `checkout-form-left` | Left column: billing fields and product selection group. |
| `checkout-form-right` | Right column: order summary, payment, terms, place-order. |
| `checkout-card` | Shared card surface applied to major content blocks. |
| `coupon-section` | Coupon code input group in the right column. |
| `payment-section` | Wrapper for the `payment.php` include. |
| `place-order-section` | Submit button, agreement text, separator, and secure-checkout indicator. |
| `billing-detail-container` | Wraps the billing fields heading and field list. |
| `section-heading` | `<h3>` heading style used in multiple sections. |

---

### `woocommerce/checkout/form-billing.php`

Billing address card. Standalone override of the WooCommerce billing form, used when WooCommerce renders the billing section independently (e.g. on the pay-for-order page). When the main checkout flow runs through `form-checkout.php`, the billing fields are rendered inline there instead.

**Behaviour**

- Shows "Billing & Shipping details" heading when `wc_ship_to_billing_address_only()` is true and cart needs shipping; otherwise shows "Billing details".
- Iterates all `billing` checkout fields via `woocommerce_form_field()`.
- Conditionally renders the "Create an account?" checkbox and account registration fields for guests when registration is enabled but not required.

| Key class | Purpose |
|---|---|
| `checkout-card` | Outer card wrapper. |
| `billing-detail-container` | Not present in this template (it is in `form-checkout.php`); billing fields are wrapped only by `.woocommerce-billing-fields`. |
| `section-heading` | `<h3>` heading (Billing details / Billing & Shipping details). |
| `woocommerce-billing-fields__field-wrapper` | Standard WooCommerce field container. |

---

### `woocommerce/checkout/form-product-selection.php`

Multi-level product/category picker. Renders radio buttons or a dropdown for selecting a product, with dynamically loaded subcategory levels. Embedded inside `.container-product-selection` in `form-checkout.php`.

**Behaviour**

- Bails early for reset-product checkouts and competition products.
- Reads `yourpropfirm_checkout_display_product_as_radio` CarbonFields option to choose radio vs dropdown render mode.
- Supports arbitrarily deep category hierarchies. Level 0 is rendered statically; deeper levels are added to `.subcategory-sections-container` on initial load if the cart product resolves a multi-level path.
- Category-level labels can be overridden via `yourpropfirm_checkout_overwrite_product_category_label` (stored in `data-category-level-labels` JSON).
- Products marked `most_popular` receive a `.product-option-popular-badge` span.
- Nests `form-product-variants.php` in `.container-product-variants` at the bottom.

**Key data attributes on `.woocommerce-product-selection`**

| Attribute | Value |
|---|---|
| `data-rest-url` | Base URL for the YPF REST API (`yourpropfirm/v1/`). Used by JS for AJAX category/product lookups. |
| `data-rest-nonce` | WP REST nonce for authenticated requests. |
| `data-category-level-labels` | JSON-encoded array of custom label strings per hierarchy level. |
| `data-display-as-radio` | `"true"` or `"false"`. Tells JS whether to render product list as radios or a `<select>`. |
| `data-display-account-size` | `"true"` or `"false"`. Switches product label from name to formatted account size. |

| Key class | Purpose |
|---|---|
| `woocommerce-product-selection` | Root container. Receives data attributes consumed by `checkout.js`. |
| `product-category-section` | One level of the category hierarchy (radio group). |
| `category-options` | Wrapper for the `<label.category-option>` radio inputs within a level. |
| `subcategory-sections-container` | Holds dynamically injected subcategory level panels (levels 1+). |
| `selected-product-section` | Final level showing the product radio options or dropdown. |
| `product-radio-options` | Wrapper for `<label.product-option>` radio inputs. |
| `product-option` | Individual product radio label. |
| `product-option-popular-badge` | "Most Popular" badge span inside a product-option. |
| `product-option-name` | Product name/account-size span. |
| `product-option-price-badge` | Formatted price span. |
| `product-dropdown-wrapper` | Wrapper used in dropdown mode (wraps `<select.product-dropdown>`). |

---

### `woocommerce/checkout/form-product-variants.php`

Attribute selection for variable products (account size, leverage, payout frequency, etc.). Rendered inside `form-product-selection.php`.

**Behaviour**

- Bails if the cart item is not a `variable` product or has no variation attributes.
- Builds a `$variations_map` (variation ID + attribute slugs for all published children) and encodes it into `data-variations-map` for JS to handle selection logic.
- Reads the currently selected variation from `WC()->session->get('ypf_selected_variation_id')`.
- Resolves human-readable attribute labels and term names from WP taxonomy.
- Adds `variant-attribute-option--wide` modifier class when a term has a badge or description, enabling an expanded card layout.

**Key data attribute**

| Attribute | Value |
|---|---|
| `data-variations-map` | JSON array of `{ variation_id, attributes: { attr_name: slug } }` objects. |
| `data-product-id` | WooCommerce product ID of the parent variable product. |

| Key class | Purpose |
|---|---|
| `woocommerce-product-variants` | Root container. |
| `variant-attribute-group` | One attribute group (e.g. "Account Size"). Has `data-attribute` with the taxonomy name. |
| `variant-attribute-options` | Wrapper for the radio button labels within a group. |
| `variant-attribute-option` | Individual radio label (compact style). |
| `variant-attribute-option--wide` | Modifier: expanded card layout when badge or description is present. |
| `variant-attribute-content` | Inner wrapper used in the wide layout. |
| `variant-attribute-header` | Row containing name + badge in wide layout. |
| `variant-attribute-name` | Term display name span. |
| `variant-attribute-badge` | Optional badge text span (from `ypf_term_badge` Carbon meta). |
| `variant-attribute-description` | Term description div (wide layout only). |
| `variant-attribute-label` | Simple name span (compact layout). |

---

### `woocommerce/checkout/form-trading-platform.php`

Trading platform radio selector (MT5, MT4, TradingView, etc.). Rendered inside `.container-trading-platform` in `form-checkout.php`.

**Behaviour**

- Bails for reset-product checkouts.
- Sorts platforms in priority order: MT5 > MT4 > TradingView > rest.
- Defaults to MT5 if available, otherwise the first platform.
- If only one platform is available, renders a hidden `<input type="radio">` with no visible UI (auto-selects silently).

| Key class | Purpose |
|---|---|
| `woocommerce-trading-platform` | Root container (only rendered when 2+ platforms exist). |
| `trading-platform-options` | Wrapper for the platform radio labels. |
| `platform-option` | Individual platform radio `<label>`. |
| `platform-radio` | The `<input type="radio" name="trading_platform">` inside each label. |
| `platform-option-content` | Inner div that displays the platform name/HTML. |

---

### `woocommerce/checkout/form-available-addons.php`

Add-on selection panel. Supports checkbox (independent selections) and radio (mutually exclusive) add-on types. Rendered inside `.container-available-addons` in `form-checkout.php`.

**Behaviour**

- Bails for reset-product and competition checkouts.
- Reads add-on configuration from CarbonFields option `yourpropfirm_addon_items`.
- Filters add-ons by platform exclusivity: if `platform_exclusive` is set on an add-on and the current trading platform is not in that list, the add-on is skipped.
- For `radio` type add-ons, each sub-option in `addons_radio` renders as its own `.addon-option.radio-type` row with a checkbox input (checked state managed by JS for radio group behaviour).
- For `checkbox` type add-ons, a single `.addon-option.checkbox-type` row is rendered.
- The fee is displayed as both a percentage (`addon-price`) and a formatted currency amount (`addon-price-desc`).

| Key class | Purpose |
|---|---|
| `woocommerce-available-add-ons` | Root container. |
| `available-addons` | Inner wrapper for all addon option rows. |
| `addon-option` | One add-on row. |
| `addon-option.checkbox-type` | Modifier for an independent checkbox add-on. |
| `addon-option.radio-type` | Modifier for a mutually-exclusive radio add-on sub-option. |
| `addon-content` | Row holding the checkbox/label. |
| `addon-input` | The `<input type="checkbox">` element. |
| `addon-pricing` | Wrapper for the fee display (both percentage and currency). |
| `addon-price` | Percentage fee display (e.g. "+10%"). |
| `addon-price-desc` | Formatted currency amount description. |

---

### `woocommerce/checkout/form-available-bundles.php`

Bundle package selection panel. Renders a radio group of pre-defined addon bundles. Rendered inside `.container-bundle-packages` in `form-checkout.php`.

**Behaviour**

- Bails for reset-product and competition checkouts.
- Retrieves bundles via `yourpropfirm_get_product_bundles()`.
- The active bundle (from WC session `bundle_id`) receives the `.is-active` modifier.
- Each bundle's `addon_ids` are encoded into `data-addon-ids` on the radio input so JS can reflect the included add-ons in the add-ons panel.
- A `.bundle-addon-list` renders the human-readable addon titles with a checkmark icon.
- When a bundle is already active (page reload), hidden `<input name="addon[n]">` inputs are injected so the server-side price calculation picks up the bundled add-ons without JS.
- A `<input type="hidden" name="bundle_id" id="bundle_id_fallback">` provides a blank fallback value.

**Key data attribute**

| Attribute | Value |
|---|---|
| `data-addon-ids` | JSON array of addon ID strings included in the bundle (e.g. `["0-profit-split","1-bi-weekly"]`). |

| Key class | Purpose |
|---|---|
| `woocommerce-bundle-packages` | Root container. |
| `bundle-packages` | Inner wrapper for all bundle option rows. |
| `bundle-option` | One bundle row. |
| `bundle-option.is-active` | Modifier indicating the currently selected bundle. |
| `bundle-content` | Row holding the radio input and label. |
| `bundle-input` | The `<input type="radio" name="bundle_id">`. |
| `bundle-addon-preview` | Container for the addon list shown under the bundle label. |
| `bundle-addon-list` | `<ul>` of included addon names. |
| `bundle-addon-item` | `<li>` for each addon in the list. |
| `bundle-addon-check-icon` | SVG checkmark icon inside each list item. |
| `bundle-forced-addon` | Class on the hidden `<input name="addon[n]">` inputs injected for active bundles. |

---

### `woocommerce/checkout/review-order.php`

Order summary panel. Renders price rows for base price, add-ons, fees, discounts, sub-total, and grand total. Embedded directly in `form-checkout.php` inside the right column card.

**Behaviour**

- Reads the currently chosen trading platform, selected add-ons, and variant attributes from POST data and WC session.
- For renewal orders (WooCommerce Subscriptions), base price and add-ons are read from subscription item meta (`_custom_line_item_meta`) rather than cart data.
- Builds `$data_order_array` containing the complete order snapshot (product, program, platform, pricing, addons, fees, coupons) and encodes it as `data-order` JSON on `#order-row-grand-total`. This is consumed by checkout JS for analytics/tracking.
- Applies `yourpropfirm_review_order_data_array` filter so addons can extend the data object.
- Conditional visibility classes (`hide-if-*`) are applied to rows that are irrelevant for specific product types.

| Key class | Purpose |
|---|---|
| `order-review-section` | Root container; also carries `woocommerce-checkout-review-order-table` for WooCommerce JS compatibility. |
| `order-review-container` | Inner wrapper with separator and rows. |
| `order-row` | One label/value price row. |
| `order-label` | Left-side label `<span>`. |
| `order-value` | Right-side value `<span>`. |
| `order-subtotal-amount` | Applied to the sub-total value span. |
| `order-discount-value` | Applied to discount value spans. |
| `order-total-amount` | Applied to the grand total value span. |
| `grand-total` | Modifier on `#order-row-grand-total`; carries `data-order` JSON. |
| `selected-platform` | Applied to the Trading Platform row; updated by JS when platform changes. |
| `hide-if-reset-product` | Row hidden when the cart contains a reset product. |
| `hide-if-renewal-subscription` | Row hidden for subscription renewal checkouts. |
| `hide-if-competition` | Row hidden for competition products. |
| `hide-if-trial-product` | Row hidden for free trial products. |

---

### `woocommerce/checkout/payment.php` and `woocommerce/checkout/payment-method.php`

**`payment.php`** — Payment gateway list container.

Renders the payment method section inside the right-column card. Contains a `<select>` for gateway selection (drives the visible gateway detail panels via JS) and an `<ul>` of individual gateway rows rendered by `payment-method.php`.

| Key class | Purpose |
|---|---|
| `woocommerce-checkout-payment` | Root container (`#payment`). |
| `yourpropfirm-checkout` | Additional class on the root for scoped styles. |
| `yourpropfirm-payment-method-section` | Inner wrapper for the heading, select, and gateway list. |
| `wc_payment_methods` | `<ul>` holding all payment method rows. |
| `payment_methods methods` | Additional WooCommerce classes on the `<ul>`. |
| `payment_method_select` | The `<select>` controlling which gateway detail panel is visible. |

**`payment-method.php`** — Individual gateway row.

Renders one `<li>` per gateway. The hidden radio input tracks the chosen gateway. An optional `.payment_box` div holds the gateway's custom fields (e.g. card iframe) and is hidden by default unless the gateway is pre-chosen.

| Key class | Purpose |
|---|---|
| `wc_payment_method` | Applied to every `<li>`. |
| `payment_method_{gateway_id}` | Gateway-specific class on the `<li>`. |
| `input-radio` | The hidden `<input type="radio" name="payment_method">`. |
| `payment_box payment_method_{id}` | Container for gateway-specific fields, toggled visible by WC JS. |

---

### `woocommerce/checkout/terms.php`

Terms & Conditions and Privacy Policy checkboxes. Rendered inside `.terms-conditions-section` in `form-checkout.php` and also included by `form-pay.php`.

**Behaviour**

- Only renders if WooCommerce terms and conditions are enabled (`woocommerce_checkout_show_terms` filter).
- Links are read from CarbonFields options: `yourpropfirm_tos_link` and `yourpropfirm_privacy_policy_link`.

| Key class | Purpose |
|---|---|
| `woocommerce-terms-and-conditions-wrapper` | Outer wrapper (WC standard class, used by WC JS). |
| `terms-checkbox-wrapper` | Inner wrapper for the checkbox + label row. |
| `terms-checkbox` | The `<input type="checkbox" name="terms" id="terms">`. |
| `terms-label` | The `<label for="terms">`. |
| `terms-link` | Applied to both the T&C and Privacy Policy `<a>` tags. |

---

### `woocommerce/checkout/thankyou.php`

Post-purchase confirmation page. Replaces the default WooCommerce thankyou template.

**Behaviour**

- Adds `thankyou-body` to `<body>` via a filter.
- For failed orders, renders a failure notice with a support email link instead of the success flow.
- For successful orders, shows a "What's next?" panel with a "Continue to Dashboard" button and a plain-text dashboard URL. Both the button and a `<span class="hide-on-iframe">` / `<span class="show-on-iframe">` pattern adapt the messaging for iframe-embedded vs normal page contexts.
- Renders a `thankyou-grid` with two cards: Order Overview (`.thankyou-order-overview`) and a billing container holding Order Details (`.thankyou-order-summary`) and Billing Address (`.thankyou-billing-address`).
- The Order Details card iterates order line items and resolves per-item metadata: `_base_price`, `_trading_platform`, `_addons_breakdown` stored at order creation time.
- Free trial orders (`_yourpropfirm_order_is_free_trial = yes`) skip the Order Details card entirely.
- Status badge variant is determined by order status: `pending` (on-hold/pending), `completed` (processing/completed), `failed`.

| Key class | Purpose |
|---|---|
| `yourpropfirm-thankyou` | Root container; also carries `woocommerce-order`. |
| `thankyou-grid` | CSS Grid that places the two side-by-side cards. |
| `thankyou-order-card` | Shared card surface for both summary panels. |
| `thankyou-order-overview` | Left card: status badge, order number, email, date. |
| `thankyou-order-overview-holder` | Inner row wrapper for overview items. |
| `thankyou-order-overview-item` | Label/value pair row in the overview card. |
| `thankyou-order-billing-container` | Right column holding the summary + billing address cards. |
| `thankyou-order-summary` | Order details card: line items, subtotal, fees, payment method, coupons, total. |
| `thankyou-billing-address` | Billing address card with formatted address and phone. |
| `thankyou-order-card-header` | `<h2>` heading inside each card. |
| `thankyou-order-details-row` | One label/value row inside the order details card. |
| `thankyou-status-badge` | Status badge `<span>` inside the overview card. |
| `thankyou-status-badge.pending` | Yellow/warning state for on-hold and pending orders. |
| `thankyou-status-badge.completed` | Green/success state for processing and completed orders. |
| `thankyou-status-badge.failed` | Red/error state for failed orders. |

---

## Minor Templates

| Template | Purpose |
|---|---|
| `repurchase-detail.php` | Purchase details card shown above the product selection group. Displays product name, account size (or prize pool for competitions), and the product short description. Uses `.woocommerce-repurchase-details`, `.repurchase-grid`, `.repurchase-card`. Also outputs the reset-checkout WP nonce when applicable. |
| `form-shipping.php` | Shipping address card. Conditionally renders the "Ship to a different address?" checkbox and the `.shipping_address` field group when the cart needs a shipping address. Also renders the Additional Information (order notes) field group. Wrapped in `.checkout-card`. Not used in the standard prop-firm checkout flow (digital products, no shipping). |
| `form-login.php` | Returning customer login prompt. Shows a collapsible login form above the checkout when the user is a guest and `woocommerce_enable_checkout_login_reminder` is enabled. Uses standard WooCommerce `.woocommerce-form-login-toggle` and the `woocommerce_login_form()` helper. |
| `form-coupon.php` | Suppressed. Contains only `return;`. The coupon form is rendered inline in `form-checkout.php` instead, placing it inside the right-column card above the payment section. |
| `form-pay.php` | Pay-for-existing-order page. Renders a `<table class="shop_table">` of order items with quantities and totals, then a `#payment` div with the gateway list (reuses `payment-method.php`) and a submit button. Used on the WooCommerce "Pay" URL for pending orders, not on the standard checkout. |
| `form-verify-email.php` | Email verification gate. Renders `<form.woocommerce-verify-email>` shown instead of the thankyou page when a guest order cannot be identified. Prompts the customer to enter their order email address to gain access. |
| `order-received.php` | Minimal "Thank you / Order failed" notice fragment. Renders a `.woocommerce-notice--success` div with either a "Thank you!" or "Order failed" heading. Used as a fallback inside `thankyou.php` when `$order` is false, and can be included independently. |
| `order-receipt.php` | Order receipt snippet for the WooCommerce "Pay" confirmation screen. Outputs a `<ul class="order_details">` with order number, date, total, and payment method. Fires the `woocommerce_receipt_{payment_method}` action hook (used by gateways that redirect back to this page after payment). |
| `cart-errors.php` | Cart validation error page. Shown by WooCommerce when items in the cart have errors preventing checkout. Renders a plain paragraph and a "Return to cart" button. |

---

## Partials (`public/partials/checkout/`)

### `public/partials/checkout/addons.php`

Alternative add-on renderer used in legacy or non-standard checkout contexts. Receives `$addons` and `$product_category_classes` as template variables. Supports both checkbox (multi-select) and radio (single-select) modes driven by the `yourpropfirm_addon_type` CarbonFields option.

**Behaviour**

- Clears any stale `chosen_addons` session data on render to force a clean state.
- Reads `chosen_addons` from WC session to pre-check inputs.
- Emits a `qm/info` debug log entry with addon state on each render.
- Applies `woo-product-cat-id-{class}` to the root div, enabling category-specific overrides.

| Key class | Purpose |
|---|---|
| `ypf-addons-default-container` | Root container; also receives `woo-product-cat-id-{n}` for category targeting. |
| `ypf-addons-default-title` | `<h4>` title from `yourpropfirm_addon_title` option. |
| `ypf-addons-wrap` | Inner wrapper for the field-group rows. |
| `field-group` | One addon row holding the input and label. |
| `ypf-addons-default-checkbox-input` | Checkbox input in checkbox mode (`name="ypf_addons[]"`). |
| `ypf-addons-default-radio-input` | Radio input in radio mode (`name="ypf_addons"`). |
| `ypf-addon-fee` | `<span>` displaying the percentage fee inside the label. |

---

### `public/partials/checkout/trading-platform.php`

Alternative trading platform field using WooCommerce's `woocommerce_form_field()` helper. Renders a `<select>` dropdown (vs the radio-button approach in the WooCommerce override template). Intended for the legacy or non-override checkout path.

**Behaviour**

- Bails if the plugin integration is disabled (`yourpropfirm_connection_enabled !== 'enable'`) or if the MT version field is disabled (`yourpropfirm_connection_mt_version_field !== 'enable'`).
- Builds the options array conditionally from individual `enable_mt4`, `enable_mt5`, `enable_ctrader`, `enable_sirix`, `enable_dx_trade`, `enable_match_trader`, `enable_tradelocker`, `enable_rithmic` toggles.
- Supports: MT4, MT5, cTrader, Sirix, DX Trade, MatchTrade, TradeLocker, Rithmic.

| Key class / element | Purpose |
|---|---|
| `trading-platform-section` | Outer wrapper div. |
| `form-row-wide ypf_mt_version_field` | Classes passed to `woocommerce_form_field()` for the select row. |
| `form-select` | Input class applied to the `<select>` element. |
| Field name: `yourpropfirm_mt_version` | The form field name submitted to the server. |

---

## Conditional Visibility Classes

These classes are applied to individual rows and containers throughout the templates. CSS rules (in `checkout.css`) set `display: none` on elements carrying these classes when the corresponding product type is active. JavaScript in `checkout.js` adds or removes these classes from `<body>` (or a high-level wrapper) as the cart state changes, causing all matching rows to show or hide in sync.

| Class | Hides the element when... |
|---|---|
| `hide-if-reset-product` | The cart contains a reset product (a product that resets an existing trading account). Product selection, variant, platform, addon, and bundle sections are all hidden; only the purchase details and billing form are shown. |
| `hide-if-renewal-subscription` | The checkout is a WooCommerce Subscriptions renewal. Product name, category, variant attribute, platform, and similar rows are hidden because they cannot change for a renewal; only pricing rows remain visible. |
| `hide-if-competition` | The cart contains a competition product. Category and variant attribute rows that are specific to standard prop accounts (account size, leverage) are hidden. |
| `hide-if-trial-product` | The cart contains a free trial product. Pricing rows (Base Price, Sub Total, Total) are hidden since there is no charge. |
| `iframe-mode` | Added to `<body>` when the page is loaded inside an `<iframe>` (`window.self !== window.top`, detected in `single-checkout.php`). The `.checkout-header-container` (logo/controls) and the SVG background blobs are hidden via CSS. The "Continue to Dashboard" button and dashboard URL link in `thankyou.php` are also hidden; iframe-specific messaging (`show-on-iframe` spans) is shown instead. This enables the checkout to be embedded cleanly inside a dashboard widget. |
