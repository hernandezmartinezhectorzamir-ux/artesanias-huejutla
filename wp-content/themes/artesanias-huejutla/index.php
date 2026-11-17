<?php
/**
 * Listado del blog (y plantilla de respaldo).
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ah_blog = (int) get_option( 'page_for_posts' );
if ( is_home() && $ah_blog ) {
	$ah_titulo    = get_the_title( $ah_blog );
	$ah_subtitulo = __( 'Historias, tradiciones y guías sobre artesanías mexicanas hechas a mano en la Huasteca Hidalguense.', 'artesanias-huejutla' );
} elseif ( is_search() ) {
	/* translators: %s: término buscado. */
	$ah_titulo    = sprintf( __( 'Resultados para “%s”', 'artesanias-huejutla' ), get_search_query() );
	$ah_subtitulo = '';
} elseif ( is_archive() ) {
	$ah_titulo    = wp_strip_all_tags( get_the_archive_title() );
	$ah_subtitulo = wp_strip_all_tags( get_the_archive_description() );
} else {
	$ah_titulo    = get_bloginfo( 'name' );
	$ah_subtitulo = '';
}
?>
<main id="contenido">
	<?php ah_tema_cabecera_pagina( $ah_titulo, $ah_subtitulo, is_home() ? 'artesana-tejiendo-en-telar' : '' ); ?>
	<div class="ah-contenedor ah-seccion ah-seccion--compacta">
		<?php if ( have_posts() ) : ?>
			<?php
			$ah_cats = get_categories( array( 'hide_empty' => true ) );
			if ( ( is_home() || is_category() ) && count( $ah_cats ) > 1 ) :
				?>
				<nav class="ah-chips" aria-label="<?php esc_attr_e( 'Categorías del blog', 'artesanias-huejutla' ); ?>">
					<a class="ah-chip<?php echo is_home() ? ' activo' : ''; ?>" href="<?php echo esc_url( get_permalink( $ah_blog ) ); ?>"><?php esc_html_e( 'Todos', 'artesanias-huejutla' ); ?></a>
					<?php foreach ( $ah_cats as $ah_cat ) : ?>
						<a class="ah-chip<?php echo is_category( $ah_cat->term_id ) ? ' activo' : ''; ?>" href="<?php echo esc_url( get_category_link( $ah_cat ) ); ?>"><?php echo esc_html( $ah_cat->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<div class="ah-rejilla-entradas">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/tarjeta-entrada' );
				endwhile;
				?>
			</div>
			<?php
			the_posts_pagination(
				array(
					'prev_text' => __( 'Anterior', 'artesanias-huejutla' ),
					'next_text' => __( 'Siguiente', 'artesanias-huejutla' ),
				)
			);
			?>
		<?php else : ?>
			<div class="ah-vacio">
				<h2><?php esc_html_e( 'No encontramos contenido', 'artesanias-huejutla' ); ?></h2>
				<p><?php esc_html_e( 'Intenta con otra búsqueda o explora el catálogo.', 'artesanias-huejutla' ); ?></p>
				<?php get_search_form(); ?>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
