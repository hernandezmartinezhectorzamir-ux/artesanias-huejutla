<?php
/**
 * Google Analytics 4 y Google Search Console.
 *
 * - gtag.js solo se inserta si el administrador captura un ID de medición válido.
 * - Eventos recomendados de comercio electrónico: view_item_list, view_item,
 *   add_to_cart, view_cart, begin_checkout y purchase.
 * - Todos los eventos también se envían a window.dataLayer (compatible con GTM).
 * - No se generan ni se muestran datos de Analytics inventados.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Medición de GA4.
 */
final class AH_Analitica {

	/** Clave de sesión de WooCommerce con eventos pendientes. */
	const SESION = 'ah_ga4_pendientes';

	/**
	 * Eventos que se imprimirán en la página actual.
	 *
	 * @var array<int,array{0:string,1:array}>
	 */
	private static $eventos = array();

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'wp_head', array( __CLASS__, 'verificacion_search_console' ), 1 );

		if ( ! self::activa() ) {
			return;
		}
		add_action( 'wp_head', array( __CLASS__, 'etiqueta_gtag' ), 2 );
		add_action( 'wp_footer', array( __CLASS__, 'imprimir_eventos' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'script' ) );

		// Eventos de WooCommerce.
		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'view_item' ) );
		add_action( 'woocommerce_after_shop_loop', array( __CLASS__, 'view_item_list' ) );
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'add_to_cart' ), 10, 4 );
		add_action( 'woocommerce_after_cart', array( __CLASS__, 'view_cart' ) );
		add_action( 'woocommerce_after_checkout_form', array( __CLASS__, 'begin_checkout' ) );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'purchase' ) );
		add_filter( 'woocommerce_loop_add_to_cart_args', array( __CLASS__, 'datos_boton' ), 10, 2 );
	}

	/**
	 * La medición está activa solo con un ID válido y fuera de exclusiones.
	 *
	 * @return bool
	 */
	public static function activa() {
		return AH_Calculos::ga4_id_valido( (string) ah_gti_opcion( 'ga4_id' ) );
	}

	/**
	 * Indica si se debe medir la visita actual (se evalúa al dibujar la página).
	 *
	 * @return bool
	 */
	public static function medir() {
		return ! ( ah_gti_opcion( 'ga4_excluir_admin' ) && current_user_can( 'manage_options' ) );
	}

	/**
	 * Meta de verificación de Search Console (si se configuró).
	 */
	public static function verificacion_search_console() {
		$codigo = (string) ah_gti_opcion( 'gsc_verificacion' );
		if ( '' !== $codigo ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $codigo ) . '">' . "\n";
		}
	}

	/**
	 * Etiqueta global de gtag.js.
	 */
	public static function etiqueta_gtag() {
		if ( ! self::medir() ) {
			return;
		}
		$id     = (string) ah_gti_opcion( 'ga4_id' );
		$config = array( 'send_page_view' => true );
		if ( ah_gti_opcion( 'ga4_debug' ) ) {
			$config['debug_mode'] = true;
		}
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- gtag.js debe cargarse en el head con el ID.
		echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $id ) . '"></script>' . "\n";
		echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . wp_json_encode( $id ) . ',' . wp_json_encode( $config ) . ');</script>' . "\n";
	}

	/**
	 * Script para add_to_cart por AJAX en los listados.
	 */
	public static function script() {
		if ( ! self::medir() ) {
			return;
		}
		wp_enqueue_script( 'ah-gti-ga4', AH_GTI_URL . 'assets/js/ga4.js', array( 'jquery' ), ah_gti_version_archivo( 'assets/js/ga4.js' ), true );
		wp_localize_script( 'ah-gti-ga4', 'ahGa4', array( 'moneda' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'MXN' ) );
	}

	/**
	 * Agrega los datos del producto al botón "Agregar al carrito" del listado.
	 *
	 * @param array      $args    Argumentos del botón.
	 * @param WC_Product $product Producto.
	 * @return array
	 */
	public static function datos_boton( $args, $product ) {
		$args['attributes']['data-ah_item'] = wp_json_encode( self::item( $product ) );
		return $args;
	}

	/**
	 * Formato "item" de GA4.
	 *
	 * @param WC_Product $producto Producto.
	 * @param int        $cantidad Cantidad.
	 * @return array<string,mixed>
	 */
	public static function item( $producto, $cantidad = 1 ) {
		$categorias = wp_get_post_terms( $producto->get_parent_id() ? $producto->get_parent_id() : $producto->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		return array(
			'item_id'       => $producto->get_sku() ? $producto->get_sku() : (string) $producto->get_id(),
			'item_name'     => $producto->get_name(),
			'item_brand'    => 'Artesanías Huejutla',
			'item_category' => is_array( $categorias ) && $categorias ? $categorias[0] : '',
			'price'         => (float) wc_get_price_to_display( $producto ),
			'quantity'      => (int) $cantidad,
		);
	}

	/**
	 * Encola un evento para imprimirlo en esta página.
	 *
	 * @param string $nombre Nombre del evento.
	 * @param array  $datos  Parámetros.
	 */
	private static function agregar( $nombre, array $datos ) {
		self::$eventos[] = array( $nombre, $datos );
	}

	/**
	 * view_item en la ficha de producto.
	 */
	public static function view_item() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		self::agregar(
			'view_item',
			array(
				'currency' => get_woocommerce_currency(),
				'value'    => (float) wc_get_price_to_display( $product ),
				'items'    => array( self::item( $product ) ),
			)
		);
	}

	/**
	 * view_item_list en catálogo y categorías.
	 */
	public static function view_item_list() {
		global $wp_query;
		$items = array();
		foreach ( (array) $wp_query->posts as $indice => $post ) {
			$producto = wc_get_product( $post );
			if ( $producto ) {
				$item          = self::item( $producto );
				$item['index'] = $indice;
				$items[]       = $item;
			}
		}
		if ( $items ) {
			$lista = is_product_category() ? single_term_title( '', false ) : __( 'Catálogo', 'ah-gestion-ti' );
			self::agregar(
				'view_item_list',
				array(
					'item_list_name' => $lista,
					'items'          => $items,
				)
			);
		}
	}

	/**
	 * add_to_cart desde formularios normales (no AJAX).
	 *
	 * Los agregados por AJAX se miden en el navegador con ga4.js para no
	 * contarlos dos veces.
	 *
	 * @param string $clave        Clave del carrito.
	 * @param int    $producto_id  Producto.
	 * @param int    $cantidad     Cantidad.
	 * @param int    $variacion_id Variación.
	 */
	public static function add_to_cart( $clave, $producto_id, $cantidad, $variacion_id ) {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$producto = wc_get_product( $variacion_id ? $variacion_id : $producto_id );
		if ( ! $producto ) {
			return;
		}
		$pendientes   = (array) WC()->session->get( self::SESION, array() );
		$pendientes[] = array(
			'add_to_cart',
			array(
				'currency' => get_woocommerce_currency(),
				'value'    => (float) wc_get_price_to_display( $producto ) * (int) $cantidad,
				'items'    => array( self::item( $producto, $cantidad ) ),
			),
		);
		WC()->session->set( self::SESION, $pendientes );
	}

	/**
	 * Datos del carrito actual en formato GA4.
	 *
	 * @return array{value:float,items:array}
	 */
	private static function datos_carrito() {
		$items = array();
		foreach ( WC()->cart->get_cart() as $linea ) {
			$items[] = self::item( $linea['data'], $linea['quantity'] );
		}
		return array(
			'currency' => get_woocommerce_currency(),
			'value'    => (float) WC()->cart->get_cart_contents_total(),
			'items'    => $items,
		);
	}

	/**
	 * view_cart.
	 */
	public static function view_cart() {
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			self::agregar( 'view_cart', self::datos_carrito() );
		}
	}

	/**
	 * begin_checkout.
	 */
	public static function begin_checkout() {
		if ( WC()->cart && ! WC()->cart->is_empty() && ! is_order_received_page() ) {
			self::agregar( 'begin_checkout', self::datos_carrito() );
		}
	}

	/**
	 * purchase en la página de pedido recibido (una sola vez por pedido).
	 *
	 * @param int $pedido_id Pedido.
	 */
	public static function purchase( $pedido_id ) {
		$pedido = wc_get_order( $pedido_id );
		if ( ! $pedido || $pedido->get_meta( '_ah_ga4_purchase' ) ) {
			return;
		}
		$items = array();
		foreach ( $pedido->get_items() as $linea ) {
			$producto = $linea->get_product();
			if ( $producto ) {
				$item          = self::item( $producto, $linea->get_quantity() );
				$item['price'] = (float) $pedido->get_item_subtotal( $linea, true );
				$items[]       = $item;
			}
		}
		self::agregar(
			'purchase',
			array(
				'transaction_id' => (string) $pedido->get_order_number(),
				'currency'       => $pedido->get_currency(),
				'value'          => (float) $pedido->get_total(),
				'shipping'       => (float) $pedido->get_shipping_total(),
				'tax'            => (float) $pedido->get_total_tax(),
				'coupon'         => implode( ',', $pedido->get_coupon_codes() ),
				'items'          => $items,
			)
		);
		$pedido->update_meta_data( '_ah_ga4_purchase', current_time( 'mysql' ) );
		$pedido->save();
	}

	/**
	 * Imprime los eventos de la página y los pendientes de la sesión.
	 */
	public static function imprimir_eventos() {
		if ( ! self::medir() ) {
			return;
		}
		if ( function_exists( 'WC' ) && WC()->session ) {
			$pendientes = (array) WC()->session->get( self::SESION, array() );
			if ( $pendientes ) {
				self::$eventos = array_merge( $pendientes, self::$eventos );
				WC()->session->set( self::SESION, array() );
			}
		}
		if ( ! self::$eventos ) {
			return;
		}
		echo '<script>';
		foreach ( self::$eventos as list( $nombre, $datos ) ) {
			printf( 'gtag("event",%1$s,%2$s);', wp_json_encode( $nombre ), wp_json_encode( $datos ) );
		}
		echo '</script>' . "\n";
	}
}
