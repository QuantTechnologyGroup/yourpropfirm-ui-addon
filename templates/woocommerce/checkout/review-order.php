<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;

$post_data = [
	"addon" => [],
	"trading_platform" => ""
];

// Check if post_data exists and parse it safely
if ( isset( $_POST['post_data'] ) ) {
	parse_str( $_POST['post_data'], $post_data );
}

$program_data = yourpropfirm_get_dashboard_program_by_product();
$trading_platform = "";

// Resolve selected variant attributes for variable products
$ypf_variant_attribute_rows = array();
$cart_item_data = yourpropfirm_get_first_cart_item_data();
if ( $cart_item_data && $cart_item_data['is_variable'] && $cart_item_data['variation_id'] && $cart_item_data['variation'] ) {
	$variation_attributes = $cart_item_data['variation']->get_attributes();
	// Use parent product's attribute order to ensure consistent ordering
	$parent_attributes = $cart_item_data['product']->get_attributes();
	foreach ( $parent_attributes as $attr_name => $attr_obj ) {
		if ( ! $attr_obj->get_variation() ) {
			continue;
		}
		$attr_slug = isset( $variation_attributes[ $attr_name ] ) ? $variation_attributes[ $attr_name ] : '';
		if ( empty( $attr_slug ) ) {
			continue;
		}
		// Get human-readable attribute label
		if ( taxonomy_exists( $attr_name ) ) {
			$attr_label = wc_attribute_label( $attr_name );
			$term = get_term_by( 'slug', $attr_slug, $attr_name );
			$attr_value = $term ? $term->name : $attr_slug;
		} else {
			$attr_label = $attr_name;
			$attr_value = $attr_slug;
		}
		$ypf_variant_attribute_rows[] = array(
			'label' => $attr_label,
			'value' => $attr_value,
		);
	}
}

if ( isset( $post_data['trading_platform'] ) && ! empty( $post_data['trading_platform'] ) ) {
	$trading_platform = $post_data['trading_platform'];

	if ( isset( yourpropfirm_get_trading_platforms()[ $trading_platform ] ) ) {
		$trading_platform = yourpropfirm_get_trading_platforms()[ $trading_platform ];
	}
}

$data_order_array = [];

$first_item = yourpropfirm_get_first_item();

// Build dynamic category levels for review order
$review_category_levels = array();
$overwrite_labels_raw = carbon_get_theme_option( 'yourpropfirm_checkout_overwrite_product_category_label' );
$category_level_labels = array();
if ( is_array( $overwrite_labels_raw ) ) {
	foreach ( $overwrite_labels_raw as $index => $label_entry ) {
		if ( ! empty( $label_entry['category'] ) ) {
			$category_level_labels[ $index ] = $label_entry['category'];
		}
	}
}

$default_product_id = yourpropfirm_get_first_product_in_cart();
if ( $default_product_id ) {
	$categories = yourpropfirm_get_product_categories();
	$cart_categories = yourpropfirm_get_cart_product_categories();
	$default_category = null;

	if ( ! empty( $cart_categories ) ) {
		$default_category = $cart_categories[0];
	} else {
		// Product not directly assigned to a root enabled category,
		// check if it belongs to any enabled category's subtree.
		$product_cat_ids = wc_get_product_cat_ids( $default_product_id );
		foreach ( array_keys( $categories ) as $enabled_cat_id ) {
			foreach ( $product_cat_ids as $pcat_id ) {
				if ( (int) $pcat_id === (int) $enabled_cat_id || term_is_ancestor_of( $enabled_cat_id, $pcat_id, 'product_cat' ) ) {
					$default_category = $enabled_cat_id;
					break 2;
				}
			}
		}
	}

	if ( null !== $default_category ) {
		$leaf_data = yourpropfirm_get_product_category_path( $default_product_id, $default_category );
		$category_path = $leaf_data['path'];

		foreach ( $category_path as $level => $cat_id ) {
			$term = get_term( $cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$label = ! empty( $category_level_labels[ $level ] ) ? $category_level_labels[ $level ] : __( 'Product Category', 'yourpropfirm' );
				$review_category_levels[] = array(
					'label' => $label,
					'value' => $term->name,
				);
			}
		}
	}
}

