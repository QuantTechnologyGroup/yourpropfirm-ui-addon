# Sync Workflow: checkout-frontend Repo ↔ YourPropFirm Plugin

## 1. Overview

Two sync directions exist depending on the stage of work:

| Direction | When to use |
|---|---|
| **Frontend Repo → Plugin** | Normal development. Changes are authored in the frontend repo (clean git history, PR review) then pushed into the live plugin directory. |
| **Plugin → Frontend Repo** | Hotfixes. A developer edited the plugin directly (e.g., a production emergency). Changes must be pulled back into the repo so it stays the source of truth. |

The plugin path referenced throughout this document:

```
C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin
```

The frontend repo path:

```
C:\Users\ADVAN\repos\checkout-frontend
```

---

## 2. Direction 1: Frontend Repo → Plugin (most common)

Copy changed files from the repo into the plugin directory. Always run with `-DryRun` / `--dry-run` first to review what will change before committing to it.

### PowerShell

```powershell
# --- DRY RUN (review only, no files written) ---
$repo   = "C:\Users\ADVAN\repos\checkout-frontend"
$plugin = "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"

# CSS source files
Copy-Item "$repo\src\css\checkout.css"   "$plugin\public\src\css\checkout.css"   -WhatIf
Copy-Item "$repo\src\css\components.css" "$plugin\public\src\css\components.css"  -WhatIf

# Compiled CSS (if pre-built in repo)
Copy-Item "$repo\build\checkout.css"     "$plugin\public\css\checkout.css"        -WhatIf

# JS files
Copy-Item "$repo\js\checkout.js"         "$plugin\public\js\checkout.js"          -WhatIf
Copy-Item "$repo\js\dark-mode.js"        "$plugin\public\js\dark-mode.js"         -WhatIf

# WooCommerce checkout templates
Copy-Item "$repo\templates\woocommerce\checkout\*" "$plugin\woocommerce\checkout\" -WhatIf

# --- LIVE RUN (remove -WhatIf to apply) ---
Copy-Item "$repo\src\css\checkout.css"   "$plugin\public\src\css\checkout.css"
Copy-Item "$repo\src\css\components.css" "$plugin\public\src\css\components.css"
Copy-Item "$repo\build\checkout.css"     "$plugin\public\css\checkout.css"
Copy-Item "$repo\js\checkout.js"         "$plugin\public\js\checkout.js"
Copy-Item "$repo\js\dark-mode.js"        "$plugin\public\js\dark-mode.js"
Copy-Item "$repo\templates\woocommerce\checkout\*" "$plugin\woocommerce\checkout\"
```

### Bash

```bash
REPO="$HOME/repos/checkout-frontend"
PLUGIN="/c/Users/ADVAN/Local Sites/ypf/app/public/wp-content/plugins/yourpropfirm-plugin"

# --- DRY RUN ---
rsync -av --dry-run \
  "$REPO/src/css/checkout.css"   "$PLUGIN/public/src/css/checkout.css"
rsync -av --dry-run \
  "$REPO/src/css/components.css" "$PLUGIN/public/src/css/components.css"
rsync -av --dry-run \
  "$REPO/build/checkout.css"     "$PLUGIN/public/css/checkout.css"
rsync -av --dry-run \
  "$REPO/js/checkout.js"         "$PLUGIN/public/js/checkout.js"
rsync -av --dry-run \
  "$REPO/js/dark-mode.js"        "$PLUGIN/public/js/dark-mode.js"
rsync -av --dry-run \
  "$REPO/templates/woocommerce/checkout/" "$PLUGIN/woocommerce/checkout/"

# --- LIVE RUN (remove --dry-run) ---
rsync -av "$REPO/src/css/checkout.css"   "$PLUGIN/public/src/css/checkout.css"
rsync -av "$REPO/src/css/components.css" "$PLUGIN/public/src/css/components.css"
rsync -av "$REPO/build/checkout.css"     "$PLUGIN/public/css/checkout.css"
rsync -av "$REPO/js/checkout.js"         "$PLUGIN/public/js/checkout.js"
rsync -av "$REPO/js/dark-mode.js"        "$PLUGIN/public/js/dark-mode.js"
rsync -av "$REPO/templates/woocommerce/checkout/" "$PLUGIN/woocommerce/checkout/"
```

