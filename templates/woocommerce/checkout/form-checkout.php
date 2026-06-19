<?php
/**
 * Checkout Form — FundedBot multi-step redesign.
 *
 * A two-step wizard:
 *   Step 1 "Challenge"     — product / evaluation selection (Choose Your Challenge)
 *   Step 2 "Information"    — billing details
 * Payment happens on the order-pay page after the order is created (step 3 of
 * the shared multi-step JS, which lives off-page).
 *
 * Driven by the main plugin's js/checkout-multistep.js, which is enqueued when
 * the `yourpropfirm_checkout_enable_multi_step` theme option is on. We therefore
 * reuse its exact DOM contract:
 *   - .checkout-step-indicator__item[data-step]
 *   - <section data-checkout-step="N">
 *   - [data-checkout-step-next] / [data-checkout-step-prev]
 *   - [data-sidebar-step="N"]
 *   - form.checkout
 * Do NOT rename these — renaming breaks the stepper.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Render notices after the form (so gateway JS errors appear below it).
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );
do_action( 'woocommerce_before_checkout_form', $checkout );
add_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'yourpropfirm' ) ) );
	return;
}

$ypf_terms_link          = function_exists( 'carbon_get_theme_option' ) ? esc_url( carbon_get_theme_option( 'yourpropfirm_tos_link' ) ) : '#';
$ypf_privacy_policy_link = function_exists( 'carbon_get_theme_option' ) ? esc_url( carbon_get_theme_option( 'yourpropfirm_privacy_policy_link' ) ) : '#';
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout ypf-checkout-wizard"
	action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data"
	aria-label="<?php echo esc_attr__( 'Checkout', 'yourpropfirm' ); ?>">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<!-- ===================== STEPPER (Challenge → Information) ===================== -->
		<nav class="checkout-step-indicator ypf-stepper" aria-label="<?php esc_attr_e( 'Checkout progress', 'yourpropfirm' ); ?>">
			<ol class="checkout-step-indicator__list">
				<li class="checkout-step-indicator__item is-active" data-step="1"
					data-hint-active="<?php esc_attr_e( 'You are here', 'yourpropfirm' ); ?>"
					data-hint-completed="<?php esc_attr_e( 'Completed', 'yourpropfirm' ); ?>"
					data-hint-upcoming="<?php esc_attr_e( 'Next step', 'yourpropfirm' ); ?>">
					<span class="checkout-step-indicator__number">
							<span class="checkout-step-indicator__num-text">1</span>
							<img src="<?php echo esc_url( YOURPROPFIRM_UI_ADDON_URL . 'assets/images/check.png' ); ?>" alt="" class="checkout-step-indicator__check" aria-hidden="true" />
						</span>
					<span class="checkout-step-indicator__text">
						<span class="checkout-step-indicator__hint"><?php esc_html_e( 'You are here', 'yourpropfirm' ); ?></span>
						<span class="checkout-step-indicator__label"><?php esc_html_e( 'Challenge', 'yourpropfirm' ); ?></span>
					</span>
				</li>
				<li class="checkout-step-indicator__item is-upcoming" data-step="2"
					data-hint-active="<?php esc_attr_e( 'You are here', 'yourpropfirm' ); ?>"
					data-hint-completed="<?php esc_attr_e( 'Completed', 'yourpropfirm' ); ?>"
					data-hint-upcoming="<?php esc_attr_e( 'Next step', 'yourpropfirm' ); ?>">
					<span class="checkout-step-indicator__number">2</span>
					<span class="checkout-step-indicator__text">
						<span class="checkout-step-indicator__hint"><?php esc_html_e( 'Next step', 'yourpropfirm' ); ?></span>
						<span class="checkout-step-indicator__label"><?php esc_html_e( 'Information', 'yourpropfirm' ); ?></span>
					</span>
				</li>
			</ol>
		</nav>

		<!-- Step titles — centered above BOTH columns; multistep JS toggles per step -->
		<div class="ypf-step-titles">
			<header class="ypf-step-header" data-checkout-step="1">
				<h2 class="ypf-step-title"><?php esc_html_e( 'Choose Your Challenge', 'yourpropfirm' ); ?></h2>
				<p class="ypf-step-subtitle"><?php esc_html_e( 'Select evaluation type and account size to get started', 'yourpropfirm' ); ?></p>
			</header>
			<header class="ypf-step-header" data-checkout-step="2" hidden>
				<h2 class="ypf-step-title"><?php esc_html_e( 'Your Information', 'yourpropfirm' ); ?></h2>
				<p class="ypf-step-subtitle"><?php esc_html_e( 'Fill in your billing details to proceed', 'yourpropfirm' ); ?></p>
			</header>
		</div>

		<div class="checkout-grid" id="customer_details">
			<div class="checkout-form-left">

				<!-- ===================== STEP 1: CHOOSE YOUR CHALLENGE ===================== -->
				<!--
					Data-driven: form-product-selection.php emits the plugin's
					.woocommerce-product-selection (category + product + variation attr
					groups) and form-trading-platform.php emits .woocommerce-trading-platform.
					yourpropfirm-public.js re-renders these via innerHTML on selection, so
					the FUNDEDBIT look is applied purely by CSS targeting its classes — the
					markup/JS hooks are preserved verbatim.
				-->
				<section data-checkout-step="1" class="checkout-step">
					<div class="container-product-selection-group">
						<div class="container-product-selection hide-if-reset-product hide-if-renewal-subscription">
							<?php wc_get_template( 'checkout/form-product-selection.php' ); ?>
						</div>
						<div class="container-trading-platform hide-if-reset-product hide-if-renewal-subscription">
							<?php wc_get_template( 'checkout/form-trading-platform.php' ); ?>
						</div>
					</div>
				</section>

				<!-- ===================== STEP 2: YOUR INFORMATION ===================== -->
				<section data-checkout-step="2" class="checkout-step" hidden>
					<div class="checkout-card">
						<p class="ypf-substep-label"><?php esc_html_e( 'Information', 'yourpropfirm' ); ?></p>
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

						<p class="ypf-consent-text ypf-field-hidden">
							<?php esc_html_e( 'By placing your order, you agree to our', 'yourpropfirm' ); ?>
							<a href="https://app.fundedbit.com/privacy?_gl=1*1jxnv9h*_ga*MTMxODM3NzI5NS4xNzc3Mjc1OTE1*_ga_LKRKCECBFF*czE3ODA1NTQ0MzkkbzckZzEkdDE3ODA1NTQ0MzkkajYwJGwwJGgyMTA1MTMzOTcw*_fplc*WE1iRGp0WWhFNVdTUHMyNk1kMk11MjduckIxdWducGxvYnhETlpjTDJGMSUyQlRNbzBuSVp3cTNuZCUyQmlXMHhXRkZWQmpZN1FVazZUZzNuRlFJRDJWZ2dtZlYybHkxNE5nRUdGMVI2T0Y2TVBCTEN5MEFoRnpXT3NmRGphbjNqUSUzRCUzRA.."
							   class="ypf-consent-link" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacy Policy', 'yourpropfirm' ); ?></a>
							<?php esc_html_e( 'and', 'yourpropfirm' ); ?>
							<a href="https://app.fundedbit.com/terms?_gl=1*1jxnv9h*_ga*MTMxODM3NzI5NS4xNzc3Mjc1OTE1*_ga_LKRKCECBFF*czE3ODA1NTQ0MzkkbzckZzEkdDE3ODA1NTQ0MzkkajYwJGwwJGgyMTA1MTMzOTcw*_fplc*WE1iRGp0WWhFNVdTUHMyNk1kMk11MjduckIxdWducGxvYnhETlpjTDJGMSUyQlRNbzBuSVp3cTNuZCUyQmlXMHhXRkZWQmpZN1FVazZUZzNuRlFJRDJWZ2dtZlYybHkxNE5nRUdGMVI2T0Y2TVBCTEN5MEFoRnpXT3NmRGphbjNqUSUzRCUzRA.."
							   class="ypf-consent-link" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Terms of Use', 'yourpropfirm' ); ?></a>
							<?php esc_html_e( ', and consent to receive updates and marketing communications from FundedBit.', 'yourpropfirm' ); ?>
						</p>

						<!-- Shown on email-only sub-step; hidden after Continue -->
						<div class="ypf-substep-nav ypf-field-hidden" id="ypf-substep-nav">
							<button type="button" class="btn-outline ypf-substep-prev" id="ypf-email-prev">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
								<span><?php esc_html_e( 'Previous', 'yourpropfirm' ); ?></span>
							</button>
							<button type="button" class="btn-primary ypf-substep-next" id="ypf-email-next">
								<span><?php esc_html_e( 'Continue', 'yourpropfirm' ); ?></span>
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
							</button>
						</div>
					</div>
				</section>

			</div>

			<!-- ===================== ORDER SUMMARY (persistent across steps) ===================== -->
			<div class="checkout-form-right">
				<div class="order-summary checkout-card">
					<h3 class="section-heading tw-pb-0">
						<?php esc_html_e( 'Order Summary', 'yourpropfirm' ); ?>
					</h3>

					<!-- Static, JS-driven order summary (updated by js/checkout-wizard.js) -->
					<div id="ypf-order-summary" class="ypf-order-summary">
						<!-- Challenge Requirement box removed: its Phase/Loss values were hardcoded
						     placeholders, not real per-program data. Re-add here (and wire real
						     targets) when that data is available. -->

						<!-- Payment method + Coupon — full-form step only (JS shows it after email) -->
						<div class="ypf-payment-coupon ypf-field-hidden" id="ypf-payment-coupon">
							<!-- Native WC payment: payment.php renders #payment_method_select (a dropdown
							     built dynamically from the merchant's ENABLED gateways) + the payment_method
							     radios + the checkout nonce. The plugin JS syncs the dropdown -> radios.
							     Styled to the design; the radios/notices are hidden via CSS. -->
							<div class="ypf-pm-field">
								<?php wc_get_template( 'checkout/payment.php' ); ?>
							</div>
							<div class="ypf-coupon-field">
								<label class="ypf-coupon-label" for="ypf-coupon-input"><?php esc_html_e( 'Coupon code', 'yourpropfirm' ); ?></label>
								<div class="ypf-coupon-row">
									<input type="text" class="ypf-coupon-input" id="ypf-coupon-input" placeholder="<?php esc_attr_e( 'Insert coupon code', 'yourpropfirm' ); ?>" />
									<button type="button" class="ypf-coupon-apply" id="ypf-coupon-apply"><?php esc_html_e( 'Apply', 'yourpropfirm' ); ?></button>
								</div>
								<p class="ypf-coupon-msg ypf-field-hidden" id="ypf-coupon-msg" role="status"></p>
							</div>
						</div>

						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Product', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="product">&mdash;</span></div>
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Category', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="category">&mdash;</span></div>
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Account', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="account">&mdash;</span></div>
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Platform', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="platform">&mdash;</span></div>
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Currency', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="currency">USD</span></div>
						<hr class="ypf-summary-divider" />
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Base Price', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="base">&mdash;</span></div>
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Sub Total', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="subtotal">&mdash;</span></div>
						<div class="ypf-summary-row ypf-field-hidden" id="ypf-summary-discount-row"><span class="ypf-summary-label"><?php esc_html_e( 'Discount', 'yourpropfirm' ); ?> <span class="ypf-discount-code" data-ypf="discount-code"></span></span><span class="ypf-summary-value ypf-value--green" data-ypf="discount">&mdash;</span></div>
						<hr class="ypf-summary-divider" />
						<div class="ypf-summary-row ypf-summary-total"><span><?php esc_html_e( 'Total', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="total">&mdash;</span></div>
					</div>

					<!-- We Accept payment methods -->
					<div class="ypf-we-accept">
						<span class="ypf-we-accept__label"><?php esc_html_e( 'We Accept', 'yourpropfirm' ); ?></span>
						<div class="ypf-we-accept__cards">
							<?php
							$ypf_cards = array(
								array( 'file' => 'MasterCard.png', 'label' => 'Mastercard' ),
								array( 'file' => 'Visa.png',       'label' => 'Visa' ),
								array( 'file' => 'PayPal.png',     'label' => 'PayPal' ),
								array( 'file' => 'Google Pay.png', 'label' => 'Google Pay' ),
								array( 'file' => 'ApplePay.png',   'label' => 'Apple Pay' ),
							);
							foreach ( $ypf_cards as $card ) : ?>
								<img src="<?php echo esc_url( YOURPROPFIRM_UI_ADDON_URL . 'assets/images/' . $card['file'] ); ?>"
									alt="<?php echo esc_attr( $card['label'] ); ?>" class="ypf-pay-card-img" />
							<?php endforeach; ?>
						</div>
						<div class="ypf-crypto-card">
							<div class="ypf-we-accept__crypto">
								<?php
								$ypf_cryptos = array(
									array( 'file' => 'Bitcoin (BTC).png',   'label' => 'Bitcoin' ),
									array( 'file' => 'Ethereum (ETH).png',  'label' => 'Ethereum' ),
									array( 'file' => 'Tether (USDT).png',   'label' => 'Tether' ),
									array( 'file' => 'USD Coin (USDC).png', 'label' => 'USD Coin' ),
									array( 'file' => 'Solana (SOL).png',    'label' => 'Solana' ),
									array( 'file' => 'Litecoin (LTC).png',  'label' => 'Litecoin' ),
									array( 'file' => 'TRON (TRX).png',      'label' => 'TRON' ),
								);
								foreach ( $ypf_cryptos as $crypto ) : ?>
									<img src="<?php echo esc_url( YOURPROPFIRM_UI_ADDON_URL . 'assets/images/' . $crypto['file'] ); ?>"
										alt="<?php echo esc_attr( $crypto['label'] ); ?>" class="ypf-crypto-icon" />
								<?php endforeach; ?>
								<span class="ypf-crypto-more">+</span>
							</div>
						</div>
					</div>

					<!-- Back / Next nav — JS controls visibility + label per step -->
					<div class="checkout-step-nav">
						<button type="button" class="btn-outline ypf-nav-prev" data-checkout-step-prev hidden>
							<?php esc_html_e( 'Back', 'yourpropfirm' ); ?>
						</button>
						<button type="button" class="btn-primary ypf-nav-next" data-checkout-step-next
							data-loading-text="<?php esc_attr_e( 'Processing…', 'yourpropfirm' ); ?>">
							<?php esc_html_e( 'Continue', 'yourpropfirm' ); ?>
						</button>
					</div>

					<!-- Secure checkout assurance — always visible -->
					<!-- Native WC checkout machinery (hidden): review-order for update_checkout + the real #place_order. "Proceed to Payment" triggers it via the step engine submitNativeOrder(). -->
					<div class="ypf-native-checkout ypf-field-hidden" aria-hidden="true">
						<!-- Consent is given by placing the order (the consent text above states this);
						     satisfy the plugin terms check without a checkbox the design intentionally omits. -->
						<input type="hidden" name="terms" value="1" />
						<input type="hidden" name="terms-field" value="1" />
						<div class="woocommerce-checkout-review-order">
							<?php wc_get_template( 'checkout/review-order.php' ); ?>
						</div>
						<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="<?php esc_attr_e( 'Proceed to Payment', 'yourpropfirm' ); ?>" data-value="<?php esc_attr_e( 'Proceed to Payment', 'yourpropfirm' ); ?>"><?php esc_html_e( 'Proceed to Payment', 'yourpropfirm' ); ?></button>
					</div>

					<div class="ypf-secure-checkout">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
						<span><?php esc_html_e( 'Secure checkout — data is protected', 'yourpropfirm' ); ?></span>
					</div>

				</div>
			</div>
		</div>

		<!-- ===================== TRUST BADGES (footer) ===================== -->
		<div class="ypf-trust-badges">
			<div class="ypf-trust-badges__inner">
			<div class="ypf-trust-badge">
				<span class="ypf-trust-badge__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
				</span>
				<span class="ypf-trust-badge__title"><?php esc_html_e( 'Secure Payments', 'yourpropfirm' ); ?></span>
				<span class="ypf-trust-badge__sub"><?php esc_html_e( 'SSL encrypted', 'yourpropfirm' ); ?></span>
			</div>
			<div class="ypf-trust-badge">
				<span class="ypf-trust-badge__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m13 2-3 7h6l-3 13"/></svg>
				</span>
				<span class="ypf-trust-badge__title"><?php esc_html_e( 'Instant Activation', 'yourpropfirm' ); ?></span>
				<span class="ypf-trust-badge__sub"><?php esc_html_e( 'Start trading immediately', 'yourpropfirm' ); ?></span>
			</div>
			<div class="ypf-trust-badge">
				<span class="ypf-trust-badge__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
				</span>
				<span class="ypf-trust-badge__title"><?php esc_html_e( 'Excellent Support', 'yourpropfirm' ); ?></span>
				<span class="ypf-trust-badge__sub"><?php esc_html_e( '24/7 customer service', 'yourpropfirm' ); ?></span>
			</div>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

</form>

<?php
if ( function_exists( 'woocommerce_output_all_notices' ) ) :
	woocommerce_output_all_notices();
endif;
?>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
