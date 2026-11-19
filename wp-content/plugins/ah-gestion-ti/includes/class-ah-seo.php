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
		add_shortcode( 'ah_videos_proceso', array( __CLASS__, 'shortcode_videos_proceso' ) );
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
	 * Datos de un video de la biblioteca de medios: URL, título, descripción, miniatura, duración y fecha.
	 *
	 * La miniatura se toma de la meta _ah_poster_id (o de la imagen destacada del adjunto).
	 *
	 * @param int $id ID del adjunto de video.
	 * @return array<string,string>|null
	 */
	public static function datos_video( $id ) {
		$adjunto = get_post( $id );
		if ( ! $adjunto || 'attachment' !== $adjunto->post_type || ! wp_attachment_is( 'video', $adjunto ) ) {
			return null;
		}
		$meta   = wp_get_attachment_metadata( $adjunto->ID );
		$poster = (int) get_post_meta( $adjunto->ID, '_ah_poster_id', true );
		$poster = $poster ? $poster : (int) get_post_thumbnail_id( $adjunto->ID );
		$largo  = isset( $meta['length'] ) ? (int) $meta['length'] : 0;
		return array(
			'id'          => (string) $adjunto->ID,
			'url'         => (string) wp_get_attachment_url( $adjunto->ID ),
			'titulo'      => get_the_title( $adjunto ),
			'descripcion' => $adjunto->post_excerpt ? $adjunto->post_excerpt : wp_strip_all_tags( $adjunto->post_content ),
			'detalle'     => wp_strip_all_tags( $adjunto->post_content ),
			'miniatura'   => $poster ? (string) wp_get_attachment_image_url( $poster, 'full' ) : '',
			'alt'         => $poster ? (string) get_post_meta( $poster, '_wp_attachment_image_alt', true ) : '',
			'duracion'    => $largo ? 'PT' . ( $largo >= 60 ? floor( $largo / 60 ) . 'M' : '' ) . ( $largo % 60 ) . 'S' : '',
			'segundos'    => (string) $largo,
			'fecha'       => get_the_date( 'c', $adjunto ),
			'ia'          => get_post_meta( $adjunto->ID, '_ah_video_ia', true ) ? '1' : '',
		);
	}

	/**
	 * Videos del proceso artesanal (adjuntos con la meta _ah_video_proceso), en orden.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function videos_proceso() {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'video',
				'posts_per_page' => 6,
				'fields'         => 'ids',
				'meta_key'       => '_ah_video_proceso', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Pocos adjuntos.
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
			)
		);
		return array_values( array_filter( array_map( array( __CLASS__, 'datos_video' ), $ids ) ) );
	}

	/**
	 * Registra un VideoObject para imprimirlo en el pie (una sola vez por URL).
	 *
	 * @param array<string,string> $v Datos del video (ver datos_video()).
	 */
	public static function agregar_schema_video( array $v ) {
		foreach ( self::$schemas as $existente ) {
			if ( 'VideoObject' === ( $existente['@type'] ?? '' ) && ( $existente['contentUrl'] ?? '' ) === $v['url'] ) {
				return;
			}
		}
		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'VideoObject',
			'name'        => $v['titulo'],
			'description' => ! empty( $v['detalle'] ) ? $v['detalle'] : ( $v['descripcion'] ? $v['descripcion'] : $v['titulo'] ),
			'contentUrl'  => $v['url'],
		);
		if ( ! empty( $v['miniatura'] ) ) {
			$schema['thumbnailUrl'] = $v['miniatura'];
		}
		if ( ! empty( $v['duracion'] ) ) {
			$schema['duration'] = $v['duracion'];
		}
		if ( ! empty( $v['fecha'] ) ) {
			$schema['uploadDate'] = $v['fecha'];
		}
		self::$schemas[] = $schema;
	}

	/**
	 * [ah_videos_proceso limite="3"]: cuadrícula con los videos del proceso artesanal.
	 *
	 * @param array<string,string>|string $atts Atributos.
	 * @return string
	 */
	public static function shortcode_videos_proceso( $atts = array() ) {
		$a      = shortcode_atts( array( 'limite' => '6' ), $atts, 'ah_videos_proceso' );
		$videos = array_slice( self::videos_proceso(), 0, max( 1, absint( $a['limite'] ) ) );
		if ( ! $videos ) {
			return '';
		}
		$html = '';
		foreach ( $videos as $v ) {
			$html .= self::shortcode_video( array( 'id' => $v['id'] ) );
		}
		return '<div class="ah-videos">' . $html . '</div>';
	}

	/**
	 * [ah_video id="123"] para un video de la biblioteca de medios, o
	 * [ah_video url="" titulo="" descripcion="" miniatura="" duracion="PT1M30S" fecha="2026-11-01"] para YouTube, Vimeo o MP4 externo.
	 *
	 * @param array<string,string> $atts Atributos.
	 * @return string
	 */
	public static function shortcode_video( $atts ) {
		$a  = shortcode_atts(
			array(
				'id'          => '',
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
		$ia = '';
		if ( $a['id'] ) {
			$v = self::datos_video( absint( $a['id'] ) );
			if ( ! $v ) {
				return current_user_can( 'edit_posts' ) ? '<p><em>' . esc_html__( '[ah_video] el id no corresponde a un video de la biblioteca de medios.', 'ah-gestion-ti' ) . '</em></p>' : '';
			}
			// Los atributos escritos en el shortcode tienen prioridad sobre los del adjunto.
			foreach ( array( 'url', 'titulo', 'descripcion', 'miniatura', 'duracion', 'fecha' ) as $campo ) {
				$a[ $campo ] = $a[ $campo ] ? $a[ $campo ] : $v[ $campo ];
			}
			$a['detalle'] = $v['detalle'];
			$ia           = $v['ia'];
		}
		if ( ! $a['url'] || ! $a['titulo'] ) {
			return current_user_can( 'edit_posts' ) ? '<p><em>' . esc_html__( '[ah_video] requiere id, o bien los atributos url y titulo.', 'ah-gestion-ti' ) . '</em></p>' : '';
		}
		$embed = $a['id'] ? false : wp_oembed_get( esc_url_raw( $a['url'] ) );
		if ( ! $embed ) {
			$embed = '<video controls playsinline preload="none"' . ( $a['miniatura'] ? ' poster="' . esc_url( $a['miniatura'] ) . '"' : '' ) . ' aria-label="' . esc_attr( $a['titulo'] ) . '"><source src="' . esc_url( $a['url'] ) . '" type="' . esc_attr( wp_check_filetype( $a['url'] )['type'] ? wp_check_filetype( $a['url'] )['type'] : 'video/mp4' ) . '"></video>';
		}

		self::agregar_schema_video( $a );

		$nota = $ia ? '<span class="ah-video__ia">' . esc_html__( 'Video ilustrativo generado con IA', 'ah-gestion-ti' ) . '</span>' : '';
		return '<figure class="ah-video">' . $embed . '<figcaption><strong>' . esc_html( $a['titulo'] ) . '</strong>' . ( $a['descripcion'] ? ' — ' . esc_html( $a['descripcion'] ) : '' ) . $nota . '</figcaption></figure>';
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
