<?php

$addons_title = carbon_get_theme_option( 'yourpropfirm_addon_title' );
$addons_type = carbon_get_theme_option( 'yourpropfirm_addon_type' );

if ( ! is_array( $addons ) || empty( $addons ) ) {
	return;
}

if ( function_exists( 'WC' ) && isset( WC()->session ) ) {
	WC()->session->__unset( 'chosen_addons' );
	WC()->session->__unset( 'chosen_addons_percentage' );
}

$chosen_addons = null;

if ( function_exists( 'WC' ) && isset( WC()->session ) ) {
	if ( $addons_type === "checkbox" ) {
		$chosen_addons = WC()->session->get( 'chosen_addons' );
	} else {
		$chosen_addons = WC()->session->get( 'chosen_addons', [] );
	}
}

do_action(
	"qm/info",
	[ 
		"ypf_addons_display_" . uniqid(),
		[ 
			"addons_title" => $addons_title,
			"addons_type" => $addons_type,
			"chosen_addons" => $chosen_addons,
		]
	]
);

?>
<div class="ypf-addons-default-container woo-product-cat-id-<?php echo esc_attr( $product_category_classes ); ?>">
	<h4 class="heading ypf-addons-default-title"><?php echo esc_html( $addons_title ); ?></h4>
	<div class="ypf-addons-wrap">
		<?php
		$isFirst = true;
		foreach ( $addons as $addon ) :
			$display_percentage = ( intval( $addon['addons_fee'] ) == floatval( $addon['addons_fee'] ) ) ? intval( $addon['addons_fee'] ) : floatval( $addon['addons_fee'] );
			?>
			<div class="field-group">

				<?php if ( $addons_type === "checkbox" ) :
					$isChecked = ( is_array( $chosen_addons ) && in_array( $addon['id'], $chosen_addons, true ) ) ? 'checked' : '';
					?>
					<input type="checkbox" id="ypf-addon-<?php echo esc_attr( $addon['id'] ); ?>"
						class="ypf-addons-default-checkbox-input" name="ypf_addons[]"
						value="<?php echo esc_attr( $addon['id'] ); ?>"
						data-value="<?php echo esc_attr( $addon['addons_fee'] ); ?>" <?php checked( is_array( $chosen_addons ) && in_array( $addon['id'], $chosen_addons, true ) ); ?> />
				<?php else :
					$isChecked = $chosend_addons === $addon['id'] ? 'checked' : '';
					?>
					<input type="radio" id="ypf-addon-<?php echo esc_attr( $addon['id'] ); ?>"
						class="ypf-addons-default-radio-input" name="ypf_addons" value="<?php echo esc_attr( $addon['id'] ); ?>"
						data-value="<?php echo esc_attr( $addon['addons_fee'] ); ?>"
						checked="<?php echo esc_attr( $isChecked ); ?>" />
				<?php endif; ?>

				<label for="ypf-addon-<?php echo esc_attr( $addon['id'] ); ?>">
					<?php echo esc_html( $addon['title'] ); ?>
					<?php if ( $display_percentage > 0 ) : ?>
						- <span class="ypf-addon-fee"><?php echo esc_html( $display_percentage ); ?>%</span>
					<?php endif; ?>
				</label>
			</div>
			<?php
			$isFirst = false; // Set to false after the first iteration
		endforeach;
		?>
	</div>
</div>