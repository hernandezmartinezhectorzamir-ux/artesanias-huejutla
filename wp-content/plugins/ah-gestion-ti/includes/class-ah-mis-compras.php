<?php
/**
 * Mis compras: seguimiento de pedidos para el cliente.
 *
 * - Sección "Mis compras" en Mi cuenta (lista y detalle de cada compra).
 * - Etapas: Compra confirmada → En preparación → En camino → Entregado,
 *   con fecha y hora reales de cada cambio de estado.
 * - Estado de pedido "En camino" con paquetería y número de guía.
 * - Fecha estimada de entrega (días hábiles) e indicador "A tiempo"/"Retrasado".
 * - Notificaciones para el cliente (campana del encabezado) y correo al salir a ruta.
 * - Rastreo para compras sin cuenta: [ah_rastreo_pedido] (número de pedido + correo).
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Seguimiento de compras al estilo de un marketplace, integrado con WooCommerce.
 */
final class AH_Mis_Compras {

	/** Endpoint de Mi cuenta. */
	const ENDPOINT = 'mis-compras';

	/** Estado de pedido "En camino" (sin prefijo wc-). */
	const ESTADO_CAMINO = 'en-camino';

	/** Meta del pedido con la fecha de cada etapa. */
	const META_SEGUIMIENTO = '_ah_seguimiento';

	/** Metas del envío. */
	const META_PAQUETERIA = '_ah_paqueteria';
	const META_GUIA       = '_ah_guia';
	const META_RASTREO    = '_ah_url_rastreo';

	/** Meta de usuario con notificaciones. */
	const META_NOTIFICACIONES = '_ah_notificaciones';

	/**
	 * Pedido mostrado por el rastreo público tras una consulta válida.
	 *
	 * @var WC_Order|null
	 */
	private static $pedido_rastreado = null;

	/**
	 * Error del rastreo público.
	 *
	 * @var string
	 */
	private static $error_rastreo = '';

