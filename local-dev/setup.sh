#!/usr/bin/env bash
#
# One-shot setup for the local YourPropFirm UI Addon dev environment.
# Run AFTER `docker compose up -d` (and after extracting the main plugin into
# ./yourpropfirm-plugin). Safe to re-run.
#
set -euo pipefail
cd "$(dirname "$0")"

SITE_URL="${SITE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"

wp() { docker compose exec -T wpcli wp "$@"; }

echo "==> Waiting for WordPress core files…"
for i in $(seq 1 30); do
  if docker compose exec -T wpcli sh -c '[ -f /var/www/html/wp-includes/version.php ]' 2>/dev/null; then
    echo "    core present."; break
  fi
  sleep 2
done

if ! wp core is-installed 2>/dev/null; then
  echo "==> Installing WordPress…"
  wp core install \
    --url="$SITE_URL" --title="YPF Local" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" --skip-email
else
  echo "==> WordPress already installed — skipping."
fi

echo "==> WooCommerce…"
wp plugin is-installed woocommerce >/dev/null 2>&1 || wp plugin install woocommerce
wp plugin is-active woocommerce >/dev/null 2>&1 || wp plugin activate woocommerce

echo "==> Activating YourPropFirm plugins…"
wp plugin activate yourpropfirm-plugin yourpropfirm-ui-addon

echo "==> Pretty permalinks (required — /checkout/ must resolve)…"
wp rewrite structure '/%postname%/' --hard >/dev/null
wp rewrite flush --hard >/dev/null

echo "==> Force the CLASSIC checkout shortcode (the plugin's PHP templates are"
echo "    ignored by the WooCommerce block checkout)…"
CHECKOUT_ID="$(wp option get woocommerce_checkout_page_id 2>/dev/null || echo 7)"
wp post update "$CHECKOUT_ID" --post_content='[woocommerce_checkout]' >/dev/null

echo "==> Enable multi-step wizard + product selection (Carbon theme options)…"
wp option update _yourpropfirm_checkout_enable_multi_step "yes" >/dev/null
wp option update _yourpropfirm_checkout_enable_product_selection "yes" >/dev/null
wp option update _yourpropfirm_checkout_display_product_as_radio "yes" >/dev/null
wp option update _yourpropfirm_checkout_product_display_account_size "yes" >/dev/null

echo "==> Sample product (id 10) with YPF program meta…"
if ! wp post list --post_type=product --field=ID 2>/dev/null | grep -qx 10; then
  wp wc product create --name="Test Challenge" --type=simple \
    --regular_price=100 --status=publish --user="$ADMIN_USER" >/dev/null
fi
PID="$(wp post list --post_type=product --field=ID | head -1)"
wp post meta update "$PID" _yourpropfirm_selection_type "challenge" >/dev/null
wp post meta update "$PID" _yourpropfirm_program_id "local-test-program" >/dev/null
wp post meta update "$PID" _yourpropfirm_trading_options '["MT5","MT4"]' --format=json >/dev/null

echo ""
echo "✅ Done. Open the dark checkout preview:"
echo "   $SITE_URL/?add-to-cart=$PID&quantity=1&theme=dark"
echo "   Admin: $SITE_URL/wp-admin  ($ADMIN_USER / $ADMIN_PASS)"
