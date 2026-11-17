<?php
/**
 * Template Name: Contacto
 *
 * Formulario (shortcode del plugin), datos comerciales configurables,
 * WhatsApp, videollamada y mapa de Huejutla de Reyes.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ah_datos    = array_filter(
	array(
		'ubicacion' => array( __( 'Ubicación', 'artesanias-huejutla' ), ah_tema_opcion( 'direccion', 'Huejutla de Reyes, Hidalgo, México' ) ),
		'telefono'  => array( __( 'Teléfono', 'artesanias-huejutla' ), ah_tema_opcion( 'telefono' ) ),
		'correo'    => array( __( 'Correo', 'artesanias-huejutla' ), ah_tema_opcion( 'correo' ) ),
		'reloj'     => array( __( 'Horario', 'artesanias-huejutla' ), ah_tema_opcion( 'horario' ) ),
	),
	static fn( $d ) => '' !== $d[1]
);
$ah_whatsapp = function_exists( 'ah_gti_whatsapp_url' ) ? ah_gti_whatsapp_url() : '';
$ah_video    = ah_tema_opcion( 'videollamada_url' );
$ah_mesa     = class_exists( 'AH_Mesa_Ayuda_Publica' ) ? AH_Mesa_Ayuda_Publica::url_pagina() : '';

while ( have_posts() ) :
	the_post();
	?>
	<main id="contenido">
		<?php ah_tema_cabecera_pagina( get_the_title(), __( '¿Tienes dudas sobre una pieza, un pedido o compras al mayoreo? Escríbenos.', 'artesanias-huejutla' ), 'bolsas-palma-bordadas-artesanales' ); ?>
		<div class="ah-contenedor ah-seccion ah-seccion--compacta ah-contacto">
			<section class="ah-contacto__formulario" aria-labelledby="ah-contacto-form">
				<h2 id="ah-contacto-form"><?php esc_html_e( 'Envíanos un mensaje', 'artesanias-huejutla' ); ?></h2>
				<div class="ah-contenido"><?php the_content(); ?></div>
			</section>

			<aside class="ah-contacto__info" aria-label="<?php esc_attr_e( 'Información de contacto', 'artesanias-huejutla' ); ?>">
				<ul class="ah-info-contacto">
					<?php foreach ( $ah_datos as $ah_icono => list( $ah_etiqueta, $ah_valor ) ) : ?>
						<li>
							<span class="ah-info-contacto__icono"><?php echo ah_tema_icono( $ah_icono ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<div><h3><?php echo esc_html( $ah_etiqueta ); ?></h3>
								<?php if ( 'telefono' === $ah_icono ) : ?>
									<p><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $ah_valor ) ); ?>"><?php echo esc_html( $ah_valor ); ?></a></p>
								<?php elseif ( 'correo' === $ah_icono ) : ?>
									<p><a href="mailto:<?php echo esc_attr( antispambot( $ah_valor ) ); ?>"><?php echo esc_html( antispambot( $ah_valor ) ); ?></a></p>
								<?php else : ?>
									<p><?php echo esc_html( $ah_valor ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $ah_whatsapp || $ah_video ) : ?>
					<div class="ah-canales">
						<?php if ( $ah_whatsapp ) : ?>
							<a class="ah-boton ah-boton--whatsapp" href="<?php echo esc_url( $ah_whatsapp ); ?>" target="_blank" rel="noopener noreferrer"><?php echo ah_tema_icono( 'whatsapp', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'WhatsApp Business', 'artesanias-huejutla' ); ?></a>
						<?php endif; ?>
						<?php if ( $ah_video ) : ?>
							<a class="ah-boton ah-boton--secundario" href="<?php echo esc_url( $ah_video ); ?>" target="_blank" rel="noopener noreferrer"><?php echo ah_tema_icono( 'video', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Agendar videollamada', 'artesanias-huejutla' ); ?></a>
							<?php if ( ah_tema_opcion( 'videollamada_texto' ) ) : ?>
								<p class="ah-texto-suave"><?php echo esc_html( ah_tema_opcion( 'videollamada_texto' ) ); ?></p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $ah_mesa ) : ?>
					<p class="ah-nota"><?php echo ah_tema_icono( 'soporte', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php esc_html_e( '¿Problema con un pedido o pago?', 'artesanias-huejutla' ); ?> <a href="<?php echo esc_url( $ah_mesa ); ?>"><?php esc_html_e( 'Abre un ticket en la mesa de ayuda', 'artesanias-huejutla' ); ?></a>.</span></p>
				<?php endif; ?>

				<div class="ah-mapa">
					<iframe title="<?php esc_attr_e( 'Mapa de Huejutla de Reyes, Hidalgo', 'artesanias-huejutla' ); ?>" loading="lazy" referrerpolicy="no-referrer"
						src="https://www.openstreetmap.org/export/embed.html?bbox=-98.4330%2C21.1310%2C-98.4060%2C21.1490&amp;layer=mapnik&amp;marker=21.1400%2C-98.4195"></iframe>
					<a href="https://www.openstreetmap.org/?mlat=21.1400&amp;mlon=-98.4195#map=15/21.1400/-98.4195" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver mapa más grande', 'artesanias-huejutla' ); ?></a>
				</div>
			</aside>
		</div>
	</main>
	<?php
endwhile;

get_footer();
