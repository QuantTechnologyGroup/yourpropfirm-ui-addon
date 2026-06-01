<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enabled = carbon_get_theme_option( 'yourpropfirm_checkout_enable_product_selection' );

if ( ! $enabled ) {
	return;
}

// has reset product
if ( true === yourpropfirm_has_reset_product() ) {
	return;
}

if ( true === yourpropfirm_is_competition_product() ) {
	return;
}

// Get all product categories
$categories = yourpropfirm_get_product_categories();

if ( empty( $categories ) ) {
	return;
}

// Get first product in cart as default selection
$default_product = yourpropfirm_get_first_product_in_cart();

// Disable product selection based on first product in cart
if ( $default_product ) {
	$disable_selection = get_post_meta( $default_product, '_yourpropfirm_disable_product_selection', true );
	if ( 'yes' === $disable_selection ) {
		return;
	}
}

// Get cart categories to determine default selection
$cart_categories = yourpropfirm_get_cart_product_categories();

// Display settings
$display_as_radio = carbon_get_theme_option( 'yourpropfirm_checkout_display_product_as_radio' );
$display_account_size = carbon_get_theme_option( 'yourpropfirm_checkout_product_display_account_size' );


$default_category = ! empty( $cart_categories ) ? $cart_categories[0] : array_key_first( $categories );

// Get leaf category and path for initial load
// If there's a product in cart, resolve the path based on its actual categories
if ( $default_product ) {
	$leaf_data = yourpropfirm_get_product_category_path( $default_product, $default_category );
} else {
	$leaf_data = yourpropfirm_get_leaf_category( $default_category );
}

$leaf_category_id = $leaf_data['category_id'];
$category_path = $leaf_data['path'];

// Get products for leaf category
$default_products = yourpropfirm_get_products_by_category( $leaf_category_id );

// Build subcategory levels data for initial render
$subcategory_levels = array();
foreach ( $category_path as $index => $path_category_id ) {
	if ( $index === 0 ) {
		continue; // Skip root category (already shown in main category section)
	}
	$parent_id = $category_path[ $index - 1 ];
	$siblings = yourpropfirm_get_child_categories( $parent_id );
	$subcategory_levels[] = array(
		'level' => $index,
		'parent_id' => $parent_id,
		'selected_id' => $path_category_id,
		'categories' => $siblings,
	);
}

