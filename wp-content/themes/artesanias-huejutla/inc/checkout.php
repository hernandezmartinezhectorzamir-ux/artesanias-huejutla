<?php
/**
 * Finalizar compra: forma de entrega en tarjetas, medios de pago a la izquierda y resumen fijo.
 *
 * Estructura (checkout clásico [woocommerce_checkout]):
 * - Izquierda: datos de envío → Forma de entrega (.ah-entrega) → Medios de pago (#payment).
 * - Derecha: Resumen de compra (review-order.php) + bloque "Pagar y finalizar" (términos y botón).
 * Los fragmentos AJAX de WooCommerce se conservan: .woocommerce-checkout-review-order-table,
 * .woocommerce-checkout-payment y el propio .ah-entrega.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

/* Medios de pago a la columna izquierda; el resumen conserva solo el botón de pagar. */
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
add_action( 'woocommerce_checkout_order_review', 'ah_checkout_finalizar', 20 );
add_action( 'woocommerce_checkout_after_customer_details', 'ah_checkout_seccion_entrega', 10 );
add_action( 'woocommerce_checkout_after_customer_details', 'ah_checkout_seccion_pago', 20 );

add_filter(
	'woocommerce_update_order_review_fragments',
	static function ( $fragmentos ) {
		$fragmentos['.ah-entrega'] = ah_checkout_entrega_html();
		return $fragmentos;
	}
);

add_filter( 'woocommerce_order_button_text', static fn() => __( 'Pagar y finalizar', 'artesanias-huejutla' ) );

/*
 * Con envío gratis disponible, el estándar con costo sobra (es la misma entrega, cobrada).
 * Se conservan express y recoger en taller.
 */
add_filter(
	'woocommerce_package_rates',
	static function ( $tarifas ) {
		$hay_gratis = (bool) array_filter( $tarifas, static fn( $t ) => 'free_shipping' === $t->get_method_id() );
		if ( ! $hay_gratis ) {
			return $tarifas;
		}
		foreach ( $tarifas as $id => $tarifa ) {
			if ( 'flat_rate' === $tarifa->get_method_id() && 'estandar' === ah_checkout_tipo_envio( $tarifa ) ) {
				unset( $tarifas[ $id ] );
			}
		}
		// El envío gratis va primero.
		uasort( $tarifas, static fn( $a, $b ) => ( 'free_shipping' === $b->get_method_id() ) <=> ( 'free_shipping' === $a->get_method_id() ) );
		return $tarifas;
	},
	20
);


/*
 * Si la opción elegida deja de existir (p. ej. el estándar con costo al alcanzar el envío gratis),
 * WooCommerce tomaría la primera disponible, que puede ser express. Se elige el envío gratis o,
 * si no hay, la entrega a domicilio más barata: nunca se sube el costo sin que el cliente lo pida.
 */
add_filter(
	'woocommerce_shipping_chosen_method',
	static function ( $por_defecto, $tarifas, $elegido = '' ) {
		if ( $elegido && isset( $tarifas[ $elegido ] ) ) {
			return $por_defecto;
		}
		$mejor = null;
		foreach ( $tarifas as $id => $tarifa ) {
			if ( 'free_shipping' === $tarifa->get_method_id() ) {
				return $id;
			}
			if ( 'recoger' !== ah_checkout_tipo_envio( $tarifa ) && ( null === $mejor || (float) $tarifa->get_cost() < (float) $tarifas[ $mejor ]->get_cost() ) ) {
				$mejor = $id;
			}
		}
		return $mejor ? $mejor : $por_defecto;
	},
	10,
	3
);
/* Íconos para los métodos fuera de línea (las pasarelas traen el suyo). */
add_filter(
	'woocommerce_gateway_icon',
	static function ( $icono, $id ) {
		$propios = array(
			'bacs'   => 'banco',
			'cod'    => 'efectivo',
			'cheque' => 'recibo',
		);
		if ( isset( $propios[ $id ] ) && '' === trim( (string) $icono ) ) {
			return '<span class="ah-pago__icono">' . ah_tema_icono( $propios[ $id ], 22 ) . '</span>';
		}
		return $icono;
	},
	10,
	2
);

/**
 * Tipo de envío según la tarifa: recoger, express o estándar.
 *
 * @param WC_Shipping_Rate $tarifa Tarifa.
 * @return string recoger|express|estandar
 */
function ah_checkout_tipo_envio( $tarifa ) {
	if ( 'local_pickup' === $tarifa->get_method_id() || 'pickup_location' === $tarifa->get_method_id() ) {
		return 'recoger';
	}
	return false !== stripos( $tarifa->get_label(), 'express' ) ? 'express' : 'estandar';
}

/**
 * "Llega entre el 25 y el 27 de noviembre" (días hábiles desde hoy).
 *
 * @param string $tipo recoger|express|estandar.
 * @return string
 */
