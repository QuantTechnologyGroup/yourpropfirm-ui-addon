<?php
/**
 * Local DEV seed — CATEGORY-DRIVEN demo (the dev's model).
 *
 *   Product Category (level 0) = evaluation types  -> "Select Evaluation Type"
 *   Product (leaf)             = account sizes      -> "Select Account Balance"
 *
 * This is the structure the old dev expects: step 1 reads the WooCommerce
 * category hierarchy + products, NOT variation attributes. The main plugin
 * already renders this drill-down dynamically; our add-on only restyles it.
 *
 * Run: wp eval-file wp-content/plugins/yourpropfirm-ui-addon/local-dev/seed-category-demo.php
 * Idempotent: wipes its own categories' products + the variation seed first.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'wc_get_product' ) ) { fwrite( STDERR, "WooCommerce not loaded\n" ); return; }
function ypf_cat_log( $m ) { echo $m . "\n"; }

global $wpdb;

/* 1. Evaluation-type categories (these become the "Select Evaluation Type" cards) */
$evals = array(
	'1-step'     => array( 'name' => '1-Step',     'desc' => 'Single Phase Evaluation', 'badge' => '' ),
	'2-step'     => array( 'name' => '2-Step',     'desc' => 'Standard Evaluation',     'badge' => 'Best Value' ),
	'fast-track' => array( 'name' => 'Fast Track', 'desc' => 'Pay After You Pass',      'badge' => 'Most Popular' ),
);
$cat_ids = array();
foreach ( $evals as $slug => $def ) {
	$t = get_term_by( 'slug', 'fb-' . $slug, 'product_cat' );
	if ( ! $t ) {
		$ins = wp_insert_term( $def['name'], 'product_cat', array( 'slug' => 'fb-' . $slug, 'description' => $def['desc'] ) );
		$cid = is_wp_error( $ins ) ? 0 : $ins['term_id'];
	} else {
		$cid = $t->term_id;
		wp_update_term( $cid, 'product_cat', array( 'name' => $def['name'], 'description' => $def['desc'] ) );
	}
	// Badge shown on the eval-type card (rendered by our form-product-selection.php override).
	update_term_meta( $cid, '_ypf_term_badge', $def['badge'] );
	$cat_ids[ $slug ] = $cid;
}
ypf_cat_log( 'eval categories: ' . wp_json_encode( $cat_ids ) );

/* 2. Wipe previous demo products in these cats + the variation seed (#74) so it stops cluttering */
foreach ( $cat_ids as $slug => $cid ) {
	$q = new WP_Query( array( 'post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids',
		'tax_query' => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cid ) ) ) );
	foreach ( $q->posts as $pid ) { wp_delete_post( $pid, true ); }
}
$v74 = get_page_by_title( 'FundedBit Challenge', OBJECT, 'product' );
if ( $v74 ) {
	$p = wc_get_product( $v74->ID );
	if ( $p ) { foreach ( $p->get_children() as $c ) { wp_delete_post( $c, true ); } }
	wp_delete_post( $v74->ID, true );
	ypf_cat_log( 'removed variation seed product #' . $v74->ID );
}

/* 3. Account-size products under each eval category (these become the "Account Size" cards) */
$balances = array( 100000 => '$100,000', 50000 => '$50,000', 25000 => '$25,000', 10000 => '$10,000', 5000 => '$5,000' );
$prices   = array(
	'1-step'     => array( 100000 => 489, 50000 => 289, 25000 => 189, 10000 => 99,  5000 => 59 ),
	'2-step'     => array( 100000 => 439, 50000 => 259, 25000 => 169, 10000 => 89,  5000 => 49 ),
	'fast-track' => array( 100000 => 549, 50000 => 329, 25000 => 219, 10000 => 119, 5000 => 69 ),
);

// Match account sizes to real synced programs (best effort) so program data resolves.
$prog_rows = $wpdb->get_results( "SELECT program_id, data FROM {$wpdb->prefix}ypf_programs LIMIT 2000" );
$prog_by_balance = array();
foreach ( $prog_rows as $r ) {
	$d = @unserialize( $r->data );
	if ( is_array( $d ) && isset( $d['initialBalance'] ) ) {
		$b = (int) $d['initialBalance'];
		if ( ! isset( $prog_by_balance[ $b ] ) ) { $prog_by_balance[ $b ] = $r->program_id; }
	}
}

$first_pid = 0;
$count = 0;
foreach ( $cat_ids as $slug => $cid ) {
	if ( ! $cid ) { continue; }
	foreach ( $balances as $bal => $label ) {
		$p = new WC_Product_Simple();
		$p->set_name( $evals[ $slug ]['name'] . ' ' . $label );
		$p->set_status( 'publish' );
		$p->set_catalog_visibility( 'visible' );
		$p->set_regular_price( (string) $prices[ $slug ][ $bal ] );
		$p->set_category_ids( array( $cid ) );
		$p->update_meta_data( '_yourpropfirm_selection_type', 'challenge' );
		$p->update_meta_data( '_yourpropfirm_trading_options', array( 'MT5', 'MT4' ) );
		$p->update_meta_data( '_yourpropfirm_account_size', (string) $bal );
		$p->update_meta_data( '_yourpropfirm_account_currency', 'USD' );
		if ( isset( $prog_by_balance[ $bal ] ) ) { $p->update_meta_data( '_yourpropfirm_program_id', $prog_by_balance[ $bal ] ); }
		$pid = $p->save();
		if ( ! $first_pid ) { $first_pid = $pid; }
		$count++;
	}
}
ypf_cat_log( "products created: $count, first=$first_pid" );

/* 4. Point checkout at these categories + display as cards; seat the first product */
if ( function_exists( 'carbon_set_theme_option' ) ) {
	carbon_set_theme_option( 'yourpropfirm_checkout_enable_product_selection', true );
	carbon_set_theme_option( 'yourpropfirm_checkout_product_selection_categories',
		array_values( array_map( function ( $cid ) { return 'term:product_cat:' . $cid; }, $cat_ids ) ) );
	carbon_set_theme_option( 'yourpropfirm_checkout_display_product_as_radio', true );
	carbon_set_theme_option( 'yourpropfirm_checkout_product_display_account_size', true );
	carbon_set_theme_option( 'yourpropfirm_homepage_global_add_to_cart', $first_pid );
	carbon_set_theme_option( 'yourpropfirm_redirect_to_dashboard_on_homepage', false );
	// Per-level label: level 0 -> "Select Evaluation Type" (complex field; may need admin save to persist).
	carbon_set_theme_option( 'yourpropfirm_checkout_overwrite_product_category_label',
		array( array( 'category' => 'Select Evaluation Type' ) ) );
}

/* 5. Clear the cached checkout store so it rebuilds with the new categories/products */
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ypf_checkout_store_%' OR option_name LIKE '_transient_timeout_ypf_checkout_store_%' OR option_name LIKE '_transient_ypf_products_%'" );

ypf_cat_log( 'DONE. eval cats=' . implode( ',', $cat_ids ) . ' products=' . $count . ' seated=' . $first_pid );
