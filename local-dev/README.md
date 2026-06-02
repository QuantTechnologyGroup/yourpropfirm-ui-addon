# Local Dev Environment (Docker)

A self-contained WordPress + WooCommerce + YourPropFirm stack for developing
this UI add-on and previewing the checkout. Nothing here is shipped with the
plugin — it only exists to run the checkout locally.

> The whole stack runs in Docker. It does **not** touch your machine's PHP/MySQL
> and is fully disposable (`docker compose down -v` wipes it).

---

## Prerequisites

- **Docker Desktop** (running)
- **Node.js ≥ 18** + npm (only needed if you edit CSS — see the root [README](../README.md))
- **The main YourPropFirm plugin** — you must supply this yourself. It is a
  separate, proprietary plugin and is **not** included in this repo.

---

## One-time setup

```bash
# 1. Clone this add-on repo and enter the local-dev folder
cd yourpropfirm-ui-addon/local-dev

# 2. Provide the main plugin: extract the YourPropFirm plugin zip here so that
#    ./yourpropfirm-plugin/yourpropfirm.php exists
unzip /path/to/yourpropfirm-plugin.zip -d yourpropfirm-plugin

# 3. Start the stack
docker compose up -d

# 4. Install WordPress, WooCommerce, activate plugins, seed a test product
./setup.sh
```

When `setup.sh` finishes it prints the preview URL. Open it:

```
http://localhost:8080/?add-to-cart=10&quantity=1&theme=dark
```

This adds the test product to the cart **and** lands you on the checkout in
**dark mode** (the FundedBot look). Admin is at
`http://localhost:8080/wp-admin` (`admin` / `admin`).

---

## Day-to-day

| You changed… | What to do |
|---|---|
| PHP templates (`templates/`) | reload the browser |
| JavaScript (`js/`) | reload the browser |
| CSS source (`src/css/`) | `cd ../build && npm run dev` (watch) → reload |

Edits are live-mounted into the container, so saving a file is enough — no
rebuild of the container, no re-activation.

### Managing the stack
```bash
docker compose stop      # pause (keeps data)
docker compose up -d      # resume
docker compose down -v    # destroy everything, including the database
```

---

## Why these specific setup steps?

Three non-obvious things are required for the checkout to render — `setup.sh`
handles them, but if you set up by hand, you need all three:

1. **Pretty permalinks.** On default "plain" permalinks `/checkout/` does not
   resolve to the checkout page, so `is_checkout()` is false and the plugin
   redirect-loops to `/checkout/?lang`. `setup.sh` runs
   `wp rewrite structure '/%postname%/'`.
2. **No inline debug output.** The main plugin loads its textdomain early (a
   WP 6.7 `_load_textdomain_just_in_time` notice). If notices print inline they
   break header redirects. The compose file sets `WP_DEBUG_DISPLAY=false`.
3. **A product with YPF program meta.** A plain WooCommerce product isn't valid
   in the YPF checkout; it needs `_yourpropfirm_selection_type`,
   `_yourpropfirm_program_id`, `_yourpropfirm_trading_options`. `setup.sh` adds
   them to the test product.

Light vs dark: append `?theme=dark` or `?theme=light` to any checkout URL.

---

## Prompt for a teammate using Claude Code

Paste this into Claude Code from the cloned repo root:

```
Set up a local environment to preview this WordPress checkout UI add-on
(yourpropfirm-ui-addon) so I can iterate on its CSS.

Context:
- This repo IS the add-on plugin. It requires the main "YourPropFirm" plugin,
  which I have as a zip at: <PUT THE PATH TO YOUR yourpropfirm-plugin.zip HERE>
- A portable Docker setup already exists in ./local-dev (docker-compose.yml +
  setup.sh + README.md). Use it.

Do this:
1. Read local-dev/README.md.
2. Extract my main-plugin zip into local-dev/yourpropfirm-plugin/ (so that
   local-dev/yourpropfirm-plugin/yourpropfirm.php exists).
3. From local-dev/: `docker compose up -d`, then `./setup.sh`.
4. Verify the checkout renders: load
   http://localhost:8080/?add-to-cart=10&quantity=1&theme=dark and confirm it
   returns HTTP 200 with the add-on's dist/css/checkout.css enqueued and a
   dark-green themed page (not a redirect loop).
5. If it redirect-loops to /checkout/?lang, the fix is pretty permalinks —
   re-run the rewrite flush from setup.sh.

Report the final preview URL and whether dark-green styling is applied.
```
