<?php
/**
 * "Order received" message.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-received.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;

if ( ! $order ) {
	return;
}
?>

<div class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
	<?php
	if ( $order->has_status( 'failed' ) ) {
		?>
		<h1 class="thank-you-title"><?php esc_html_e( 'Order failed', 'yourpropfirm' ); ?></h1>
		<p><?php esc_html_e( "We weren't able to complete your order.", 'yourpropfirm' ); ?></p>
		<p class="information">
			<?php
			printf(
				__( 'If you believe this was a mistake, please contact <a href="mailto:%s">%s</a>', 'yourpropfirm' ),
				get_option( 'admin_email' ),
				get_option( 'admin_email' )
			); ?>
		</p>
		<?php
	} else {
		?>
		<h1 class="thank-you-title"><?php esc_html_e( 'Thank you!', 'yourpropfirm' ); ?></h1>
		<p><?php esc_html_e( 'Your order has been received.', 'yourpropfirm' ); ?></p>
		<?php
	}
	?>
</div>