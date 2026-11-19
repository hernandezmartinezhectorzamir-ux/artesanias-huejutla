<?php
/**
 * Integración visual con WooCommerce mediante hooks (sin modificar su núcleo).
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Contenedores ---------- */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

/**
 * ¿Es un listado de productos (tienda, categoría, etiqueta o búsqueda)?
 *
 * @return bool
 */
function ah_tema_es_listado() {
	return is_shop() || is_product_taxonomy();
}

add_action(
	'woocommerce_before_main_content',
	static function () {
		echo '<main id="contenido" class="ah-tienda"><div class="ah-contenedor">';
		if ( ah_tema_es_listado() ) {
			echo '<div class="ah-catalogo">';
			get_template_part( 'template-parts/filtros-tienda' );
			echo '<div class="ah-catalogo__resultados">';
		}
	},
	5
);

add_action(
	'woocommerce_after_main_content',
	static function () {
		if ( ah_tema_es_listado() ) {
			echo '</div></div>';
		}
		echo '</div></main>';
	},
	50
);

/* ---------- Migas de pan ---------- */
add_filter(
	'woocommerce_breadcrumb_defaults',
	static function ( $args ) {
		$args['delimiter']   = '<span class="ah-migas__sep" aria-hidden="true">/</span>';
		$args['wrap_before'] = '<nav class="ah-migas woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Ruta de navegación', 'artesanias-huejutla' ) . '">';
		$args['home']        = __( 'Inicio', 'artesanias-huejutla' );
		return $args;
	}
);

/* ---------- Tarjetas de producto ---------- */
add_action(
	'woocommerce_before_shop_loop_item',
	static function () {
		ah_tema_boton_favorito( get_the_ID() );
	},
	5
);

add_action(
	'woocommerce_shop_loop_item_title',
	static function () {
		$terminos = get_the_terms( get_the_ID(), 'product_cat' );
		if ( $terminos && ! is_wp_error( $terminos ) ) {
			echo '<span class="ah-producto__categoria">' . esc_html( $terminos[0]->name ) . '</span>';
		}
	},
	5
);

add_action(
	'woocommerce_before_shop_loop_item_title',
	static function () {
		global $product;
		if ( $product && ! $product->is_in_stock() ) {
			echo '<span class="ah-insignia ah-insignia--agotado">' . esc_html__( 'Agotado', 'artesanias-huejutla' ) . '</span>';
		}
	},
	9
);

/**
 * Insignia de oferta con el porcentaje real de descuento.
 */
add_filter(
	'woocommerce_sale_flash',
	static function ( $html, $post, $product ) {
		$regular = (float) $product->get_regular_price();
		$oferta  = (float) $product->get_sale_price();
		if ( $regular > 0 && $oferta > 0 ) {
			return '<span class="onsale">-' . (int) round( ( 1 - $oferta / $regular ) * 100 ) . '%</span>';
		}
		return '<span class="onsale">' . esc_html__( 'Oferta', 'artesanias-huejutla' ) . '</span>';
	},
	10,
	3
);

/* ---------- Ficha de producto ---------- */
add_action(
	'woocommerce_single_product_summary',
	static function () {
		echo '<p class="ah-hecho-a-mano">' . ah_tema_icono( 'manos', 18 ) . esc_html__( 'Pieza hecha a mano en la Huasteca Hidalguense', 'artesanias-huejutla' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno.
	},
	15
);

add_action(
	'woocommerce_single_product_summary',
	static function () {
		echo '<div class="ah-producto__acciones">';
		ah_tema_boton_favorito( get_the_ID(), true );
		echo '</div>';
		ah_tema_compartir();
	},
	35
);

add_action(
	'woocommerce_single_product_summary',
	static function () {
		$minimo = ah_tema_envio_gratis();
		echo '<ul class="ah-info-envio">';
		echo '<li>' . ah_tema_icono( 'camion' ) . '<span><strong>' . esc_html__( 'Envíos a todo México', 'artesanias-huejutla' ) . '</strong>' . ( $minimo ? wp_kses_post( sprintf( /* translators: %s: monto. */ __( 'Envío estándar gratis desde %s', 'artesanias-huejutla' ), wc_price( $minimo ) ) ) : '' ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno.
		echo '<li>' . ah_tema_icono( 'paquete' ) . '<span><strong>' . esc_html__( 'Empaque protector', 'artesanias-huejutla' ) . '</strong>' . esc_html__( 'Cada pieza viaja asegurada', 'artesanias-huejutla' ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<li>' . ah_tema_icono( 'devolucion' ) . '<span><strong>' . esc_html__( 'Cambios en 15 días', 'artesanias-huejutla' ) . '</strong>' . esc_html__( 'Si tu pieza llega dañada, la reponemos', 'artesanias-huejutla' ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</ul>';
	},
	45
);

add_filter(
	'woocommerce_product_tabs',
	static function ( $pestanas ) {
		if ( isset( $pestanas['additional_information'] ) ) {
			$pestanas['additional_information']['title'] = __( 'Materiales y medidas', 'artesanias-huejutla' );
		}
		return $pestanas;
	},
	98
);

add_filter(
	'woocommerce_output_related_products_args',
	static function ( $args ) {
		$args['posts_per_page'] = 4;
		$args['columns']        = 4;
		return $args;
	}
);

add_filter( 'loop_shop_columns', static fn() => 3 );
add_filter( 'loop_shop_per_page', static fn() => 12 );

/* ---------- Ancla del formulario de registro (enlace "Crear cuenta") ---------- */
add_action(
	'woocommerce_register_form_start',
	static function () {
		echo '<span id="crear-cuenta" class="ah-ancla"></span>';
	}
);

/* ---------- Contador del carrito por AJAX ---------- */
add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( $fragmentos ) {
		$fragmentos['span.ah-contador-carrito'] = ah_tema_contador_carrito();
		return $fragmentos;
	}
);

/**
 * Contador del carrito.
 *
 * @return string
 */
function ah_tema_contador_carrito() {
	$total = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	return '<span class="ah-contador ah-contador-carrito" aria-label="' . esc_attr( sprintf( /* translators: %d: piezas. */ _n( '%d pieza en el carrito', '%d piezas en el carrito', $total, 'artesanias-huejutla' ), $total ) ) . '">' . (int) $total . '</span>';
}

/* ---------- Formulario de pago ---------- */
add_filter(
	'woocommerce_checkout_fields',
	static function ( $campos ) {
		if ( isset( $campos['billing']['billing_company'] ) ) {
			$campos['billing']['billing_company']['required'] = false;
		}
		if ( isset( $campos['billing']['billing_phone'] ) ) {
			$campos['billing']['billing_phone']['required']    = true;
			$campos['billing']['billing_phone']['placeholder'] = __( '10 dígitos', 'artesanias-huejutla' );
		}
		return $campos;
	}
);

/*
 * ---------- Ficha de producto: galería y resumen en su propio contenedor ----------
 * La galería es "sticky"; si su contenedor fuera todo div.product (que incluye pestañas,
 * valoraciones y relacionados), al bajar quedaría encima de ellos. Así solo acompaña al resumen.
 */
add_action(
	'woocommerce_before_single_product_summary',
	static function () {
		echo '<div class="ah-producto-principal">';
	},
	1
);
add_action(
	'woocommerce_after_single_product_summary',
	static function () {
		echo '</div>';
	},
	1
);
