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
		<?php _e( 'Product selection', 'yourpropfirm-ui-addon' ); ?>
	</h4>

	<div class="product-selection-content">
		<!-- Product Category Section -->
		<div class="product-category-section" data-level="0">
			<h5 class="section-subheading">
				<?php
				// Level labels are admin free-text; pass them through the catalog so known
				// design labels (e.g. "Select Trading Platform") translate, custom ones show as-is.
				$ypf_lvl0_label = ! empty( $category_level_labels[0] ) ? $category_level_labels[0] : __( 'Product Category', 'yourpropfirm-ui-addon' );
				echo esc_html( __( $ypf_lvl0_label, 'yourpropfirm-ui-addon' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				?>
			</h5>
			<div class="category-options">
				<?php
				foreach ( $categories as $category_id => $category_name ) :
					$is_selected = $category_id == $default_category;
					$has_children = yourpropfirm_category_has_children( $category_id );
					// FUNDEDBIT: surface the category description + badge (term meta) so the
					// eval-type cards match the design. Level-0 categories are server-rendered
					// (the plugin JS only re-paints sub-levels/products), so this persists.
					$cat_term  = get_term( $category_id, 'product_cat' );
					$cat_desc  = ( $cat_term && ! is_wp_error( $cat_term ) ) ? $cat_term->description : '';
					$cat_badge = get_term_meta( $category_id, '_ypf_term_badge', true );
					// FUNDEDBIT: a bundled platform logo (Bybit wordmark replaces the
					// text; Platform 5 icon sits next to the kept label). Name-keyed so
					// it works whatever the term IDs are.
					$ypf_logo = YPF_UI_Addon_Hooks::platform_logo_config( (string) $category_name );
					?>
					<label class="category-option">
						<input type="radio" name="product_category_0" value="<?php echo esc_attr( $category_id ); ?>" <?php checked( $is_selected, true ); ?> class="category-radio"
							data-has-children="<?php echo $has_children ? 'true' : 'false'; ?>" />
						<div class="category-option-content<?php echo $ypf_logo ? ' has-platform-logo' : ''; ?>">
							<?php if ( $cat_badge ) : ?>
								<span class="category-option-badge"><?php echo esc_html( $cat_badge ); ?></span>
							<?php endif; ?>
							<?php if ( $ypf_logo ) : ?>
								<span class="category-option-logo-wrap<?php echo $ypf_logo['light_url'] ? ' has-light-logo' : ''; ?>">
									<img class="category-option-logo category-option-logo--dark <?php echo $ypf_logo['wordmark'] ? 'is-wordmark' : 'is-icon'; ?>"
										src="<?php echo esc_url( $ypf_logo['dark_url'] ); ?>" alt="<?php echo esc_attr( $category_name ); ?>" loading="lazy" />
									<?php if ( $ypf_logo['light_url'] ) : ?>
										<img class="category-option-logo category-option-logo--light <?php echo $ypf_logo['wordmark'] ? 'is-wordmark' : 'is-icon'; ?>"
											src="<?php echo esc_url( $ypf_logo['light_url'] ); ?>" alt="<?php echo esc_attr( $category_name ); ?>" loading="lazy" />
									<?php endif; ?>
								</span>
							<?php endif; ?>
							<span class="category-option-name<?php echo ( $ypf_logo && $ypf_logo['hide_name'] ) ? ' tw-sr-only' : ''; ?>"><?php echo esc_html( $category_name ); ?></span>
							<?php if ( $cat_desc ) : ?>
								<span class="category-option-desc"><?php echo esc_html( $cat_desc ); ?></span>
							<?php endif; ?>
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
						<?php
						$ypf_sub_label = ! empty( $category_level_labels[ $level_data['level'] ] ) ? $category_level_labels[ $level_data['level'] ] : __( 'Subcategory', 'yourpropfirm-ui-addon' );
						echo esc_html( __( $ypf_sub_label, 'yourpropfirm-ui-addon' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
						?>
					</h5>
					<div class="category-options">
						<?php foreach ( $level_data['categories'] as $cat_id => $cat_name ) :
							$is_selected = $cat_id == $level_data['selected_id'];
							$has_children = yourpropfirm_category_has_children( $cat_id );
								// Eval cards live at the sub-category level now (platform is level 0): surface
								// description + badge here too. checkout-wizard.js re-injects these after the
								// plugin re-renders sub-levels (name only) on a platform change.
								$sub_term  = get_term( $cat_id, 'product_cat' );
								$sub_desc  = ( $sub_term && ! is_wp_error( $sub_term ) ) ? $sub_term->description : '';
								$sub_badge = get_term_meta( $cat_id, '_ypf_term_badge', true );
							?>
							<label class="category-option">
								<input type="radio" name="product_category_<?php echo esc_attr( $level_data['level'] ); ?>"
									value="<?php echo esc_attr( $cat_id ); ?>" <?php checked( $is_selected, true ); ?>
									class="category-radio"
									data-has-children="<?php echo $has_children ? 'true' : 'false'; ?>" />
								<div class="category-option-content">
										<?php if ( $sub_badge ) : ?><span class="category-option-badge"><?php echo esc_html( $sub_badge ); ?></span><?php endif; ?>
										<span class="category-option-name"><?php echo esc_html( $cat_name ); ?></span>
										<?php if ( $sub_desc ) : ?><span class="category-option-desc"><?php echo esc_html( $sub_desc ); ?></span><?php endif; ?>
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
				<?php echo $display_account_size ? esc_html__( 'Select Account Balance', 'yourpropfirm-ui-addon' ) : esc_html__( 'Selected Product', 'yourpropfirm-ui-addon' ); ?>
			</h5>
			<?php
			// The account BALANCE must follow the product's Account Currency
			// (_yourpropfirm_account_currency). The plugin's account_size_formatted
			// falls back to the STORE currency (get_woocommerce_currency()), which is
			// the price currency, not the account currency — so re-format it here via
			// the SAME helper the wizard JS mirrors (YPF_UI_Addon_Hooks::account_label),
			// so the balance keeps its shape across the plugin's JS re-render.
			$ypf_account_label = function ( $pid, $account_size, $fallback ) {
				$cur   = get_post_meta( $pid, '_yourpropfirm_account_currency', true );
				$label = class_exists( 'YPF_UI_Addon_Hooks' )
					? YPF_UI_Addon_Hooks::account_label( $account_size, (string) $cur )
					: '';
				return '' !== $label ? $label : $fallback;
			};
			?>
			<?php if ( $display_as_radio ) : ?>
				<div class="product-radio-options">
					<?php
					$last_product_id = ! empty( $default_products ) ? absint( end( $default_products )['id'] ) : 0;
					foreach ( $default_products as $product_data ) :
						$product_id = absint( $product_data['id'] );
						$is_selected = ( $default_product ? $product_id === absint( $default_product ) : $product_id === $last_product_id );
						$ypf_fallback = ( ! empty( $product_data['account_size_formatted'] ) ? $product_data['account_size_formatted'] : $product_data['name'] );
						$label_text   = $display_account_size
							? $ypf_account_label( $product_id, $product_data['account_size'] ?? '', $ypf_fallback )
							: $product_data['name'];
						?>
						<label class="product-option">
							<input type="radio" name="selected_product" value="<?php echo esc_attr( $product_id ); ?>"
								class="product-radio" <?php checked( $is_selected, true ); ?>
								data-account-label="<?php echo esc_attr( $label_text ); ?>"
									data-account-currency="<?php echo esc_attr( YPF_UI_Addon_Hooks::display_currency_code( (string) get_post_meta( $product_id, '_yourpropfirm_account_currency', true ) ) ); ?>"
								data-price="<?php echo esc_attr( $product_data['price'] ); ?>"
								data-currency="<?php echo esc_attr( $product_data['currency'] ?? '' ); ?>"
								data-most-popular="<?php echo $product_data['most_popular'] ? 'true' : 'false'; ?>" />
							<div class="product-option-content">
								<?php if ( $product_data['most_popular'] ) : ?>
									<span class="product-option-popular-badge">
										<?php esc_html_e( 'Most Popular', 'yourpropfirm-ui-addon' ); ?>
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
							$most_popular_text = $product_data['most_popular'] ? ' (' . __( 'Most Popular', 'yourpropfirm-ui-addon' ) . ')' : '';
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