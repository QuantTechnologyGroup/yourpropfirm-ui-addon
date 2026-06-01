<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;

$dashboard_url = carbon_get_theme_option( 'yourpropfirm_connection_dashboard_url' );
$current_language = function_exists( 'yourpropfirm_current_language' ) ? yourpropfirm_current_language() : 'en';

$echo_dashboard_url = $dashboard_url;
// remove http:// or https:// from the URL and remove last slash
if ( strpos( $echo_dashboard_url, 'http://' ) === 0 ) {
	$echo_dashboard_url = substr( $echo_dashboard_url, 7 );
} elseif ( strpos( $echo_dashboard_url, 'https://' ) === 0 ) {
	$echo_dashboard_url = substr( $echo_dashboard_url, 8 );
}
if ( substr( $echo_dashboard_url, -1 ) === '/' ) {
	$echo_dashboard_url = substr( $echo_dashboard_url, 0, -1 );
}

$dashboard_url = add_query_arg( array(
	'lang' => $current_language,
), $dashboard_url );

// Check if order is free trial
$is_free_trial = false;
if ( $order ) {
	$is_free_trial = get_post_meta( $order->get_id(), '_yourpropfirm_order_is_free_trial', true ) === 'yes';
}

// Add body class for styling
add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'thankyou-body';
	return $classes;
} );

$support_email = carbon_get_theme_option( 'yourpropfirm_email_support' );
?>

