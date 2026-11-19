<?php
/**
 * Reglas de negocio de la tienda (WooCommerce).
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ajustes de comportamiento de WooCommerce sin modificar su núcleo.
 */
final class AH_Tienda {

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'ocultar_estandar_si_gratis' ), 20 );
		add_action( 'woocommerce_before_cart_table', array( __CLASS__, 'aviso_envio_gratis' ) );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'aviso_pago_demo' ) );
	}

	/**
	 * Si el pedido obtiene envío gratis, oculta las tarifas fijas "estándar"
	 * (conserva express y recolección en taller).
	 *
	 * @param array<string,WC_Shipping_Rate> $tarifas Tarifas.
	 * @return array<string,WC_Shipping_Rate>
	 */
	public static function ocultar_estandar_si_gratis( $tarifas ) {
		$hay_gratis = false;
		foreach ( $tarifas as $tarifa ) {
			if ( 'free_shipping' === $tarifa->get_method_id() ) {
				$hay_gratis = true;
			}
		}
		if ( ! $hay_gratis ) {
			return $tarifas;
		}
		foreach ( $tarifas as $id => $tarifa ) {
			if ( 'flat_rate' === $tarifa->get_method_id() && false !== stripos( $tarifa->get_label(), 'estándar' ) ) {
				unset( $tarifas[ $id ] );
			}
		}
		return $tarifas;
	}

	/**
	 * Monto mínimo configurado para envío gratis (lee la zona de envío).
	 *
	 * @return float
	 */
	public static function minimo_envio_gratis() {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return 0.0;
		}
		foreach ( WC_Shipping_Zones::get_zones() as $zona ) {
			foreach ( $zona['shipping_methods'] as $metodo ) {
				if ( 'free_shipping' === $metodo->id && 'yes' === $metodo->enabled && (float) $metodo->min_amount > 0 ) {
					return (float) $metodo->min_amount;
				}
			}
		}
		return 0.0;
	}

	/**
	 * Aviso en el carrito de cuánto falta para el envío gratis.
	 */
	public static function aviso_envio_gratis() {
		$minimo = self::minimo_envio_gratis();
		if ( ! $minimo || ! WC()->cart ) {
			return;
		}
		$subtotal = (float) WC()->cart->get_displayed_subtotal();
		$falta    = $minimo - $subtotal;
		$avance   = min( 100, round( $subtotal / $minimo * 100 ) );
		echo '<div class="ah-progreso-envio">';
		if ( $falta > 0 ) {
			/* translators: %s: monto faltante. */
			echo '<p>' . wp_kses_post( sprintf( __( 'Te faltan %s para obtener <strong>envío estándar gratis</strong>.', 'ah-gestion-ti' ), wc_price( $falta ) ) ) . '</p>';
		} else {
			echo '<p>' . esc_html__( '¡Tu pedido tiene envío estándar gratis!', 'ah-gestion-ti' ) . '</p>';
		}
		echo '<div class="ah-progreso-envio__barra" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . (int) $avance . '"><span style="width:' . (int) $avance . '%"></span></div></div>';
	}

	/**
	 * Aviso de que no se procesan pagos reales mientras no haya pasarela configurada.
	 */
	public static function aviso_pago_demo() {
		if ( 'production' === wp_get_environment_type() ) {
			return;
		}
		$disponibles = function_exists( 'WC' ) && WC()->payment_gateways() ? array_keys( WC()->payment_gateways()->get_available_payment_gateways() ) : array();
		$en_linea    = array_diff( $disponibles, array( 'bacs', 'cod', 'cheque' ) );
		$texto       = $en_linea
			? __( 'Entorno de pruebas: las pasarelas en línea deben usar credenciales y tarjetas de prueba (sandbox) de Mercado Pago o PayPal. No uses tarjetas reales.', 'ah-gestion-ti' )
			: __( 'Entorno de pruebas: los pedidos usan métodos de pago fuera de línea (transferencia o contra entrega). No se procesan cobros ni se solicitan datos de tarjeta.', 'ah-gestion-ti' );
		echo '<p class="ah-aviso-demo">' . esc_html( $texto ) . '</p>';
	}
}
