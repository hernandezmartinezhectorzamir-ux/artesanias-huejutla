<?php
/**
 * Pedido recibido (sustituye a checkout/thankyou.php de WooCommerce).
 *
 * Encabezado de agradecimiento, etapas del pedido y tarjetas con número, fecha, total y método.
 * Conserva los ganchos originales (datos bancarios de SPEI, seguimiento, analítica, detalles).
 *
 * @package Artesanias_Huejutla
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order ah-gracias">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Ganchos de WooCommerce en toda la plantilla.
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_thankyou_order_failed_text', __( 'Lo sentimos: el banco o la pasarela rechazó el pago y tu pedido no se pudo procesar. Intenta de nuevo.', 'artesanias-huejutla' ), $order ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>
			</p>
			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Intentar pagar de nuevo', 'artesanias-huejutla' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'Mi cuenta', 'artesanias-huejutla' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<?php
			$ah_propio = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
			$ah_nombre = $order->get_billing_first_name();
			$ah_texto  = $ah_propio && $order->get_billing_email()
				/* translators: 1: número de pedido, 2: correo. */
				? sprintf( __( 'Recibimos tu pedido #%1$s. Te enviamos la confirmación a %2$s.', 'artesanias-huejutla' ), $order->get_order_number(), $order->get_billing_email() )
				/* translators: %s: número de pedido. */
				: sprintf( __( 'Recibimos tu pedido #%s. Te enviamos la confirmación por correo.', 'artesanias-huejutla' ), $order->get_order_number() );
			$ah_etapas = array(
				array( 'check', __( 'Compra confirmada', 'artesanias-huejutla' ) ),
				array( 'paquete', __( 'En preparación', 'artesanias-huejutla' ) ),
				array( 'camion', __( 'En camino', 'artesanias-huejutla' ) ),
				array( 'casa', __( 'Entregado', 'artesanias-huejutla' ) ),
			);
			?>

			<section class="ah-gracias__encabezado woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
				<span class="ah-gracias__icono" aria-hidden="true"><?php echo ah_tema_icono( 'check', 34 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<h2 class="ah-gracias__titulo">
						<?php
						echo esc_html(
							$ah_nombre
								/* translators: %s: nombre del cliente. */
								? sprintf( __( '¡Gracias por tu compra, %s!', 'artesanias-huejutla' ), $ah_nombre )
								: __( '¡Gracias por tu compra!', 'artesanias-huejutla' )
						);
						?>
					</h2>
					<p class="ah-gracias__texto"><?php echo wp_kses_post( apply_filters( 'woocommerce_thankyou_order_received_text', esc_html( $ah_texto ), $order ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?></p>
				</div>
			</section>

			<ol class="ah-gracias__etapas" aria-label="<?php esc_attr_e( 'Etapas de tu pedido', 'artesanias-huejutla' ); ?>">
				<?php foreach ( $ah_etapas as $ah_i => list( $ah_icono, $ah_etapa ) ) : ?>
					<li class="<?php echo 0 === $ah_i ? 'is-hecha' : ''; ?>" <?php echo 0 === $ah_i ? 'aria-current="step"' : ''; ?>>
						<span class="ah-gracias__etapa-icono" aria-hidden="true"><?php echo ah_tema_icono( $ah_icono, 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php echo esc_html( $ah_etapa ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details ah-gracias__datos">
				<li class="woocommerce-order-overview__order order">
					<span><?php esc_html_e( 'Número de pedido', 'artesanias-huejutla' ); ?></span>
					<strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>
				</li>
				<li class="woocommerce-order-overview__date date">
					<span><?php esc_html_e( 'Fecha', 'artesanias-huejutla' ); ?></span>
					<strong><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></strong>
				</li>
				<li class="woocommerce-order-overview__total total">
					<span><?php esc_html_e( 'Total', 'artesanias-huejutla' ); ?></span>
					<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
				</li>
				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<span><?php esc_html_e( 'Método de pago', 'artesanias-huejutla' ); ?></span>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>
			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>
