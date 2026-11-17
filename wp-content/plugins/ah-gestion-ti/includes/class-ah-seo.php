<?php
/**
 * SEO complementario al plugin SEO (Rank Math / Yoast).
 *
 * - robots.txt virtual con exclusión de áreas privadas y referencia al sitemap.
 * - noindex en páginas privadas o sin valor de búsqueda.
 * - Atributo TITLE y ALT de respaldo en imágenes.
 * - Generación de miniaturas en WebP cuando el servidor lo soporta.
 * - Shortcode [ah_video] con datos estructurados VideoObject.
 * - Shortcode [ah_faq] con datos estructurados FAQPage.
 * - Metadatos de respaldo (description, Open Graph, Store) si NO hay plugin SEO.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Funciones SEO.
 */
final class AH_SEO {

	/**
	 * Datos estructurados pendientes de imprimir en el pie.
	 *
	 * @var array<int,array>
	 */
	private static $schemas = array();

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_filter( 'robots_txt', array( __CLASS__, 'robots' ), 20, 2 );
		add_filter( 'wp_robots', array( __CLASS__, 'noindex' ) );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'atributos_imagen' ), 10, 2 );
		add_filter( 'image_editor_output_format', array( __CLASS__, 'formato_webp' ) );
		add_shortcode( 'ah_video', array( __CLASS__, 'shortcode_video' ) );
		add_shortcode( 'ah_faq', array( __CLASS__, 'shortcode_faq' ) );
		add_action( 'wp_footer', array( __CLASS__, 'imprimir_schemas' ), 50 );
		add_action( 'wp_head', array( __CLASS__, 'respaldo_meta' ), 3 );
	}

	/**
	 * Hay un plugin SEO activo que ya genera title, description, OG y sitemap.
	 *
	 * @return bool
	 */
	public static function plugin_seo_activo() {
		return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' );
	}

	/**
	 * robots.txt virtual.
	 *
	 * @param string $salida  Contenido actual.
	 * @param bool   $publico Si el sitio permite indexación.
	 * @return string
	 */
	public static function robots( $salida, $publico ) {
		if ( ! $publico ) {
			return "User-agent: *\nDisallow: /\n";
		}
		$ruta   = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$ruta   = $ruta ? trailingslashit( $ruta ) : '/';
		$lineas = array(
			'User-agent: *',
			'Disallow: ' . $ruta . 'wp-admin/',
			'Allow: ' . $ruta . 'wp-admin/admin-ajax.php',
			'Disallow: ' . $ruta . '*?add-to-cart=',
			'Disallow: ' . $ruta . '*?orderby=',
			'Disallow: ' . $ruta . '*?s=',
			'Disallow: ' . $ruta . 'wp-json/',
		);
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $pagina ) {
			$id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( $pagina ) : 0;
			if ( $id > 0 ) {
				$lineas[] = 'Disallow: ' . wp_parse_url( get_permalink( $id ), PHP_URL_PATH );
			}
		}
		$favoritos = (int) get_option( 'ah_gti_pagina_favoritos' );
		if ( $favoritos ) {
			$lineas[] = 'Disallow: ' . wp_parse_url( get_permalink( $favoritos ), PHP_URL_PATH );
		}
		$sitemap  = defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) ? home_url( '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' );
		$lineas[] = '';
		$lineas[] = 'Sitemap: ' . $sitemap;
		return implode( "\n", $lineas ) . "\n";
	}

	/**
	 * noindex en páginas privadas (carrito, pago, cuenta, favoritos, búsquedas internas).
	 *
	 * @param array<string,bool|string> $robots Directivas.
	 * @return array<string,bool|string>
	 */
	public static function noindex( $robots ) {
		$privada = false;
		if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
			$privada = true;
		}
		$favoritos = (int) get_option( 'ah_gti_pagina_favoritos' );
		if ( ( $favoritos && is_page( $favoritos ) ) || is_search() ) {
			$privada = true;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo lectura.
		if ( isset( $_GET['ver_ticket'] ) || isset( $_GET['ah_ok'] ) ) {
			$privada = true;
		}
		if ( $privada ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'], $robots['max-image-preview'] );
		}
		return $robots;
	}

	/**
	 * Agrega TITLE y ALT de respaldo a las imágenes.
	 *
	 * @param array<string,string> $atributos Atributos.
	 * @param WP_Post              $adjunto   Imagen.
	 * @return array<string,string>
	 */
	public static function atributos_imagen( $atributos, $adjunto ) {
		$titulo = get_the_title( $adjunto );
		if ( empty( $atributos['title'] ) && $titulo ) {
			$atributos['title'] = $titulo;
		}
		if ( empty( $atributos['alt'] ) && $titulo ) {
			$atributos['alt'] = $titulo;
		}
		if ( empty( $atributos['loading'] ) ) {
			$atributos['loading'] = 'lazy';
		}
		return $atributos;
	}

	/**
	 * Genera los tamaños intermedios en WebP si el editor de imágenes lo soporta.
	 *
	 * @param array<string,string> $formatos Mapeo mime origen => mime destino.
	 * @return array<string,string>
	 */
	public static function formato_webp( $formatos ) {
		if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$formatos['image/jpeg'] = 'image/webp';
			$formatos['image/png']  = 'image/webp';
		}
		return $formatos;
	}

	/**
	 * [ah_video url="" titulo="" descripcion="" miniatura="" duracion="PT1M30S" fecha="2026-11-01"]
	 *
	 * @param array<string,string> $atts Atributos.
	 * @return string
	 */
	public static function shortcode_video( $atts ) {
		$a = shortcode_atts(
			array(
				'url'         => '',
				'titulo'      => '',
				'descripcion' => '',
				'miniatura'   => '',
				'duracion'    => '',
				'fecha'       => '',
			),
			$atts,
			'ah_video'
		);
		if ( ! $a['url'] || ! $a['titulo'] ) {
			return current_user_can( 'edit_posts' ) ? '<p><em>' . esc_html__( '[ah_video] requiere los atributos url y titulo.', 'ah-gestion-ti' ) . '</em></p>' : '';
		}
		$embed = wp_oembed_get( esc_url_raw( $a['url'] ) );
		if ( ! $embed ) {
			$embed = '<video controls preload="none"' . ( $a['miniatura'] ? ' poster="' . esc_url( $a['miniatura'] ) . '"' : '' ) . '><source src="' . esc_url( $a['url'] ) . '"></video>';
		}

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'VideoObject',
			'name'        => $a['titulo'],
			'description' => $a['descripcion'] ? $a['descripcion'] : $a['titulo'],
			'contentUrl'  => $a['url'],
		);
		if ( $a['miniatura'] ) {
			$schema['thumbnailUrl'] = $a['miniatura'];
		}
		if ( $a['duracion'] ) {
			$schema['duration'] = $a['duracion'];
		}
		if ( $a['fecha'] ) {
			$schema['uploadDate'] = $a['fecha'];
		}
		self::$schemas[] = $schema;

		return '<figure class="ah-video">' . $embed . '<figcaption><strong>' . esc_html( $a['titulo'] ) . '</strong>' . ( $a['descripcion'] ? ' — ' . esc_html( $a['descripcion'] ) : '' ) . '</figcaption></figure>';
	}

	/**
	 * [ah_faq] Pregunta || Respuesta (una por línea) [/ah_faq]
	 *
	 * @param array<string,string> $atts      Atributos.
	 * @param string               $contenido Contenido.
	 * @return string
	 */
	public static function shortcode_faq( $atts, $contenido = '' ) {
		$preguntas = array();
		foreach ( preg_split( '/\R/', wp_strip_all_tags( (string) $contenido ) ) as $linea ) {
			if ( false !== strpos( $linea, '||' ) ) {
				list( $p, $r ) = array_map( 'trim', explode( '||', $linea, 2 ) );
				if ( $p && $r ) {
					$preguntas[] = array( $p, $r );
				}
			}
		}
		if ( ! $preguntas ) {
			return '';
		}
		$html   = '<div class="ah-faq">';
		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array(),
		);
		foreach ( $preguntas as $i => list( $p, $r ) ) {
			$html                  .= '<details class="ah-faq__item"' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $p ) . '</summary><p>' . esc_html( $r ) . '</p></details>';
			$schema['mainEntity'][] = array(
				'@type'          => 'Question',
				'name'           => $p,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $r,
				),
			);
		}
		self::$schemas[] = $schema;
		return $html . '</div>';
	}

	/**
	 * Imprime los datos estructurados acumulados.
	 */
	public static function imprimir_schemas() {
		foreach ( self::$schemas as $schema ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}
	}

	/**
	 * Metadatos mínimos SOLO si no hay plugin SEO activo.
	 */
	public static function respaldo_meta() {
		if ( self::plugin_seo_activo() ) {
			return;
		}
		$descripcion = '';
		if ( is_front_page() ) {
			$descripcion = get_bloginfo( 'description' );
		} elseif ( is_singular() ) {
			$descripcion = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', get_queried_object_id() ) ), 28, '' );
		} elseif ( is_category() || is_tax() ) {
			$descripcion = term_description();
		}
		$descripcion = trim( wp_strip_all_tags( (string) $descripcion ) );
		if ( $descripcion ) {
			echo '<meta name="description" content="' . esc_attr( $descripcion ) . '">' . "\n";
			echo '<meta property="og:description" content="' . esc_attr( $descripcion ) . '">' . "\n";
		}
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		echo '<meta property="og:locale" content="es_MX">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
		if ( is_singular() && has_post_thumbnail() ) {
			echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( null, 'large' ) ) . '">' . "\n";
		}
	}
}
