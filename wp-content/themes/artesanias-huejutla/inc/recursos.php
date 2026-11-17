<?php
/**
 * Hojas de estilo, scripts y fuentes.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Versión de un recurso basada en su fecha de modificación (invalida la caché tras cada despliegue).
 *
 * @param string $ruta Ruta relativa al tema.
 * @return string
 */
function ah_tema_version_archivo( $ruta ) {
	$archivo = AH_TEMA_DIR . '/' . $ruta;
	return file_exists( $archivo ) ? AH_TEMA_VERSION . '.' . filemtime( $archivo ) : AH_TEMA_VERSION;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'ah-fuentes', 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Manrope:wght@400;500;600;700;800&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Versión gestionada por Google Fonts.
		wp_enqueue_style( 'ah-tema', AH_TEMA_URI . '/assets/css/tema.css', array(), ah_tema_version_archivo( 'assets/css/tema.css' ) );
		if ( class_exists( 'WooCommerce' ) ) {
			// Se carga después de los estilos de WooCommerce para integrarlos con la identidad del tema.
			$ah_deps = array( 'ah-tema' );
			foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen' ) as $ah_estilo ) {
				if ( wp_style_is( $ah_estilo, 'registered' ) ) {
					$ah_deps[] = $ah_estilo;
				}
			}
			wp_enqueue_style( 'ah-woocommerce', AH_TEMA_URI . '/assets/css/woocommerce.css', $ah_deps, ah_tema_version_archivo( 'assets/css/woocommerce.css' ) );
		}
		wp_enqueue_script( 'ah-tema', AH_TEMA_URI . '/assets/js/tema.js', array(), ah_tema_version_archivo( 'assets/js/tema.js' ), true );
		wp_localize_script(
			'ah-tema',
			'ahTema',
			array(
				'copiado'  => __( 'Enlace copiado', 'artesanias-huejutla' ),
				'abrirSub' => __( 'Mostrar submenú', 'artesanias-huejutla' ),
			)
		);
	}
);

/**
 * Conexión anticipada a Google Fonts.
 */
add_filter(
	'wp_resource_hints',
	static function ( $urls, $tipo ) {
		if ( 'preconnect' === $tipo ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array(
				'href' => 'https://fonts.gstatic.com',
				'crossorigin',
			);
		}
		return $urls;
	},
	10,
	2
);

/**
 * Precarga la imagen principal de la portada (mejora el LCP).
 */
add_action(
	'wp_head',
	static function () {
		if ( is_front_page() ) {
			$id = ah_tema_imagen_por_slug( 'puesto-cesteria-palma-huejutla' );
			if ( $id ) {
				$src = wp_get_attachment_image_url( $id, 'ah-portada' );
				echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '" fetchpriority="high">' . "\n";
			}
		}
	},
	5
);

/**
 * Elimina recursos que el tema no usa.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! is_singular() || ! has_blocks() ) {
			wp_dequeue_style( 'wp-block-library-theme' );
		}
	},
	100
);
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
