# CSS Custom Properties Reference

All CSS custom properties used in the YourPropFirm checkout frontend. These variables are consumed by Tailwind via `tailwind.config.js` and are injected at runtime by the WordPress plugin (yourpropfirm-plugin) based on the merchant's dashboard configuration.

---

## 1. Primary Brand Colors

These variables represent the tenant's primary brand palette. The base value (`--yourpropfirm-primary`) is set by the plugin from the admin color picker; the scale variants (`-50`, `-300`, `-light`, `-dark`, `-hover`) are derived or also set alongside it.

| Variable | Usage | Tailwind Class |
|---|---|---|
| `--yourpropfirm-primary` | Main brand accent: buttons, active borders, checked states, links, progress fills | `tw-bg-yourpropfirm-primary` / `tw-text-yourpropfirm-primary` / `tw-border-yourpropfirm-primary` |
| `--yourpropfirm-primary-hover` | Hover state of primary elements (e.g., terms link hover) | `tw-bg-yourpropfirm-primary-hover` / `tw-text-yourpropfirm-primary-hover` |
| `--yourpropfirm-primary-light` | Subtle tinted backgrounds, highlight areas | `tw-bg-yourpropfirm-primary-light` |
| `--yourpropfirm-primary-dark` | Pressed / deeper shade of primary | `tw-bg-yourpropfirm-primary-dark` |
| `--yourpropfirm-primary-50` | Very light tint; used as selected-option background in SelectWoo dropdown | `tw-bg-yourpropfirm-primary-50` |
| `--yourpropfirm-primary-300` | Mid-weight primary tint; available for decorative use | `tw-bg-yourpropfirm-primary-300` |

---

## 2. Theme Colors

These variables cover the full page palette and are set per-tenant by the plugin. They swap automatically between light and dark mode (see Dark Mode section below).

| Variable | Usage | Tailwind Class |
|---|---|---|
| `--yourpropfirm-secondary` | Secondary brand accent; supplementary UI elements | `tw-bg-yourpropfirm-secondary` / `tw-text-yourpropfirm-secondary` |
| `--yourpropfirm-background` | Page / body background color | `tw-bg-yourpropfirm-background` |
| `--yourpropfirm-card` | Card surface background (`.card` component) | `tw-bg-yourpropfirm-card` |
| `--yourpropfirm-text` | General body text color | `tw-text-yourpropfirm-text` |
| `--yourpropfirm-primary-text` | Primary emphasis text (headings, labels) | `tw-text-yourpropfirm-primary-text` |
| `--yourpropfirm-secondary-text` | De-emphasized / secondary body text, coupon input | `tw-text-yourpropfirm-secondary-text` |
| `--yourpropfirm-button-text` | Text color rendered on top of primary-colored buttons and badges | `tw-text-yourpropfirm-button-text` |
| `--yourpropfirm-border` | Default border color for dividers and containers | `tw-border-yourpropfirm-border` |
| `--yourpropfirm-accent` | Supplementary accent for decorative highlights | `tw-bg-yourpropfirm-accent` / `tw-text-yourpropfirm-accent` |

---

## 3. Progress Bar

Used to paint the gradient fill of checkout step / progress bar components.

| Variable | Usage | Tailwind Class |
|---|---|---|
| `--yourpropfirm-progressbar-from` | Start color of progress bar gradient | `tw-from-yourpropfirm-progressbar-from` |
| `--yourpropfirm-progressbar-to` | End color of progress bar gradient | `tw-to-yourpropfirm-progressbar-to` |

---

## 4. Status Colors

These have hard-coded defaults in `:root` and are used for semantic feedback states across badges, validation, and secure-checkout indicators.

