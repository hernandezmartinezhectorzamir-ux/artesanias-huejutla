<?php
/**
 * Catálogo de servicios (ITIL) en /servicios/.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();
$ah_mesa = class_exists( 'AH_Mesa_Ayuda_Publica' ) ? AH_Mesa_Ayuda_Publica::url_pagina() : '';
?>
<main id="contenido">
	<?php ah_tema_cabecera_pagina( __( 'Catálogo de servicios', 'artesanias-huejutla' ), __( 'Conoce los servicios que ofrecemos, sus condiciones de atención y el canal para solicitarlos.', 'artesanias-huejutla' ), 'bolsas-palma-bordadas-artesanales' ); ?>
	<div class="ah-contenedor ah-seccion ah-seccion--compacta">
		<?php if ( have_posts() ) : ?>
			<div class="ah-servicios">
				<?php
				while ( have_posts() ) :
					the_post();
					echo AH_Servicios::tarjeta( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en el plugin.
				endwhile;
				?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e( 'Aún no hay servicios publicados.', 'artesanias-huejutla' ); ?></p>
		<?php endif; ?>

		<?php if ( $ah_mesa ) : ?>
			<aside class="ah-llamado">
				<div>
					<h2><?php esc_html_e( '¿Necesitas ayuda ahora?', 'artesanias-huejutla' ); ?></h2>
					<p><?php esc_html_e( 'Crea un ticket en la mesa de ayuda y recibe un número de seguimiento.', 'artesanias-huejutla' ); ?></p>
				</div>
				<a class="ah-boton ah-boton--claro" href="<?php echo esc_url( $ah_mesa ); ?>"><?php esc_html_e( 'Ir a la mesa de ayuda', 'artesanias-huejutla' ); ?></a>
			</aside>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
