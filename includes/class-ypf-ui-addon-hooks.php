<?php
/**
 * WordPress hook registrations for YourPropFirm UI Addon.
 *
 * All hook priorities must be > 9999 for template filters because the main
 * plugin's disable_other_checkout_overrides() removes every callback with
 * priority < 9999 from woocommerce_locate_template / wc_get_template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YPF_UI_Addon_Hooks {

	public static function init(): void {
		// Template override — priority 10000 beats main plugin's force_template_override at 9999.
		add_filter( 'woocommerce_locate_template', [ __CLASS__, 'override_template' ], 10000, 3 );

		// CSS override — priority 1000 runs after main plugin's enqueue_checkout_styles at 999.
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'override_styles' ], 1000 );

		// Full-page checkout wrapper override — priority 10001 beats the main
		// plugin's override_checkout_template_hierarchy at 9999. The main plugin
		// loads its own woocommerce/single-checkout.php directly via
		// template_include (bypassing woocommerce_locate_template), so we re-point
		// it to our copy to control the header (logo) and wrapper markup.
		add_filter( 'template_include', [ __CLASS__, 'override_single_checkout' ], 10001 );

		// Interactive wizard JS — priority 1001 runs after the main plugin has
		// registered its checkout scripts (window.ypfMultistep etc.).
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_wizard_script' ], 1001 );
	}

	/**
	 * Enqueue the static, JS-driven checkout wizard controller + its price catalog.
	 * Only on the checkout (not order-received).
	 */
	public static function enqueue_wizard_script(): void {
		if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		$js_path = YOURPROPFIRM_UI_ADDON_DIR . 'js/checkout-wizard.js';
		$js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : YOURPROPFIRM_UI_ADDON_VERSION;

		wp_enqueue_script(
			'yourpropfirm-ui-addon-wizard',
			YOURPROPFIRM_UI_ADDON_URL . 'js/checkout-wizard.js',
			[ 'jquery' ],
			$js_ver,
			true
		);

		// Static placeholder catalog (UI only — not wired to real products yet).
		// Price = base price for each evaluation type × account balance.
		wp_localize_script(
			'yourpropfirm-ui-addon-wizard',
			'ypfCheckoutWizard',
			[
				'currency'      => 'USD',
				'continueLabel' => __( 'Continue', 'yourpropfirm' ),
				'payLabel'      => __( 'Proceed to Payment', 'yourpropfirm' ),
				'prices'        => [
					'1-step'     => [ 5000 => 59, 10000 => 99, 25000 => 189, 50000 => 289, 100000 => 489 ],
					'2-step'     => [ 5000 => 49, 10000 => 89, 25000 => 169, 50000 => 259, 100000 => 439 ],
					'fast-track' => [ 5000 => 69, 10000 => 119, 25000 => 219, 50000 => 329, 100000 => 549 ],
				],
			]
		);
	}

	/**
	 * Re-point the checkout page wrapper to the add-on's single-checkout.php
	 * when the main plugin has resolved it to its own copy.
	 *
	 * @param string $template Resolved absolute template path.
	 * @return string
	 */
	public static function override_single_checkout( string $template ): string {
		if ( 'single-checkout.php' === basename( $template ) ) {
			$addon_template = YOURPROPFIRM_UI_ADDON_DIR . 'templates/woocommerce/single-checkout.php';
			if ( file_exists( $addon_template ) ) {
				return $addon_template;
			}
		}

		return $template;
	}

	/**
	 * Serve add-on templates for any file that exists under templates/woocommerce/.
	 * Falls back to the main plugin's template when the add-on has no override.
	 *
	 * @param string $template      Resolved absolute path from previous filters.
	 * @param string $template_name Relative name, e.g. "checkout/form-billing.php".
	 * @param string $template_path Template path hint passed by WooCommerce.
	 * @return string
	 */
	public static function override_template( string $template, string $template_name, string $template_path ): string {
		$addon_template = YOURPROPFIRM_UI_ADDON_DIR . 'templates/woocommerce/' . $template_name;

		if ( file_exists( $addon_template ) ) {
			return $addon_template;
		}

		return $template;
	}

	/**
	 * Replace the main plugin's compiled checkout stylesheet with the add-on's own.
	 * Only runs on the checkout and order-received pages.
	 */
	public static function override_styles(): void {
		if ( ! is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		// Remove main plugin CSS.
		wp_dequeue_style( 'yourpropfirm-checkout' );

		// Version the stylesheet by its file modification time so every rebuild
		// busts the browser cache automatically (no stale CSS during development,
		// and a fresh cache key on every deploy).
		$css_path = YOURPROPFIRM_UI_ADDON_DIR . 'dist/css/checkout.css';
		$css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : YOURPROPFIRM_UI_ADDON_VERSION;

		// Enqueue add-on CSS (dist/css/checkout.css — compiled Tailwind output).
		wp_enqueue_style(
			'yourpropfirm-ui-addon',
			YOURPROPFIRM_UI_ADDON_URL . 'dist/css/checkout.css',
			[],
			$css_ver
		);
	}
}
