<?php
/**
 * Local DEV seed — builds a FUNDEDBIT-shaped variable product so the real
 * `ypfCheckoutStore` drives the FUNDEDBIT checkout UI. This is LOCAL TEST DATA
 * only (WooCommerce posts + term meta + Carbon options) — it does NOT modify
 * the main plugin. Run inside the wpcli container:
 *
 *   wp eval-file wp-content/plugins/yourpropfirm-ui-addon/local-dev/seed-fundedbit-product.php
 *
 * Idempotent-ish: re-running trashes the previous "FundedBit Challenge" product
 * + its variations first, then rebuilds.
 *
 * Mapping (matches the FUNDEDBIT design):
 *   Select Evaluation Type  -> pa_evaluation variation attr (badge + subtitle)
 *   Select Account Balance  -> pa_account_size variation attr (plain pills)
 *   Select Trading Platform -> product _yourpropfirm_trading_options
 *   Price / program         -> per-variation _price + _yourpropfirm_program_id
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'wc_create_attribute' ) ) { fwrite( STDERR, "WooCommerce not loaded\n" ); return; }

function ypf_seed_log( $m ) { echo $m . "\n"; }

/* ---------------------------------------------------------------------------
 * 1. Global attributes (pa_evaluation, pa_account_size) + register this request
 * ------------------------------------------------------------------------- */
$attr_defs = array(
	'evaluation'   => 'Evaluation Type',
	'account_size' => 'Account Size',
);
foreach ( $attr_defs as $slug => $label ) {
	$tax = 'pa_' . $slug;
	if ( ! wc_attribute_taxonomy_id_by_name( $tax ) ) {
		$res = wc_create_attribute( array(
			'name'         => $label,
			'slug'         => $slug,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		) );
		if ( is_wp_error( $res ) ) { ypf_seed_log( "ERR create attr $slug: " . $res->get_error_message() ); return; }
		ypf_seed_log( "attribute created: $tax (id $res)" );
	} else {
		ypf_seed_log( "attribute exists: $tax" );
	}
	// Register the taxonomy for THIS request so we can insert terms immediately.
	if ( ! taxonomy_exists( $tax ) ) {
		register_taxonomy( $tax, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
	}
}

/* ---------------------------------------------------------------------------
 * 2. Terms — eval types (badge + subtitle) and account sizes
 * ------------------------------------------------------------------------- */
$eval_terms = array(
	'1-step'     => array( 'name' => '1-Step',     'desc' => 'Single Phase Evaluation', 'badge' => '' ),
	'2-step'     => array( 'name' => '2-Step',     'desc' => 'Standard Evaluation',     'badge' => 'Best Value' ),
	'fast-track' => array( 'name' => 'Fast Track', 'desc' => 'Pay After You Pass',      'badge' => 'Most Popular' ),
);
$size_terms = array(
	'5000'   => '$5,000',
	'10000'  => '$10,000',
	'25000'  => '$25,000',
	'50000'  => '$50,000',
	'100000' => '$100,000',
);

$eval_term_ids = array();
foreach ( $eval_terms as $slug => $def ) {
	$term = get_term_by( 'slug', $slug, 'pa_evaluation' );
	if ( ! $term ) {
		$ins = wp_insert_term( $def['name'], 'pa_evaluation', array( 'slug' => $slug, 'description' => $def['desc'] ) );
		if ( is_wp_error( $ins ) ) { ypf_seed_log( "ERR term $slug: " . $ins->get_error_message() ); return; }
		$term_id = $ins['term_id'];
	} else {
		$term_id = $term->term_id;
		wp_update_term( $term_id, 'pa_evaluation', array( 'name' => $def['name'], 'description' => $def['desc'] ) );
	}
	// Badge term meta — the store + form-product-variants.php read it via
	// carbon_get_term_meta('ypf_term_badge'), which maps to the underscore-prefixed
	// term meta. Write it directly (carbon_set_term_meta did not persist in eval).
	update_term_meta( $term_id, '_ypf_term_badge', $def['badge'] );
	$eval_term_ids[ $slug ] = $term_id;
}
ypf_seed_log( 'eval terms: ' . wp_json_encode( $eval_term_ids ) );

$size_term_ids = array();
foreach ( $size_terms as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'pa_account_size' );
	if ( ! $term ) {
		$ins = wp_insert_term( $name, 'pa_account_size', array( 'slug' => $slug ) );
		if ( is_wp_error( $ins ) ) { ypf_seed_log( "ERR size $slug: " . $ins->get_error_message() ); return; }
		$size_term_ids[ $slug ] = $ins['term_id'];
	} else {
		$size_term_ids[ $slug ] = $term->term_id;
	}
}
ypf_seed_log( 'size terms: ' . wp_json_encode( $size_term_ids ) );

/* ---------------------------------------------------------------------------
 * 3. Dedicated product category (so the store has one clean root)
 * ------------------------------------------------------------------------- */
$cat = get_term_by( 'slug', 'fundedbit-challenge', 'product_cat' );
if ( ! $cat ) {
	$ins = wp_insert_term( 'FundedBit Challenge', 'product_cat', array( 'slug' => 'fundedbit-challenge' ) );
	$cat_id = is_wp_error( $ins ) ? 0 : $ins['term_id'];
} else {
	$cat_id = $cat->term_id;
}
ypf_seed_log( "category id: $cat_id" );

/* ---------------------------------------------------------------------------
 * 4. Match each account size to a real synced program (best effort)
 * ------------------------------------------------------------------------- */