// Build category level labels from overwrite settings
$overwrite_labels_raw = carbon_get_theme_option( 'yourpropfirm_checkout_overwrite_product_category_label' );
$category_level_labels = array();
if ( is_array( $overwrite_labels_raw ) ) {
	foreach ( $overwrite_labels_raw as $index => $label_entry ) {
		if ( ! empty( $label_entry['category'] ) ) {
			$category_level_labels[ $index ] = $label_entry['category'];
		}
	}
}
?>
<div class="woocommerce-product-selection product-details"
	data-rest-url="<?php echo esc_url( rest_url( 'yourpropfirm/v1/' ) ); ?>"
	data-rest-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
	data-category-level-labels="<?php echo esc_attr( wp_json_encode( $category_level_labels ) ); ?>"
	data-display-as-radio="<?php echo $display_as_radio ? 'true' : 'false'; ?>"
	data-display-account-size="<?php echo $display_account_size ? 'true' : 'false'; ?>">
	<h4 class="section-heading">
		<?php _e( 'Product selection', 'yourpropfirm' ); ?>
	</h4>

	<div class="product-selection-content">
		<!-- Product Category Section -->
		<div class="product-category-section" data-level="0">
			<h5 class="section-subheading">
				<?php echo esc_html( ! empty( $category_level_labels[0] ) ? $category_level_labels[0] : __( 'Product Category', 'yourpropfirm' ) ); ?>
			</h5>
			<div class="category-options">
				<?php
				foreach ( $categories as $category_id => $category_name ) :
					$is_selected = $category_id == $default_category;
					$has_children = yourpropfirm_category_has_children( $category_id );
					?>
					<label class="category-option">
						<input type="radio" name="product_category_0" value="<?php echo esc_attr( $category_id ); ?>" <?php checked( $is_selected, true ); ?> class="category-radio"
							data-has-children="<?php echo $has_children ? 'true' : 'false'; ?>" />
						<div class="category-option-content">
							<?php echo esc_html( $category_name ); ?>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Subcategory Sections Container -->
		<div class="subcategory-sections-container">
			<?php foreach ( $subcategory_levels as $level_data ) : ?>
				<div class="subcategory-section product-category-section"
					data-level="<?php echo esc_attr( $level_data['level'] ); ?>"
					data-parent-id="<?php echo esc_attr( $level_data['parent_id'] ); ?>">
					<h5 class="section-subheading">
						<?php echo esc_html( ! empty( $category_level_labels[ $level_data['level'] ] ) ? $category_level_labels[ $level_data['level'] ] : __( 'Subcategory', 'yourpropfirm' ) ); ?>
					</h5>
					<div class="category-options">
						<?php foreach ( $level_data['categories'] as $cat_id => $cat_name ) :
							$is_selected = $cat_id == $level_data['selected_id'];
							$has_children = yourpropfirm_category_has_children( $cat_id );
							?>
							<label class="category-option">
								<input type="radio" name="product_category_<?php echo esc_attr( $level_data['level'] ); ?>"
									value="<?php echo esc_attr( $cat_id ); ?>" <?php checked( $is_selected, true ); ?>
									class="category-radio"
									data-has-children="<?php echo $has_children ? 'true' : 'false'; ?>" />
								<div class="category-option-content">
									<?php echo esc_html( $cat_name ); ?>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Selected Product Section -->
		<div class="selected-product-section">
			<h5 class="section-subheading">
				<?php echo $display_account_size ? esc_html__( 'Account Size', 'yourpropfirm' ) : esc_html__( 'Selected Product', 'yourpropfirm' ); ?>
			</h5>
			<?php if ( $display_as_radio ) : ?>
				<div class="product-radio-options">
					<?php
					$last_product_id = ! empty( $default_products ) ? absint( end( $default_products )['id'] ) : 0;
					foreach ( $default_products as $product_data ) :
						$product_id = absint( $product_data['id'] );
						$is_selected = ( $default_product ? $product_id === absint( $default_product ) : $product_id === $last_product_id );
						$label_text = ( $display_account_size && ! empty( $product_data['account_size_formatted'] ) )
							? $product_data['account_size_formatted']
							: $product_data['name'];
						?>
						<label class="product-option">
							<input type="radio" name="selected_product" value="<?php echo esc_attr( $product_id ); ?>"
								class="product-radio" <?php checked( $is_selected, true ); ?>
								data-price="<?php echo esc_attr( $product_data['price'] ); ?>"
								data-currency="<?php echo esc_attr( $product_data['currency'] ?? '' ); ?>"
								data-most-popular="<?php echo $product_data['most_popular'] ? 'true' : 'false'; ?>" />
							<div class="product-option-content">
								<?php if ( $product_data['most_popular'] ) : ?>
									<span class="product-option-popular-badge">
										<?php esc_html_e( 'Most Popular', 'yourpropfirm' ); ?>
									</span>
								<?php endif; ?>
								<span class="product-option-name"><?php echo esc_html( $label_text ); ?></span>
								<span
									class="product-option-price-badge"><?php echo wp_kses_post( $product_data['formatted_price'] ); ?></span>
							</div>
						</label>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="product-dropdown-wrapper">
					<select name="selected_product" id="selected_product" class="product-dropdown">
						<?php
						foreach ( $default_products as $product_data ) :
							$product_id = absint( $product_data['id'] );
							$is_selected = $product_id == $default_product;
							$most_popular_text = $product_data['most_popular'] ? ' (' . __( 'Most Popular', 'yourpropfirm' ) . ')' : '';
							?>
							<option value="<?php echo esc_attr( $product_id ); ?>" <?php selected( $is_selected, true ); ?>
								data-most-popular="<?php echo $product_data['most_popular'] ? 'true' : 'false'; ?>"
								data-price="<?php echo esc_attr( $product_data['price'] ); ?>"
								data-currency="<?php echo esc_attr( $product_data['currency'] ?? '' ); ?>">
								<?php echo esc_html( $product_data['name'] . $most_popular_text ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<div class="dropdown-icon" style="display:none">
						<svg width="14" height="8" viewBox="0 0 14 8" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M1 1L7 7L13 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"
								stroke-linejoin="round" />
						</svg>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div class="container-product-variants">
		<?php wc_get_template( 'checkout/form-product-variants.php' ); ?>
	</div>
</div>