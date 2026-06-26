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

		// Config for the wizard controller. The order summary is driven by the
		// real window.ypfCheckoutStore (no static price matrix); this just carries
		// labels + the admin-ajax URL used for the real coupon endpoint.
		//
		// `maps` carries the data the main plugin's JS STRIPS when it re-renders the
		// category-model selection: the eval sub-categories' description + badge, and
		// every product's ACCOUNT currency/size (the store only knows the WC/store
		// currency). checkout-wizard.js re-applies these after each re-render.
		// Translated category level labels (e.g. "Select Trading Platform" /
		// "Select Evaluation Type"). The main plugin re-renders the sub-category
		// heading from its RAW store label on a platform switch, so the wizard JS
		// re-applies these to keep the heading translated.
		$ypf_level_labels = [];
		$ypf_raw_labels   = function_exists( 'carbon_get_theme_option' ) ? carbon_get_theme_option( 'yourpropfirm_checkout_overwrite_product_category_label' ) : null;
		if ( is_array( $ypf_raw_labels ) ) {
			foreach ( $ypf_raw_labels as $ypf_i => $ypf_entry ) {
				if ( ! empty( $ypf_entry['category'] ) ) {
					$ypf_level_labels[ (string) $ypf_i ] = __( $ypf_entry['category'], 'yourpropfirm-ui-addon' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				}
			}
		}

		wp_localize_script(
			'yourpropfirm-ui-addon-wizard',
			'ypfCheckoutWizard',
			[
				'currency'          => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
				'continueLabel'     => __( 'Continue', 'yourpropfirm-ui-addon' ),
				'payLabel'          => __( 'Proceed to Payment', 'yourpropfirm-ui-addon' ),
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'maps'              => self::build_selection_maps(),
				'levelLabels'       => $ypf_level_labels,
				'applyLabel'        => __( 'Apply', 'yourpropfirm-ui-addon' ),
				'applyingLabel'     => __( 'Applying…', 'yourpropfirm-ui-addon' ),
				'enterCouponMsg'    => __( 'Please enter a coupon code.', 'yourpropfirm-ui-addon' ),
				'couponApplied'     => __( 'Coupon applied.', 'yourpropfirm-ui-addon' ),
				'couponAppliedMsg'  => __( 'Coupon applied successfully!', 'yourpropfirm-ui-addon' ),
				'invalidCoupon'     => __( 'Invalid coupon code.', 'yourpropfirm-ui-addon' ),
				'couponError'       => __( 'Error applying coupon. Please try again.', 'yourpropfirm-ui-addon' ),
				'couponErrorPrefix' => __( 'Error applying coupon: ', 'yourpropfirm-ui-addon' ),
				'pasteNotAllowed'   => __( 'Paste is not allowed in Email Confirmation field for security.', 'yourpropfirm-ui-addon' ),
				'emailMismatch'     => __( 'Email does not match', 'yourpropfirm-ui-addon' ),
			]
		);

		// NOTE: plugin 1.15 removed checkout-multistep.js (and window.ypfMultistep).
		// The add-on now owns the step engine + Next-button labels in
		// js/checkout-wizard.js, so there is no plugin handle to prime here.
	}

	/**
	 * Normalize an account currency code to its DISPLAY code.
	 *
	 * Business decision (CEO): the program API reports Bybit accounts in USDT,
	 * but they are presented to the customer as plain USD ("$50,000"), matching
	 * the USD platforms (e.g. Platform 5) and the WC product titles (already
	 * "$50,000 - 1-Step"). USDT (and the other USD-pegged stablecoins) are
	 * therefore mapped to USD for DISPLAY only — the underlying
	 * `_yourpropfirm_account_currency` meta and the program API are untouched.
	 *
	 * The stablecoin set is filterable via `ypf_ui_addon_usd_stablecoins`.
	 *
	 * @param string $currency Raw account currency code (e.g. USD, USDT, USDC).
	 * @return string Display currency code (USD for USD-pegged stablecoins).
	 */
	public static function display_currency_code( string $currency ): string {
		$code = strtoupper( trim( $currency ) );

		/** Filter the set of currency codes shown to the customer as USD. */
		$usd_stablecoins = apply_filters(
			'ypf_ui_addon_usd_stablecoins',
			[ 'USDT', 'USDC', 'BUSD', 'DAI', 'TUSD', 'USDP', 'FDUSD' ]
		);

		return in_array( $code, (array) $usd_stablecoins, true ) ? 'USD' : $code;
	}

	/**
	 * Format an account BALANCE in the product's account currency.
	 *
	 * The platform model needs the balance shown in the account currency, not
	 * the WC/store currency the main plugin uses. The currency is first
	 * normalized through display_currency_code() (so USD-pegged stablecoins like
	 * USDT render as "$50,000" per the CEO decision). Known fiat currencies then
	 * render with their symbol ($5,000); any remaining unknown code renders as a
	 * suffix (5,000 XYZ) so it is never mislabelled with a "$".
	 *
	 * Single source of truth for BOTH the initial server render
	 * (form-product-selection.php) and the JS re-render (checkout-wizard.js), so
	 * the balance never changes shape between them.
	 *
	 * @param mixed  $size     Numeric account size.
	 * @param string $currency Account currency code (e.g. USD, USDT).
	 * @return string
	 */
	public static function account_label( $size, string $currency ): string {
		if ( '' === (string) $size || ! is_numeric( $size ) ) {
			return '';
		}
		$size = floatval( $size );
		$code = self::display_currency_code( (string) $currency );

		$known = [
			'USD' => '$',
			'EUR' => '€',
			'GBP' => '£',
			'JPY' => '¥',
			'AUD' => 'A$',
			'CAD' => 'C$',
		];

		if ( isset( $known[ $code ] ) ) {
			return $known[ $code ] . number_format( $size );
		}
		if ( '' === $code ) {
			return number_format( $size );
		}
		return number_format( $size ) . ' ' . $code;
	}

	/**
	 * Resolve the bundled logo for a level-0 Trading Platform category.
	 *
	 * Name-keyed (sanitize_title) so it works across environments where the term
	 * IDs differ (local Bybit=31, staging ByBit=126). Returns null when the
	 * platform has no bundled logo. Otherwise:
	 *   wordmark   bool        the logo IS the platform name (Bybit) and replaces
	 *                          the text; false => an icon shown ALONGSIDE the text
	 *                          (Platform 5).
	 *   hide_name  bool        hide the text name (true for wordmarks).
	 *   dark_url   string      logo for dark theme (the default / base).
	 *   light_url  string|null a designer-provided light-theme variant; null =>
	 *                          light theme reuses dark_url (CSS keeps it visible).
	 *
	 * Filterable via `ypf_ui_addon_platform_logos`.
	 *
	 * @param string $name Platform category name.
	 * @return array{wordmark:bool,hide_name:bool,dark_url:string,light_url:?string}|null
	 */
	public static function platform_logo_config( string $name ): ?array {
		$slug    = sanitize_title( $name );
		$img_url = YOURPROPFIRM_UI_ADDON_URL . 'assets/images/';
		$img_dir = YOURPROPFIRM_UI_ADDON_DIR . 'assets/images/';

		$map = apply_filters(
			'ypf_ui_addon_platform_logos',
			[
				// Bybit wordmark, per-theme (both keep the amber "i"): white
				// letters on dark, dark letters on light.
				'bybit'      => [
					'wordmark'  => true,
					'hide_name' => true,
					'dark'      => 'Bybit_dark.png',
					'light'     => 'Bybit_light.png',
				],
				// Platform 5: a full-colour icon that reads on both themes, shown
				// next to the kept "Platform 5" label.
				'platform-5' => [
					'wordmark'  => false,
					'hide_name' => false,
					'dark'      => 'platform_5.png',
					'light'     => 'platform_5.png',
				],
			]
		);

		if ( empty( $map[ $slug ] ) ) {
			return null;
		}
		$cfg  = $map[ $slug ];
		$dark = $cfg['dark'] ?? '';
		if ( '' === $dark || ! file_exists( $img_dir . $dark ) ) {
			return null;
		}
		$light     = $cfg['light'] ?? '';
		$has_light = ( '' !== $light && file_exists( $img_dir . $light ) );

		return [
			'wordmark'  => ! empty( $cfg['wordmark'] ),
			'hide_name' => ! empty( $cfg['hide_name'] ),
			'dark_url'  => $img_url . $dark,
			'light_url' => $has_light ? ( $img_url . $light ) : null,
		];
	}

	/**
	 * Build the category + product meta maps the wizard JS re-applies after the
	 * main plugin re-renders the category-model selection.
	 *
	 * Sourced from the enabled "product selection" categories (the platform
	 * parents) and all their descendant terms + products — so it is data-driven,
	 * not tied to any specific seed.
	 *
	 * @return array{categories: array<string, array>, products: array<string, array>}
	 */
	private static function build_selection_maps(): array {
		$out = [ 'categories' => [], 'products' => [] ];

		if ( ! function_exists( 'carbon_get_theme_option' ) || ! taxonomy_exists( 'product_cat' ) ) {
			return $out;
		}

		// Resolve the enabled selection categories to term IDs. Carbon's association
		// field stores each entry as an array
		// ( ['value'=>'term:product_cat:31','type'=>'term','subtype'=>'product_cat','id'=>'31'] );
		// older/simpler configs may store a bare "term:product_cat:31" string or a
		// numeric ID. Handle all three.
		$raw        = carbon_get_theme_option( 'yourpropfirm_checkout_product_selection_categories' );
		$parent_ids = [];
		if ( is_array( $raw ) ) {
			foreach ( $raw as $entry ) {
				if ( is_array( $entry ) ) {
					if ( isset( $entry['id'] ) && is_numeric( $entry['id'] ) ) {
						$parent_ids[] = (int) $entry['id'];
					} elseif ( ! empty( $entry['value'] ) && preg_match( '/(\d+)\s*$/', (string) $entry['value'], $m ) ) {
						$parent_ids[] = (int) $m[1];
					}
				} elseif ( is_numeric( $entry ) ) {
					$parent_ids[] = (int) $entry;
				} elseif ( is_string( $entry ) && preg_match( '/(\d+)\s*$/', $entry, $m ) ) {
					$parent_ids[] = (int) $m[1];
				}
			}
		}
		$parent_ids = array_values( array_unique( array_filter( $parent_ids ) ) );
		if ( empty( $parent_ids ) ) {
			return $out;
		}

		// Collect parents + all descendant terms.
		$term_ids = $parent_ids;
		foreach ( $parent_ids as $pid ) {
			$kids = get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'child_of'   => $pid,
				'fields'     => 'ids',
			] );
			if ( is_array( $kids ) ) {
				$term_ids = array_merge( $term_ids, array_map( 'intval', $kids ) );
			}
		}
		$term_ids = array_values( array_unique( $term_ids ) );

		foreach ( $term_ids as $tid ) {
			$term = get_term( $tid, 'product_cat' );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
			$out['categories'][ (string) $tid ] = [
				'name'        => $term->name,
				'description' => $term->description,
				'badge'       => (string) get_term_meta( $tid, '_ypf_term_badge', true ),
			];
		}

		// Products in any of those terms → account size + currency + formatted label.
		$product_ids = get_posts( [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => [
				[
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $term_ids,
				],
			],
		] );
		foreach ( $product_ids as $product_id ) {
			$size = get_post_meta( $product_id, '_yourpropfirm_account_size', true );
			$cur  = get_post_meta( $product_id, '_yourpropfirm_account_currency', true );
			$out['products'][ (string) $product_id ] = [
				'accountSize'     => (string) $size,
				// Display code (USDT -> USD) so the summary Currency row and the
				// pill data-attr the JS reads both show USD. account_label()
				// normalizes internally, so the label already matches.
				'accountCurrency' => self::display_currency_code( (string) $cur ),
				'accountLabel'    => self::account_label( $size, (string) $cur ),
			];
		}

		return $out;
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
