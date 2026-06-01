<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$first_item = yourpropfirm_get_first_item();

if ( ! $first_item ) {
	return;
}

// Only show for Variable product type
if ( ! $first_item->is_type( 'variable' ) ) {
	return;
}

$product_id = $first_item->get_id();
$wc_product = $first_item;

// Get variation attributes: ['pa_payout-frequency' => ['slug1', 'slug2'], ...]
$attributes = $wc_product->get_variation_attributes();

if ( empty( $attributes ) ) {
	return;
}

// Build variation map for JS: array of { variation_id, attributes: { attr_name: slug } }
$variation_ids = $wc_product->get_children();
$variations_map = array();

foreach ( $variation_ids as $variation_id ) {
	$variation = wc_get_product( $variation_id );
	if ( ! $variation || $variation->get_status() !== 'publish' ) {
		continue;
	}
	$variations_map[] = array(
		'variation_id' => $variation_id,
		'attributes' => $variation->get_attributes(),
	);
}

if ( empty( $variations_map ) ) {
	return;
}

// Determine currently selected variation from session
$selected_variation_id = WC()->session->get( 'ypf_selected_variation_id', null );
if ( null === $selected_variation_id ) {
	$selected_variation_id = $variations_map[0]['variation_id'];
}

// Get selected variation's attribute values for pre-selecting buttons
$selected_attributes = array();
foreach ( $variations_map as $var_data ) {
	if ( (int) $var_data['variation_id'] === (int) $selected_variation_id ) {
		$selected_attributes = $var_data['attributes'];
		break;
	}
}

?>

<div class="woocommerce-product-variants product-details" data-product-id="<?php echo esc_attr( $product_id ); ?>"
	data-variations-map="<?php echo esc_attr( wp_json_encode( $variations_map ) ); ?>">

	<?php foreach ( $attributes as $attribute_name => $options ) :
		// Get human-readable label for this attribute
		if ( taxonomy_exists( $attribute_name ) ) {
			$attribute_label = wc_attribute_label( $attribute_name );
		} else {
			$attribute_label = $attribute_name;
		}

		$selected_value = isset( $selected_attributes[ $attribute_name ] ) ? $selected_attributes[ $attribute_name ] : '';
		?>
		<div class="variant-attribute-group" data-attribute="<?php echo esc_attr( $attribute_name ); ?>">
			<h4 class="section-subheading">
				<?php echo esc_html( $attribute_label ); ?>
			</h4>
			<div class="variant-attribute-options">
				<?php foreach ( $options as $option ) :
					$display_name = $option;
					$badge = '';
					$description = '';

					if ( taxonomy_exists( $attribute_name ) ) {
						$term = get_term_by( 'slug', $option, $attribute_name );
						if ( $term ) {
							$display_name = $term->name;
							$badge = carbon_get_term_meta( $term->term_id, 'ypf_term_badge' );
							$description = $term->description;
						}
					}

					$has_extra = ! empty( $badge ) || ! empty( $description );
					?>
					<label class="variant-attribute-option <?php echo $has_extra ? 'variant-attribute-option--wide' : ''; ?>">
						<input type="radio"
							name="variant_attribute_<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"
							value="<?php echo esc_attr( $option ); ?>" class="variant-attribute-radio"
							data-attribute="<?php echo esc_attr( $attribute_name ); ?>" <?php checked( $selected_value, $option ); ?> />
						<?php if ( $has_extra ) : ?>
							<div class="variant-attribute-content">
								<div class="variant-attribute-header">
									<span class="variant-attribute-name"><?php echo esc_html( $display_name ); ?></span>
									<?php if ( ! empty( $badge ) ) : ?>
										<span class="variant-attribute-badge"><?php echo esc_html( $badge ); ?></span>
									<?php endif; ?>
								</div>
								<?php if ( ! empty( $description ) ) : ?>
									<div class="variant-attribute-description"><?php echo esc_html( $description ); ?></div>
								<?php endif; ?>
							</div>
						<?php else : ?>
							<span class="variant-attribute-label"><?php echo esc_html( $display_name ); ?></span>
						<?php endif; ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

</div>