| Variable | Default | Usage | Tailwind Class |
|---|---|---|---|
| `--yourpropfirm-success` | `#28a745` | Success states: `.badge-success`, `.secure-checkout-icon`, green validation indicators | `tw-text-yourpropfirm-success` / `tw-bg-yourpropfirm-success` |
| `--yourpropfirm-warning` | `#ffc107` | Warning states: `.badge-warning`, warning notices | `tw-text-yourpropfirm-warning` / `tw-bg-yourpropfirm-warning` |
| `--yourpropfirm-error` | `#dc3545` | Error states: `.badge-error`, WooCommerce invalid field borders and text | `tw-text-yourpropfirm-error` / `tw-border-yourpropfirm-error` |
| `--border-standby` | `#666666` | Neutral / standby field border; aliased as `yourpropfirm-fieldborder` in Tailwind | `tw-border-yourpropfirm-fieldborder` |

---

## 5. Message / Feedback Colors

Used exclusively for WooCommerce inline notification banners (`.woocommerce-error`, `.woocommerce-message`). The alpha variants (`1a` suffix) produce a 10% opacity tinted background.

| Variable | Default | Usage | Tailwind Class |
|---|---|---|---|
| `--danger-color` | `#ff4d4d` | Error notification text and border | `tw-text-message-error-color` / `tw-border-message-error-color` |
| `--danger-background` | `#ff4d4d1a` | Error notification background (10% opacity red) | `tw-bg-message-error-background` |
| `--success-color` | `#16bc53` | Success notification text and border | `tw-text-message-success-color` / `tw-border-message-success-color` |
| `--success-background` | `#16bc531a` | Success notification background (10% opacity green) | `tw-bg-message-success-background` |

> These are mapped in `tailwind.config.js` under the `message.error` and `message.success` color namespaces.

---

## Dark Mode

The `--yourpropfirm-*` tokens swap automatically when the `dark` class is present on the `<html>` element. The plugin sets this class via `public/js/dark-mode.js`, which checks `localStorage.theme` first and falls back to `prefers-color-scheme`.

**How it works:**

- The plugin defines a second set of `--yourpropfirm-*` values inside a `.dark { }` block in the compiled CSS. When `.dark` is on `<html>`, those overrides take effect globally.
- Any component that references a `--yourpropfirm-*` token via a Tailwind utility class automatically renders in the correct mode without additional `dark:` variants.
- For Tailwind's built-in gray scale and other non-token utilities, use the `dark:` prefix explicitly:

```html
<!-- Token-based: no dark: prefix needed -->
<div class="tw-bg-yourpropfirm-card tw-text-yourpropfirm-text">...</div>

<!-- Gray scale: requires dark: prefix -->
<p class="tw-text-gray-600 dark:tw-text-white">...</p>

<!-- Mixed -->
<button class="tw-bg-yourpropfirm-primary tw-text-yourpropfirm-button-text hover:tw-opacity-90">
  Place Order
</button>
```

**Dark mode is class-based, not media-query-based.** The Tailwind config sets `darkMode: ["class", '[class="dark"]']`, so `prefers-color-scheme` alone does not activate dark utilities — the JS toggle must set the class.

---

## Extending with New Tokens

Follow these steps whenever a new design token is needed. Coordinate with the plugin team before shipping, as variable injection happens server-side in PHP.

1. **Add to `:root` in `public/src/css/checkout.css`** — define the variable with a sensible light-mode default:
   ```css
   :root {
     --yourpropfirm-surface-alt: #f5f5f5;
   }
   ```

2. **Add a `.dark` override** in the same file so the token responds to theme switching:
   ```css
   .dark {
     --yourpropfirm-surface-alt: #1a1a1a;
   }
   ```

3. **Register in `tailwind.config.js`** under `theme.extend.colors.yourpropfirm` so a utility class is generated:
   ```js
   yourpropfirm: {
     // ... existing tokens ...
     "surface-alt": "var(--yourpropfirm-surface-alt)",
   }
   ```

4. **Use in templates** with the generated class:
   ```html
   <div class="tw-bg-yourpropfirm-surface-alt">...</div>
   ```

5. **Run the build** to compile the new utility into the output CSS:
   ```sh
   npm run build
   ```

6. **Coordinate with the plugin team** — if the token should be tenant-configurable (i.e., overridden per merchant from the WP admin), the PHP side must inject the corresponding CSS variable at runtime. Static defaults defined in `:root` are sufficient for non-configurable tokens.