function ah_checkout_texto_llegada( $tipo ) {
	if ( ! class_exists( 'AH_Calculos' ) ) {
		return '';
	}
	list( $desde, $hasta ) = AH_Calculos::ventana_entrega( new DateTimeImmutable( 'now', wp_timezone() ), $tipo );
	$fecha                 = static fn( DateTimeImmutable $d ) => wp_date( 'j \d\e F', $d->getTimestamp() );
	if ( 'recoger' === $tipo ) {
		/* translators: %s: fecha. */
		return sprintf( __( 'Lista para recoger a partir del %s', 'artesanias-huejutla' ), $fecha( $desde ) );
	}
	if ( $desde->format( 'Y-m-d' ) === $hasta->format( 'Y-m-d' ) ) {
		/* translators: %s: fecha. */
		return sprintf( __( 'Llega el %s', 'artesanias-huejutla' ), $fecha( $hasta ) );
	}
	$mismo_mes = $desde->format( 'Y-m' ) === $hasta->format( 'Y-m' );
	return sprintf(
		/* translators: 1: fecha inicial, 2: fecha final. */
		__( 'Llega entre el %1$s y el %2$s', 'artesanias-huejutla' ),
		$mismo_mes ? $desde->format( 'j' ) : $fecha( $desde ),
		$fecha( $hasta )
	);
}

/**
 * Monto mínimo para envío gratis en la zona del paquete (0 si no hay).
 *
 * @param array $paquete Paquete de envío.
 * @return float
 */
function ah_checkout_minimo_envio_gratis( $paquete ) {
	$zona = WC_Shipping_Zones::get_zone_matching_package( $paquete );
	foreach ( $zona->get_shipping_methods( true ) as $metodo ) {
		if ( 'free_shipping' === $metodo->id && in_array( $metodo->get_option( 'requires' ), array( 'min_amount', 'either' ), true ) ) {
			return (float) $metodo->get_option( 'min_amount' );
		}
	}
	return 0.0;
}

/**
 * HTML de la sección "Forma de entrega" (también se usa como fragmento AJAX).
 *
 * @return string
 */
function ah_checkout_entrega_html() {
	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return '<div class="ah-entrega" hidden></div>';
	}
	$paquetes = WC()->shipping()->get_packages();
	$elegidos = (array) WC()->session->get( 'chosen_shipping_methods', array() );
	$subtotal = (float) WC()->cart->get_displayed_subtotal() - (float) WC()->cart->get_discount_total();
	$grupos   = array(
		'domicilio' => array( 'casa', __( 'Envío a domicilio', 'artesanias-huejutla' ) ),
		'recoger'   => array( 'ubicacion', __( 'Recoger en taller', 'artesanias-huejutla' ) ),
	);
	ob_start();
	?>
	<div class="ah-entrega">
		<?php foreach ( $paquetes as $i => $paquete ) : ?>
			<?php
			$tarifas = $paquete['rates'];
			$elegido = $elegidos[ $i ] ?? ( $tarifas ? array_key_first( $tarifas ) : '' );
			$minimo  = ah_checkout_minimo_envio_gratis( $paquete );
			$falta   = $minimo > 0 ? $minimo - $subtotal : 0;
			?>
			<?php if ( ! $tarifas ) : ?>
				<p class="ah-entrega__vacio"><?php esc_html_e( 'Escribe tu dirección completa para ver las opciones de entrega.', 'artesanias-huejutla' ); ?></p>
			<?php else : ?>
				<?php if ( $falta > 0 ) : ?>
					<div class="ah-entrega__gratis">
						<p>
							<?php
							/* translators: %s: monto faltante. */
							echo wp_kses_post( sprintf( __( 'Agrega %s más y tu envío estándar es <strong>gratis</strong>.', 'artesanias-huejutla' ), wc_price( $falta ) ) );
							?>
						</p>
						<span class="ah-entrega__barra" aria-hidden="true"><span style="width:<?php echo esc_attr( (string) min( 100, round( $subtotal / $minimo * 100 ) ) ); ?>%"></span></span>
					</div>
				<?php endif; ?>
				<?php foreach ( $grupos as $grupo => list( $icono_grupo, $titulo_grupo ) ) : ?>
					<?php
					$del_grupo = array_filter( $tarifas, static fn( $t ) => ( 'recoger' === ah_checkout_tipo_envio( $t ) ) === ( 'recoger' === $grupo ) );
					if ( ! $del_grupo ) {
						continue;
					}
					?>
					<fieldset class="ah-entrega__grupo">
						<legend><?php echo ah_tema_icono( $icono_grupo, 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $titulo_grupo ); ?></legend>
						<?php foreach ( $del_grupo as $tarifa ) : ?>
							<?php
							$id_input = 'shipping_method_' . $i . '_' . sanitize_title( $tarifa->get_id() );
							$costo    = (float) $tarifa->get_cost() + array_sum( (array) $tarifa->get_taxes() );
							$etiqueta = trim( preg_replace( '/\s*\([^)]*\)\s*$/', '', $tarifa->get_label() ) );
							?>
							<label class="ah-entrega__opcion<?php echo $tarifa->get_id() === $elegido ? ' is-elegida' : ''; ?>" for="<?php echo esc_attr( $id_input ); ?>">
								<input type="radio" name="shipping_method[<?php echo esc_attr( (string) $i ); ?>]" data-index="<?php echo esc_attr( (string) $i ); ?>" id="<?php echo esc_attr( $id_input ); ?>" value="<?php echo esc_attr( $tarifa->get_id() ); ?>" class="shipping_method" <?php checked( $tarifa->get_id(), $elegido ); ?>>
								<span class="ah-entrega__radio" aria-hidden="true"></span>
								<span class="ah-entrega__texto">
									<strong><?php echo esc_html( ah_checkout_texto_llegada( ah_checkout_tipo_envio( $tarifa ) ) ); ?></strong>
									<span><?php echo esc_html( $etiqueta ); ?></span>
								</span>
								<span class="ah-entrega__precio<?php echo $costo <= 0 ? ' is-gratis' : ''; ?>">
									<?php echo $costo > 0 ? wp_kses_post( wc_price( $costo ) ) : esc_html__( 'Gratis', 'artesanias-huejutla' ); ?>
								</span>
								<?php do_action( 'woocommerce_after_shipping_rate', $tarifa, $i ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Gancho de WooCommerce. ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Sección "Forma de entrega" en la columna izquierda.
 */
function ah_checkout_seccion_entrega() {
	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return;
	}
	echo '<section class="ah-checkout-seccion ah-checkout-seccion--entrega" aria-labelledby="ah-entrega-titulo"><h3 id="ah-entrega-titulo" class="ah-checkout-seccion__titulo"><span class="ah-checkout-seccion__paso" aria-hidden="true">2</span>' . esc_html__( 'Forma de entrega', 'artesanias-huejutla' ) . '</h3>';
	echo ah_checkout_entrega_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en la función.
	echo '</section>';
}

