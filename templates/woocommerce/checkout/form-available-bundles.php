<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( true === yourpropfirm_has_reset_product() ) {
	return;
}

if ( true === yourpropfirm_is_competition_product() ) {
	return;
}

$bundles = function_exists( 'yourpropfirm_get_product_bundles' )
	? yourpropfirm_get_product_bundles()
	: [];

if ( empty( $bundles ) ) {
	return;
}

$active_bundle_id = \WC()->session ? \WC()->session->get( 'bundle_id', '' ) : '';
$addon_map        = yourpropfirm_get_addons_data();
?>

<div class="woocommerce-bundle-packages">
	<h4 class="section-subheading">
		<?php esc_html_e( 'Bundle Packages', 'yourpropfirm-ui-addon' ); ?>
	</h4>
	<div class="bundle-packages">
		<?php foreach ( $bundles as $bundle ) :
			$bundle_id    = esc_attr( $bundle['id'] );
			$bundle_label = ! empty( $bundle['label'] ) ? $bundle['label'] : $bundle['title'];
			$is_checked   = ( $active_bundle_id === $bundle['id'] );
		?>
		<div class="bundle-option<?php echo $is_checked ? ' is-active' : ''; ?>">
			<div class="bundle-content">
				<label for="bundle-<?php echo $bundle_id; ?>">
					<input class="bundle-input" type="radio" name="bundle_id"
						id="bundle-<?php echo $bundle_id; ?>"
						value="<?php echo $bundle_id; ?>"
						data-addon-ids="<?php echo esc_attr( wp_json_encode( $bundle['addon_ids'] ?? [] ) ); ?>"
						<?php checked( $is_checked ); ?>>
					<span class="tw-ms-2 caption-1 tw-font-medium">
						<?php echo esc_html( $bundle_label ); ?>
					</span>
				</label>
			</div>
			<?php if ( ! empty( $bundle['addon_ids'] ) ) : ?>
			<div class="bundle-addon-preview">
				<ul class="bundle-addon-list">
					<?php foreach ( $bundle['addon_ids'] as $addon_id ) :
						$addon_title = isset( $addon_map[ $addon_id ] )
							? $addon_map[ $addon_id ]['title']
							: $addon_id;
					?>
					<li class="bundle-addon-item">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="bundle-addon-check-icon">
							<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
						</svg>
						<?php echo esc_html( $addon_title ); ?>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
	<input type="hidden" name="bundle_id" value="" id="bundle_id_fallback">
</div>

<?php
// When bundle active: inject hidden addon[] inputs so server-side price calc reads them.
if ( ! empty( $active_bundle_id ) ) :
	foreach ( $bundles as $bundle ) :
		if ( $bundle['id'] !== $active_bundle_id ) {
			continue;
		}
		foreach ( $bundle['addon_ids'] as $addon_id ) :
			$parts = explode( '-', $addon_id, 2 );
			$index = $parts[0];
			?>
			<input type="hidden" name="addon[<?php echo esc_attr( $index ); ?>]"
				value="<?php echo esc_attr( $addon_id ); ?>"
				class="bundle-forced-addon">
			<?php
		endforeach;
	endforeach;
endif;
