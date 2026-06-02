<?php
/**
 * "Choose Your Challenge" selection — STATIC UI placeholder.
 *
 * NOTE: This is intentionally rendered from a static array (UI-only redesign).
 * It does NOT read WooCommerce categories/products and does NOT drive pricing.
 * Wiring these controls back to real product data (the original REST/category
 * flow) is a follow-up once the visual design is signed off.
 *
 * Uses dedicated `ypf-*` classes so the live product-selection JS / REST calls
 * stay dormant during design work.
 *
 * @package YourPropFirm UI Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Static JSON-shaped placeholder data --------------------------------------
$ypf_eval_types = array(
	array( 'id' => '1-step', 'title' => __( '1-Step', 'yourpropfirm' ), 'desc' => __( 'Single Phase Evaluation', 'yourpropfirm' ), 'badge' => __( 'Most Popular', 'yourpropfirm' ), 'selected' => true ),
	array( 'id' => '2-step', 'title' => __( '2-Step', 'yourpropfirm' ), 'desc' => __( 'Standard Evaluation', 'yourpropfirm' ), 'badge' => '', 'selected' => false ),
	array( 'id' => 'fast-track', 'title' => __( 'Fast Track', 'yourpropfirm' ), 'desc' => __( 'Pay When You Pass', 'yourpropfirm' ), 'badge' => __( 'New', 'yourpropfirm' ), 'selected' => false ),
);

$ypf_balances = array(
	array( 'label' => '$10,000', 'value' => '10000', 'selected' => false ),
	array( 'label' => '$25,000', 'value' => '25000', 'selected' => false ),
	array( 'label' => '$50,000', 'value' => '50000', 'selected' => false ),
	array( 'label' => '$100,000', 'value' => '100000', 'selected' => false ),
	array( 'label' => '$45,000', 'value' => '45000', 'selected' => true ),
	array( 'label' => '$200,000', 'value' => '200000', 'selected' => false ),
);

$ypf_platforms = array( 'Bybit', 'MetaTrader 5', 'cTrader', 'DXtrade' );
?>
<div class="ypf-challenge-selection">

	<!-- Select Evaluation Type -->
	<div class="ypf-field-group">
		<h3 class="ypf-field-label"><?php esc_html_e( 'Select Evaluation Type', 'yourpropfirm' ); ?></h3>
		<div class="ypf-eval-grid">
			<?php foreach ( $ypf_eval_types as $type ) : ?>
				<label class="ypf-eval-option<?php echo $type['selected'] ? ' is-selected' : ''; ?>">
					<input type="radio" name="ypf_eval_type" value="<?php echo esc_attr( $type['id'] ); ?>" class="ypf-eval-radio" <?php checked( $type['selected'], true ); ?> />
					<?php if ( $type['badge'] ) : ?>
						<span class="ypf-eval-badge"><?php echo esc_html( $type['badge'] ); ?></span>
					<?php endif; ?>
					<span class="ypf-eval-title"><?php echo esc_html( $type['title'] ); ?></span>
					<span class="ypf-eval-desc"><?php echo esc_html( $type['desc'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Select Account Balance -->
	<div class="ypf-field-group">
		<h3 class="ypf-field-label"><?php esc_html_e( 'Select Account Balance', 'yourpropfirm' ); ?></h3>
		<div class="ypf-balance-grid">
			<?php foreach ( $ypf_balances as $bal ) : ?>
				<label class="ypf-balance-option<?php echo $bal['selected'] ? ' is-selected' : ''; ?>">
					<input type="radio" name="ypf_account_balance" value="<?php echo esc_attr( $bal['value'] ); ?>" class="ypf-balance-radio" <?php checked( $bal['selected'], true ); ?> />
					<span class="ypf-balance-amount"><?php echo esc_html( $bal['label'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Select Trading Platform -->
	<div class="ypf-field-group">
		<h3 class="ypf-field-label"><?php esc_html_e( 'Select Trading Platform', 'yourpropfirm' ); ?></h3>
		<div class="ypf-platform-row">
			<div class="ypf-select-wrapper">
				<select name="ypf_trading_platform_static" class="ypf-platform-select">
					<?php foreach ( $ypf_platforms as $platform ) : ?>
						<option value="<?php echo esc_attr( $platform ); ?>"><?php echo esc_html( $platform ); ?></option>
					<?php endforeach; ?>
				</select>
				<svg class="ypf-select-chevron" width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</div>
			<span class="ypf-platform-hint"><?php esc_html_e( 'Platform', 'yourpropfirm' ); ?></span>
		</div>
	</div>

</div>