<div class="yourpropfirm-thankyou woocommerce-order">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-failed">
				<h1 class="thank-you-title"><?php esc_html_e( 'Order failed', 'yourpropfirm' ); ?></h1>
				<p><?php esc_html_e( "We weren't able to complete your order.", 'yourpropfirm' ); ?></p>
				<p class="information">
					<?php
					printf(
						__( 'If you believe this was a mistake, please contact <a href="mailto:%s">%s</a>', 'yourpropfirm' ),
						$support_email,
						$support_email
					); ?>
				</p>
			</div>

		<?php else : ?>

			<div class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
				<h1 class="thank-you-title"><?php esc_html_e( 'Thank you!', 'yourpropfirm' ); ?></h1>
				<p><?php esc_html_e( 'Your order has been received.', 'yourpropfirm' ); ?></p>
			</div>

			<div class="yourpropfirm-dashboard">
				<h2 class="tw-text-center paragraph tw-font-medium"><?php esc_html_e( "What's next?", 'yourpropfirm' ); ?></h2>
				<p class="tw-text-center caption-1 tw-font-medium !tw-mt-2">
					<span class="hide-on-iframe">
						<?php esc_html_e( 'Head to your accounts page to see your new trading account.', 'yourpropfirm' ); ?>
					</span>
					<span class="show-on-iframe">
						<?php esc_html_e( 'Head to your dashboard to access your account and begin trading.', 'yourpropfirm' ); ?>
					</span>
					<br />
					<span class="yourpropfirm-dashboard-info hide-on-iframe">
						<?php

						printf(
							__( 'A credentials for your trading platform will be available in the selected account dashboard. If you experience any issues, please contact <a style="text-decoration: underline;" href="mailto:%s">%s</a>.', 'yourpropfirm' ),
							$support_email,
							$support_email
						);
						?>
					</span>

					<span class="yourpropfirm-dashboard-info show-on-iframe">
						<?php
						printf(
							__( 'A credentials for your trading platform will be available in your dashboard. If you experience any issues, please contact <a style="text-decoration: underline;" href="mailto:%s">%s</a>.', 'yourpropfirm' ),
							$support_email,
							$support_email
						);
						?>
					</span>
				</p>
				<div class="yourpropfirm-dashboard-button">
					<!-- Hide in iframe mode -->
					<a href="<?php echo esc_url( $dashboard_url ); ?>" class="button" data-sub-section="Dashboard">
						<?php esc_html_e( 'Continue to Dashboard', 'yourpropfirm' ); ?>
					</a>
					<!-- Hide in iframe mode -->
					<p class="yourpropfirm-dashboard-or">
						<?php esc_html_e( 'or visit', 'yourpropfirm' ); ?>
						<a href="<?php echo esc_url( $dashboard_url ); ?>" data-sub-section="Dashboard">
							<?php echo esc_url( $echo_dashboard_url ); ?>
						</a>
					</p>
				</div>
			</div>
		<?php endif; ?>

		<div class="thankyou-grid">

			<!-- Order Overview -->
			<div class="thankyou-order-overview thankyou-order-card">
				<h2 class="thankyou-order-card-header"><?php esc_html_e( 'Order summary', 'yourpropfirm' ); ?></h2>
				<div class="thankyou-order-overview-holder">
					<div class="thankyou-order-overview-item">
						<span class="thankyou-order-overview-label"><?php esc_html_e( 'Status', 'yourpropfirm' ); ?></span>
						<span class="thankyou-order-overview-value">
							<?php if ( $order->has_status( [ 'on-hold', 'pending' ] ) ) : ?>
								<span class="thankyou-status-badge pending">
									<?php esc_html_e( 'Pending', 'yourpropfirm' ); ?>
								</span>
							<?php elseif ( $order->has_status( [ 'processing', 'completed' ] ) ) : ?>
								<span class="thankyou-status-badge completed">
									<?php esc_html_e( 'Completed', 'yourpropfirm' ); ?>
								</span>
							<?php elseif ( $order->has_status( 'failed' ) ) : ?>
								<span class="thankyou-status-badge failed">
									<?php esc_html_e( 'Failed', 'yourpropfirm' ); ?>
								</span>
							<?php endif; ?>

						</span>
					</div>

					<div class="thankyou-order-overview-item">
						<span
							class="thankyou-order-overview-label"><?php esc_html_e( 'Order number', 'yourpropfirm' ); ?></span>
						<span
							class="thankyou-order-overview-value"><?php echo esc_html( $order->get_order_number() ); ?></span>
					</div>

					<div class="thankyou-order-overview-item">
						<span class="thankyou-order-overview-label"><?php esc_html_e( 'Email', 'yourpropfirm' ); ?></span>
						<span
							class="thankyou-order-overview-value"><?php echo esc_html( $order->get_billing_email() ); ?></span>
					</div>

					<div class="thankyou-order-overview-item">
						<span class="thankyou-order-overview-label"><?php esc_html_e( 'Date', 'yourpropfirm' ); ?></span>
						<span
							class="thankyou-order-overview-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd F Y' ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="thankyou-order-billing-container">
				<?php if ( ! $is_free_trial ) : ?>
					<!-- Order Summary Card -->
					<div class="thankyou-order-summary thankyou-order-card">
						<h2 class="thankyou-order-card-header">
							<?php esc_html_e( 'Order details', 'yourpropfirm' ); ?>
						</h2>

						<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
							<?php
							// Get pricing breakdown from order item meta
							$base_price = $item->get_meta( '_base_price', true );
							$trading_platform = $item->get_meta( '_trading_platform', true );
							$addons_breakdown = $item->get_meta( '_addons_breakdown', true );

							// Resolve variant attributes for variable products
							$ypf_thankyou_variant_rows = array();
							$variation_id = $item->get_variation_id();
							if ( $variation_id ) {
								$variation_product = $item->get_product();
								$parent_product = wc_get_product( $item->get_product_id() );
								if ( $variation_product && $parent_product && $parent_product->is_type( 'variable' ) ) {
									$variation_attributes = $variation_product->get_attributes();
									$parent_attributes = $parent_product->get_attributes();
									foreach ( $parent_attributes as $attr_name => $attr_obj ) {
										if ( ! $attr_obj->get_variation() ) {
											continue;
										}
										$attr_slug = isset( $variation_attributes[ $attr_name ] ) ? $variation_attributes[ $attr_name ] : '';
										if ( empty( $attr_slug ) ) {
											continue;
										}
										if ( taxonomy_exists( $attr_name ) ) {
											$attr_label = wc_attribute_label( $attr_name );
											$term = get_term_by( 'slug', $attr_slug, $attr_name );
											$attr_value = $term ? $term->name : $attr_slug;
										} else {
											$attr_label = $attr_name;
											$attr_value = $attr_slug;
										}
										$ypf_thankyou_variant_rows[] = array(
											'label' => $attr_label,
											'value' => $attr_value,
										);
									}
								}
							}
							?>

							<!-- Product Name -->
							<div class="thankyou-order-details-row">
								<span class="thankyou-order-details-label">
									<?php
									$_ypf_display_name = yourpropfirm_get_product_display_name( $item->get_product() ) ?: $item->get_name();
									echo esc_html( $_ypf_display_name );
									?>
									<?php if ( $item->get_quantity() > 1 ) : ?>
										<span class="quantity"> × <?php echo esc_html( $item->get_quantity() ); ?></span>
									<?php endif; ?>
								</span>
								<span
									class="thankyou-order-details-value"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></span>
							</div>

							<!-- Variant Attributes -->
							<?php foreach ( $ypf_thankyou_variant_rows as $attr_row ) : ?>
								<div class="thankyou-order-details-row" style="padding-left: 20px; font-size: 0.9em; color: #666;">
									<span class="thankyou-order-details-label"><?php echo esc_html( $attr_row['label'] ); ?></span>
									<span class="thankyou-order-details-value"><?php echo esc_html( $attr_row['value'] ); ?></span>
								</div>
							<?php endforeach; ?>

							<!-- Base Price -->
							<?php if ( ! empty( $base_price ) ) : ?>
								<div class="thankyou-order-details-row" style="padding-left: 20px; font-size: 0.9em; color: #666;">
									<span class="thankyou-order-details-label">
										<?php esc_html_e( 'Base Price', 'yourpropfirm' ); ?>
										<?php if ( ! empty( $trading_platform ) ) : ?>
											<span style="font-size: 0.85em;">(<?php echo esc_html( $trading_platform ); ?>)</span>
										<?php endif; ?>
									</span>
									<span class="thankyou-order-details-value"><?php
									$order_currency = $order->get_currency();
									$format_args = yourpropfirm_get_currency_format_args( $order_currency );
									$format_args['currency'] = $order_currency;
									echo wc_price( $base_price, $format_args );
									?></span>
								</div>
							<?php endif; ?>

							<!-- Addons Breakdown -->
							<?php if ( ! empty( $addons_breakdown ) && is_array( $addons_breakdown ) ) : ?>
								<?php foreach ( $addons_breakdown as $addon ) : ?>
									<div class="thankyou-order-details-row" style="padding-left: 20px; font-size: 0.9em; color: #666;">
										<span class="thankyou-order-details-label">
											<?php echo esc_html( $addon['title'] ); ?>
											<span style="font-size: 0.85em;">(+<?php echo esc_html( $addon['percentage'] ); ?>%)</span>
										</span>
										<span class="thankyou-order-details-value"><?php
										$order_currency = $order->get_currency();
										$format_args = yourpropfirm_get_currency_format_args( $order_currency );
										$format_args['currency'] = $order_currency;
										echo wc_price( $addon['amount'], $format_args );
										?></span>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>

						<?php endforeach; ?>

						<div class="thankyou-order-details-row">
							<span class="thankyou-order-details-label"><?php esc_html_e( 'Subtotal', 'yourpropfirm' ); ?></span>
							<span
								class="thankyou-order-details-value"><?php echo wp_kses_post( $order->get_subtotal_to_display() ); ?></span>
						</div>

						<?php
						// Display order fees if any
						$fees = $order->get_fees();

						if ( ! empty( $fees ) ) :
							foreach ( $fees as $fee ) : ?>
								<div class="thankyou-order-details-row">
									<span class="thankyou-order-details-label"><?php echo esc_html( $fee->get_name() ); ?></span>
									<span class="thankyou-order-details-value"><?php
									$order_currency = $order->get_currency();
									$format_args = yourpropfirm_get_currency_format_args( $order_currency );
									$format_args['currency'] = $order_currency;
									echo wc_price( $fee->get_amount(), $format_args );
									?></span>
								</div>
							<?php endforeach;
						endif;
						?>

						<?php if ( $order->get_payment_method_title() ) : ?>
							<div class="thankyou-order-details-row">
								<span
									class="thankyou-order-details-label"><?php esc_html_e( 'Payment method', 'yourpropfirm' ); ?></span>
								<span
									class="thankyou-order-details-value"><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></span>
							</div>
						<?php endif; ?>

						<?php
						// if has coupon applied, display coupon code
						$coupons = $order->get_items( 'coupon' );
						if ( ! empty( $coupons ) ) :
							do_action(

								"qm/info",
								[
									"coupons" => $coupons,
								]
							);
							foreach ( $coupons as $coupon ) : ?>
								<div class="thankyou-order-details-row">
									<span class="thankyou-order-details-label">
										<?php esc_html_e( 'Coupon', 'yourpropfirm' ); ?>
										<span class="coupon-code">(<?php echo esc_html( $coupon->get_code() ); ?>)</span>
									</span>
									<span class="thankyou-order-details-value">
										-<?php
										$order_currency = $order->get_currency();
										$format_args = yourpropfirm_get_currency_format_args( $order_currency );
										$format_args['currency'] = $order_currency;
										echo wc_price( $coupon->get_discount(), $format_args );
										?>
									</span>
								</div>
							<?php endforeach;
						endif;
						?>


						<div class="thankyou-order-details-row">
							<span class="thankyou-order-details-label">
								<?php
								esc_html_e( 'Total', 'yourpropfirm' );
								// Get first product from order items
								foreach ( $order->get_items() as $item ) {
									$product = $item->get_product();
									echo esc_html( yourpropfirm_get_subscription_period_suffix( $product ) );
									break; // Only check first item
								}
								?>
							</span>
							<span
								class="thankyou-order-details-value thankyou-total-amount"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<!-- Billing Address Card -->
				<div class="thankyou-billing-address thankyou-order-card">
					<h2 class="thankyou-order-card-header">
						<?php esc_html_e( 'Billing address', 'yourpropfirm' ); ?>
					</h2>

					<?php if ( $order->get_formatted_billing_address() ) : ?>
						<div class="billing-details">
							<div class="thankyou-billing-icon">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path
										d="M16.6654 8.33317C16.6654 12.494 12.0495 16.8273 10.4995 18.1657C10.3551 18.2743 10.1794 18.333 9.9987 18.333C9.81803 18.333 9.64226 18.2743 9.49786 18.1657C7.94786 16.8273 3.33203 12.494 3.33203 8.33317C3.33203 6.56506 4.03441 4.86937 5.28465 3.61913C6.5349 2.36888 8.23059 1.6665 9.9987 1.6665C11.7668 1.6665 13.4625 2.36888 14.7127 3.61913C15.963 4.86937 16.6654 6.56506 16.6654 8.33317Z"
										stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
									<path
										d="M9.9987 10.8332C11.3794 10.8332 12.4987 9.71388 12.4987 8.33317C12.4987 6.95246 11.3794 5.83317 9.9987 5.83317C8.61799 5.83317 7.4987 6.95246 7.4987 8.33317C7.4987 9.71388 8.61799 10.8332 9.9987 10.8332Z"
										stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
								</svg>

							</div>
							<div class="thankyou-address-text">
								<?php echo wp_kses_post( $order->get_formatted_billing_address() ); ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $order->get_billing_phone() ) : ?>
						<div class="billing-details">
							<div class="thankyou-billing-icon">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
									<g clip-path="url(#clip0_2955_5702)">
										<path
											d="M18.3351 14.0999V16.5999C18.3361 16.832 18.2886 17.0617 18.1956 17.2744C18.1026 17.487 17.9662 17.6779 17.7952 17.8348C17.6242 17.9917 17.4223 18.1112 17.2024 18.1855C16.9826 18.2599 16.7496 18.2875 16.5185 18.2666C13.9542 17.988 11.491 17.1117 9.32682 15.7083C7.31334 14.4288 5.60626 12.7217 4.32682 10.7083C2.91846 8.53426 2.04202 6.05908 1.76848 3.48325C1.74766 3.25281 1.77505 3.02055 1.8489 2.80127C1.92275 2.58199 2.04146 2.38049 2.19745 2.2096C2.35345 2.03871 2.54332 1.90218 2.75498 1.80869C2.96663 1.7152 3.19543 1.6668 3.42682 1.66658H5.92682C6.33124 1.6626 6.72331 1.80582 7.02995 2.06953C7.33659 2.33324 7.53688 2.69946 7.59348 3.09992C7.699 3.89997 7.89469 4.68552 8.17682 5.44158C8.28894 5.73985 8.3132 6.06401 8.24674 6.37565C8.18028 6.68729 8.02587 6.97334 7.80182 7.19992L6.74348 8.25825C7.92978 10.3445 9.65719 12.072 11.7435 13.2583L12.8018 12.1999C13.0284 11.9759 13.3144 11.8215 13.6261 11.755C13.9377 11.6885 14.2619 11.7128 14.5601 11.8249C15.3162 12.107 16.1018 12.3027 16.9018 12.4083C17.3066 12.4654 17.6763 12.6693 17.9406 12.9812C18.2049 13.2931 18.3453 13.6912 18.3351 14.0999Z"
											stroke="currentColor" stroke-width="2" stroke-linecap="round"
											stroke-linejoin="round" />
									</g>
									<defs>
										<clipPath id="clip0_2955_5702">
											<rect width="20" height="20" fill="white" />
										</clipPath>
									</defs>
								</svg>

							</div>
							<span class="thankyou-phone-text"><?php echo esc_html( $order->get_billing_phone() ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

	<?php else : ?>

		<div class="thankyou-grid">
			<div class="thankyou-card">
				<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>
			</div>
		</div>

	<?php endif; ?>

</div>