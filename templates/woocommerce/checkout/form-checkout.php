<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Suppress the default notices wrapper here — we render it after the form instead,
// so Triple A payment gateway errors (injected via JS) appear below the checkout form.
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );
do_action( 'woocommerce_before_checkout_form', $checkout );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'yourpropfirm' ) ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout"
	action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data"
	aria-label="<?php echo esc_attr__( 'Checkout', 'yourpropfirm' ); ?>">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="checkout-grid" id="customer_details">
			<div class="checkout-form-left">
				<div class="checkout-card">

					<div class="billing-detail-container">
						<h3 class="section-heading">
							<?php esc_html_e( 'Billing details', 'yourpropfirm' ); ?>
						</h3>

						<div class="woocommerce-billing-fields">
							<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

							<div class="woocommerce-billing-fields__field-wrapper">
								<?php
								$fields = $checkout->get_checkout_fields( 'billing' );

								foreach ( $fields as $key => $field ) {
									woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
								}
								?>
							</div>

							<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
						</div>
					</div>
					<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
						<div class="woocommerce-account-fields">
							<?php if ( ! $checkout->is_registration_required() ) : ?>

								<p class="form-row form-row-wide create-account">
									<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
										<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
											id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" />
										<span><?php esc_html_e( 'Create an account?', 'yourpropfirm' ); ?></span>
									</label>
								</p>

							<?php endif; ?>

							<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

							<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

								<div class="create-account">
									<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
										<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
									<?php endforeach; ?>
									<div class="clear"></div>
								</div>

							<?php endif; ?>

							<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
						</div>
					<?php endif; ?>

					<div class="container-product-selection-group">

						<div class="container-purchase-details">
							<?php wc_get_template( 'checkout/repurchase-detail.php' ); ?>
						</div>

						<div class="container-product-selection hide-if-reset-product hide-if-renewal-subscription">
							<?php wc_get_template( 'checkout/form-product-selection.php' ); ?>
						</div>

						<div class="container-trading-platform hide-if-reset-product hide-if-renewal-subscription">
							<?php wc_get_template( 'checkout/form-trading-platform.php' ); ?>
						</div>

						<div class="container-available-addons hide-if-reset-product hide-if-renewal-subscription">
							<?php wc_get_template( 'checkout/form-available-addons.php' ); ?>
						</div>

						<div class="container-bundle-packages hide-if-reset-product hide-if-renewal-subscription">
							<?php wc_get_template( 'checkout/form-available-bundles.php' ); ?>
						</div>

					</div>

				</div>
			</div>

			<div class="checkout-form-right">
				<div class="order-summary checkout-card">
					<h3 class="section-heading tw-pb-0">
						<?php esc_html_e( 'Order summary', 'yourpropfirm' ); ?>
					</h3>

					<!-- Coupon Code Section -->
					<?php if ( wc_coupons_enabled() ) : ?>
						<div class="coupon-section">
							<div class="coupon-form-group">
								<label for="coupon_code" class="form-label">
									<?php esc_html_e( 'Coupon Code', 'yourpropfirm' ); ?>
									<span
										class="coupon-label-optional"><?php esc_html_e( 'Optional', 'yourpropfirm' ); ?></span>
								</label>
								<div class="coupon-input-group error">
									<input type="text" name="coupon_code" class="coupon-input"
										placeholder="<?php esc_attr_e( 'Insert coupon code', 'yourpropfirm' ); ?>"
										id="coupon_code" value="" />
									<button type="button" class="btn-outline" id="apply_coupon_btn"
										data-sub-section="Apply Coupon">
										<?php esc_html_e( 'Apply', 'yourpropfirm' ); ?>
									</button>
								</div>
								<p class="coupon-message checkout-inline-error-message">
									<?php _e( "Coupon code applied successfully.", 'yourpropfirm' ); ?>
								</p>
								<div class="affiliate-info-message" style="display:none;"></div>
							</div>
						</div>
					<?php endif; ?>

					<!-- Payment Section -->
					<div class="payment-section">
						<?php wc_get_template( 'checkout/payment.php' ); ?>
					</div>


					<!-- Order Review Section -->

					<?php wc_get_template( 'checkout/review-order.php' ); ?>

					<!-- Terms and Conditions -->
					<div class="terms-conditions-section">
						<?php wc_get_template( 'checkout/terms.php' ); ?>
					</div>

					<!-- Process to Payment Button -->
					<div class="place-order-section">
						<!-- Active Button -->
						<button type="submit" class="woocommerce-button button alt" name="woocommerce_checkout_place_order"
							id="place_order" value="<?php esc_attr_e( 'Proceed to Payment', 'yourpropfirm' ); ?>"
							data-value="<?php esc_attr_e( 'Proceed to Payment', 'yourpropfirm' ); ?>"
							data-sub-section="Place Order">
							<?php esc_html_e( 'Proceed to Payment', 'yourpropfirm' ); ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
								stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
								class="lucide lucide-arrow-right-icon lucide-arrow-right tw-size-4 rtl:tw-rotate-180">
								<path d="M5 12h14" />
								<path d="m12 5 7 7-7 7" />
							</svg>
						</button>

						<!-- Agreement Text -->
						<div class="agreement-text">
							<p>
								<?php esc_html_e( 'By continuing, I confirm that my full name and country match my government-issued ID. I understand that providing false or incorrect information may lead to account suspension or termination.', 'yourpropfirm' ); ?>
							</p>
						</div>

						<hr class="separator" />

						<!-- Secure checkout indicator -->
						<div class="secure-checkout-indicator">
							<svg class="secure-checkout-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
								viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
								stroke-linecap="round" stroke-linejoin="round"
								class="lucide lucide-shield-check-icon lucide-shield-check">
								<path
									d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
								<path d="m9 12 2 2 4-4" />
							</svg>
							<span
								class="secure-checkout-text"><?php esc_html_e( 'Secure checkout - your data is protected', 'yourpropfirm' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

</form>

<?php
// Notices wrapper placed after the form so validation error messages (e.g. from Triple A
// payment gateway) are injected below the checkout form, consistent with the plugin's
// standard WooCommerce error display.
if ( function_exists( 'woocommerce_output_all_notices' ) ) :
	woocommerce_output_all_notices();
endif;
?>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>