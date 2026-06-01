<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( true === yourpropfirm_has_reset_product() )
	return;

$platforms = yourpropfirm_get_trading_options_from_product();

if ( null === $platforms || empty( $platforms ) ) {
	return;
}

// Sort platforms: MT5 > MT4 > TradingView > rest
$platform_order = array( 'MT5', 'MT4', 'TradingView' );
uksort( $platforms, function ( $a, $b ) use ( $platform_order ) {
	$pos_a = array_search( $a, $platform_order );
	$pos_b = array_search( $b, $platform_order );

	// If both are in priority list, sort by their position
	if ( $pos_a !== false && $pos_b !== false ) {
		return $pos_a - $pos_b;
	}
	// If only $a is in priority list, it comes first
	if ( $pos_a !== false ) {
		return -1;
	}
	// If only $b is in priority list, it comes first
	if ( $pos_b !== false ) {
		return 1;
	}
	// Neither in priority list, maintain original order
	return 0;
} );

// Determine default platform - prefer MT5 if available, otherwise first option
$default_platform = array_key_exists( 'MT5', $platforms ) ? 'MT5' : array_key_first( $platforms );

if ( is_array( $platforms ) && count( $platforms ) === 1 ) {
	// get first key
	$first_key = array_keys( $platforms )[0];
	?>
	<div class="tw-hidden">
		<input type="radio" name="trading_platform" value="<?php echo $first_key ?>" <?php echo checked( true ); ?> />
	</div>
	<?php
} else {

	?>
	<div class="woocommerce-trading-platform product-details">
		<h4 class="section-subheading">
			<?php esc_html_e( 'Trading Platform', 'yourpropfirm' ); ?>
		</h4>
		<div class="trading-platform-options">
			<?php
			foreach ( $platforms as $key => $platform ) : ?>

				<label class="platform-option">
					<input type="radio" name="trading_platform" value="<?php echo $key; ?>" <?php checked( $key === $default_platform, true ); ?> class="platform-radio" />
					<div class="platform-option-content">
						<?php echo $platform; ?>
					</div>
				</label>
				<?php
			endforeach;
			?>
		</div>
	</div>
	<?php
}