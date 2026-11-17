<?php
/**
 * Pie de página: enlaces, redes sociales, información comercial y métodos de pago.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

$ah_redes    = ah_tema_redes();
$ah_metodos  = (array) ah_tema_opcion( 'metodos_pago', array() );
$ah_contacto = array_filter(
	array(
		'ubicacion' => ah_tema_opcion( 'direccion' ),
		'telefono'  => ah_tema_opcion( 'telefono' ),
		'correo'    => ah_tema_opcion( 'correo' ),
		'reloj'     => ah_tema_opcion( 'horario' ),
	)
);
$ah_whatsapp = function_exists( 'ah_gti_whatsapp_url' ) ? ah_gti_whatsapp_url() : '';
$ah_nombres  = array(
	'facebook'  => 'Facebook',
	'instagram' => 'Instagram',
	'tiktok'    => 'TikTok',
	'whatsapp'  => 'WhatsApp',
);
?>
<footer class="ah-pie">
	<div class="ah-greca" aria-hidden="true"></div>
	<?php if ( class_exists( 'AH_Boletin' ) ) : ?>
		<section class="ah-boletin" aria-labelledby="ah-boletin-titulo">
			<div class="ah-contenedor ah-boletin__interior">
				<div class="ah-boletin__texto">
					<h2 id="ah-boletin-titulo" class="ah-boletin__titulo"><?php esc_html_e( 'Recibe novedades de la Huasteca', 'artesanias-huejutla' ); ?></h2>
					<p><?php esc_html_e( 'Nuevas piezas, historias de nuestros artesanos y promociones. Un correo de vez en cuando, sin spam.', 'artesanias-huejutla' ); ?></p>
				</div>
				<?php echo AH_Boletin::formulario( 'pie' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escapado en el plugin. ?>
			</div>
		</section>
	<?php endif; ?>
	<div class="ah-pie__principal">
		<div class="ah-contenedor ah-pie__rejilla">
			<div class="ah-pie__marca">
				<?php ah_tema_logo( 'ah-logo--claro' ); ?>
				<p><?php esc_html_e( 'Artesanías auténticas de la Huasteca Hidalguense, elaboradas a mano por familias artesanas de Huejutla de Reyes, Hidalgo.', 'artesanias-huejutla' ); ?></p>
				<?php if ( $ah_redes ) : ?>
					<ul class="ah-redes" aria-label="<?php esc_attr_e( 'Redes sociales', 'artesanias-huejutla' ); ?>">
						<?php foreach ( $ah_redes as $ah_red => $ah_url ) : ?>
							<li><a href="<?php echo esc_url( $ah_url ); ?>" target="_blank" rel="noopener noreferrer me" aria-label="<?php echo esc_attr( $ah_nombres[ $ah_red ] ); ?>"><?php echo ah_tema_icono( $ah_red, 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="ah-pie__col">
				<h2 class="ah-pie__titulo"><?php esc_html_e( 'Tienda', 'artesanias-huejutla' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'pie_tienda',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</div>

			<div class="ah-pie__col">
				<h2 class="ah-pie__titulo"><?php esc_html_e( 'Ayuda', 'artesanias-huejutla' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'pie_ayuda',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</div>

			<div class="ah-pie__col">
				<h2 class="ah-pie__titulo"><?php esc_html_e( 'Información comercial', 'artesanias-huejutla' ); ?></h2>
				<?php if ( $ah_contacto ) : ?>
					<address class="ah-pie__contacto">
						<?php foreach ( $ah_contacto as $ah_icono => $ah_valor ) : ?>
							<p>
								<?php echo ah_tema_icono( $ah_icono, 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php if ( 'telefono' === $ah_icono ) : ?>
									<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $ah_valor ) ); ?>"><?php echo esc_html( $ah_valor ); ?></a>
								<?php elseif ( 'correo' === $ah_icono ) : ?>
									<a href="mailto:<?php echo esc_attr( antispambot( $ah_valor ) ); ?>"><?php echo esc_html( antispambot( $ah_valor ) ); ?></a>
								<?php else : ?>
									<span><?php echo esc_html( $ah_valor ); ?></span>
								<?php endif; ?>
							</p>
						<?php endforeach; ?>
					</address>
				<?php endif; ?>
				<?php if ( $ah_metodos ) : ?>
					<h3 class="ah-pie__titulo ah-pie__titulo--sm"><?php esc_html_e( 'Métodos de pago', 'artesanias-huejutla' ); ?></h3>
					<ul class="ah-pagos">
						<?php
						foreach ( $ah_metodos as $ah_metodo ) {
							echo ah_tema_metodo_pago( $ah_metodo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en la función.
						}
						?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="ah-pie__inferior">
		<div class="ah-contenedor ah-pie__inferior-interior">
			<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?> · <?php esc_html_e( 'Proyecto de Gestiones Tecnológicas Empresariales – ISC, Instituto Tecnológico de Huejutla.', 'artesanias-huejutla' ); ?></p>
			<?php
			wp_nav_menu(
				array(
					'theme_location'       => 'pie_legal',
					'container'            => 'nav',
					'container_class'      => 'ah-pie__legal',
					'container_aria_label' => __( 'Legal', 'artesanias-huejutla' ),
					'depth'                => 1,
					'fallback_cb'          => false,
				)
			);
			?>
		</div>
	</div>
</footer>

<?php if ( $ah_whatsapp ) : ?>
	<a class="ah-whatsapp-flotante" href="<?php echo esc_url( $ah_whatsapp ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e( 'Escríbenos por WhatsApp', 'artesanias-huejutla' ); ?>"><?php echo ah_tema_icono( 'whatsapp', 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
<?php endif; ?>
<button type="button" class="ah-arriba" aria-label="<?php esc_attr_e( 'Volver arriba', 'artesanias-huejutla' ); ?>"><?php echo ah_tema_icono( 'arriba' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>

<?php wp_footer(); ?>
</body>
</html>
