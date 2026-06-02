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
					<span class="checkout-step-indicator__number">1</span>
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

		<div class="checkout-grid" id="customer_details">
			<div class="checkout-form-left">

				<!-- ===================== STEP 1: CHOOSE YOUR CHALLENGE ===================== -->
				<section data-checkout-step="1" class="checkout-step">
					<header class="ypf-step-header">
						<h2 class="ypf-step-title"><?php esc_html_e( 'Choose Your Challenge', 'yourpropfirm' ); ?></h2>
						<p class="ypf-step-subtitle"><?php esc_html_e( 'Select evaluation type and account size to get started', 'yourpropfirm' ); ?></p>
					</header>

					<div class="container-product-selection-group">
						<?php wc_get_template( 'checkout/form-product-selection.php' ); ?>
					</div>
				</section>

				<!-- ===================== STEP 2: YOUR INFORMATION ===================== -->
				<section data-checkout-step="2" class="checkout-step" hidden>
					<div class="checkout-card">
						<header class="ypf-step-header">
							<h2 class="ypf-step-title"><?php esc_html_e( 'Your Information', 'yourpropfirm' ); ?></h2>
							<p class="ypf-step-subtitle"><?php esc_html_e( 'Fill in your billing details to proceed', 'yourpropfirm' ); ?></p>
						</header>

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
						<button type="button" class="ypf-summary-toggle" data-ypf-toggle aria-expanded="true">
							<span><?php esc_html_e( 'Challenge Requirement', 'yourpropfirm' ); ?></span>
							<svg class="ypf-summary-chevron" width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</button>
						<div class="ypf-summary-details" data-ypf-details>
							<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Product', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="product">&mdash;</span></div>
							<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Category', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="category">&mdash;</span></div>
							<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Account', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="account">&mdash;</span></div>
							<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Platform', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="platform">&mdash;</span></div>
							<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Currency', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="currency">USD</span></div>
						</div>
						<hr class="ypf-summary-divider" />
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Base Price', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="base">&mdash;</span></div>
						<div class="ypf-summary-row"><span class="ypf-summary-label"><?php esc_html_e( 'Sub Total', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="subtotal">&mdash;</span></div>
						<hr class="ypf-summary-divider" />
						<div class="ypf-summary-row ypf-summary-total"><span><?php esc_html_e( 'Total', 'yourpropfirm' ); ?></span><span class="ypf-summary-value" data-ypf="total">&mdash;</span></div>
					</div>

					<!-- We Accept payment methods (placeholder chips) -->
					<div class="ypf-we-accept">
						<span class="ypf-we-accept__label"><?php esc_html_e( 'We Accept', 'yourpropfirm' ); ?></span>
						<div class="ypf-we-accept__cards">
							<span class="ypf-pay-card">Mastercard</span>
							<span class="ypf-pay-card">VISA</span>
							<span class="ypf-pay-card">PayPal</span>
							<span class="ypf-pay-card">G&nbsp;Pay</span>
							<span class="ypf-pay-card">&#63743;&nbsp;Pay</span>
						</div>
						<div class="ypf-we-accept__crypto">
							<span class="ypf-crypto-dot" style="--c:#f7931a">&#8383;</span>
							<span class="ypf-crypto-dot" style="--c:#627eea">&#926;</span>
							<span class="ypf-crypto-dot" style="--c:#26a17b">&#8366;</span>
							<span class="ypf-crypto-dot" style="--c:#23292f">&#9830;</span>
							<span class="ypf-crypto-dot" style="--c:#345d9d">L</span>
							<span class="ypf-crypto-more">+</span>
						</div>
					</div>

					<!-- Step 2 only: TOS agreement -->
					<div class="ypf-step-agreements" data-sidebar-step="2" hidden>
						<div class="ypf-agreement-item">
							<input type="checkbox" id="ypf_terms" name="ypf_terms" value="1"
								class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" />
							<label for="ypf_terms" class="ypf-agreement-label">
								<?php esc_html_e( 'I agree to the', 'yourpropfirm' ); ?>
								<a href="<?php echo $ypf_terms_link; ?>" class="terms-link" target="_blank"><?php esc_html_e( 'Terms & Conditions', 'yourpropfirm' ); ?></a>
								<?php esc_html_e( 'and', 'yourpropfirm' ); ?>
								<a href="<?php echo $ypf_privacy_policy_link; ?>" class="terms-link" target="_blank"><?php esc_html_e( 'Privacy Policy', 'yourpropfirm' ); ?></a>.
							</label>
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
					<div class="ypf-secure-checkout">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
						<span><?php esc_html_e( 'Secure checkout — data is protected', 'yourpropfirm' ); ?></span>
					</div>

					<!-- Consent line — step 2 -->
					<div class="agreement-text" data-sidebar-step="2" hidden>
						<p><?php esc_html_e( 'By proceeding with your purchase you agree to our Terms and Conditions and Privacy Policy.', 'yourpropfirm' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- ===================== TRUST BADGES (footer) ===================== -->
		<div class="ypf-trust-badges">
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

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

</form>

<?php
if ( function_exists( 'woocommerce_output_all_notices' ) ) :
	woocommerce_output_all_notices();
endif;
?>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
