<?php

$plugin_enabled = carbon_get_theme_option( 'yourpropfirm_connection_enabled' );
$enable_mtversion_field = carbon_get_theme_option( 'yourpropfirm_connection_mt_version_field' );
$default_mt = carbon_get_theme_option( 'yourpropfirm_connection_default_mt_version_field' );
$trading_platforms_options = carbon_get_theme_option( 'yourpropfirm_connection_trading_platforms' );

if ( $plugin_enabled !== 'enable' || $enable_mtversion_field !== 'enable' ) {
	return;
}


$options = [ '' => __( 'Select Trading Platform', 'yourpropfirm-ui-addon' ) ];
if ( ! empty( $trading_platforms_options['enable_mt4'] ) ) {
	$options['MT4'] = __( 'MT4', 'yourpropfirm-ui-addon' );
}
if ( ! empty( $trading_platforms_options['enable_mt5'] ) ) {
	$options['MT5'] = __( 'MT5', 'yourpropfirm-ui-addon' );
}

if ( ! empty( $trading_platforms_options['enable_ctrader'] ) ) {
	$options['CTrader'] = __( 'cTrader', 'yourpropfirm-ui-addon' );
}
if ( ! empty( $trading_platforms_options['enable_sirix'] ) ) {
	$options['Sirix'] = __( 'Sirix', 'yourpropfirm-ui-addon' );
}
if ( ! empty( $trading_platforms_options['enable_dx_trade'] ) ) {
	$options['DXTrade'] = __( 'DX Trade', 'yourpropfirm-ui-addon' );
}
if ( ! empty( $trading_platforms_options['enable_match_trader'] ) ) {
	$options['MatchTrade'] = __( 'MatchTrade', 'yourpropfirm-ui-addon' );
}
if ( ! empty( $trading_platforms_options['enable_tradelocker'] ) ) {
	$options['tradeLocker'] = __( 'TradeLocker', 'yourpropfirm-ui-addon' );
}

if ( ! empty( $trading_platforms_options['enable_rithmic'] ) ) {
	$options['Rithmic'] = __( 'Rithmic', 'yourpropfirm-ui-addon' );
}

?>
<div class="trading-platform-section">
	<h3>
		<?php esc_html_e( 'Trading Platform', 'yourpropfirm-ui-addon' ); ?>
	</h3>
	<?php
	woocommerce_form_field( 'yourpropfirm_mt_version', array(
		'type' => 'select',
		'class' => array( 'form-row-wide ypf_mt_version_field' ),
		'label' => __( 'Trading Platforms', 'yourpropfirm-ui-addon' ),
		'required' => true,
		'options' => $options, // Use the conditional options here
		'input_class' => array( 'form-select' )
	), '' );
	?>
</div>