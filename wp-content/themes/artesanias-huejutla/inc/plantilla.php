<?php
/**
 * Funciones de plantilla reutilizables.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lee un ajuste del plugin si está activo.
 *
 * @param string $clave       Clave.
 * @param mixed  $por_defecto Valor por defecto.
 * @return mixed
 */
function ah_tema_opcion( $clave, $por_defecto = '' ) {
	return function_exists( 'ah_gti_opcion' ) ? ah_gti_opcion( $clave, $por_defecto ) : $por_defecto;
}

/**
 * ID de una imagen de la biblioteca por su slug.
 *
 * @param string $slug Slug del adjunto.
 * @return int
 */
function ah_tema_imagen_por_slug( $slug ) {
	$cache = wp_cache_get( 'img_' . $slug, 'ah_tema' );
	if ( false !== $cache ) {
		return (int) $cache;
	}
	$adjunto = get_posts(
		array(
			'post_type'   => 'attachment',
			'name'        => $slug,
			'numberposts' => 1,
			'post_status' => 'inherit',
			'fields'      => 'ids',
		)
	);
	$id      = $adjunto ? (int) $adjunto[0] : 0;
	wp_cache_set( 'img_' . $slug, $id, 'ah_tema' );
	return $id;
}

/**
 * Imagen de la biblioteca o, si no existe, del tema.
 *
 * @param string $slug    Slug del adjunto / nombre del archivo en assets/img.
 * @param string $tamano  Tamaño registrado.
 * @param array  $atributos Atributos extra.
 * @return string
 */
