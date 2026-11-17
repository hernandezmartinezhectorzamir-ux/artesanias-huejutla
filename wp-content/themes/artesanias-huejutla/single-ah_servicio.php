<?php
/**
 * Detalle de un servicio.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$ah_d = AH_Servicios::datos( get_post() );
	?>
	<main id="contenido">
		<?php ah_tema_cabecera_pagina( get_the_title(), $ah_d['descripcion'] ); ?>
		<div class="ah-contenedor ah-contenedor--estrecho ah-seccion ah-seccion--compacta">
			<div class="ah-contenido"><?php the_content(); ?></div>
			<?php echo AH_Servicios::tarjeta( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en el plugin. ?>
			<p><a class="ah-enlace-flecha" href="<?php echo esc_url( get_post_type_archive_link( AH_Servicios::TIPO ) ); ?>"><?php esc_html_e( 'Ver todos los servicios', 'artesanias-huejutla' ); ?> <?php echo ah_tema_icono( 'flecha', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></p>
		</div>
	</main>
	<?php
endwhile;

get_footer();
