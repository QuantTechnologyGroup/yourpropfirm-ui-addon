<?php
/**
 * Local DEV seed — PLATFORM-as-category model (post-Ibnu alignment).
 *
 *   Parent category   = Trading Platform   (Bybit, Platform 5)   -> "Select Trading Platform"
 *   Sub-category       = Evaluation Type    (1-Step/2-Step/Fast)  -> "Select Evaluation Type"
 *   Product            = Account Size        ($5K..$100K)          -> "Select Account Balance"
 *   Currency           = per platform        (Bybit USDT, P5 USD)  -> follows the product
 *
 * Works with the EXISTING Bybit / Platform 5 categories set up in admin. Wipes
 * their eval-sub products and rebuilds both platforms identically (mirrored), so
 * switching platform only changes currency/program, not the eval/account options.
 *
 * Run: wp eval-file wp-content/plugins/yourpropfirm-ui-addon/local-dev/seed-platform-model.php
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'wc_get_product' ) ) { fwrite( STDERR, "WooCommerce not loaded\n" ); return; }
function ypf_pm_log( $m ) { echo $m . "\n"; }

global $wpdb;

/* Platforms (parent categories) + their currency/trading. Mirrored eval + sizes. */
$platforms = array(
	'Bybit'      => array( 'currency' => 'USDT', 'trading' => array( 'MT5' ) ),
	'Platform 5' => array( 'currency' => 'USD',  'trading' => array( 'MT5', 'MT4' ) ),
);
$eval_badges = array( '1-Step' => '', '2-Step' => 'Best Value', 'Fast Track' => 'Most Popular' );
$balances    = array( 100000, 50000, 25000, 10000, 5000 );
$prices      = array(
	'1-Step'     => array( 100000 => 489, 50000 => 289, 25000 => 189, 10000 => 99,  5000 => 59 ),
	'2-Step'     => array( 100000 => 439, 50000 => 259, 25000 => 169, 10000 => 89,  5000 => 49 ),
	'Fast Track' => array( 100000 => 549, 50000 => 329, 25000 => 219, 10000 => 119, 5000 => 69 ),
);

/* Best-effort program match by balance (currency comes from account meta, not the program). */
$prog_rows = $wpdb->get_results( "SELECT program_id, data FROM {$wpdb->prefix}ypf_programs LIMIT 2000" );
$prog_by_balance = array();
foreach ( $prog_rows as $r ) {
	$d = @unserialize( $r->data );
	if ( is_array( $d ) && isset( $d['initialBalance'] ) ) {
		$b = (int) $d['initialBalance'];
		if ( ! isset( $prog_by_balance[ $b ] ) ) { $prog_by_balance[ $b ] = $r->program_id; }
	}
}

$parent_ids = array();
$first_pid  = 0;
$total      = 0;

foreach ( $platforms as $platform_name => $cfg ) {
	$parent = get_term_by( 'name', $platform_name, 'product_cat' );
	if ( ! $parent ) { ypf_pm_log( "MISSING parent category: $platform_name — create it in admin first" ); continue; }
	$parent_ids[] = $parent->term_id;

	foreach ( $eval_badges as $eval_name => $badge ) {
		$sub = get_term_by( 'name', $eval_name, 'product_cat' );
		// Resolve the eval sub-cat that belongs to THIS platform parent.
		$kids = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => $parent->term_id, 'name' => $eval_name ) );
		$sub  = ! empty( $kids ) ? $kids[0] : null;
		if ( ! $sub ) { ypf_pm_log( "MISSING sub-cat $eval_name under $platform_name — create it in admin first" ); continue; }

		update_term_meta( $sub->term_id, '_ypf_term_badge', $badge );

		// Wipe existing products in this sub-cat, then rebuild.
		$existing = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids',
			'tax_query' => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $sub->term_id ) ) ) );
		foreach ( $existing as $eid ) { wp_delete_post( $eid, true ); }

		foreach ( $balances as $bal ) {
			$p = new WC_Product_Simple();
			$p->set_name( $platform_name . ' ' . $eval_name . ' $' . number_format( $bal ) );
			$p->set_status( 'publish' );
			$p->set_catalog_visibility( 'visible' );
			$p->set_regular_price( (string) $prices[ $eval_name ][ $bal ] );
			$p->set_category_ids( array( $sub->term_id ) );
			$p->update_meta_data( '_yourpropfirm_selection_type', 'challenge' );
			$p->update_meta_data( '_yourpropfirm_trading_options', $cfg['trading'] );
			$p->update_meta_data( '_yourpropfirm_account_size', (string) $bal );
			$p->update_meta_data( '_yourpropfirm_account_currency', $cfg['currency'] );
			if ( isset( $prog_by_balance[ $bal ] ) ) { $p->update_meta_data( '_yourpropfirm_program_id', $prog_by_balance[ $bal ] ); }
			$pid = $p->save();
			if ( ! $first_pid ) { $first_pid = $pid; }
			$total++;
		}
	}
}

/* Enable the PLATFORM PARENTS as the checkout selection (drill: platform -> eval -> product). */
if ( function_exists( 'carbon_set_theme_option' ) && $parent_ids ) {
	carbon_set_theme_option( 'yourpropfirm_checkout_enable_product_selection', true );
	carbon_set_theme_option( 'yourpropfirm_checkout_product_selection_categories',
		array_values( array_map( function ( $id ) { return 'term:product_cat:' . $id; }, $parent_ids ) ) );
	carbon_set_theme_option( 'yourpropfirm_checkout_display_product_as_radio', true );
	carbon_set_theme_option( 'yourpropfirm_checkout_product_display_account_size', true );
	carbon_set_theme_option( 'yourpropfirm_homepage_global_add_to_cart', $first_pid );
	carbon_set_theme_option( 'yourpropfirm_redirect_to_dashboard_on_homepage', false );
	// Level labels: 0 = platform, 1 = eval type.
	carbon_set_theme_option( 'yourpropfirm_checkout_overwrite_product_category_label', array(
		array( 'category' => 'Select Trading Platform' ),
		array( 'category' => 'Select Evaluation Type' ),
	) );
}

$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ypf_checkout_store_%' OR option_name LIKE '_transient_timeout_ypf_checkout_store_%' OR option_name LIKE '_transient_ypf_products_%'" );

ypf_pm_log( 'DONE. platform parents=' . implode( ',', $parent_ids ) . ' products=' . $total . ' seated=' . $first_pid );