function ah_tema_imagen( $slug, $tamano = 'large', array $atributos = array() ) {
	$id = ah_tema_imagen_por_slug( $slug );
	if ( $id ) {
		return wp_get_attachment_image( $id, $tamano, false, $atributos );
	}
	$alt = $atributos['alt'] ?? '';
	return '<img src="' . esc_url( AH_TEMA_URI . '/assets/img/' . $slug . '.jpg' ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy">';
}

/**
 * Logotipo del sitio (personalizado o SVG del tema).
 *
 * @param string $clase Clase adicional.
 */
function ah_tema_logo( $clase = '' ) {
	if ( has_custom_logo() && ! $clase ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="ah-logo %1$s" href="%2$s" rel="home" aria-label="%3$s">%4$s<span class="ah-logo__texto"><strong>Artesanías</strong><span>Huejutla</span></span></a>',
		esc_attr( $clase ),
		esc_url( home_url( '/' ) ),
		esc_attr__( 'Artesanías Huejutla, ir al inicio', 'artesanias-huejutla' ),
		ah_tema_logo_svg() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno.
	);
}

/**
 * Redes sociales configuradas (solo cuentas reales capturadas por el administrador).
 *
 * @return array<string,string> red => URL
 */
function ah_tema_redes() {
	$redes = array();
	foreach ( array( 'facebook', 'instagram', 'tiktok' ) as $red ) {
		$url = ah_tema_opcion( $red );
		if ( $url ) {
			$redes[ $red ] = $url;
		}
	}
	$whatsapp = function_exists( 'ah_gti_whatsapp_url' ) ? ah_gti_whatsapp_url() : '';
	if ( $whatsapp ) {
		$redes['whatsapp'] = $whatsapp;
	}
	return $redes;
}

/**
 * Botones para compartir (URLs públicas de cada red; no requieren cuentas).
 *
 * @param string $url    URL a compartir.
 * @param string $titulo Título.
 */
function ah_tema_compartir( $url = '', $titulo = '' ) {
	$url    = $url ? $url : get_permalink();
	$titulo = $titulo ? $titulo : get_the_title();
	$redes  = array(
		'facebook'  => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
		'x'         => array( 'X', 'https://x.com/intent/post?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $titulo ) ),
		'whatsapp'  => array( 'WhatsApp', 'https://wa.me/?text=' . rawurlencode( $titulo . ' ' . $url ) ),
		'pinterest' => array( 'Pinterest', 'https://www.pinterest.com/pin/create/button/?url=' . rawurlencode( $url ) . '&description=' . rawurlencode( $titulo ) ),
	);
	echo '<div class="ah-compartir"><span class="ah-compartir__titulo">' . esc_html__( 'Compartir', 'artesanias-huejutla' ) . '</span><ul>';
	foreach ( $redes as $clave => list( $nombre, $enlace ) ) {
		printf(
			'<li><a class="ah-compartir__boton ah-compartir__boton--%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">%4$s</a></li>',
			esc_attr( $clave ),
			esc_url( $enlace ),
			/* translators: %s: red social. */
			esc_attr( sprintf( __( 'Compartir en %s', 'artesanias-huejutla' ), $nombre ) ),
			ah_tema_icono( $clave, 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno.
		);
	}
	printf(
		'<li><button type="button" class="ah-compartir__boton" data-ah-copiar="%1$s" aria-label="%2$s">%3$s</button></li>',
		esc_url( $url ),
		esc_attr__( 'Copiar enlace', 'artesanias-huejutla' ),
		ah_tema_icono( 'enlace', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno.
	);
	echo '</ul></div>';
}

/**
 * Migas de pan (Rank Math si está activo; si no, una versión sencilla).
 */
function ah_tema_migas() {
	if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
		echo '<nav class="ah-migas" aria-label="' . esc_attr__( 'Ruta de navegación', 'artesanias-huejutla' ) . '">';
		rank_math_the_breadcrumbs();
		echo '</nav>';
		return;
	}
	echo '<nav class="ah-migas" aria-label="' . esc_attr__( 'Ruta de navegación', 'artesanias-huejutla' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Inicio', 'artesanias-huejutla' ) . '</a> <span>/</span> ' . esc_html( wp_get_document_title() ) . '</nav>';
}

/**
 * Encabezado de página interna.
 *
 * @param string $titulo    Título (H1).
 * @param string $subtitulo Texto opcional.
 * @param string $imagen    Slug de imagen de fondo opcional.
 */
function ah_tema_cabecera_pagina( $titulo, $subtitulo = '', $imagen = '' ) {
	$id_img = $imagen ? ah_tema_imagen_por_slug( $imagen ) : 0;
	echo '<header class="ah-cabecera-pagina' . ( $id_img ? ' ah-cabecera-pagina--imagen' : '' ) . '">';
	if ( $id_img ) {
		echo wp_get_attachment_image(
			$id_img,
			'large',
			false,
			array(
				'class'   => 'ah-cabecera-pagina__fondo',
				'alt'     => '',
				'loading' => 'eager',
			)
		);
	}
	echo '<div class="ah-contenedor">';
	ah_tema_migas();
	echo '<h1>' . esc_html( $titulo ) . '</h1>';
	if ( $subtitulo ) {
		echo '<p>' . esc_html( $subtitulo ) . '</p>';
	}
	echo '</div></header>';
}

/**
 * Monto mínimo de envío gratis (si el plugin está activo).
 *
 * @return float
 */
function ah_tema_envio_gratis() {
	return class_exists( 'AH_Tienda' ) ? AH_Tienda::minimo_envio_gratis() : 0.0;
}

/**
 * Imprime un botón de favorito para un producto.
 *
 * @param int  $producto_id Producto.
 * @param bool $con_texto   Con texto.
 */
function ah_tema_boton_favorito( $producto_id, $con_texto = false ) {
	if ( class_exists( 'AH_Favoritos' ) ) {
		echo AH_Favoritos::boton( $producto_id, $con_texto ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en el plugin.
	}
}

/**
 * Etiqueta de método de pago con ícono.
 *
 * @param string $clave Clave del método.
 * @return string
 */
function ah_tema_metodo_pago( $clave ) {
	$iconos  = array(
		'transferencia' => 'banco',
		'contraentrega' => 'efectivo',
		'oxxo'          => 'efectivo',
	);
	$nombres = class_exists( 'AH_Ajustes' ) ? AH_Ajustes::metodos_pago() : array();
	return '<li>' . ah_tema_icono( $iconos[ $clave ] ?? 'tarjeta', 18 ) . esc_html( $nombres[ $clave ] ?? $clave ) . '</li>';
}

/**
 * Enlaces visibles de cuenta: acceso y registro para visitantes;
 * saludo, cuenta y cierre de sesión para clientes.
 *
 * @param string $clase Clase de la lista.
 */
function ah_tema_enlaces_cuenta( $clase ) {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	$cuenta = wc_get_page_permalink( 'myaccount' );
	echo '<ul class="' . esc_attr( $clase ) . '">';
	if ( is_user_logged_in() ) {
		$usuario = wp_get_current_user();
		/* translators: %s: nombre del cliente. */
		$texto = $usuario->first_name ? sprintf( __( 'Hola, %s', 'artesanias-huejutla' ), $usuario->first_name ) : __( 'Mi cuenta', 'artesanias-huejutla' );
		echo '<li><a href="' . esc_url( $cuenta ) . '">' . esc_html( $texto ) . '</a></li>';
		if ( class_exists( 'AH_Mis_Compras' ) ) {
			echo '<li><a href="' . esc_url( wc_get_account_endpoint_url( AH_Mis_Compras::ENDPOINT ) ) . '">' . esc_html__( 'Mis compras', 'artesanias-huejutla' ) . '</a></li>';
		}
		echo '<li><a href="' . esc_url( wc_logout_url() ) . '">' . esc_html__( 'Cerrar sesión', 'artesanias-huejutla' ) . '</a></li>';
	} elseif ( class_exists( 'AH_Mis_Compras' ) && 'ah-anuncio__cuenta' === $clase ) {
		echo '<li><a href="' . esc_url( AH_Mis_Compras::url_rastreo() ) . '">' . esc_html__( 'Rastrear pedido', 'artesanias-huejutla' ) . '</a></li>';
		echo '<li><a href="' . esc_url( $cuenta ) . '">' . esc_html__( 'Iniciar sesión', 'artesanias-huejutla' ) . '</a></li>';
		echo '<li><a href="' . esc_url( $cuenta . '#crear-cuenta' ) . '">' . esc_html__( 'Crear cuenta', 'artesanias-huejutla' ) . '</a></li>';
	} else {
		echo '<li><a href="' . esc_url( $cuenta ) . '">' . esc_html__( 'Iniciar sesión', 'artesanias-huejutla' ) . '</a></li>';
		echo '<li><a href="' . esc_url( $cuenta . '#crear-cuenta' ) . '">' . esc_html__( 'Crear cuenta', 'artesanias-huejutla' ) . '</a></li>';
	}
	echo '</ul>';
}
