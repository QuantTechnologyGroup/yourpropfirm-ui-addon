<?php
/**
 * Admin: "Checkout Badge" field on the Product Category editor.
 *
 * Lets the merchant set the eval-type card badge ("Best Value" / "Most Popular")
 * per product category, stored in the `_ypf_term_badge` term meta that
 * templates/woocommerce/checkout/form-product-selection.php already reads.
 *
 * Add-on only — standard WordPress taxonomy hooks, no main-plugin change.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YPF_UI_Addon_Category_Badge {

	const META_KEY = '_ypf_term_badge';
	const TAXONOMY = 'product_cat';

	public static function init(): void {
		add_action( self::TAXONOMY . '_add_form_fields', [ __CLASS__, 'render_add_field' ] );
		add_action( self::TAXONOMY . '_edit_form_fields', [ __CLASS__, 'render_edit_field' ], 10, 1 );
		add_action( 'created_' . self::TAXONOMY, [ __CLASS__, 'save' ] );
		add_action( 'edited_' . self::TAXONOMY, [ __CLASS__, 'save' ] );
	}

	/**
	 * Help text shared by both screens.
	 */
	private static function description(): string {
		return __( 'Optional. Shown as a badge on this category\'s checkout card, e.g. "Best Value" or "Most Popular". Leave empty for no badge.', 'yourpropfirm-ui-addon' );
	}

	/**
	 * "Add new category" screen — stacked .form-field layout.
	 */
	public static function render_add_field(): void {
		?>
		<div class="form-field term-ypf-badge-wrap">
			<label for="ypf_term_badge"><?php esc_html_e( 'Checkout Badge', 'yourpropfirm-ui-addon' ); ?></label>
			<input type="text" name="ypf_term_badge" id="ypf_term_badge" value="" placeholder="<?php esc_attr_e( 'e.g. Best Value', 'yourpropfirm-ui-addon' ); ?>" />
			<p><?php echo esc_html( self::description() ); ?></p>
		</div>
		<?php
		wp_nonce_field( 'ypf_save_term_badge', 'ypf_term_badge_nonce' );
	}

	/**
	 * "Edit category" screen — table-row layout.
	 *
	 * @param WP_Term $term The category being edited.
	 */
	public static function render_edit_field( $term ): void {
		$value = get_term_meta( $term->term_id, self::META_KEY, true );
		?>
		<tr class="form-field term-ypf-badge-wrap">
			<th scope="row"><label for="ypf_term_badge"><?php esc_html_e( 'Checkout Badge', 'yourpropfirm-ui-addon' ); ?></label></th>
			<td>
				<input type="text" name="ypf_term_badge" id="ypf_term_badge" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'e.g. Best Value', 'yourpropfirm-ui-addon' ); ?>" />
				<p class="description"><?php echo esc_html( self::description() ); ?></p>
			</td>
		</tr>
		<?php
		wp_nonce_field( 'ypf_save_term_badge', 'ypf_term_badge_nonce' );
	}

	/**
	 * Persist on create + edit. No-ops for programmatic term creation
	 * (e.g. the seed) where our nonce is absent — the seed writes the meta itself.
	 *
	 * @param int $term_id The category term ID.
	 */
	public static function save( $term_id ): void {
		if (
			! isset( $_POST['ypf_term_badge_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ypf_term_badge_nonce'] ) ), 'ypf_save_term_badge' )
		) {
			return;
		}
		if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$badge = isset( $_POST['ypf_term_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['ypf_term_badge'] ) ) : '';
		update_term_meta( $term_id, self::META_KEY, $badge );
	}
}