### After sync — if CSS source files changed

The compiled `public/css/checkout.css` must be regenerated from the updated source. Run the Tailwind build inside the **plugin** directory:

```powershell
Set-Location "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"
npm run build
```

Or with the watcher during active development:

```powershell
npm run dev
```

Skip the build step only when you synced a pre-compiled `build/checkout.css` from the repo and did not touch any source CSS.

---

## 3. Direction 2: Plugin → Frontend Repo (hotfixes)

Copy files back from the plugin into the repo so that hotfix changes are not lost on the next forward sync.

> **Warning: this overwrites repo files.** Commit or stash any uncommitted repo work before running these commands.

### PowerShell

```powershell
$repo   = "C:\Users\ADVAN\repos\checkout-frontend"
$plugin = "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"

# --- DRY RUN ---
Copy-Item "$plugin\public\src\css\checkout.css"   "$repo\src\css\checkout.css"   -WhatIf
Copy-Item "$plugin\public\src\css\components.css" "$repo\src\css\components.css"  -WhatIf
Copy-Item "$plugin\public\css\checkout.css"        "$repo\build\checkout.css"      -WhatIf
Copy-Item "$plugin\public\js\checkout.js"          "$repo\js\checkout.js"          -WhatIf
Copy-Item "$plugin\public\js\dark-mode.js"         "$repo\js\dark-mode.js"         -WhatIf
Copy-Item "$plugin\woocommerce\checkout\*"         "$repo\templates\woocommerce\checkout\" -WhatIf

# --- LIVE RUN ---
Copy-Item "$plugin\public\src\css\checkout.css"   "$repo\src\css\checkout.css"
Copy-Item "$plugin\public\src\css\components.css" "$repo\src\css\components.css"
Copy-Item "$plugin\public\css\checkout.css"        "$repo\build\checkout.css"
Copy-Item "$plugin\public\js\checkout.js"          "$repo\js\checkout.js"
Copy-Item "$plugin\public\js\dark-mode.js"         "$repo\js\dark-mode.js"
Copy-Item "$plugin\woocommerce\checkout\*"         "$repo\templates\woocommerce\checkout\"
```

### Bash

```bash
REPO="$HOME/repos/checkout-frontend"
PLUGIN="/c/Users/ADVAN/Local Sites/ypf/app/public/wp-content/plugins/yourpropfirm-plugin"

# --- DRY RUN ---
rsync -av --dry-run "$PLUGIN/public/src/css/"        "$REPO/src/css/"
rsync -av --dry-run "$PLUGIN/public/css/checkout.css" "$REPO/build/checkout.css"
rsync -av --dry-run "$PLUGIN/public/js/"             "$REPO/js/"
rsync -av --dry-run "$PLUGIN/woocommerce/checkout/"  "$REPO/templates/woocommerce/checkout/"

# --- LIVE RUN ---
rsync -av "$PLUGIN/public/src/css/"        "$REPO/src/css/"
rsync -av "$PLUGIN/public/css/checkout.css" "$REPO/build/checkout.css"
rsync -av "$PLUGIN/public/js/"             "$REPO/js/"
rsync -av "$PLUGIN/woocommerce/checkout/"  "$REPO/templates/woocommerce/checkout/"
```

After the live run, always review what changed:

```bash
git -C "$HOME/repos/checkout-frontend" diff
```

Or in PowerShell:

```powershell
git -C "C:\Users\ADVAN\repos\checkout-frontend" diff
```

Commit only the intentional hotfix lines; discard any noise introduced by unrelated plugin edits.

---

## 4. File Mapping Table

