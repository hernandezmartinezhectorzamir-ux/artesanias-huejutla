<?php
/**
 * Medios de pago (sustituye a checkout/payment.php de WooCommerce).
 *
 * Solo la lista de métodos: el bloque de términos y el botón "Pagar y finalizar"
 * se muestran en el resumen de compra (ah_checkout_finalizar() en inc/checkout.php).
 *
 * @package Artesanias_Huejutla
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Gancho de WooCommerce.
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods" aria-label="<?php esc_attr_e( 'Medios de pago', 'artesanias-huejutla' ); ?>">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				}
			} else {
				echo '<li>';
				wc_print_notice( esc_html__( 'Escribe tus datos para ver los medios de pago disponibles.', 'artesanias-huejutla' ), 'notice' );
				echo '</li>';
			}
			?>
		</ul>
	<?php else : ?>
		<p class="ah-pago__sin-cobro"><?php esc_html_e( 'Este pedido no requiere pago.', 'artesanias-huejutla' ); ?></p>
	<?php endif; ?>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
}
