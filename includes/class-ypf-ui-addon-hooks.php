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

		// Enqueue add-on CSS (dist/css/checkout.css — compiled Tailwind output).
		wp_enqueue_style(
			'yourpropfirm-ui-addon',
			YOURPROPFIRM_UI_ADDON_URL . 'dist/css/checkout.css',
			[],
			YOURPROPFIRM_UI_ADDON_VERSION
		);
	}
}
