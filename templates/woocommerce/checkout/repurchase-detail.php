<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product = yourpropfirm_get_first_item();
$product_id = $product->get_id();
$is_reset_checkout = yourpropfirm_has_reset_product();

$short_description = $product->get_short_description();



?>
<div class="woocommerce-repurchase-details product-details">
	<h3 class="section-heading">
		<?php esc_html_e( 'Purchase details', 'yourpropfirm-ui-addon' ); ?>
	</h3>
	<div class="repurchase-grid">
		<div class="repurchase-card">
			<h4 class="repurchase-card-label"><?php _e( "Product Name", "yourpropfirm" ); ?></h4>
			<h5 class="repurchase-card-value"><?php echo esc_html( $product->get_name() ); ?></h5>
		</div>

		<?php if ( true !== $is_reset_checkout && true !== yourpropfirm_is_competition_product() ) :
			$program_data = yourpropfirm_get_dashboard_program_by_product();
			$account_size = number_format( $program_data->data['initialBalance'] ?? 0 );
			$currency = $program_data->data['currency'] ?? 'USD';
			?>
			<div class="repurchase-card">
				<h4 class="repurchase-card-label"><?php _e( "Account Size", "yourpropfirm" ); ?></h4>
				<h5 class="repurchase-card-value">
					<?php echo yourpropfirm_get_currency_symbol( $currency ) . $account_size; ?>
				</h5>
			</div>
		<?php elseif ( true === yourpropfirm_is_competition_product() ) :
			$competition_data = yourpropfirm_get_dashboard_competition_by_product();
			$prize_pool = $competition_data->data['prizePool'] ?? 0;
			$currency = $competition_data->data['prizePoolCurrency'] ?? 'USD';
			?>
			<div class="repurchase-card">
				<h4 class="repurchase-card-label"><?php _e( "Prize Pool", "yourpropfirm" ); ?></h4>
				<h5 class="repurchase-card-value">
					<?php echo yourpropfirm_get_currency_symbol( $currency ) . $prize_pool; ?>
				</h5>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $short_description ) ) : ?>
		<div class="product-short-description repurchase-card">
			<h4 class="repurchase-card-label">
				<?php _e( "Product Description", "yourpropfirm" ); ?>
			</h4>
			<div class="repurchase-card-value">
				<?php echo wpautop( wp_kses( $short_description, [
					'p' => [],
					'strong' => [],
					'em' => [],
					'br' => [],
					'hr' => [],
				] ) ); ?>
			</div>
		</div>
	<?php endif; ?>
	<hr class="separator tw-mt-4" />
</div>

<?php
if ( $is_reset_checkout ) {
	echo wp_nonce_field( 'yourpropfirm_reset_checkout', 'yourpropfirm_reset_checkout_nonce' );
}