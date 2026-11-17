<?php
/**
 * Favoritos (lista de deseos).
 *
 * - Visitantes: se guardan en el navegador (localStorage).
 * - Clientes con sesión: se sincronizan en su cuenta (user meta) por REST.
 * - Página de favoritos con el shortcode [ah_favoritos].
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lista de favoritos.
 */
final class AH_Favoritos {

	/** Meta de usuario. */
	const META = '_ah_favoritos';

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'rest_api_init', array( __CLASS__, 'rutas' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'script' ) );
		add_shortcode( 'ah_favoritos', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Rutas REST protegidas por sesión + nonce wp_rest.
	 */
	public static function rutas() {
		register_rest_route(
			'ah/v1',
			'/favoritos',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => 'is_user_logged_in',
					'callback'            => static fn() => rest_ensure_response( self::del_usuario( get_current_user_id() ) ),
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'ids' => array(
							'type'     => 'array',
							'items'    => array( 'type' => 'integer' ),
							'required' => true,
						),
					),
					'callback'            => array( __CLASS__, 'guardar' ),
				),
			)
		);
	}

	/**
	 * IDs guardados por un usuario.
	 *
	 * @param int $user_id Usuario.
	 * @return array<int,int>
	 */
	public static function del_usuario( $user_id ) {
		return array_values( array_map( 'intval', (array) get_user_meta( $user_id, self::META, true ) ) );
	}

	/**
	 * Guarda la lista (solo productos publicados, máximo 100).
	 *
	 * @param WP_REST_Request $peticion Petición.
	 * @return WP_REST_Response
	 */
	public static function guardar( WP_REST_Request $peticion ) {
		$ids = array_unique( array_map( 'absint', (array) $peticion->get_param( 'ids' ) ) );
		$ids = array_values(
			array_filter(
				$ids,
				static fn( $id ) => $id && 'product' === get_post_type( $id ) && 'publish' === get_post_status( $id )
			)
		);
		$ids = array_slice( $ids, 0, 100 );
		update_user_meta( get_current_user_id(), self::META, $ids );
		return rest_ensure_response( $ids );
	}

	/**
	 * Script de favoritos.
	 */
	public static function script() {
		wp_enqueue_script( 'ah-gti-favoritos', AH_GTI_URL . 'assets/js/favoritos.js', array(), AH_GTI_VERSION, true );
		$pagina = (int) get_option( 'ah_gti_pagina_favoritos' );
		wp_localize_script(
			'ah-gti-favoritos',
			'ahFavoritos',
			array(
				'sesion'   => is_user_logged_in(),
				'rest'     => esc_url_raw( rest_url( 'ah/v1/favoritos' ) ),
				'tienda'   => esc_url_raw( rest_url( 'wc/store/v1/products' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'pagina'   => $pagina ? get_permalink( $pagina ) : '',
				'catalogo' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'textos'   => array(
					'agregado'    => __( 'Se guardó en tus favoritos.', 'ah-gestion-ti' ),
					'quitado'     => __( 'Se quitó de tus favoritos.', 'ah-gestion-ti' ),
					'vacio'       => __( 'Aún no tienes favoritos. Toca el corazón de cualquier artesanía para guardarla aquí.', 'ah-gestion-ti' ),
					'explorar'    => __( 'Explorar el catálogo', 'ah-gestion-ti' ),
					'quitar'      => __( 'Quitar', 'ah-gestion-ti' ),
					'ver'         => __( 'Ver producto', 'ah-gestion-ti' ),
					'cargando'    => __( 'Cargando favoritos…', 'ah-gestion-ti' ),
					'agotado'     => __( 'Agotado', 'ah-gestion-ti' ),
					'enFavoritos' => __( 'En favoritos', 'ah-gestion-ti' ),
					'agregarFav'  => __( 'Agregar a favoritos', 'ah-gestion-ti' ),
				),
			)
		);
	}

	/**
	 * Botón de corazón para un producto (lo usa el tema).
	 *
	 * @param int  $producto_id Producto.
	 * @param bool $con_texto   Muestra texto junto al ícono.
	 * @return string
	 */
	public static function boton( $producto_id, $con_texto = false ) {
		$nombre = get_the_title( $producto_id );
		$icono  = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>';
		return sprintf(
			'<button type="button" class="ah-fav%1$s" data-ah-fav="%2$d" aria-pressed="false" aria-label="%3$s">%4$s%5$s</button>',
			$con_texto ? ' ah-fav--texto' : '',
			(int) $producto_id,
			/* translators: %s: producto. */
			esc_attr( sprintf( __( 'Agregar a favoritos: %s', 'ah-gestion-ti' ), $nombre ) ),
			$icono,
			$con_texto ? '<span data-ah-fav-texto>' . esc_html__( 'Agregar a favoritos', 'ah-gestion-ti' ) . '</span>' : ''
		);
	}

	/**
	 * Shortcode [ah_favoritos]: el contenido lo dibuja favoritos.js.
	 *
	 * @return string
	 */
	public static function shortcode() {
		return '<div class="ah-favoritos" id="ah-favoritos-lista" aria-live="polite"><p>' . esc_html__( 'Cargando favoritos…', 'ah-gestion-ti' ) . '</p></div>'
			. '<noscript><p>' . esc_html__( 'Activa JavaScript para ver tus favoritos.', 'ah-gestion-ti' ) . '</p></noscript>';
	}
}
