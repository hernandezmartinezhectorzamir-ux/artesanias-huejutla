<?php
/**
 * Resumen de compra (sustituye a checkout/review-order.php de WooCommerce).
 *
 * Productos con miniatura, subtotal, cupones, envío elegido (las opciones están en
 * "Forma de entrega"), cargos, impuestos, total y ahorro. Conserva los ganchos originales.
 *
 * @package Artesanias_Huejutla
 * @version 11.2.2
 */

defined( 'ABSPATH' ) || exit;

$ah_ahorro   = function_exists( 'ah_checkout_ahorro' ) ? ah_checkout_ahorro() : 0;
$ah_elegidos = (array) WC()->session->get( 'chosen_shipping_methods', array() );
$ah_envio    = null;
$ah_recoger  = false;
foreach ( WC()->shipping()->get_packages() as $ah_i => $ah_paquete ) {
	if ( isset( $ah_elegidos[ $ah_i ], $ah_paquete['rates'][ $ah_elegidos[ $ah_i ] ] ) ) {
		$ah_tarifa  = $ah_paquete['rates'][ $ah_elegidos[ $ah_i ] ];
		$ah_envio   = (float) $ah_envio + (float) $ah_tarifa->get_cost() + array_sum( (array) $ah_tarifa->get_taxes() );
		$ah_recoger = $ah_recoger || ( function_exists( 'ah_checkout_tipo_envio' ) && 'recoger' === ah_checkout_tipo_envio( $ah_tarifa ) );
	}
}
?>
<table class="shop_table woocommerce-checkout-review-order-table ah-resumen">
	<caption class="ah-resumen__titulo"><?php esc_html_e( 'Resumen de compra', 'artesanias-huejutla' ); ?></caption>
	<thead class="screen-reader-text">
		<tr>
			<th class="product-name"><?php esc_html_e( 'Producto', 'artesanias-huejutla' ); ?></th>
			<th class="product-total"><?php esc_html_e( 'Subtotal', 'artesanias-huejutla' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Ganchos de WooCommerce en toda la plantilla.

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals

			if ( $_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
				$ah_nombre = apply_filters( 'woocommerce_cart_item_name', WC()->cart->get_item_product_name( $cart_item, $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>">
					<td class="product-name">
						<span class="ah-resumen__producto">
							<span class="ah-resumen__miniatura">
								<?php echo wp_kses_post( $_product->get_image( 'woocommerce_gallery_thumbnail', array( 'alt' => '' ) ) ); ?>
								<span class="ah-resumen__cantidad" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: cantidad. */ __( 'Cantidad: %d', 'artesanias-huejutla' ), $cart_item['quantity'] ) ); ?>"><?php echo esc_html( (string) $cart_item['quantity'] ); ?></span>
							</span>
							<span class="ah-resumen__nombre">
								<?php echo wp_kses_post( $ah_nombre ); ?>
								<?php echo wc_get_formatted_cart_item_data( $cart_item, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
						</span>
					</td>
					<td class="product-total">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals ?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		?>
	</tbody>
	<tfoot>
		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Productos', 'artesanias-huejutla' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>
			<tr class="woocommerce-shipping-totals shipping ah-resumen__envio">
				<th><?php esc_html_e( 'Envío', 'artesanias-huejutla' ); ?></th>
				<td>
					<?php
					if ( null === $ah_envio ) {
						esc_html_e( 'Elige una opción', 'artesanias-huejutla' );
					} elseif ( $ah_envio <= 0 ) {
						echo '<span class="ah-resumen__gratis">' . ( $ah_recoger ? esc_html__( 'Sin costo', 'artesanias-huejutla' ) : esc_html__( 'Gratis', 'artesanias-huejutla' ) ) . '</span>';
					} else {
						echo wp_kses_post( wc_price( $ah_envio ) );
					}
					?>
				</td>
			</tr>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'artesanias-huejutla' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php if ( $ah_ahorro > 0 || ( null !== $ah_envio && $ah_envio <= 0 && ! $ah_recoger ) ) : ?>
			<tr class="ah-resumen__beneficios">
				<td colspan="2">
					<?php if ( $ah_ahorro > 0 ) : ?>
						<span>
							<?php
							/* translators: %s: monto ahorrado. */
							echo wp_kses_post( sprintf( __( 'Ahorraste %s', 'artesanias-huejutla' ), wc_price( $ah_ahorro ) ) );
							?>
						</span>
					<?php endif; ?>
					<?php if ( null !== $ah_envio && $ah_envio <= 0 && ! $ah_recoger ) : ?>
						<span><?php esc_html_e( 'Envío gratis', 'artesanias-huejutla' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>
	</tfoot>
</table>