// Get cart items and check if renewal
$cart_items = WC()->cart->get_cart();
$first_cart_item = reset( $cart_items );
$is_renewal = isset( $first_cart_item['subscription_renewal'] );
?>
<div class="order-review-section woocommerce-checkout-review-order-table">
	<div class="order-review-container">
		<hr class="separator">

		<?php if ( true !== yourpropfirm_has_reset_product() ) : ?>
			<?php if ( $first_item ) : ?>
				<!-- Product Name Row -->
				<div class="order-row hide-if-reset-product hide-if-renewal-subscription">
					<span class="order-label"><?php _e( 'Product Name', 'yourpropfirm' ); ?></span>
					<span class="order-value">
						<?php
						if ( $cart_item_data && $cart_item_data['is_variable'] && $cart_item_data['variation'] ) {
							echo esc_html( yourpropfirm_get_product_display_name( $cart_item_data['variation'] ) );
						} else {
							echo esc_html( yourpropfirm_get_product_display_name( $first_item ) );
						}
						?>
					</span>
				</div>
			<?php endif; ?>
			<?php foreach ( $ypf_variant_attribute_rows as $attr_row ) : ?>
				<div class="order-row hide-if-reset-product hide-if-renewal-subscription hide-if-competition">
					<span class="order-label"><?php echo esc_html( $attr_row['label'] ); ?></span>
					<span class="order-value">
						<?php echo esc_html( $attr_row['value'] ); ?>
					</span>
				</div>
			<?php endforeach; ?>
			<?php foreach ( $review_category_levels as $cat_level ) : ?>
				<div class="order-row hide-if-reset-product hide-if-renewal-subscription hide-if-competition">
					<span class="order-label"><?php echo esc_html( $cat_level['label'] ); ?></span>
					<span class="order-value">
						<?php echo esc_html( $cat_level['value'] ); ?>
					</span>
				</div>
			<?php endforeach; ?>

			<?php if ( $program_data && ! empty( $program_data->data ) ) : ?>
				<div class="order-row hide-if-reset-product hide-if-renewal-subscription hide-if-competition">
					<span class="order-label"><?php _e( 'Account Size', 'yourpropfirm' ); ?></span>
					<span class="order-value">
						<?php echo yourpropfirm_get_currency_symbol( $program_data->data['currency'] ) . number_format( $program_data->data['initialBalance'] ); ?>
					</span>
				</div>
			<?php endif; ?>
			<div class="order-row selected-platform hide-if-reset-product hide-if-renewal-subscription hide-if-competition">
				<span class="order-label"><?php _e( 'Trading Platform', 'yourpropfirm' ); ?></span>
				<span class="order-value" id="selected-platform">
					<?php echo esc_html( $trading_platform ); ?>
				</span>
			</div>
		<?php endif; ?>

		<!-- Currency Row -->
		<div class="order-row">
			<span class="order-label"><?php _e( 'Currency', 'yourpropfirm' ); ?></span>
			<span class="order-value"><?php echo get_woocommerce_currency(); ?></span>
		</div>

		<hr class="separator">

		<!-- Base Price (Subtotal before addons) -->
		<?php
		// Get product from cart
		$product = yourpropfirm_get_first_item();

		$base_price = 0;
		$item_to_renew = null;

		if ( $is_renewal ) {
			// For renewal orders, use the price from subscription
			$subscription_id = $first_cart_item['subscription_renewal']['subscription_id'] ?? false;

			if ( $subscription_id && function_exists( 'wcs_get_subscription' ) ) {
				$subscription = wcs_get_subscription( $subscription_id );

				if ( $subscription ) {
					$subscription_items = $subscription->get_items( 'line_item' );
					$line_item_id = $first_cart_item['subscription_renewal']['line_item_id'] ?? false;

					// Find matching subscription item
					if ( $line_item_id && isset( $subscription_items[ $line_item_id ] ) ) {
						$item_to_renew = $subscription_items[ $line_item_id ];
					} else {
						// Fallback: match by product ID
						foreach ( $subscription_items as $sub_item ) {
							if ( $sub_item->get_product_id() === $first_cart_item['product_id'] ) {
								$item_to_renew = $sub_item;
								break;
							}
						}
					}

					if ( $item_to_renew ) {
						// Get base price from subscription item's custom meta
						$custom_line_item_meta = $item_to_renew->get_meta( '_custom_line_item_meta', true );

						if ( is_array( $custom_line_item_meta ) && isset( $custom_line_item_meta['_base_price'] ) ) {
							$base_price = floatval( $custom_line_item_meta['_base_price'] );
						} else {
							// Fallback: use subtotal from subscription
							$base_price = floatval( $item_to_renew->get_subtotal() );
						}

						// Log renewal pricing data for debugging
						do_action(
							'inspect',
							[
								'review_order_renewal_pricing_' . uniqid(),
								[
									'subscription_id' => $subscription_id,
									'line_item_id' => $line_item_id,
									'base_price' => $base_price,
									'custom_line_item_meta' => $custom_line_item_meta,
									'subtotal' => $item_to_renew->get_subtotal(),
								]
							]
						);
					}
				}
			}
		} else {
			// For new orders, calculate base price normally
			// Get trading platform
			$trading_platform_session = \WC()->session->get( 'trading_platform', '' );

			// Get product price (sale price if exists, otherwise regular price)
			$product_price = $product->get_price();

			// Get trading platform custom price
			$custom_trading_price = yourpropfirm_get_custom_trading_price( $product );
			// Determine base price:
			// 1. If trading platform has custom price → use that
			// 2. Otherwise → use product price (sale price if exists, otherwise regular price)
			$base_price = $product_price;
			if ( ! empty( $trading_platform_session ) && isset( $custom_trading_price[ $trading_platform_session ] ) && ! empty( $custom_trading_price[ $trading_platform_session ] ) ) {
				$base_price = $custom_trading_price[ $trading_platform_session ];
			}
		}
		?>

		<div class="order-row hide-if-trial-product">
			<span class="order-label">
				<?php
				_e( 'Base Price', 'yourpropfirm' );
				echo esc_html( yourpropfirm_get_subscription_period_suffix( $product ) );
				?>
			</span>
			<span class="order-value">
				<?php echo wc_price( $base_price ); ?>
			</span>
		</div>

		<!-- Display selected addons and their fees -->
		<?php
		$addons = [];
		$addon_data = yourpropfirm_get_addons_data();
		$total_addon_fee = 0;

		// For renewal orders, get addons from subscription meta
		if ( $is_renewal && isset( $item_to_renew ) ) {
			$custom_line_item_meta = $item_to_renew->get_meta( '_custom_line_item_meta', true );

			if ( is_array( $custom_line_item_meta ) && isset( $custom_line_item_meta['_addons'] ) ) {
				$addons = $custom_line_item_meta['_addons'];
			}
		} else {
			// For new orders, get addons from post data
			$addons = isset( $post_data['addon'] ) && is_array( $post_data['addon'] ) ? $post_data['addon'] : [];
		}

		// Build addons array for data_order_array
		$addons_array = [];
		if ( ! empty( $addons ) ) :
			foreach ( $addons as $addon ) :
				if ( ! isset( $addon_data[ $addon ] ) ) {
					continue;
				}

				$selected_addon = $addon_data[ $addon ];
				$addon_fee = ( $base_price * $selected_addon['addons_fee'] ) / 100;
				$total_addon_fee += $addon_fee;

				// Store addon data in array
				$addons_array[] = [
					'key' => $addon,
					'title' => $selected_addon['title'],
					'percentage' => $selected_addon['addons_fee'],
					'fee' => $addon_fee,
				];
				?>
				<div class="order-row">
					<span class="order-label">
						<?php echo esc_html( $selected_addon['title'] ); ?>
						<span class="addon-percentage">(+<?php echo esc_html( $selected_addon['addons_fee'] ); ?>%)</span>
					</span>
					<span class="order-value">
						<?php echo wc_price( $addon_fee ); ?>
					</span>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<hr class="separator">

		<!-- Sub Total (Base Price + Addons) -->
		<div class="order-row hide-if-trial-product">
			<span class="order-label">
				<?php
				_e( 'Sub Total', 'yourpropfirm' );
				echo esc_html( yourpropfirm_get_subscription_period_suffix( $product ) );
				?>
			</span>
			<span class="order-value order-subtotal-amount">
				<?php wc_cart_totals_subtotal_html(); ?>
			</span>
		</div>

		<!-- Display other fees if any (excluding addon fees as they are now included in product price) -->
		<?php
		$fees = WC()->cart->get_fees();
		$total_fee = 0;
		$fees_array = [];
		if ( ! empty( $fees ) ) :
			?>
			<hr class="separator">
			<?php foreach ( $fees as $fee ) :
				// Store fee data in array
				$fees_array[] = [
					'name' => $fee->name,
					'amount' => $fee->amount,
				];
				?>
				<div class="order-row">
					<span class="order-label"><?php echo esc_html( $fee->name ); ?></span>
					<span class="order-value">
						<?php echo wc_price( $fee->amount ); ?>
					</span>
				</div>
				<?php
				$total_fee += $fee->amount;
			endforeach;
			?>

		<?php endif; ?>

		<?php
		// Build coupons array for data_order_array
		$coupons_array = [];
		if ( WC()->cart->get_discount_total() > 0 ) :
			// Get all applied coupons
			$coupons = WC()->cart->get_coupons();
			foreach ( $coupons as $code => $coupon ) :
				// Store coupon data in array
				$coupons_array[] = [
					'code' => $coupon->get_code(),
					'discount_type' => $coupon->get_discount_type(),
					'discount_amount' => WC()->cart->get_coupon_discount_amount( $code ),
				];
				?>
				<!-- Discount Row -->
				<div class="order-row hide-if-trial-product">
					<span class="order-label">
						<?php _e( 'Discount', 'yourpropfirm' ); ?>
						<span class="coupon-code">(<?php echo esc_html( $coupon->get_code() ); ?>)</span>
					</span>
					<span class="order-discount-value">
						<?php wc_cart_totals_coupon_html( $code ); ?></span>
				</div>
			<?php endforeach; ?>

			<!-- Total discount -->
			<div class="order-row hide-if-trial-product">
				<span class="order-label"><?php _e( 'Total Discount', 'yourpropfirm' );
				?></span>
				<span class="order-discount-value">
					<?php echo wc_price( WC()->cart->get_discount_total() ); ?>
				</span>
			</div>
		<?php endif; ?>

		<?php
		// Build the complete data order array
		$data_order_array = [
			// Product Information
			'product' => [
				'id' => $product->get_id(),
				'name' => yourpropfirm_get_product_display_name( $product ),
				'type' => $product->get_type(),
			],
			// Program Data
			'program' => [
				'product_category' => isset( $program_data->product_category->name ) ? $program_data->product_category->name : '',
				'product_name' => isset( $program_data->product_name ) ? $program_data->product_name : '',
				'account_size' => isset( $program_data->data['initialBalance'] ) ? $program_data->data['initialBalance'] : 0,
				'currency_symbol' => isset( $program_data->data['currency'] ) ? yourpropfirm_get_currency_symbol( $program_data->data['currency'] ) : '',
				'currency' => isset( $program_data->data['currency'] ) ? $program_data->data['currency'] : '',
			],
			// Trading Platform
			'trading_platform' => $trading_platform,
			// Currency
			'currency' => get_woocommerce_currency(),
			// Pricing
			'pricing' => [
				'base_price' => $base_price,
				'total_addon_fee' => $total_addon_fee,
				'subtotal' => WC()->cart->get_subtotal(),
				'total_fee' => $total_fee,
				'discount_total' => WC()->cart->get_discount_total(),
				'total' => WC()->cart->get_total( 'edit' ),
			],
			// Addons
			'addons' => $addons_array ?? [],
			// Fees
			'fees' => $fees_array ?? [],
			// Coupons
			'coupons' => $coupons_array ?? [],
			// Is Renewal
			'is_renewal' => $is_renewal,
			// Subscription Period Suffix
			'subscription_period_suffix' => yourpropfirm_get_subscription_period_suffix( $product ),
			// Cart Items Count
			'cart_items_count' => WC()->cart->get_cart_contents_count(),
			// Payment Method
			'payment_method' => [
				'id' => WC()->session->get( 'chosen_payment_method', '' ),
				'title' => '',
			],
		];

		// Get payment method title
		$chosen_payment_method = $data_order_array['payment_method']['id'];
		if ( ! empty( $chosen_payment_method ) ) {
			$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
			if ( isset( $available_gateways[ $chosen_payment_method ] ) ) {
				$data_order_array['payment_method']['title'] = $available_gateways[ $chosen_payment_method ]->get_title();
			}
		}

		/**
		 * Filter the order data array in review order
		 *
		 * @since 1.0.0
		 * @param array $data_order_array The order data array
		 * @param WC_Product $product The product object
		 * @param object $program_data The program data object
		 */
		$data_order_array = apply_filters( 'yourpropfirm_review_order_data_array', $data_order_array, $product, $program_data );
		?>

		<!-- Total Row -->
		<div id="order-row-grand-total" class="order-row grand-total hide-if-trial-product"
			data-order='<?php echo json_encode( $data_order_array ); ?>'>
			<span class="order-label">
				<?php
				_e( 'Total', 'yourpropfirm' );
				echo esc_html( yourpropfirm_get_subscription_period_suffix( $product ) );
				?>
			</span>
			<span class="order-total-amount">
				<!-- calculate total with fees if any -->
				<?php echo wc_cart_totals_order_total_html() ?>
			</span>
		</div>
	</div>
</div>