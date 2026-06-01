# YourPropFirm Checkout Frontend

This repository contains the **UI layer** for the YourPropFirm WooCommerce checkout experience. It is not a standalone application and does not include any backend logic, REST endpoints, payment processing, or WordPress/WooCommerce internals. Everything here — CSS, JavaScript, and PHP templates — is consumed by the [yourpropfirm-plugin](https://github.com/yourpropfirm/yourpropfirm-plugin) at runtime.

If you are a client frontend developer, this is the correct starting point. You do not need to touch the plugin's PHP business logic to iterate on the checkout UI.

---

## What's Included

| Path | Description |
|---|---|
| `src/css/` | Tailwind 3 source files (`checkout.css`, `components.css`) — edit these |
| `dist/css/` | Compiled output (`checkout.css`) — committed to the plugin, do not edit directly |
| `js/` | Frontend scripts (`checkout.js`, `dark-mode.js`) |
| `templates/` | WooCommerce checkout PHP template overrides |
| `build/` | Node.js build tooling (`package.json`, `tailwind.config.js`, PostCSS config) |
| `scripts/` | Sync helpers (`sync-to-plugin.ps1`, `sync-to-plugin.sh`) |
| `docs/` | Extended reference docs (`CSS_VARIABLES.md`, `TEMPLATE_REFERENCE.md`, `SYNC_WORKFLOW.md`) |

---

## Prerequisites

- **Node.js** >= 18
- **npm** >= 9
- **WordPress + WooCommerce + YourPropFirm plugin** running locally (see [Getting Started](#getting-started))
- **Git**

The recommended local WordPress environment is [Local by Flywheel](https://localwp.com/). The default checkout URL used throughout this project is `http://yourpropfirm.local/checkout` (plain HTTP — Local does not require HTTPS by default).

---

## Getting Started

1. Fork this repository on GitHub, then clone your fork:
   ```bash
   git clone https://github.com/<your-username>/checkout-frontend.git
   ```

2. Move into the build directory and install dependencies:
   ```bash
   cd checkout-frontend/build && npm install
   ```

3. Start the CSS watcher. Tailwind will recompile `dist/css/checkout.css` on every save:
   ```bash
   npm run dev
   ```

4. Edit source files in `src/css/`, `js/`, or `templates/` as needed.

5. Sync your changes into the running WordPress plugin so the browser reflects them:
   ```bash
   # PowerShell (Windows)
   ../scripts/sync-to-plugin.ps1

   # bash (macOS / Linux / WSL)
   ../scripts/sync-to-plugin.sh
   ```
   See [Sync Scripts](#sync-scripts) for configuration options.

6. Open `http://yourpropfirm.local/checkout` in your browser to test. The cart is auto-populated with a test product on every visit — no manual cart setup is required.

7. Before opening a pull request, produce a production-minified build:
   ```bash
   npm run build
   ```

8. Submit a pull request from your fork. See [Submitting a Pull Request](#submitting-a-pull-request) for the required checklist.

---

## CSS Architecture

### Tailwind 3 with `tw-` prefix

All Tailwind utility classes are prefixed with `tw-` (e.g., `tw-flex`, `tw-bg-yourpropfirm-primary`). Every utility has `!important` applied automatically to prevent conflicts with the host WordPress theme. This is configured in `build/tailwind.config.js` and is not negotiable — do not remove these settings.

### Dark mode

Dark mode is driven by a `dark` class on the `<html>` element, not by `prefers-color-scheme` alone. The toggle is managed by `js/dark-mode.js` and respects `localStorage.theme` with `prefers-color-scheme` as a fallback.

To style dark mode variants in source CSS:
```css
/* in src/css/checkout.css or a component file */
.dark .my-component {
  background-color: var(--yourpropfirm-card);
}
```

Tailwind dark-mode utilities are also available via the `dark:` variant (e.g., `dark:tw-bg-yourpropfirm-card`).

### Editing source CSS

- **`src/css/checkout.css`** — main entry point; contains `:root` CSS variable declarations, Tailwind directives, and page-level overrides.
- **`src/css/components.css`** — shared reusable component utilities. Add new component classes here inside an `@layer components { }` block.

Never edit `dist/css/checkout.css` directly. It is generated output.

### Hardcoded hex values

**Never hardcode hex colors.** Always use an existing `--yourpropfirm-*` CSS variable or map to a Tailwind token. See [Design Tokens](#design-tokens).

---

## Design Tokens

CSS variables are declared in `src/css/checkout.css` under `:root` (light theme) and `.dark` (dark theme overrides). The same names are wired into `tailwind.config.js` so they are available as Tailwind utility classes.

| Variable | Usage |
|---|---|
| `--yourpropfirm-primary` | Brand accent color, primary buttons |
| `--yourpropfirm-primary-hover` | Hover state for primary buttons |
| `--yourpropfirm-primary-light` | Subtle tint backgrounds, highlights |
| `--yourpropfirm-secondary` | Secondary actions, labels |
| `--yourpropfirm-background` | Page / outer background |
| `--yourpropfirm-card` | Card and panel surface color |
| `--yourpropfirm-text` | Default body text |
| `--yourpropfirm-button-text` | Text color on filled buttons |
| `--yourpropfirm-border` | Default border color |
| `--yourpropfirm-success` | Success states, confirmations |
| `--yourpropfirm-warning` | Warning states |
| `--yourpropfirm-error` | Error / destructive states |
| `--yourpropfirm-border-standby` | Inactive / disabled border |
| `--yourpropfirm-progressbar-from` | Progress bar gradient start |
| `--yourpropfirm-progressbar-to` | Progress bar gradient end |

For the full variable listing, default values, and guidance on adding new tokens, see [docs/CSS_VARIABLES.md](docs/CSS_VARIABLES.md).

---

## JavaScript

### `js/checkout.js`

Handles all interactive checkout behavior:

- **Coupon logic** — applies and removes WooCommerce coupon codes via AJAX without a full page reload.
- **Auto-fill** — pre-populates checkout fields from known user data on page load.
- **AJAX requests** — communicates with the plugin back end using WordPress `wp_ajax_*` actions. Do not rename these action strings.

**Global PHP objects available to this script:**

| Object | Description |
|---|---|
| `yourpropfirm_purchase` | Localized data about the current purchase (product, pricing, user context) |
| `ypfCheckoutStore` | Reactive store for checkout state (selected bundle, coupon status, etc.) |

Both objects are injected via `wp_localize_script` in the plugin. Their structure is documented in the plugin source — do not redefine or shadow them from frontend JS.

### `js/dark-mode.js`

Manages theme switching. Exposes a public API at `window.YourPropFirmTheme`:

```js
window.YourPropFirmTheme.toggle()   // flip between light and dark
window.YourPropFirmTheme.setDark()  // force dark
window.YourPropFirmTheme.setLight() // force light
window.YourPropFirmTheme.current()  // returns 'dark' | 'light'
```

Preference is persisted to `localStorage.theme`. On first visit, `prefers-color-scheme` is read as the default.

---

## PHP Templates

The `templates/` directory contains WooCommerce checkout template overrides. These follow WooCommerce's standard override convention: files here take precedence over WooCommerce core templates at the matching path.

Plugin-specific sections (bundles, add-ons, progress bar, etc.) live in `templates/partials/checkout/` and are included from the main checkout template.

For a full list of available template files, the hooks they expose, and the PHP variables injected into each, see [docs/TEMPLATE_REFERENCE.md](docs/TEMPLATE_REFERENCE.md).

---

## What You Can and Cannot Change

### SAFE to modify

- Colors, spacing, typography, and border styles via CSS variables or Tailwind utilities
- HTML markup structure within template files (adding/removing elements, reordering sections)
- Dark mode selectors (`.dark .element { }`)
- Visible user-facing text, provided you wrap it in a WordPress translation function (`__()`, `_e()`, `esc_html__()`, etc.)
- New `@layer components` entries in `src/css/components.css`
- New CSS variable tokens (add to `:root` and `.dark` in `src/css/checkout.css`, then extend `tailwind.config.js`)

### DO NOT change

- **WooCommerce action and filter hooks** (`do_action('woocommerce_checkout_*')`, `apply_filters(...)`) — removing or reordering these breaks payment processing and order submission
- **CSS class selectors referenced by JavaScript** — if `checkout.js` targets a class, renaming that class in CSS/HTML will silently break behavior
- **`data-sub-section` attributes** — used by the AJAX reload mechanism to patch DOM fragments; renaming them causes partial-page updates to fail
- **AJAX action names** — string literals like `ypf_apply_coupon` are registered server-side; changing them client-side breaks the request routing
- **Global JS object names** (`yourpropfirm_purchase`, `ypfCheckoutStore`) — these are injected by PHP and consumed across multiple scripts; do not shadow or reassign them

---

## Sync Scripts

After editing source files, use the sync scripts to copy compiled output and templates into your local WordPress plugin directory.

### PowerShell (Windows)

```powershell
# Default — uses the path configured inside the script
.\scripts\sync-to-plugin.ps1

# Override plugin path at runtime
.\scripts\sync-to-plugin.ps1 -PluginPath "C:\Users\YourName\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"
```

### bash (macOS / Linux / WSL)

```bash
# Default
./scripts/sync-to-plugin.sh

# Override plugin path at runtime
PLUGIN_PATH="/Users/yourname/Local Sites/yourpropfirm/app/public/wp-content/plugins/yourpropfirm-plugin" ./scripts/sync-to-plugin.sh
```

Both scripts copy:
- `dist/css/checkout.css` → plugin `public/css/`
- `js/` → plugin `public/js/`
- `templates/` → plugin `woocommerce/` and `public/partials/`

For advanced usage (watch + auto-sync, dry-run mode, rsync options), see [docs/SYNC_WORKFLOW.md](docs/SYNC_WORKFLOW.md).

---

## Submitting a Pull Request

1. Create a feature branch from `main`:
   ```bash
   git checkout -b feat/your-description
   ```

2. Make your changes, keeping commits focused and descriptive.

3. Run a production build before pushing:
   ```bash
   cd build && npm run build
   ```
   The compiled `dist/css/checkout.css` must be committed alongside your source changes.

4. Push your branch and open a pull request against `main`.

### PR checklist

Your PR description must include:

- [ ] A clear summary of what changed and why
- [ ] Screenshots showing the affected UI in both **light mode** and **dark mode**
- [ ] The WooCommerce version you tested against (e.g., WooCommerce 8.x)
- [ ] Confirmation that `npm run build` completed without errors and the compiled CSS is committed

PRs that are missing screenshots or that include uncommitted source-only changes (no compiled output) will be returned for revision.

---

## License

GPL-2.0+

This project is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html), consistent with WordPress and WooCommerce licensing requirements.
