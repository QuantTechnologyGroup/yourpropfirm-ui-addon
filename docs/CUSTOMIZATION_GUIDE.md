# Checkout Frontend — Customization Guide

A practical reference for frontend developers working on the YourPropFirm checkout UI. Read this before touching any source file.

---

## 1. Setup

### Prerequisites

- Node.js 18+
- Git

### Getting started

```bash
# Fork the repo on GitHub, then clone your fork
git clone https://github.com/<your-org>/checkout-frontend.git
cd checkout-frontend

# Install build dependencies
cd build
npm install

# Start the development watcher
npm run dev
```

The watcher compiles `src/css/checkout.css` → `dist/css/checkout.css` on every save. Keep this process running in a terminal while you work. Reload your browser after each CSS change — there is no hot-module reload.

The local development URL is `http://yourpropfirm.local/checkout`. Navigating to that path auto-populates the cart with a test product, so no manual cart setup is needed.

---

## 2. CSS Customization

### Architecture overview

The build pipeline is Tailwind 3 with the `tw-` prefix. Every utility class is emitted with `!important` to prevent conflicts with the host WordPress theme.

Source files:

| File | Purpose |
|---|---|
| `src/css/checkout.css` | Entry point; defines CSS custom properties (design tokens) |
| `src/css/components.css` | Reusable component classes in `@layer components` |

The compiled output is `dist/css/checkout.css` (also `public/css/checkout.css` in the plugin). Do not edit the compiled file directly — your changes will be overwritten on the next build.

### Design tokens

All colors, and most recurring values, are exposed as CSS custom properties prefixed `--yourpropfirm-*`. They are declared in the `:root` block for light mode and overridden in the `.dark` block for dark mode.

Available tokens include:

- `--yourpropfirm-primary`, `--yourpropfirm-primary-hover`, `--yourpropfirm-primary-light`
- `--yourpropfirm-secondary`
- `--yourpropfirm-background`, `--yourpropfirm-card`
- `--yourpropfirm-text`, `--yourpropfirm-button-text`
- `--yourpropfirm-border`, `--yourpropfirm-border-standby`
- `--yourpropfirm-success`, `--yourpropfirm-warning`, `--yourpropfirm-error`
- `--yourpropfirm-progressbar-from`, `--yourpropfirm-progressbar-to`

These tokens are also wired into `tailwind.config.js` so Tailwind utility classes resolve to the live variable. For example, `tw-bg-yourpropfirm-primary` expands to `background-color: var(--yourpropfirm-primary)`.

**Never use hardcoded hex values.** If none of the existing tokens fit, add a new one:

1. Declare it in `src/css/checkout.css` inside `:root`.
2. Add the dark-mode override in the `.dark` block.
3. Register it in `tailwind.config.js` under `theme.extend.colors.yourpropfirm`.
4. Use the new `tw-*` utility class in templates or components.

### Adding new component styles

Place reusable, multi-property component classes in `src/css/components.css` inside `@layer components`:

```css
/* src/css/components.css */
@layer components {
  .ypf-badge {
    @apply tw-inline-flex tw-items-center tw-rounded-full tw-px-3 tw-py-1;
    @apply tw-text-sm tw-font-medium;
    background-color: var(--yourpropfirm-primary-light);
    color: var(--yourpropfirm-primary);
  }
}
```

Rules:

- Use `tw-` utility classes via `@apply` wherever a single utility suffices.
- Use `var(--yourpropfirm-*)` for any color or theming value.
- Do not write one-off component classes directly in a template's inline `style` attribute.
- Do not use `@apply` with non-prefixed Tailwind names — the build will not find them.

### Dark mode

Dark mode is class-based. When `<html class="dark">` is present, the `.dark` block in `src/css/checkout.css` overrides the token values. Your component styles inherit dark variants automatically as long as they use tokens rather than hardcoded values.

If you need an explicit dark-mode variant in a component class, use the `dark:` modifier:

```css
@layer components {
  .ypf-card {
    @apply tw-bg-yourpropfirm-card tw-border tw-border-yourpropfirm-border;
    @apply dark:tw-shadow-none;
  }
}
```

---

## 3. Template Customization

### File locations

| Template type | Location |
|---|---|
| WooCommerce checkout overrides | `woocommerce/checkout/*.php` |
| Plugin-specific sections | `public/partials/checkout/*.php` |

### Safe places to add HTML

- Inside existing wrapper `<div>` elements that do not carry JS selector classes.
- As new `<section>` or `<div>` blocks between existing sections.
- As additional content inside `<label>` elements, after the existing text node.

