<?php
/**
 * Checkout terms and conditions area.
 *
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( apply_filters( 'woocommerce_checkout_show_terms', true ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) ) {
	do_action( 'woocommerce_checkout_before_terms_and_conditions' );

	?>
	<div class="woocommerce-terms-and-conditions-wrapper">
		<?php
		/**
		 * Terms and conditions hook used to inject content.
		 *
		 * @since 3.4.0.
		 * @hooked wc_checkout_privacy_policy_text() Shows custom privacy policy text. Priority 20.
		 * @hooked wc_terms_and_conditions_page_content() Shows t&c page content. Priority 30.
		 */
		$terms_link = esc_url( carbon_get_theme_option( 'yourpropfirm_tos_link' ) );
		$privacy_policy_link = esc_url( carbon_get_theme_option( 'yourpropfirm_privacy_policy_link' ) );

		do_action( 'woocommerce_checkout_terms_and_conditions' );
		?>

		<div class="terms-checkbox-wrapper">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox terms-checkbox" id="terms"
				<?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); // WPCS: input var ok, CSRF ok. ?> type="checkbox" name="terms" value="1" />
			<label for="terms" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox terms-label">
				<span class="woocommerce-terms-and-conditions-checkbox-text">
					<?php esc_html_e( 'I agree to the', 'yourpropfirm-ui-addon' ); ?>
					<a href="<?php echo esc_url( $terms_link ); ?>" class="terms-link"
						target="_blank"><?php esc_html_e( 'Terms & Conditions', 'yourpropfirm-ui-addon' ); ?></a>
					<?php esc_html_e( 'and', 'yourpropfirm-ui-addon' ); ?>
					<a href="<?php echo esc_url( $privacy_policy_link ); ?>" class="terms-link"
						target="_blank"><?php esc_html_e( 'Privacy Policy', 'yourpropfirm-ui-addon' ); ?></a>.
				</span>
			</label>
		</div>

		<?php
		do_action( 'woocommerce_checkout_post_terms_and_conditions' );
		?>
	</div>
	<?php

	do_action( 'woocommerce_checkout_after_terms_and_conditions' );
}