global $wpdb;
$prog_rows = $wpdb->get_results( "SELECT program_id, data FROM {$wpdb->prefix}ypf_programs LIMIT 2000" );
$prog_by_balance = array();
foreach ( $prog_rows as $r ) {
	$d = @unserialize( $r->data );
	if ( is_array( $d ) && isset( $d['initialBalance'] ) ) {
		$bal = (int) $d['initialBalance'];
		if ( ! isset( $prog_by_balance[ $bal ] ) ) { $prog_by_balance[ $bal ] = $r->program_id; }
	}
}
ypf_seed_log( 'programs matched by balance: ' . wp_json_encode( array_intersect_key( $prog_by_balance, array_flip( array_map( 'intval', array_keys( $size_terms ) ) ) ) ) );
$default_program = $prog_by_balance[5000] ?? ( $prog_rows[0]->program_id ?? '' );

/* ---------------------------------------------------------------------------
 * 5. (Re)create the variable product
 * ------------------------------------------------------------------------- */
$existing = get_page_by_title( 'FundedBit Challenge', OBJECT, 'product' );
if ( $existing ) {
	$old = wc_get_product( $existing->ID );
	if ( $old ) { foreach ( $old->get_children() as $cid ) { wp_delete_post( $cid, true ); } }
	wp_delete_post( $existing->ID, true );
	ypf_seed_log( "removed previous product #{$existing->ID}" );
}

$platforms = array( 'MT5', 'MT4' );

$product = new WC_Product_Variable();
$product->set_name( 'FundedBit Challenge' );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
if ( $cat_id ) { $product->set_category_ids( array( $cat_id ) ); }

$a_eval = new WC_Product_Attribute();
$a_eval->set_id( wc_attribute_taxonomy_id_by_name( 'pa_evaluation' ) );
$a_eval->set_name( 'pa_evaluation' );
$a_eval->set_options( array_values( $eval_term_ids ) );
$a_eval->set_visible( true );
$a_eval->set_variation( true );

$a_size = new WC_Product_Attribute();
$a_size->set_id( wc_attribute_taxonomy_id_by_name( 'pa_account_size' ) );
$a_size->set_name( 'pa_account_size' );
$a_size->set_options( array_values( $size_term_ids ) );
$a_size->set_visible( true );
$a_size->set_variation( true );

$product->set_attributes( array( $a_eval, $a_size ) );
$product->update_meta_data( '_yourpropfirm_selection_type', 'challenge' );
$product->update_meta_data( '_yourpropfirm_trading_options', $platforms );
$product->update_meta_data( '_yourpropfirm_program_id', $default_program );
$product->update_meta_data( '_yourpropfirm_account_currency', 'USD' );
$product_id = $product->save();
ypf_seed_log( "variable product id: $product_id" );

/* ---------------------------------------------------------------------------
 * 6. Variations (eval x size) with price + matched program
 * ------------------------------------------------------------------------- */
$prices = array(
	'1-step'     => array( 5000 => 59, 10000 => 99,  25000 => 189, 50000 => 289, 100000 => 489 ),
	'2-step'     => array( 5000 => 49, 10000 => 89,  25000 => 169, 50000 => 259, 100000 => 439 ),
	'fast-track' => array( 5000 => 69, 10000 => 119, 25000 => 219, 50000 => 329, 100000 => 549 ),
);
$count = 0;
foreach ( $eval_term_ids as $eval_slug => $eid ) {
	foreach ( $size_term_ids as $size_slug => $sid ) {
		$v = new WC_Product_Variation();
		$v->set_parent_id( $product_id );
		$v->set_status( 'publish' );
		$v->set_attributes( array(
			'pa_evaluation'   => $eval_slug,
			'pa_account_size' => $size_slug,
		) );
		$v->set_regular_price( (string) $prices[ $eval_slug ][ (int) $size_slug ] );
		$v->update_meta_data( '_yourpropfirm_program_id', $prog_by_balance[ (int) $size_slug ] ?? $default_program );
		$v->update_meta_data( '_yourpropfirm_trading_options', $platforms );
		$v->save();
		$count++;
	}
}
ypf_seed_log( "variations created: $count" );

/* ---------------------------------------------------------------------------
 * 7. Point checkout product-selection at this one category + display settings
 * ------------------------------------------------------------------------- */
if ( function_exists( 'carbon_set_theme_option' ) && $cat_id ) {
	carbon_set_theme_option( 'yourpropfirm_checkout_product_selection_categories', array( 'term:product_cat:' . $cat_id ) );
	carbon_set_theme_option( 'yourpropfirm_checkout_display_product_as_radio', true );
	carbon_set_theme_option( 'yourpropfirm_checkout_product_display_account_size', true );
	// Auto-seat this product so visiting the homepage lands on a populated
	// checkout (the variation cards only render when a variable product is in
	// the cart). No more manual ?add-to-cart= juggling for reviewers.
	carbon_set_theme_option( 'yourpropfirm_homepage_global_add_to_cart', $product_id );
	carbon_set_theme_option( 'yourpropfirm_redirect_to_dashboard_on_homepage', false );
}

// Clear the checkout store transient so it rebuilds with the new product.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ypf_checkout_store_%' OR option_name LIKE '_transient_timeout_ypf_checkout_store_%'" );

ypf_seed_log( 'DONE. product_id=' . $product_id . ' category=' . $cat_id . ' variations=' . $count );