	/**
	 * Paqueterías disponibles.
	 *
	 * @return array<string,string>
	 */
	public static function paqueterias() {
		return array(
			''              => __( 'Selecciona…', 'ah-gestion-ti' ),
			'estafeta'      => 'Estafeta',
			'dhl'           => 'DHL Express',
			'fedex'         => 'FedEx',
			'jt'            => 'J&T Express',
			'paquetexpress' => 'Paquetexpress',
			'correos'       => 'Correos de México',
			'local'         => __( 'Entrega local Artesanías Huejutla', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'init', array( __CLASS__, 'registrar' ) );
		add_action( 'init', array( __CLASS__, 'refrescar_enlaces' ), 99 );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'estados_pedido' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( __CLASS__, 'estados_pagados' ) );
		add_filter( 'woocommerce_valid_order_statuses_for_order_again', array( __CLASS__, 'estados_volver_a_comprar' ) );
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'menu_cuenta' ), 20 );
		add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', array( __CLASS__, 'titulo_endpoint' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'contenido_cuenta' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirigir_pedidos_wc' ) );
		add_action( 'template_redirect', array( __CLASS__, 'procesar_formularios' ) );

		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'registrar_confirmacion' ) );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'al_cambiar_estado' ), 10, 4 );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'boton_seguimiento' ), 5 );

		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'guardar_envio' ), 50 );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'accion_masiva' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'accion_masiva' ) );

		add_shortcode( 'ah_rastreo_pedido', array( __CLASS__, 'shortcode_rastreo' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'recursos' ) );
	}

	/* =====================================================================
	 * Registro: estado "En camino" y endpoint
	 * ================================================================== */

	/**
	 * Estado de pedido y endpoint.
	 */
	public static function registrar() {
		register_post_status(
			'wc-' . self::ESTADO_CAMINO,
			array(
				'label'                     => _x( 'En camino', 'Estado del pedido', 'ah-gestion-ti' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: número de pedidos. */
				'label_count'               => _n_noop( 'En camino <span class="count">(%s)</span>', 'En camino <span class="count">(%s)</span>', 'ah-gestion-ti' ),
			)
		);
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Regenera las reglas de URL una sola vez cuando cambia la versión del módulo.
	 */
	public static function refrescar_enlaces() {
		if ( get_option( 'ah_gti_mis_compras_version' ) !== AH_GTI_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'ah_gti_mis_compras_version', AH_GTI_VERSION, true );
		}
	}

	/**
	 * Agrega "En camino" después de "Procesando".
	 *
	 * @param array<string,string> $estados Estados.
	 * @return array<string,string>
	 */
	public static function estados_pedido( $estados ) {
		$nuevos = array();
		foreach ( $estados as $clave => $nombre ) {
			$nuevos[ $clave ] = $nombre;
			if ( 'wc-processing' === $clave ) {
				$nuevos[ 'wc-' . self::ESTADO_CAMINO ] = _x( 'En camino', 'Estado del pedido', 'ah-gestion-ti' );
			}
		}
		return $nuevos;
	}

	/**
	 * Un pedido en camino ya está pagado (cuenta en reportes y ventas).
	 *
	 * @param array<int,string> $estados Estados pagados.
	 * @return array<int,string>
	 */
	public static function estados_pagados( $estados ) {
		$estados[] = self::ESTADO_CAMINO;
		return $estados;
	}

	/**
	 * Permite "Volver a comprar" en pedidos en preparación, en camino o entregados.
	 *
	 * @param array<int,string> $estados Estados.
	 * @return array<int,string>
	 */
	public static function estados_volver_a_comprar( $estados ) {
		return array_unique( array_merge( $estados, array( 'processing', self::ESTADO_CAMINO, 'completed' ) ) );
	}

	/**
	 * Informa a WooCommerce del endpoint.
	 *
	 * @param array<string,string> $vars Variables.
	 * @return array<string,string>
	 */
	public static function query_vars( $vars ) {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * "Mis compras" reemplaza a "Pedidos" y se quita "Descargas" (no hay productos descargables).
	 *
	 * @param array<string,string> $items Elementos del menú.
	 * @return array<string,string>
	 */
	public static function menu_cuenta( $items ) {
		$nuevo = array();
		foreach ( $items as $clave => $etiqueta ) {
			if ( 'downloads' === $clave ) {
				continue;
			}
			if ( 'orders' === $clave ) {
				$nuevo[ self::ENDPOINT ] = __( 'Mis compras', 'ah-gestion-ti' );
				continue;
			}
			$nuevo[ $clave ] = $etiqueta;
		}
		if ( ! isset( $nuevo[ self::ENDPOINT ] ) ) {
			$nuevo = array_merge( array_slice( $nuevo, 0, 1, true ), array( self::ENDPOINT => __( 'Mis compras', 'ah-gestion-ti' ) ), array_slice( $nuevo, 1, null, true ) );
		}
		return $nuevo;
	}

	/**
	 * Título de la sección.
	 *
	 * @return string
	 */
	public static function titulo_endpoint() {
		return __( 'Mis compras', 'ah-gestion-ti' );
	}

	/**
	 * Las URLs de pedidos de WooCommerce (correos, página de gracias) llevan a Mis compras.
	 */
	public static function redirigir_pedidos_wc() {
		if ( ! is_user_logged_in() || ! function_exists( 'is_wc_endpoint_url' ) ) {
			return;
		}
		global $wp;
		if ( is_wc_endpoint_url( 'view-order' ) ) {
			wp_safe_redirect( self::url_compra( absint( $wp->query_vars['view-order'] ?? 0 ) ) );
			exit;
		}
		if ( is_wc_endpoint_url( 'orders' ) ) {
			wp_safe_redirect( wc_get_account_endpoint_url( self::ENDPOINT ) );
			exit;
		}
	}

	/**
	 * URL del detalle de una compra.
	 *
	 * @param int $pedido_id Pedido.
	 * @return string
	 */
	public static function url_compra( $pedido_id ) {
		return wc_get_endpoint_url( self::ENDPOINT, (string) (int) $pedido_id, wc_get_page_permalink( 'myaccount' ) );
	}

	/**
	 * Estilos y comportamiento del panel de seguimiento.
	 */
	public static function recursos() {
		wp_register_style( 'ah-mis-compras', AH_GTI_URL . 'assets/css/mis-compras.css', array(), AH_GTI_VERSION . '.' . filemtime( AH_GTI_DIR . 'assets/css/mis-compras.css' ) );
		wp_register_script( 'ah-mis-compras', AH_GTI_URL . 'assets/js/mis-compras.js', array(), AH_GTI_VERSION . '.' . filemtime( AH_GTI_DIR . 'assets/js/mis-compras.js' ), true );
		global $post;
		$con_rastreo = $post && has_shortcode( (string) $post->post_content, 'ah_rastreo_pedido' );
		if ( is_account_page() || is_order_received_page() || $con_rastreo ) {
			wp_enqueue_style( 'ah-mis-compras' );
			wp_enqueue_script( 'ah-mis-compras' );
		}
	}

	/* =====================================================================
	 * Registro de etapas y notificaciones
	 * ================================================================== */

	/**
	 * Guarda la fecha de confirmación al crear el pedido.
	 *
	 * @param int $pedido_id Pedido.
	 */
	public static function registrar_confirmacion( $pedido_id ) {
		$pedido = wc_get_order( $pedido_id );
		if ( $pedido ) {
			self::marcar( $pedido, 'confirmada' );
			$pedido->save();
		}
	}

	/**
	 * Registra la hora real de cada etapa y avisa al cliente.
	 *
	 * @param int      $pedido_id Pedido.
	 * @param string   $de        Estado anterior.
	 * @param string   $a         Estado nuevo.
	 * @param WC_Order $pedido    Pedido.
	 */
	public static function al_cambiar_estado( $pedido_id, $de, $a, $pedido ) {
		$etapas = array(
			'on-hold'           => 'confirmada',
			'processing'        => 'preparacion',
			self::ESTADO_CAMINO => 'camino',
			'completed'         => 'entregado',
			'cancelled'         => 'cancelado',
			'refunded'          => 'reembolsado',
		);
		if ( isset( $etapas[ $a ] ) ) {
			self::marcar( $pedido, 'confirmada' );
			self::marcar( $pedido, $etapas[ $a ] );
			$pedido->save();
		}

		$numero   = $pedido->get_order_number();
		$mensajes = array(
			'on-hold'           => sprintf( /* translators: %s: número. */ __( 'Recibimos tu compra #%s. Esperamos la confirmación de tu pago.', 'ah-gestion-ti' ), $numero ),
			'processing'        => sprintf( /* translators: %s: número. */ __( 'Tu compra #%s está en preparación.', 'ah-gestion-ti' ), $numero ),
			self::ESTADO_CAMINO => sprintf( /* translators: %s: número. */ __( '¡Tu compra #%s va en camino!', 'ah-gestion-ti' ), $numero ),
			'completed'         => sprintf( /* translators: %s: número. */ __( 'Tu compra #%s fue entregada. ¿Qué te pareció?', 'ah-gestion-ti' ), $numero ),
			'cancelled'         => sprintf( /* translators: %s: número. */ __( 'Tu compra #%s fue cancelada.', 'ah-gestion-ti' ), $numero ),
		);
		if ( isset( $mensajes[ $a ] ) && $pedido->get_customer_id() ) {
			self::notificar( $pedido->get_customer_id(), $pedido->get_id(), $mensajes[ $a ] );
		}
		if ( self::ESTADO_CAMINO === $a ) {
			self::correo_en_camino( $pedido );
		}
	}

	/**
	 * Guarda la fecha de una etapa si aún no existe.
	 *
	 * @param WC_Order $pedido Pedido.
	 * @param string   $etapa  Etapa.
	 */
	private static function marcar( WC_Order $pedido, $etapa ) {
		$registro = (array) $pedido->get_meta( self::META_SEGUIMIENTO );
		if ( empty( $registro[ $etapa ] ) ) {
			$registro[ $etapa ] = 'confirmada' === $etapa && $pedido->get_date_created() ? $pedido->get_date_created()->getTimestamp() : time();
			$pedido->update_meta_data( self::META_SEGUIMIENTO, $registro );
		}
	}

	/**
	 * Agrega una notificación al cliente (se muestran en la campana del encabezado).
	 *
	 * @param int    $user_id   Cliente.
	 * @param int    $pedido_id Pedido.
	 * @param string $texto     Mensaje.
	 */
	public static function notificar( $user_id, $pedido_id, $texto ) {
		$lista   = self::notificaciones( $user_id );
		$lista[] = array(
			'id'     => wp_generate_password( 10, false ),
			'pedido' => (int) $pedido_id,
			'texto'  => sanitize_text_field( $texto ),
			't'      => time(),
			'leida'  => false,
		);
		update_user_meta( $user_id, self::META_NOTIFICACIONES, array_slice( $lista, -20 ) );
	}

	/**
	 * Notificaciones de un cliente (más recientes al final).
	 *
	 * @param int $user_id Cliente.
	 * @return array<int,array>
	 */
	public static function notificaciones( $user_id ) {
		$lista = get_user_meta( $user_id, self::META_NOTIFICACIONES, true );
		return is_array( $lista ) ? $lista : array();
	}

	/**
	 * Número de notificaciones sin leer.
	 *
	 * @param int $user_id Cliente.
	 * @return int
	 */
	public static function sin_leer( $user_id ) {
		return count( array_filter( self::notificaciones( $user_id ), static fn( $n ) => empty( $n['leida'] ) ) );
	}

	/**
	 * Marca como leídas las notificaciones (todas o las de un pedido).
	 *
	 * @param int $user_id   Cliente.
	 * @param int $pedido_id Pedido (0 = todas).
	 */
	public static function marcar_leidas( $user_id, $pedido_id = 0 ) {
		$lista   = self::notificaciones( $user_id );
		$cambios = false;
		foreach ( $lista as &$n ) {
			if ( empty( $n['leida'] ) && ( ! $pedido_id || (int) $n['pedido'] === (int) $pedido_id ) ) {
				$n['leida'] = true;
				$cambios    = true;
			}
		}
		unset( $n );
		if ( $cambios ) {
			update_user_meta( $user_id, self::META_NOTIFICACIONES, $lista );
		}
	}

	/**
	 * Formularios del cliente: marcar notificaciones y rastreo público.
	 */
	public static function procesar_formularios() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Cada acción verifica su nonce.
		$accion = isset( $_POST['ah_compras_accion'] ) ? sanitize_key( wp_unslash( $_POST['ah_compras_accion'] ) ) : '';
		// phpcs:enable
		if ( 'leidas' === $accion && is_user_logged_in() && check_admin_referer( 'ah_notificaciones_leidas', 'ah_nonce' ) ) {
			self::marcar_leidas( get_current_user_id() );
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : wc_get_account_endpoint_url( self::ENDPOINT ) );
			exit;
		}
		if ( 'rastrear' === $accion ) {
			self::procesar_rastreo();
		}
	}

	/**
	 * Correo al cliente cuando su compra sale a ruta.
	 *
	 * @param WC_Order $pedido Pedido.
	 */
	private static function correo_en_camino( WC_Order $pedido ) {
		$correo = $pedido->get_billing_email();
		if ( ! $correo || ! function_exists( 'WC' ) ) {
			return;
		}
		$envio  = self::datos_envio( $pedido );
		$enlace = $pedido->get_customer_id() ? self::url_compra( $pedido->get_id() ) : self::url_rastreo();
		/* translators: %s: número de pedido. */
		$titulo    = sprintf( __( '¡Tu compra #%s va en camino!', 'ah-gestion-ti' ), $pedido->get_order_number() );
		$contenido = '<p>' . esc_html( sprintf( /* translators: %s: nombre. */ __( 'Hola %s:', 'ah-gestion-ti' ), $pedido->get_billing_first_name() ) ) . '</p>'
			. '<p>' . esc_html__( 'Tus artesanías ya salieron de nuestro taller en Huejutla.', 'ah-gestion-ti' ) . '</p>';
		if ( $envio['paqueteria'] ) {
			$contenido .= '<p><strong>' . esc_html__( 'Paquetería:', 'ah-gestion-ti' ) . '</strong> ' . esc_html( $envio['paqueteria'] ) . '<br><strong>' . esc_html__( 'Número de guía:', 'ah-gestion-ti' ) . '</strong> ' . esc_html( $envio['guia'] ) . '</p>';
		}
		$contenido .= '<p><a href="' . esc_url( $enlace ) . '">' . esc_html__( 'Ver el seguimiento de tu compra', 'ah-gestion-ti' ) . '</a></p>';
		$mailer     = WC()->mailer();
		$mailer->send( $correo, $titulo, $mailer->wrap_message( $titulo, $contenido ) );
	}

	/* =====================================================================
	 * Datos de presentación
	 * ================================================================== */

	/**
	 * Tipo y nombre del método de envío del pedido.
	 *
	 * @param WC_Order $pedido Pedido.
	 * @return array{tipo:string,nombre:string}
	 */
	public static function metodo_envio( WC_Order $pedido ) {
		$metodos = $pedido->get_shipping_methods();
		$metodo  = $metodos ? reset( $metodos ) : null;
		if ( ! $metodo ) {
			return array(
				'tipo'   => 'estandar',
				'nombre' => __( 'Envío estándar', 'ah-gestion-ti' ),
			);
		}
		$nombre = $metodo->get_name();
		$tipo   = 'estandar';
		if ( 'local_pickup' === $metodo->get_method_id() || false !== stripos( $nombre, 'recoger' ) ) {
			$tipo = 'recoger';
		} elseif ( false !== stripos( $nombre, 'express' ) ) {
			$tipo = 'express';
		}
		return array(
			'tipo'   => $tipo,
			'nombre' => $nombre,
		);
	}

	/**
	 * Paquetería, guía y URL de rastreo.
	 *
	 * @param WC_Order $pedido Pedido.
	 * @return array{paqueteria:string,guia:string,url:string}
	 */
	public static function datos_envio( WC_Order $pedido ) {
		$clave = (string) $pedido->get_meta( self::META_PAQUETERIA );
		$lista = self::paqueterias();
		return array(
			'paqueteria' => $clave && isset( $lista[ $clave ] ) ? $lista[ $clave ] : '',
			'guia'       => (string) $pedido->get_meta( self::META_GUIA ),
			'url'        => (string) $pedido->get_meta( self::META_RASTREO ),
		);
	}

	/**
	 * Fecha con formato corto en español: "08 nov 21:52".
	 *
	 * @param int $timestamp Marca de tiempo.
	 * @return string
	 */
	private static function fecha_corta( $timestamp ) {
		return mb_strtolower( wp_date( 'd M H:i', $timestamp ) );
	}

	/**
	 * Estado de seguimiento completo del pedido.
	 *
	 * @param WC_Order $pedido Pedido.
	 * @return array<string,mixed>
	 */
	public static function seguimiento( WC_Order $pedido ) {
		$estado   = $pedido->get_status();
		$registro = (array) $pedido->get_meta( self::META_SEGUIMIENTO );
		$creado   = $pedido->get_date_created() ? $pedido->get_date_created()->getTimestamp() : time();
		$envio    = self::metodo_envio( $pedido );
		$recoger  = 'recoger' === $envio['tipo'];

		// Respaldo para pedidos anteriores al módulo.
		$registro['confirmada'] = $registro['confirmada'] ?? $creado;
		if ( empty( $registro['preparacion'] ) && in_array( $estado, array( 'processing', self::ESTADO_CAMINO, 'completed' ), true ) ) {
			$registro['preparacion'] = $pedido->get_date_paid() ? $pedido->get_date_paid()->getTimestamp() : $creado;
		}
		if ( empty( $registro['entregado'] ) && 'completed' === $estado && $pedido->get_date_completed() ) {
			$registro['entregado'] = $pedido->get_date_completed()->getTimestamp();
		}

		$indices   = array(
			'pending'           => 0,
			'failed'            => 0,
			'on-hold'           => 1,
			'processing'        => 2,
			self::ESTADO_CAMINO => 3,
			'completed'         => 4,
		);
		$actual    = $indices[ $estado ] ?? 1;
		$cancelado = in_array( $estado, array( 'cancelled', 'refunded', 'failed' ), true );

		$pasos   = array(
			array( 'confirmada', __( 'Compra confirmada', 'ah-gestion-ti' ), 'on-hold' === $estado ? __( 'Esperamos la confirmación de tu pago.', 'ah-gestion-ti' ) : __( 'Procesamos tu compra.', 'ah-gestion-ti' ), 'check' ),
			array( 'preparacion', __( 'En preparación', 'ah-gestion-ti' ), __( 'Las familias artesanas revisan y empacan tus piezas en Huejutla.', 'ah-gestion-ti' ), 'paquete' ),
			array( 'camino', $recoger ? __( 'Lista para recoger', 'ah-gestion-ti' ) : __( 'En camino', 'ah-gestion-ti' ), $recoger ? __( 'Tu compra te espera en el taller de Huejutla.', 'ah-gestion-ti' ) : __( 'Tu paquete va rumbo a tu domicilio.', 'ah-gestion-ti' ), $recoger ? 'manos' : 'camion' ),
			array( 'entregado', __( 'Entregado', 'ah-gestion-ti' ), __( '¡Disfruta tus artesanías!', 'ah-gestion-ti' ), 'casa' ),
		);
		$detalle = array();
		foreach ( $pasos as $i => list( $clave, $nombre, $texto, $icono ) ) {
			$numero = $i + 1;
			$fecha  = $registro[ $clave ] ?? null;
			if ( $cancelado ) {
				$estado_paso = $fecha ? 'hecho' : 'pendiente';
			} elseif ( $numero < $actual || ( 4 === $actual ) ) {
				$estado_paso = 'hecho';
			} elseif ( $numero === $actual ) {
				$estado_paso = 'actual';
			} else {
				$estado_paso = 'pendiente';
			}
			$detalle[] = array(
				'clave'  => $clave,
				'nombre' => $nombre,
				'texto'  => $texto,
				'icono'  => $icono,
				'estado' => $estado_paso,
				'fecha'  => $fecha ? self::fecha_corta( (int) $fecha ) : '',
			);
		}

		$titulares                = array(
			'pending'           => array( __( 'Esperando tu pago', 'ah-gestion-ti' ), __( 'Completa el pago para que comencemos a preparar tu compra.', 'ah-gestion-ti' ) ),
			'failed'            => array( __( 'No pudimos procesar el pago', 'ah-gestion-ti' ), __( 'Intenta de nuevo o elige otra forma de pago.', 'ah-gestion-ti' ) ),
			'on-hold'           => array( __( 'Recibimos tu compra', 'ah-gestion-ti' ), __( 'En cuanto confirmemos tu pago comenzaremos a prepararla.', 'ah-gestion-ti' ) ),
			'processing'        => array( __( 'Estamos preparando tu compra', 'ah-gestion-ti' ), __( 'Revisamos cada pieza a mano antes de empacarla.', 'ah-gestion-ti' ) ),
			self::ESTADO_CAMINO => $recoger ? array( __( 'Tu compra está lista para recoger', 'ah-gestion-ti' ), __( 'Pasa al taller con tu número de compra.', 'ah-gestion-ti' ) ) : array( __( 'Tu compra va en camino', 'ah-gestion-ti' ), __( 'La paquetería ya tiene tu paquete.', 'ah-gestion-ti' ) ),
			'completed'         => array( __( '¡Tu compra llegó!', 'ah-gestion-ti' ), __( 'Gracias por apoyar el trabajo de las familias artesanas de Huejutla.', 'ah-gestion-ti' ) ),
			'cancelled'         => array( __( 'Compra cancelada', 'ah-gestion-ti' ), __( 'Si tienes dudas, contáctanos desde la mesa de ayuda.', 'ah-gestion-ti' ) ),
			'refunded'          => array( __( 'Compra reembolsada', 'ah-gestion-ti' ), __( 'El reembolso se realizó por el mismo medio de pago.', 'ah-gestion-ti' ) ),
		);
		list( $titulo, $mensaje ) = $titulares[ $estado ] ?? array( wc_get_order_status_name( $estado ), '' );

		// Ventana de entrega estimada en días hábiles desde la compra.
		$zona                    = wp_timezone();
		$desde                   = ( new DateTimeImmutable( '@' . $creado ) )->setTimezone( $zona );
		list( $minimo, $maximo ) = AH_Calculos::ventana_entrega( $desde, $envio['tipo'] );
		$fin_dia                 = $maximo->setTime( 23, 59, 59 )->getTimestamp();
		$entregado_ts            = $registro['entregado'] ?? null;
		$puntualidad             = '';
		if ( ! $cancelado ) {
			if ( $entregado_ts ) {
				$puntualidad = $entregado_ts <= $fin_dia ? 'a-tiempo' : 'retrasado';
			} else {
				$puntualidad = time() <= $fin_dia ? 'a-tiempo' : 'retrasado';
			}
		}
		if ( $entregado_ts ) {
			/* translators: %s: fecha. */
			$entrega = sprintf( __( 'Entregado el %s', 'ah-gestion-ti' ), wp_date( 'j \d\e F', (int) $entregado_ts ) );
		} elseif ( $recoger ) {
			/* translators: %s: fecha. */
			$entrega = sprintf( __( 'Lista para recoger a partir del %s', 'ah-gestion-ti' ), wp_date( 'j \d\e F', $minimo->getTimestamp() ) );
		} elseif ( $minimo->format( 'm' ) === $maximo->format( 'm' ) ) {
			/* translators: 1: día inicial, 2: día final y mes. */
			$entrega = sprintf( __( 'Llega entre el %1$s y %2$s', 'ah-gestion-ti' ), $minimo->format( 'j' ), wp_date( 'j \d\e F', $maximo->getTimestamp() ) );
		} else {
			/* translators: 1: fecha inicial, 2: fecha final. */
			$entrega = sprintf( __( 'Llega entre el %1$s y el %2$s', 'ah-gestion-ti' ), wp_date( 'j \d\e F', $minimo->getTimestamp() ), wp_date( 'j \d\e F', $maximo->getTimestamp() ) );
		}

		return array(
			'estado'      => $estado,
			'actual'      => $actual,
			'cancelado'   => $cancelado,
			'titulo'      => $titulo,
			'mensaje'     => $mensaje,
			'pasos'       => $detalle,
			'envio'       => $envio,
			'entrega'     => $entrega,
			'puntualidad' => $puntualidad,
			'avance'      => $cancelado ? 0 : min( 100, max( 0, ( $actual - 1 ) / 3 * 100 ) ),
		);
	}

	/**
	 * Ícono del tema (si está disponible).
	 *
	 * @param string $nombre Ícono.
	 * @param int    $tamano Tamaño.
	 * @return string
	 */
	private static function icono( $nombre, $tamano = 20 ) {
		return function_exists( 'ah_tema_icono' ) ? ah_tema_icono( $nombre, $tamano ) : '';
	}

	/* =====================================================================
	 * Vistas
	 * ================================================================== */

	/**
	 * Contenido de Mi cuenta → Mis compras (lista o detalle).
	 *
	 * @param string $valor ID del pedido en la URL (detalle) o vacío (lista).
	 */
	public static function contenido_cuenta( $valor = '' ) {
		wp_enqueue_style( 'ah-mis-compras' );
		wp_enqueue_script( 'ah-mis-compras' );
		$id = absint( $valor );
		if ( $id ) {
			$pedido = wc_get_order( $id );
			// Seguridad: solo el dueño de la compra puede verla.
			if ( ! $pedido || (int) $pedido->get_customer_id() !== get_current_user_id() ) {
				echo '<div class="woocommerce-error">' . esc_html__( 'No encontramos esta compra en tu cuenta.', 'ah-gestion-ti' ) . '</div>';
				self::lista();
				return;
			}
			self::marcar_leidas( get_current_user_id(), $id );
			self::detalle( $pedido, true );
			return;
		}
		self::lista();
	}

	/**
	 * Lista de compras del cliente en tarjetas.
	 */
	private static function lista() {
		$pedidos = wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'limit'       => 30,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'status'      => array_keys( wc_get_order_statuses() ),
			)
		);
		echo '<div class="ah-compras">';
		if ( ! $pedidos ) {
			echo '<div class="ah-compras__vacio">' . self::icono( 'bolsa', 40 ) . '<h2>' . esc_html__( 'Aún no tienes compras', 'ah-gestion-ti' ) . '</h2><p>' . esc_html__( 'Cuando compres, aquí podrás seguir cada paso: preparación, envío y entrega.', 'ah-gestion-ti' ) . '</p><a class="ah-boton" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Descubrir artesanías', 'ah-gestion-ti' ) . '</a></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno.
			return;
		}
		foreach ( $pedidos as $pedido ) {
			$s      = self::seguimiento( $pedido );
			$items  = $pedido->get_items();
			$primer = $items ? reset( $items ) : null;
			$prod   = $primer ? $primer->get_product() : null;
			$resto  = max( 0, count( $items ) - 1 );
			?>
			<article class="ah-compra-tarjeta ah-compra-tarjeta--<?php echo esc_attr( $s['estado'] ); ?>">
				<header class="ah-compra-tarjeta__cabecera">
					<span><?php echo esc_html( wp_date( 'j \d\e F \d\e Y', $pedido->get_date_created()->getTimestamp() ) ); ?></span>
					<span><?php echo esc_html( sprintf( /* translators: %s: número. */ __( 'Compra #%s', 'ah-gestion-ti' ), $pedido->get_order_number() ) ); ?></span>
				</header>
				<div class="ah-compra-tarjeta__cuerpo">
					<div class="ah-compra-tarjeta__img">
						<?php echo $prod ? wp_kses_post( $prod->get_image( 'woocommerce_gallery_thumbnail' ) ) : ''; ?>
						<?php if ( $resto ) : ?>
							<span>+<?php echo (int) $resto; ?></span>
						<?php endif; ?>
					</div>
					<div class="ah-compra-tarjeta__info">
						<p class="ah-estado-compra ah-estado-compra--<?php echo esc_attr( $s['cancelado'] ? 'cancelado' : $s['estado'] ); ?>"><?php echo esc_html( $s['titulo'] ); ?></p>
						<?php if ( ! $s['cancelado'] ) : ?>
							<p class="ah-compra-tarjeta__entrega"><?php echo esc_html( $s['entrega'] ); ?></p>
						<?php endif; ?>
						<p class="ah-compra-tarjeta__producto"><?php echo $primer ? esc_html( $primer->get_name() ) : ''; ?><?php echo $resto ? ' ' . esc_html( sprintf( /* translators: %d: número de productos. */ _n( 'y %d producto más', 'y %d productos más', $resto, 'ah-gestion-ti' ), $resto ) ) : ''; ?></p>
						<div class="ah-mini-progreso" aria-hidden="true"><span style="width:<?php echo (int) $s['avance']; ?>%"></span></div>
					</div>
					<div class="ah-compra-tarjeta__acciones">
						<strong><?php echo wp_kses_post( $pedido->get_formatted_order_total() ); ?></strong>
						<a class="ah-boton" href="<?php echo esc_url( self::url_compra( $pedido->get_id() ) ); ?>"><?php esc_html_e( 'Ver compra', 'ah-gestion-ti' ); ?></a>
					</div>
				</div>
			</article>
			<?php
		}
		echo '</div>';
	}

	/**
	 * Detalle de una compra con su seguimiento.
	 *
	 * @param WC_Order $pedido      Pedido.
	 * @param bool     $propietario Lo ve el dueño con sesión (muestra acciones de cuenta).
	 */
	public static function detalle( WC_Order $pedido, $propietario ) {
		$s       = self::seguimiento( $pedido );
		$envio   = self::datos_envio( $pedido );
		$numero  = $pedido->get_order_number();
		$mesa    = class_exists( 'AH_Mesa_Ayuda_Publica' ) ? AH_Mesa_Ayuda_Publica::url_pagina() : '';
		$items   = $pedido->get_items();
		$volver  = $propietario && $pedido->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed' ) ) );
		$dialogo = 'ah-seguimiento-' . $pedido->get_id();
		?>
		<div class="ah-compra">
			<?php if ( $propietario ) : ?>
				<a class="ah-compra__volver" href="<?php echo esc_url( wc_get_account_endpoint_url( self::ENDPOINT ) ); ?>">← <?php esc_html_e( 'Mis compras', 'ah-gestion-ti' ); ?></a>
			<?php endif; ?>

			<div class="ah-compra__rejilla">
				<section class="ah-compra__estado" aria-labelledby="ah-compra-titulo">
					<div class="ah-compra__insignias">
						<span class="ah-insignia-envio ah-insignia-envio--<?php echo esc_attr( $s['envio']['tipo'] ); ?>"><?php echo self::icono( 'recoger' === $s['envio']['tipo'] ? 'manos' : 'camion', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $s['envio']['nombre'] ); ?></span>
						<?php if ( $s['puntualidad'] ) : ?>
							<span class="ah-puntualidad ah-puntualidad--<?php echo esc_attr( $s['puntualidad'] ); ?>"><?php echo 'a-tiempo' === $s['puntualidad'] ? esc_html__( 'A tiempo', 'ah-gestion-ti' ) : esc_html__( 'Retrasado', 'ah-gestion-ti' ); ?></span>
							<span class="ah-compra__entrega"><?php echo esc_html( $s['entrega'] ); ?></span>
						<?php endif; ?>
					</div>
					<h2 id="ah-compra-titulo" class="ah-compra__titulo"><?php echo esc_html( $s['titulo'] ); ?></h2>
					<p class="ah-compra__mensaje"><?php echo esc_html( $s['mensaje'] ); ?></p>

					<?php if ( $s['cancelado'] ) : ?>
						<p class="ah-compra__cancelado"><?php echo esc_html( wc_get_order_status_name( $s['estado'] ) ); ?></p>
					<?php else : ?>
						<ol class="ah-progreso" style="--ah-avance:<?php echo esc_attr( (string) $s['avance'] ); ?>%" aria-label="<?php esc_attr_e( 'Avance de tu compra', 'ah-gestion-ti' ); ?>">
							<?php foreach ( $s['pasos'] as $paso ) : ?>
								<li class="ah-progreso__paso ah-progreso__paso--<?php echo esc_attr( $paso['estado'] ); ?>"<?php echo 'actual' === $paso['estado'] ? ' aria-current="step"' : ''; ?>>
									<span class="ah-progreso__punto"><?php echo 'actual' === $paso['estado'] ? self::icono( $paso['icono'], 18 ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="ah-progreso__nombre"><?php echo esc_html( $paso['nombre'] ); ?></span>
									<?php if ( $paso['fecha'] ) : ?>
										<span class="ah-progreso__fecha"><?php echo esc_html( $paso['fecha'] ); ?></span>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>

					<details class="ah-compra__envio">
						<summary><?php esc_html_e( 'Detalle del envío', 'ah-gestion-ti' ); ?></summary>
						<dl>
							<div><dt><?php esc_html_e( 'Método', 'ah-gestion-ti' ); ?></dt><dd><?php echo esc_html( $s['envio']['nombre'] ); ?></dd></div>
							<?php if ( 'recoger' !== $s['envio']['tipo'] ) : ?>
								<div><dt><?php esc_html_e( 'Domicilio', 'ah-gestion-ti' ); ?></dt><dd><?php echo wp_kses_post( $pedido->get_formatted_shipping_address() ? $pedido->get_formatted_shipping_address() : $pedido->get_formatted_billing_address() ); ?></dd></div>
							<?php endif; ?>
							<div><dt><?php esc_html_e( 'Paquetería', 'ah-gestion-ti' ); ?></dt><dd><?php echo $envio['paqueteria'] ? esc_html( $envio['paqueteria'] ) : esc_html__( 'Se asigna al salir del taller', 'ah-gestion-ti' ); ?></dd></div>
							<?php if ( $envio['guia'] ) : ?>
								<div><dt><?php esc_html_e( 'Número de guía', 'ah-gestion-ti' ); ?></dt><dd><code><?php echo esc_html( $envio['guia'] ); ?></code>
								<?php if ( $envio['url'] ) : ?>
									· <a href="<?php echo esc_url( $envio['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Rastrear en la paquetería', 'ah-gestion-ti' ); ?></a>
								<?php endif; ?></dd></div>
							<?php endif; ?>
						</dl>
					</details>

					<ul class="ah-compra__acciones">
						<li><button type="button" class="ah-accion-circular" data-ah-abrir="<?php echo esc_attr( $dialogo ); ?>"><span><?php echo self::icono( 'ubicacion', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Ver seguimiento', 'ah-gestion-ti' ); ?></button></li>
						<?php if ( $mesa ) : ?>
							<li><a class="ah-accion-circular" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'categoria' => 'pedidos',
										'pedido'    => $pedido->get_id(),
									),
									$mesa
								) . '#ah-resultado'
							);
							?>
																	"><span><?php echo self::icono( 'chat', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Ayuda con mi compra', 'ah-gestion-ti' ); ?></a></li>
						<?php endif; ?>
						<?php if ( $volver ) : ?>
							<li><a class="ah-accion-circular" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'order_again', $pedido->get_id(), wc_get_cart_url() ), 'woocommerce-order_again' ) ); ?>"><span><?php echo self::icono( 'repetir', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Volver a comprar', 'ah-gestion-ti' ); ?></a></li>
						<?php endif; ?>
						<?php if ( 'completed' === $s['estado'] && $items ) : ?>
							<?php $primero = reset( $items ); ?>
							<li><a class="ah-accion-circular" href="<?php echo esc_url( get_permalink( $primero->get_product_id() ) . '#reviews' ); ?>"><span><?php echo self::icono( 'estrella', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Opinar del producto', 'ah-gestion-ti' ); ?></a></li>
						<?php endif; ?>
						<li><button type="button" class="ah-accion-circular" data-ah-imprimir><span><?php echo self::icono( 'imprimir', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Comprobante', 'ah-gestion-ti' ); ?></button></li>
					</ul>
				</section>

				<aside class="ah-compra__detalle" aria-labelledby="ah-detalle-titulo">
					<h2 id="ah-detalle-titulo"><?php esc_html_e( 'Detalle de la compra', 'ah-gestion-ti' ); ?></h2>
					<p class="ah-compra__referencia"><?php echo esc_html( wp_date( 'j \d\e F', $pedido->get_date_created()->getTimestamp() ) ); ?> | <strong>#<?php echo esc_html( $numero ); ?></strong></p>
					<ul class="ah-compra__productos">
						<?php foreach ( $items as $item ) : ?>
							<?php $producto = $item->get_product(); ?>
							<li>
								<span class="ah-compra__miniatura"><?php echo $producto ? wp_kses_post( $producto->get_image( 'woocommerce_gallery_thumbnail' ) ) : ''; ?></span>
								<span class="ah-compra__nombre">
									<?php if ( $producto ) : ?>
										<a href="<?php echo esc_url( get_permalink( $item->get_product_id() ) ); ?>"><?php echo esc_html( $item->get_name() ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $item->get_name() ); ?>
									<?php endif; ?>
									<small><?php echo esc_html( sprintf( /* translators: %d: cantidad. */ _n( '%d unidad', '%d unidades', $item->get_quantity(), 'ah-gestion-ti' ), $item->get_quantity() ) ); ?></small>
								</span>
								<strong><?php echo wp_kses_post( wc_price( (float) $item->get_total(), array( 'currency' => $pedido->get_currency() ) ) ); ?></strong>
							</li>
						<?php endforeach; ?>
					</ul>
					<dl class="ah-compra__totales">
						<div><dt><?php esc_html_e( 'Productos', 'ah-gestion-ti' ); ?></dt><dd><?php echo wp_kses_post( wc_price( (float) $pedido->get_subtotal(), array( 'currency' => $pedido->get_currency() ) ) ); ?></dd></div>
						<?php if ( (float) $pedido->get_discount_total() > 0 ) : ?>
							<div class="ah-compra__descuento"><dt><?php esc_html_e( 'Descuento', 'ah-gestion-ti' ); ?><?php echo $pedido->get_coupon_codes() ? ' (' . esc_html( strtoupper( implode( ', ', $pedido->get_coupon_codes() ) ) ) . ')' : ''; ?></dt><dd>−<?php echo wp_kses_post( wc_price( (float) $pedido->get_discount_total(), array( 'currency' => $pedido->get_currency() ) ) ); ?></dd></div>
						<?php endif; ?>
						<div><dt><?php esc_html_e( 'Envío', 'ah-gestion-ti' ); ?></dt><dd><?php echo (float) $pedido->get_shipping_total() > 0 ? wp_kses_post( wc_price( (float) $pedido->get_shipping_total(), array( 'currency' => $pedido->get_currency() ) ) ) : esc_html__( 'Gratis', 'ah-gestion-ti' ); ?></dd></div>
						<div class="ah-compra__total"><dt><?php esc_html_e( 'Total pagado', 'ah-gestion-ti' ); ?></dt><dd><?php echo wp_kses_post( $pedido->get_formatted_order_total() ); ?></dd></div>
					</dl>
					<p class="ah-compra__pago"><?php echo self::icono( 'efectivo', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $pedido->get_payment_method_title() ); ?></p>
				</aside>
			</div>

			<dialog class="ah-cajon" id="<?php echo esc_attr( $dialogo ); ?>" aria-labelledby="<?php echo esc_attr( $dialogo ); ?>-titulo">
				<div class="ah-cajon__cabecera">
					<h2 id="<?php echo esc_attr( $dialogo ); ?>-titulo"><?php esc_html_e( 'Seguimiento de tu compra', 'ah-gestion-ti' ); ?></h2>
					<button type="button" class="ah-cajon__cerrar" data-ah-cerrar aria-label="<?php esc_attr_e( 'Cerrar', 'ah-gestion-ti' ); ?>"><?php echo self::icono( 'cerrar', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
				<p class="ah-cajon__entrega"><?php echo esc_html( $s['entrega'] ); ?></p>
				<ol class="ah-linea-tiempo">
					<?php foreach ( $s['pasos'] as $paso ) : ?>
						<li class="ah-linea-tiempo__paso ah-linea-tiempo__paso--<?php echo esc_attr( $paso['estado'] ); ?>">
							<span class="ah-linea-tiempo__punto"><?php echo 'actual' === $paso['estado'] ? self::icono( $paso['icono'], 18 ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<div>
								<strong><?php echo esc_html( $paso['nombre'] ); ?></strong>
								<?php if ( 'pendiente' !== $paso['estado'] ) : ?>
									<p><?php echo esc_html( $paso['texto'] ); ?></p>
								<?php endif; ?>
								<?php if ( $paso['fecha'] ) : ?>
									<time><?php echo esc_html( $paso['fecha'] ); ?></time>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
				<div class="ah-cajon__paqueteria">
					<span><?php echo self::icono( 'camion', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php if ( $envio['paqueteria'] ) : ?>
						<p><?php echo esc_html( sprintf( /* translators: %s: paquetería. */ __( 'Enviado por %s', 'ah-gestion-ti' ), $envio['paqueteria'] ) ); ?><?php echo $envio['guia'] ? '<small>' . esc_html__( 'Guía', 'ah-gestion-ti' ) . ' ' . esc_html( $envio['guia'] ) . '</small>' : ''; ?></p>
					<?php else : ?>
						<p><?php echo esc_html( $s['envio']['nombre'] ); ?><small><?php esc_html_e( 'La paquetería se asigna cuando tu compra sale del taller.', 'ah-gestion-ti' ); ?></small></p>
					<?php endif; ?>
				</div>
			</dialog>
		</div>
		<?php
	}

	/* =====================================================================
	 * Página de gracias y rastreo para invitados
	 * ================================================================== */

	/**
	 * Botón "Sigue tu compra" en la página de pedido recibido.
	 *
	 * @param int $pedido_id Pedido.
	 */
	public static function boton_seguimiento( $pedido_id ) {
		$pedido = wc_get_order( $pedido_id );
		if ( ! $pedido ) {
			return;
		}
		$url = ( $pedido->get_customer_id() && $pedido->get_customer_id() === get_current_user_id() )
			? self::url_compra( $pedido_id )
			: add_query_arg( 'pedido', $pedido->get_order_number(), self::url_rastreo() );
		echo '<div class="ah-sigue-compra">' . self::icono( 'camion', 26 ) . '<div><strong>' . esc_html__( 'Sigue tu compra paso a paso', 'ah-gestion-ti' ) . '</strong><span>' . esc_html__( 'Te avisaremos cuando esté en preparación, en camino y entregada.', 'ah-gestion-ti' ) . '</span></div><a class="ah-boton" href="' . esc_url( $url ) . '">' . esc_html__( 'Ver seguimiento', 'ah-gestion-ti' ) . '</a></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno.
	}

	/**
	 * URL de la página de rastreo.
	 *
	 * @return string
	 */
	public static function url_rastreo() {
		$id = (int) get_option( 'ah_gti_pagina_rastreo' );
		return $id ? get_permalink( $id ) : home_url( '/rastrear-pedido/' );
	}

	/**
	 * Valida número de pedido + correo (con límite de intentos por IP).
	 */
	private static function procesar_rastreo() {
		if ( ! isset( $_POST['ah_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ah_nonce'] ) ), 'ah_rastreo_pedido' ) ) {
			self::$error_rastreo = __( 'La sesión del formulario expiró. Intenta de nuevo.', 'ah-gestion-ti' );
			return;
		}
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$clave = 'ah_lim_rastreo_' . md5( $ip );
		$usos  = (int) get_transient( $clave );
		if ( $usos >= 10 ) {
			self::$error_rastreo = __( 'Realizaste demasiados intentos. Espera unos minutos.', 'ah-gestion-ti' );
			return;
		}
		set_transient( $clave, $usos + 1, 10 * MINUTE_IN_SECONDS );

		$numero = isset( $_POST['pedido'] ) ? absint( preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['pedido'] ) ) ) ) : 0;
		$correo = isset( $_POST['correo'] ) ? strtolower( sanitize_email( wp_unslash( $_POST['correo'] ) ) ) : '';
		$pedido = $numero ? wc_get_order( $numero ) : null;
		if ( ! $pedido || ! $correo || strtolower( $pedido->get_billing_email() ) !== $correo ) {
			// Mensaje genérico: no revela si el pedido existe.
			self::$error_rastreo = __( 'No encontramos una compra con ese número y correo. Verifica los datos.', 'ah-gestion-ti' );
			return;
		}
		self::$pedido_rastreado = $pedido;
	}

	/**
	 * Shortcode [ah_rastreo_pedido].
	 *
	 * @return string
	 */
	public static function shortcode_rastreo() {
		wp_enqueue_style( 'ah-mis-compras' );
		wp_enqueue_script( 'ah-mis-compras' );
		ob_start();
		if ( self::$pedido_rastreado ) {
			self::detalle( self::$pedido_rastreado, false );
			echo '<p class="ah-rastreo__otra"><a href="' . esc_url( self::url_rastreo() ) . '">' . esc_html__( 'Rastrear otra compra', 'ah-gestion-ti' ) . '</a></p>';
			return (string) ob_get_clean();
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo precarga el número.
		$numero = isset( $_GET['pedido'] ) ? absint( $_GET['pedido'] ) : '';
		?>
		<div class="ah-rastreo">
			<form class="ah-rastreo__form" method="post">
				<input type="hidden" name="ah_compras_accion" value="rastrear">
				<?php wp_nonce_field( 'ah_rastreo_pedido', 'ah_nonce' ); ?>
				<?php if ( self::$error_rastreo ) : ?>
					<p class="woocommerce-error" role="alert"><?php echo esc_html( self::$error_rastreo ); ?></p>
				<?php endif; ?>
				<p>
					<label for="ah-rastreo-pedido"><?php esc_html_e( 'Número de compra', 'ah-gestion-ti' ); ?></label>
					<input type="text" id="ah-rastreo-pedido" name="pedido" value="<?php echo esc_attr( (string) $numero ); ?>" inputmode="numeric" placeholder="170" required>
				</p>
				<p>
					<label for="ah-rastreo-correo"><?php esc_html_e( 'Correo con el que compraste', 'ah-gestion-ti' ); ?></label>
					<input type="email" id="ah-rastreo-correo" name="correo" autocomplete="email" required>
				</p>
				<button type="submit" class="ah-boton"><?php esc_html_e( 'Rastrear mi compra', 'ah-gestion-ti' ); ?></button>
				<?php if ( ! is_user_logged_in() ) : ?>
					<p class="ah-rastreo__nota"><?php esc_html_e( '¿Tienes cuenta?', 'ah-gestion-ti' ); ?> <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Inicia sesión y consulta todas tus compras en Mis compras.', 'ah-gestion-ti' ); ?></a></p>
				<?php endif; ?>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* =====================================================================
	 * Administración del envío
	 * ================================================================== */

	/**
	 * Caja "Envío y seguimiento" en la edición del pedido (HPOS y clásico).
	 */
	public static function meta_box() {
		$pantalla = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'ah_envio_seguimiento', __( 'Envío y seguimiento', 'ah-gestion-ti' ), array( __CLASS__, 'dibujar_meta_box' ), $pantalla, 'side', 'high' );
	}

	/**
	 * Campos de paquetería y guía.
	 *
	 * @param WP_Post|WC_Order $objeto Pedido.
	 */
	public static function dibujar_meta_box( $objeto ) {
		$pedido = $objeto instanceof WC_Order ? $objeto : wc_get_order( $objeto->ID );
		if ( ! $pedido ) {
			return;
		}
		$actual = (string) $pedido->get_meta( self::META_PAQUETERIA );
		wp_nonce_field( 'ah_envio_guardar', 'ah_envio_nonce' );
		?>
		<p><label for="ah_paqueteria"><strong><?php esc_html_e( 'Paquetería', 'ah-gestion-ti' ); ?></strong></label><br>
		<select id="ah_paqueteria" name="ah_paqueteria" style="width:100%">
			<?php foreach ( self::paqueterias() as $clave => $nombre ) : ?>
				<option value="<?php echo esc_attr( $clave ); ?>" <?php selected( $actual, $clave ); ?>><?php echo esc_html( $nombre ); ?></option>
			<?php endforeach; ?>
		</select></p>
		<p><label for="ah_guia"><strong><?php esc_html_e( 'Número de guía', 'ah-gestion-ti' ); ?></strong></label><br>
		<input type="text" id="ah_guia" name="ah_guia" value="<?php echo esc_attr( (string) $pedido->get_meta( self::META_GUIA ) ); ?>" style="width:100%"></p>
		<p><label for="ah_url_rastreo"><strong><?php esc_html_e( 'URL de rastreo (opcional)', 'ah-gestion-ti' ); ?></strong></label><br>
		<input type="url" id="ah_url_rastreo" name="ah_url_rastreo" value="<?php echo esc_attr( (string) $pedido->get_meta( self::META_RASTREO ) ); ?>" style="width:100%"></p>
		<?php if ( $pedido->has_status( 'processing' ) ) : ?>
			<p><label><input type="checkbox" name="ah_marcar_camino" value="1"> <?php esc_html_e( 'Marcar como "En camino" y avisar al cliente', 'ah-gestion-ti' ); ?></label></p>
		<?php endif; ?>
		<?php
		$registro = (array) $pedido->get_meta( self::META_SEGUIMIENTO );
		if ( $registro ) {
			echo '<p class="description">';
			foreach ( $registro as $etapa => $ts ) {
				echo esc_html( $etapa . ': ' . wp_date( 'd/m/Y H:i', (int) $ts ) ) . '<br>';
			}
			echo '</p>';
		}
	}

	/**
	 * Guarda la información del envío.
	 *
	 * @param int $pedido_id Pedido.
	 */
	public static function guardar_envio( $pedido_id ) {
		if ( ! isset( $_POST['ah_envio_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ah_envio_nonce'] ) ), 'ah_envio_guardar' ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$pedido = wc_get_order( $pedido_id );
		if ( ! $pedido ) {
			return;
		}
		$paqueteria = isset( $_POST['ah_paqueteria'] ) ? sanitize_key( wp_unslash( $_POST['ah_paqueteria'] ) ) : '';
		$pedido->update_meta_data( self::META_PAQUETERIA, array_key_exists( $paqueteria, self::paqueterias() ) ? $paqueteria : '' );
		$pedido->update_meta_data( self::META_GUIA, isset( $_POST['ah_guia'] ) ? sanitize_text_field( wp_unslash( $_POST['ah_guia'] ) ) : '' );
		$pedido->update_meta_data( self::META_RASTREO, isset( $_POST['ah_url_rastreo'] ) ? esc_url_raw( wp_unslash( $_POST['ah_url_rastreo'] ) ) : '' );
		$pedido->save();
		if ( ! empty( $_POST['ah_marcar_camino'] ) && $pedido->has_status( 'processing' ) ) {
			$pedido->update_status( self::ESTADO_CAMINO, __( 'El pedido salió del taller.', 'ah-gestion-ti' ) );
		}
	}

	/**
	 * Acción masiva "Cambiar estado a En camino" en el listado de pedidos.
	 *
	 * @param array<string,string> $acciones Acciones.
	 * @return array<string,string>
	 */
	public static function accion_masiva( $acciones ) {
		$acciones[ 'mark_' . self::ESTADO_CAMINO ] = __( 'Cambiar estado a En camino', 'ah-gestion-ti' );
		return $acciones;
	}
}
