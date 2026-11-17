<?php
/**
 * Página no encontrada.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="contenido" class="ah-contenedor ah-seccion">
	<div class="ah-vacio">
		<span class="ah-vacio__icono"><?php echo ah_tema_icono( 'buscar', 40 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<h1><?php esc_html_e( 'No encontramos esta página', 'artesanias-huejutla' ); ?></h1>
		<p><?php esc_html_e( 'Es posible que el enlace sea incorrecto o que el contenido se haya movido. Busca una artesanía o regresa al inicio.', 'artesanias-huejutla' ); ?></p>
		<?php get_search_form(); ?>
		<p class="ah-vacio__acciones">
			<a class="ah-boton" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Ir al inicio', 'artesanias-huejutla' ); ?></a>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a class="ah-boton ah-boton--contorno" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Ver catálogo', 'artesanias-huejutla' ); ?></a>
			<?php endif; ?>
		</p>
	</div>
</main>
<?php
get_footer();