/**
 * Sección "Medios de pago" en la columna izquierda.
 */
function ah_checkout_seccion_pago() {
	$paso = WC()->cart && WC()->cart->needs_shipping() ? '3' : '2';
	echo '<section class="ah-checkout-seccion ah-checkout-seccion--pago" aria-labelledby="ah-pago-titulo"><h3 id="ah-pago-titulo" class="ah-checkout-seccion__titulo"><span class="ah-checkout-seccion__paso" aria-hidden="true">' . esc_html( $paso ) . '</span>' . esc_html__( 'Medios de pago', 'artesanias-huejutla' ) . '</h3>';
	woocommerce_checkout_payment();
	// Con solo métodos fuera de línea se dice explícitamente que no se pide tarjeta.
	$disponibles = array_keys( WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array() );
	$con_tarjeta = (bool) array_diff( $disponibles, array( 'bacs', 'cod', 'cheque' ) );
	$texto       = $con_tarjeta
		? __( 'Pago protegido. No guardamos los datos de tu tarjeta.', 'artesanias-huejutla' )
		: __( 'Esta tienda no te pedirá datos de tarjeta: pagas por transferencia o al recibir tu pedido.', 'artesanias-huejutla' );
	echo '<p class="ah-pago__seguro">' . ah_tema_icono( 'escudo', 16 ) . esc_html( $texto ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</section>';
}

/**
 * Bloque final del resumen: términos, botón "Pagar y finalizar" y ganchos de las pasarelas.
 */
function ah_checkout_finalizar() {
	$texto = apply_filters( 'woocommerce_order_button_text', __( 'Pagar y finalizar', 'artesanias-huejutla' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Filtro de WooCommerce.
	$clase = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
	?>
	<div class="form-row place-order ah-finalizar">
		<noscript>
			<?php esc_html_e( 'Tu navegador no tiene JavaScript activo: pulsa "Actualizar totales" antes de finalizar.', 'artesanias-huejutla' ); ?>
			<br/><button type="submit" class="button alt<?php echo esc_attr( $clase ); ?>" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Actualizar totales', 'artesanias-huejutla' ); ?>"><?php esc_html_e( 'Actualizar totales', 'artesanias-huejutla' ); ?></button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt' . esc_attr( $clase ) . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $texto ) . '" data-value="' . esc_attr( $texto ) . '">' . esc_html( $texto ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
	<?php
}

/**
 * Ahorro del carrito: precio regular menos precio de oferta, más cupones.
 *
 * @return float
 */
function ah_checkout_ahorro() {
	$ahorro = 0.0;
	foreach ( WC()->cart->get_cart() as $item ) {
		$producto = $item['data'];
		if ( $producto instanceof WC_Product && $producto->is_on_sale() ) {
			$ahorro += max( 0, (float) $producto->get_regular_price() - (float) $producto->get_price() ) * (int) $item['quantity'];
		}
	}
	return $ahorro + (float) WC()->cart->get_discount_total();
}