When adding a new section, give it a descriptive BEM-style class like `ypf-order-summary__notice` so it does not collide with WooCommerce or Tailwind selectors.

### Unsafe changes — do not do these

| Change | Why it is unsafe |
|---|---|
| Removing or reordering `do_action()` calls | Plugins and WooCommerce core hook onto these actions to inject required markup, scripts, and processing logic. Removing them silently breaks payment, shipping, and validation. |
| Changing `name` attributes on form inputs | WooCommerce and payment gateways read form data by field name. Changing a name attribute causes silent data loss during checkout submission. |
| Removing or renaming classes used as JS selectors | `checkout.js` and WooCommerce's own `checkout.js` query the DOM by class name. Any class starting with `.woocommerce-`, `.input-text`, or listed in the "JavaScript" section below should be treated as a contract. |
| Editing `dist/css/checkout.css` directly | This file is generated. Any hand-edit is overwritten on the next `npm run build`. |

### Testing template changes

1. Save the PHP file.
2. If the site uses a template sync script, run it: `npm run sync` (or the project-specific command).
3. Hard-reload the browser (`Ctrl+Shift+R` / `Cmd+Shift+R`) to bypass cache.
4. Verify at both a narrow viewport (375 px) and a wide viewport (1440 px).

---

## 4. JavaScript Customization

### checkout.js

Located at `public/js/checkout.js`. Responsibilities:

- **Coupon handling** — applies and removes coupon codes via WooCommerce AJAX endpoints.
- **Auto-fill** — populates checkout fields with stored or URL-parameter values on page load.
- **AJAX interactions** — handles order review updates, payment method switching, and other `wc-ajax` calls.

DOM selectors used as contracts (do not rename these classes in templates):

- `.ypf-coupon-input`, `.ypf-coupon-apply`, `.ypf-coupon-remove`
- `.ypf-checkout-form`
- `.woocommerce-checkout` (standard WooCommerce class; keep it present on the form element)

Safe modifications:

- Adding new event listeners for elements you have introduced.
- Reading `YourPropFirmTheme` (see below) to apply a conditional style on a custom element.
- Adding new AJAX calls to plugin-specific endpoints, provided they do not conflict with existing handler function names.

Unsafe modifications:

- Removing or reordering the `init()` call sequence.
- Changing the event targets for existing coupon or auto-fill handlers.
- Bypassing or short-circuiting the `wc-ajax` calls managed by WooCommerce core.

### dark-mode.js

Located at `public/js/dark-mode.js`. Exposes a global API:

```js
// Read current theme
const current = window.YourPropFirmTheme.getTheme();
// Returns: "dark" | "light"

// Set theme programmatically
window.YourPropFirmTheme.setTheme("dark");
window.YourPropFirmTheme.setTheme("light");
```

Internally, `setTheme` toggles the `dark` class on `<html>` and writes the value to `localStorage.theme`. On page load the script reads `localStorage.theme` first, falling back to `prefers-color-scheme`.

Safe use:

- Calling `setTheme` / `getTheme` from your own code.
- Reading `localStorage.theme` to sync a custom toggle button's visual state.

Unsafe modifications:

- Changing the `localStorage` key (`theme`) — other parts of the system rely on this exact key.
- Removing the `prefers-color-scheme` fallback — this breaks first-visit experience for users who have not set a preference.
- Toggling the `dark` class directly without going through `setTheme` — this bypasses `localStorage` persistence.

---

## 5. Build Workflow

### npm run dev

```bash
cd build
npm run dev
```

Starts Tailwind's watch mode. Recompiles `src/css/checkout.css` → `dist/css/checkout.css` whenever any source file changes. Use this during active development. The process must stay running; closing the terminal stops the watcher.

Output is unminified and includes source comments. Do not deploy from a dev build.

### npm run build

```bash
cd build
npm run build
```

Produces a minified, production-ready `dist/css/checkout.css`. Run this before committing any CSS change. The command exits after a single pass — it does not watch.

### Why dist/css/checkout.css is committed

The compiled stylesheet is committed to the repository for two reasons:

1. **WordPress plugin delivery** — The plugin is deployed by copying files, not by running a build step on the server. WordPress servers do not have Node.js. The compiled file must be present in the repo for deployment to work.
2. **Staging and review** — Pull requests can be reviewed and tested without requiring reviewers to run the build locally.

Workflow rule: always run `npm run build` and stage `dist/css/checkout.css` in the same commit as the source change. A PR that modifies `src/css/checkout.css` without a corresponding change to `dist/css/checkout.css` is incomplete and should not be merged.
