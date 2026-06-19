<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// has reset product
if ( true === yourpropfirm_has_reset_product() ) {
	return;
}

if ( true === yourpropfirm_is_competition_product() ) {
	return;
}

$addons = carbon_get_theme_option( "yourpropfirm_addon_items" );

if ( ! is_array( $addons ) || empty( $addons ) ) {
	return;
}

// Resolve product metadata (handles simple/variable automatically)
$first_item = yourpropfirm_get_first_item();
$price = yourpropfirm_get_product_price_based_on_trading_platform( $first_item );
$product_meta = yourpropfirm_resolve_product_meta( $first_item );

if ( ! $product_meta ) {
	return;
}

$current_addon_options = $product_meta['addons'];

if ( ! is_array( $current_addon_options ) || count( $current_addon_options ) === 0 ) {
	return;
}

$addons_index = array_map( function ( $option ) {
	return explode( '-', $option )[0];
}, $current_addon_options );

// Determine the current trading platform for platform exclusive filtering
$current_platform = \WC()->session ? \WC()->session->get( 'trading_platform', '' ) : '';
if ( empty( $current_platform ) ) {
	$platforms = yourpropfirm_get_trading_options_from_product();
	if ( ! empty( $platforms ) ) {
		$current_platform = array_key_exists( 'MT5', $platforms ) ? 'MT5' : array_key_first( $platforms );
	}
}
?>

<div class="woocommerce-available-add-ons">
	<h4 class="section-subheading">
		<?php esc_html_e( 'Available Add-ons', 'yourpropfirm-ui-addon' ); ?>
	</h4>
	<div class="available-addons">
		<?php
		foreach ( $addons as $index => $addon ) :
			if ( ! in_array( $index, $addons_index ) ) {
				continue;
			}

			// Platform exclusive filtering: skip addon if platform_exclusive is set and current platform is not in the list
			if ( ! empty( $addon['platform_exclusive'] ) && is_array( $addon['platform_exclusive'] ) && ! empty( $current_platform ) ) {
				if ( ! in_array( $current_platform, $addon['platform_exclusive'] ) ) {
					continue;
				}
			}

			if ( $addon['selection_type'] === "radio" ) :

				$title = ! empty( $addon['label'] ) ? $addon['label'] : $addon['title'];

				foreach ( (array) $addon['addons_radio'] as $i => $radio_addon ) :
					$radio_label = ! empty( $radio_addon['label'] ) ? $radio_addon['label'] : $radio_addon['title'];
					$id = $i . '-' . sanitize_title( $radio_addon['title'] );
					$fee = $price * $radio_addon['addons_fee'] / 100;
					$fee_text = wc_price( $fee );
					?>
					<div class="addon-option radio-type">
						<div class="addon-content">
							<label for="addon-<?php echo $id; ?>">
								<input class="addon-input" type="checkbox" name="addon[<?php echo $index; ?>]"
									id="addon-<?php echo $id; ?>" value="<?php echo $id; ?>">
								<span class="tw-ms-2 caption-1 tw-font-normal">
								<?php echo esc_html( $title . ' - ' . $radio_label ); ?>
								</span>
							</label>
							<div class="addon-price">+<?php echo $radio_addon['addons_fee']; ?>%</div>
						</div>
						<div class="addon-pricing tw-w-full">
							<div class="addon-price-desc tw-w-full">
								<?php echo sprintf( __( 'Add %s to your total', 'yourpropfirm-ui-addon' ), $fee_text ); ?>
							</div>
						</div>
					</div>
					<?php
				endforeach;
			else :

				$checkbox_label = ! empty( $addon['label'] ) ? $addon['label'] : $addon['title'];
				$id = $index . '-' . sanitize_title( $addon['title'] );
				$fee = $price * $addon['addons_fee'] / 100;
				$fee_text = wc_price( $fee );
				?>
				<div class="addon-option checkbox-type">
					<div class="addon-content">
						<label for="addon-<?php echo $id; ?>">
							<input class="addon-input" type="checkbox" name="addon[<?php echo $index; ?>]"
								id="addon-<?php echo $id; ?>" value="<?php echo $id; ?>">
							<span class="tw-ms-2 caption-1"><?php echo esc_html( $checkbox_label ); ?></span>
						</label>
					</div>
					<div class="addon-pricing">
						<div class="addon-price">+<?php echo $addon['addons_fee']; ?>%</div>
						<div class="addon-price-desc">
							<?php echo sprintf( __( 'Add %s to your total', 'yourpropfirm-ui-addon' ), $fee_text ); ?>
						</div>
					</div>
				</div>
				<?php
			endif;
		endforeach; ?>
	</div>
</div>