| Frontend Repo (`checkout-frontend/`) | Plugin (`yourpropfirm-plugin/`) | Notes |
|---|---|---|
| `src/css/checkout.css` | `public/src/css/checkout.css` | Tailwind source — CSS variables, dark-mode tokens |
| `src/css/components.css` | `public/src/css/components.css` | `@layer components` reusable utilities |
| `build/checkout.css` | `public/css/checkout.css` | Compiled output — do not edit directly |
| `js/checkout.js` | `public/js/checkout.js` | Frontend interaction logic |
| `js/dark-mode.js` | `public/js/dark-mode.js` | Theme toggle (`localStorage.theme`) |
| `templates/woocommerce/checkout/form-checkout.php` | `woocommerce/checkout/form-checkout.php` | Main checkout template |
| `templates/woocommerce/checkout/form-billing.php` | `woocommerce/checkout/form-billing.php` | Billing fields |
| `templates/woocommerce/checkout/form-product-selection.php` | `woocommerce/checkout/form-product-selection.php` | Product step |
| `templates/woocommerce/checkout/form-product-variants.php` | `woocommerce/checkout/form-product-variants.php` | Variant picker |
| `templates/woocommerce/checkout/form-available-addons.php` | `woocommerce/checkout/form-available-addons.php` | Add-ons step |
| `templates/woocommerce/checkout/form-available-bundles.php` | `woocommerce/checkout/form-available-bundles.php` | Bundles step |
| `templates/woocommerce/checkout/form-trading-platform.php` | `woocommerce/checkout/form-trading-platform.php` | Platform selection |
| `templates/woocommerce/checkout/form-verify-email.php` | `woocommerce/checkout/form-verify-email.php` | Email verification |
| `templates/woocommerce/checkout/form-coupon.php` | `woocommerce/checkout/form-coupon.php` | Coupon field |
| `templates/woocommerce/checkout/form-login.php` | `woocommerce/checkout/form-login.php` | Login prompt |
| `templates/woocommerce/checkout/form-shipping.php` | `woocommerce/checkout/form-shipping.php` | Shipping (if applicable) |
| `templates/woocommerce/checkout/form-pay.php` | `woocommerce/checkout/form-pay.php` | Pay page |
| `templates/woocommerce/checkout/payment.php` | `woocommerce/checkout/payment.php` | Payment block |
| `templates/woocommerce/checkout/payment-method.php` | `woocommerce/checkout/payment-method.php` | Single payment method |
| `templates/woocommerce/checkout/review-order.php` | `woocommerce/checkout/review-order.php` | Order review |
| `templates/woocommerce/checkout/order-received.php` | `woocommerce/checkout/order-received.php` | Thank-you page |
| `templates/woocommerce/checkout/order-receipt.php` | `woocommerce/checkout/order-receipt.php` | Receipt view |
| `templates/woocommerce/checkout/repurchase-detail.php` | `woocommerce/checkout/repurchase-detail.php` | Repurchase flow |
| `templates/woocommerce/checkout/thankyou.php` | `woocommerce/checkout/thankyou.php` | Thank-you content |
| `templates/woocommerce/checkout/terms.php` | `woocommerce/checkout/terms.php` | Terms checkbox |
| `templates/woocommerce/checkout/cart-errors.php` | `woocommerce/checkout/cart-errors.php` | Cart error messages |

---

## 5. Full Developer Workflow

1. **Fork / branch** — Create a feature branch in `checkout-frontend`:
   ```bash
   git -C ~/repos/checkout-frontend checkout -b feat/my-change
   ```

2. **Edit source files** — Work in `src/css/`, `js/`, or `templates/`. Never edit `build/` directly.

3. **Build CSS locally** (if CSS files changed) — run Tailwind inside the plugin to verify the compiled output looks correct during development:
   ```powershell
   Set-Location "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"
   npm run dev   # watch mode while iterating
   ```

4. **Dry-run sync to plugin** — preview what will change before touching the live site:
   ```powershell
   Copy-Item "C:\Users\ADVAN\repos\checkout-frontend\src\css\checkout.css" `
     "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin\public\src\css\checkout.css" `
     -WhatIf
   # ... repeat for other files
   ```

