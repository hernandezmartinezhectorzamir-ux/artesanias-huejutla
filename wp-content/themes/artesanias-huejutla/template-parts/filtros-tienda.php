<?php
/**
 * Panel de filtros del catálogo: búsqueda, categorías y rango de precios.
 *
 * Usa los parámetros nativos de WooCommerce (s, product_cat, min_price,
 * max_price, orderby), por lo que los filtros funcionan sin JavaScript.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

$ah_actual     = is_product_category() ? get_queried_object_id() : 0;
$ah_categorias = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	)
);
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Filtros públicos de solo lectura.
$ah_min    = isset( $_GET['min_price'] ) ? absint( $_GET['min_price'] ) : '';
$ah_max    = isset( $_GET['max_price'] ) ? absint( $_GET['max_price'] ) : '';
$ah_orden  = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : '';
$ah_buscar = get_search_query();
// phpcs:enable
$ah_base = $ah_actual ? get_term_link( $ah_actual ) : wc_get_page_permalink( 'shop' );
?>
<aside class="ah-filtros" aria-label="<?php esc_attr_e( 'Filtros del catálogo', 'artesanias-huejutla' ); ?>">
	<details class="ah-filtros__panel" open>
		<summary class="ah-filtros__titulo"><?php echo ah_tema_icono( 'buscar', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Filtrar artesanías', 'artesanias-huejutla' ); ?></summary>

		<form class="ah-filtros__grupo" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="ah-filtros__etiqueta" for="ah-filtro-buscar"><?php esc_html_e( 'Buscar', 'artesanias-huejutla' ); ?></label>
			<div class="ah-campo-icono">
				<input type="search" id="ah-filtro-buscar" name="s" value="<?php echo esc_attr( $ah_buscar ); ?>" placeholder="<?php esc_attr_e( 'Nombre o material…', 'artesanias-huejutla' ); ?>">
				<input type="hidden" name="post_type" value="product">
				<button type="submit" aria-label="<?php esc_attr_e( 'Buscar', 'artesanias-huejutla' ); ?>"><?php echo ah_tema_icono( 'buscar', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
		</form>

		<div class="ah-filtros__grupo">
			<h2 class="ah-filtros__etiqueta"><?php esc_html_e( 'Categorías', 'artesanias-huejutla' ); ?></h2>
			<ul class="ah-filtros__categorias">
				<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="<?php echo $ah_actual ? '' : 'activo'; ?>"><?php esc_html_e( 'Todas', 'artesanias-huejutla' ); ?></a></li>
				<?php if ( ! is_wp_error( $ah_categorias ) ) : ?>
					<?php foreach ( $ah_categorias as $ah_cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $ah_cat ) ); ?>" class="<?php echo $ah_actual === $ah_cat->term_id ? 'activo' : ''; ?>"<?php echo $ah_actual === $ah_cat->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $ah_cat->name ); ?> <span><?php echo (int) $ah_cat->count; ?></span></a></li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>

		<form class="ah-filtros__grupo" method="get" action="<?php echo esc_url( $ah_base ); ?>">
			<h2 class="ah-filtros__etiqueta"><?php esc_html_e( 'Rango de precio (MXN)', 'artesanias-huejutla' ); ?></h2>
			<div class="ah-filtros__precios">
				<label><span><?php esc_html_e( 'Mínimo', 'artesanias-huejutla' ); ?></span><input type="number" name="min_price" min="0" step="10" value="<?php echo esc_attr( $ah_min ); ?>" placeholder="0" inputmode="numeric"></label>
				<label><span><?php esc_html_e( 'Máximo', 'artesanias-huejutla' ); ?></span><input type="number" name="max_price" min="0" step="10" value="<?php echo esc_attr( $ah_max ); ?>" placeholder="1000" inputmode="numeric"></label>
			</div>
			<?php if ( $ah_orden ) : ?>
				<input type="hidden" name="orderby" value="<?php echo esc_attr( $ah_orden ); ?>">
			<?php endif; ?>
			<button type="submit" class="ah-boton ah-boton--secundario ah-boton--bloque"><?php esc_html_e( 'Aplicar precio', 'artesanias-huejutla' ); ?></button>
			<?php if ( '' !== $ah_min || '' !== $ah_max || $ah_buscar ) : ?>
				<a class="ah-filtros__limpiar" href="<?php echo esc_url( $ah_base ); ?>"><?php esc_html_e( 'Limpiar filtros', 'artesanias-huejutla' ); ?></a>
			<?php endif; ?>
		</form>

		<div class="ah-filtros__nota">
			<?php echo ah_tema_icono( 'manos' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p><strong><?php esc_html_e( 'Compra con propósito.', 'artesanias-huejutla' ); ?></strong> <?php esc_html_e( 'Tu compra llega directamente a familias artesanas de Huejutla.', 'artesanias-huejutla' ); ?></p>
		</div>
	</details>
</aside>
