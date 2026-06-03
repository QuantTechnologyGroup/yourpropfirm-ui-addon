<?php
/**
 * "Choose Your Challenge" selection — STATIC, JS-driven UI (FUNDEDBIT redesign).
 *
 * Three independent cards: Evaluation Type, Account Balance, Trading Platform.
 * Rendered from a static placeholder array; the interactive behaviour (highlight,
 * radio dot, live Order Summary recompute) is handled by js/checkout-wizard.js,
 * which reads the checked radios' data-* attributes and the localized
 * `ypfCheckoutWizard` price catalog. No WooCommerce product/REST dependency.
 *
 * Wiring these controls to real product data is a follow-up once the design is
 * signed off.
 *
 * @package YourPropFirm UI Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Static placeholder data --------------------------------------------------
$ypf_eval_types = array(
	array( 'id' => '1-step', 'label' => __( '1-Step', 'yourpropfirm' ), 'desc' => __( 'Single Phase Evaluation', 'yourpropfirm' ), 'badge' => '', 'selected' => true ),
	array( 'id' => '2-step', 'label' => __( '2-Step', 'yourpropfirm' ), 'desc' => __( 'Standard Evaluation', 'yourpropfirm' ), 'badge' => 'best-value', 'selected' => false ),
	array( 'id' => 'fast-track', 'label' => __( 'Fast Track', 'yourpropfirm' ), 'desc' => __( 'Pay After You Pass', 'yourpropfirm' ), 'badge' => 'popular', 'selected' => false ),
);

$ypf_balances = array( 100000, 50000, 25000, 10000, 5000 );
$ypf_default_balance = 5000;

$ypf_platforms = array(
	array( 'id' => 'bybit', 'label' => 'Bybit', 'img' => 'bybit.png', 'selected' => true ),
	array( 'id' => 'platform5', 'label' => __( 'Platform 5', 'yourpropfirm' ), 'img' => 'platform5.png', 'selected' => false ),
);
?>
<div class="ypf-challenge-selection">

	<!-- Select Evaluation Type -->
	<div class="ypf-select-card">
		<h3 class="ypf-field-label"><?php esc_html_e( 'Select Evaluation Type', 'yourpropfirm' ); ?></h3>
		<div class="ypf-eval-grid">
			<?php foreach ( $ypf_eval_types as $type ) : ?>
				<label class="ypf-eval-option">
					<input type="radio" name="ypf_eval_type" value="<?php echo esc_attr( $type['id'] ); ?>"
						class="ypf-eval-radio" data-label="<?php echo esc_attr( $type['label'] ); ?>"
						data-category="<?php echo esc_attr( $type['label'] ); ?>" <?php checked( $type['selected'], true ); ?> />
					<?php if ( 'best-value' === $type['badge'] ) : ?>
						<span class="ypf-eval-badge ypf-badge--best-value"><?php esc_html_e( 'Best Value', 'yourpropfirm' ); ?></span>
					<?php elseif ( 'popular' === $type['badge'] ) : ?>
						<span class="ypf-eval-badge ypf-badge--popular">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m13 2-3 7h6l-3 13"/></svg>
							<?php esc_html_e( 'Most Popular', 'yourpropfirm' ); ?>
						</span>
					<?php endif; ?>
					<span class="ypf-radio-dot" aria-hidden="true"></span>
					<span class="ypf-eval-title"><?php echo esc_html( $type['label'] ); ?></span>
					<span class="ypf-eval-desc"><?php echo esc_html( $type['desc'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Select Account Balance -->
	<div class="ypf-select-card">
		<h3 class="ypf-field-label"><?php esc_html_e( 'Select Account Balance', 'yourpropfirm' ); ?></h3>
		<div class="ypf-balance-grid">
			<?php foreach ( $ypf_balances as $balance ) :
				$label = '$' . number_format( $balance ); ?>
				<label class="ypf-balance-option">
					<input type="radio" name="ypf_account_balance" value="<?php echo esc_attr( $balance ); ?>"
						class="ypf-balance-radio" data-label="<?php echo esc_attr( $label ); ?>"
						<?php checked( $balance, $ypf_default_balance ); ?> />
					<span class="ypf-balance-amount"><?php echo esc_html( $label ); ?></span>
					<span class="ypf-radio-dot" aria-hidden="true"></span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Select Trading Platform -->
	<div class="ypf-select-card">
		<h3 class="ypf-field-label"><?php esc_html_e( 'Select Trading Platform', 'yourpropfirm' ); ?></h3>
		<div class="ypf-platform-grid">
			<?php foreach ( $ypf_platforms as $platform ) : ?>
				<label class="ypf-platform-option">
					<input type="radio" name="ypf_platform" value="<?php echo esc_attr( $platform['id'] ); ?>"
						class="ypf-platform-radio" data-label="<?php echo esc_attr( $platform['label'] ); ?>"
						<?php checked( $platform['selected'], true ); ?> />
					<?php if ( ! empty( $platform['img'] ) ) : ?>
						<img src="<?php echo esc_url( YOURPROPFIRM_UI_ADDON_URL . 'assets/images/' . $platform['img'] ); ?>"
							alt="<?php echo esc_attr( $platform['label'] ); ?>" class="ypf-platform-img" />
					<?php endif; ?>
					<span class="ypf-radio-dot" aria-hidden="true"></span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

</div>
