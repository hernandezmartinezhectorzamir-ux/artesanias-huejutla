<?php
/**
 * Soportes del tema, menús y tamaños de imagen.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( 'artesanias-huejutla', AH_TEMA_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 80,
				'width'       => 260,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support( 'editor-styles' );

		// WooCommerce: galería con zoom, lightbox y deslizador.
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 600,
				'single_image_width'    => 900,
				'product_grid'          => array(
					'default_columns' => 3,
					'default_rows'    => 4,
					'min_columns'     => 2,
					'max_columns'     => 4,
				),
			)
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'principal'  => __( 'Menú principal', 'artesanias-huejutla' ),
				'pie_tienda' => __( 'Pie de página – Tienda', 'artesanias-huejutla' ),
				'pie_ayuda'  => __( 'Pie de página – Ayuda', 'artesanias-huejutla' ),
				'pie_legal'  => __( 'Pie de página – Legal', 'artesanias-huejutla' ),
			)
		);

		add_image_size( 'ah-tarjeta', 640, 480, true );
		add_image_size( 'ah-portada', 1100, 1375, true );
	}
);

/**
 * Ancho máximo del contenido para incrustaciones.
 */
add_action(
	'after_setup_theme',
	static function () {
		$GLOBALS['content_width'] = 860;
	},
	0
);

/**
 * Extracto más corto en tarjetas del blog.
 */
add_filter( 'excerpt_length', static fn() => 24 );
add_filter( 'excerpt_more', static fn() => '…' );

/**
 * Clase en <body> para páginas con encabezado de imagen.
 */
add_filter(
	'body_class',
	static function ( $clases ) {
		if ( is_front_page() ) {
			$clases[] = 'ah-portada';
		}
		return $clases;
	}
);