5. **Live sync to plugin** — remove `-WhatIf` / `--dry-run` from the commands in Section 2.

6. **Run npm run build in plugin** if source CSS changed (see Section 2).

7. **Verify in browser** at `http://yourpropfirm.local/checkout`. The cart auto-populates so no manual cart prep is needed.

8. **Commit repo changes**:
   ```bash
   git -C ~/repos/checkout-frontend add src/css/checkout.css js/checkout.js \
     templates/woocommerce/checkout/form-checkout.php
   git -C ~/repos/checkout-frontend commit -m "feat: describe the change"
   ```

9. **Push and open PR** against `main`:
   ```bash
   git -C ~/repos/checkout-frontend push -u origin feat/my-change
   gh pr create --base main --title "feat: describe the change"
   ```

10. **Request review** — tag the relevant maintainer. Attach screenshots if the change is visual.

---

## 6. Integrating Approved Changes Back to Plugin

After a PR is merged to `main` in the frontend repo, a maintainer applies the changes to the plugin:

1. **Pull latest main**:
   ```bash
   git -C ~/repos/checkout-frontend checkout main
   git -C ~/repos/checkout-frontend pull origin main
   ```

2. **Dry-run sync** (Section 2 commands with `-WhatIf` / `--dry-run`) — confirm only expected files appear in the diff.

3. **Live sync** — remove the dry-run flag.

4. **Build compiled CSS** in the plugin directory:
   ```powershell
   Set-Location "C:\Users\ADVAN\Local Sites\ypf\app\public\wp-content\plugins\yourpropfirm-plugin"
   npm run build
   ```

5. **Verify** at `http://yourpropfirm.local/checkout`.

6. **Commit the plugin changes** on the plugin's own branch and open a plugin PR:
   ```bash
   git -C "C:/Users/ADVAN/Local Sites/ypf/app/public/wp-content/plugins/yourpropfirm-plugin" \
     add public/src/css/checkout.css public/css/checkout.css public/js/checkout.js \
         woocommerce/checkout/
   git -C "C:/Users/ADVAN/Local Sites/ypf/app/public/wp-content/plugins/yourpropfirm-plugin" \
     commit -m "chore: sync from checkout-frontend@<short-sha>"
   ```

   Including the short SHA of the frontend repo commit in the message makes it easy to trace which PR introduced a given change.

---

## 7. Resolving Merge Conflicts

When the same file has diverged in both the repo and the plugin, use the following authority rules to decide which side wins:

| File type | Authoritative side | Reason |
|---|---|---|
| CSS source (`src/css/checkout.css`, `components.css`) | **Frontend repo** | Visual design lives in the repo; plugin copy is always derived. |
| Compiled CSS (`public/css/checkout.css`) | **Regenerate** — do not merge manually | Run `npm run build` after resolving source conflicts; the compiled file is never hand-edited. |
| JavaScript (`checkout.js`, `dark-mode.js`) | **Frontend repo** | UI behavior is owned by the frontend repo. |
| WooCommerce template PHP files | **Plugin** for WooCommerce hook / action calls; **Frontend repo** for CSS class names and HTML structure | PHP hook calls (`do_action`, `apply_filters`, WC function calls) come from the plugin and must not be overwritten by the repo. Visual class names (Tailwind `tw-*` utilities, `yourpropfirm-*` tokens) are owned by the repo. |

**Practical conflict resolution for PHP templates:**

1. Open the conflicted file in a three-way diff tool.
2. Accept the **plugin** side for any line that contains `do_action(`, `apply_filters(`, `wc_`, `WC()->`, or plugin-specific PHP logic.
3. Accept the **frontend repo** side for any line that only changes HTML attributes, Tailwind class strings, or inline styles.
4. Manually reconcile lines that mix both (e.g., a `<button>` tag whose PHP `echo` was changed and whose class was also changed).
5. After resolving, run `npm run build` in the plugin directory and verify at `http://yourpropfirm.local/checkout`.
