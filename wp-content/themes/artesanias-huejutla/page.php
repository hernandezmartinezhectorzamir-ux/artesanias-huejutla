<?php
/**
 * Página genérica (Nosotros, políticas, mesa de ayuda, carrito, pago, cuenta…).
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ah_imagenes = array(
	'nosotros'                  => 'artesana-blusa-bordada-huasteca',
	'historia-de-los-artesanos' => 'artesana-tejiendo-en-telar',
	'preguntas-frecuentes'      => 'canastas-palma-collares-semillas',
	'mesa-de-ayuda'             => 'puesto-cesteria-palma-huejutla',
);

while ( have_posts() ) :
	the_post();
	$ah_slug   = get_post_field( 'post_name' );
	$ah_tienda = function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() );
	?>
	<main id="contenido">
		<?php
		// Para visitantes, "Mi cuenta" es la página de acceso y registro: el título debe decirlo.
		$ah_acceso = $ah_tienda && is_account_page() && ! is_user_logged_in() && ! is_wc_endpoint_url();
		ah_tema_cabecera_pagina(
			$ah_acceso ? __( 'Inicia sesión o crea tu cuenta', 'artesanias-huejutla' ) : get_the_title(),
			$ah_acceso ? __( 'Consulta tus pedidos, guarda tus datos de envío y da seguimiento a tus solicitudes de soporte.', 'artesanias-huejutla' ) : ( has_excerpt() ? get_the_excerpt() : '' ),
			$ah_imagenes[ $ah_slug ] ?? ''
		);
		?>
		<div class="ah-contenedor <?php echo $ah_tienda || in_array( $ah_slug, array( 'mesa-de-ayuda', 'favoritos' ), true ) ? '' : 'ah-contenedor--estrecho'; ?> ah-seccion ah-seccion--compacta">
			<div class="ah-contenido">
				<?php the_content(); ?>
			</div>
		</div>
	</main>
	<?php
endwhile;

get_footer();
