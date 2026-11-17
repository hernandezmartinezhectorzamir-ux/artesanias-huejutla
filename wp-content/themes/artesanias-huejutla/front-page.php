<?php
/**
 * Portada de Artesanías Huejutla.
 *
 * Todas las secciones dinámicas usan datos reales de la tienda: productos
 * destacados, ofertas, cupones vigentes, más vendidos (solo si hay ventas),
 * opiniones (solo si hay reseñas aprobadas), servicios y blog.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ah_titulo      = ah_tema_texto( 'ah_hero_titulo' );
$ah_destacado   = ah_tema_texto( 'ah_hero_destacado' );
$ah_titulo_html = esc_html( $ah_titulo );
if ( $ah_destacado && false !== strpos( $ah_titulo, $ah_destacado ) ) {
	$ah_titulo_html = str_replace( esc_html( $ah_destacado ), '<em>' . esc_html( $ah_destacado ) . '</em>', $ah_titulo_html );
}
$ah_wc         = class_exists( 'WooCommerce' );
$ah_productos  = $ah_wc ? (int) wp_count_posts( 'product' )->publish : 0;
$ah_categorias = $ah_wc ? get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	)
) : array();
$ah_nosotros   = get_page_by_path( 'nosotros' );
$ah_historia   = get_page_by_path( 'historia-de-los-artesanos' );
?>
<main id="contenido">

	<section class="ah-hero" aria-labelledby="ah-hero-titulo">
		<div class="ah-contenedor ah-hero__rejilla">
			<div class="ah-hero__contenido">
				<p class="ah-antetitulo"><?php echo ah_tema_icono( 'ubicacion', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'Desde Huejutla de Reyes, Hidalgo', 'artesanias-huejutla' ); ?></p>
				<h1 class="ah-hero__titulo" id="ah-hero-titulo"><?php echo $ah_titulo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado arriba. ?></h1>
				<p class="ah-hero__texto"><?php echo esc_html( ah_tema_texto( 'ah_hero_texto' ) ); ?></p>
				<div class="ah-hero__acciones">
					<?php if ( $ah_wc ) : ?>
						<a class="ah-boton ah-boton--grande" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Explorar productos', 'artesanias-huejutla' ); ?> <?php echo ah_tema_icono( 'flecha', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
					<?php if ( $ah_nosotros ) : ?>
						<a class="ah-boton ah-boton--contorno ah-boton--grande" href="<?php echo esc_url( get_permalink( $ah_nosotros ) ); ?>"><?php esc_html_e( 'Conoce nuestra historia', 'artesanias-huejutla' ); ?></a>
					<?php endif; ?>
				</div>
				<?php if ( $ah_productos ) : ?>
					<ul class="ah-hero__datos">
						<li><strong><?php echo (int) $ah_productos; ?></strong><span><?php esc_html_e( 'piezas en catálogo', 'artesanias-huejutla' ); ?></span></li>
						<li><strong><?php echo is_array( $ah_categorias ) ? count( $ah_categorias ) : 0; ?></strong><span><?php esc_html_e( 'categorías artesanales', 'artesanias-huejutla' ); ?></span></li>
						<li><strong>100%</strong><span><?php esc_html_e( 'hecho a mano', 'artesanias-huejutla' ); ?></span></li>
					</ul>
				<?php endif; ?>
			</div>
			<div class="ah-hero__media">
				<figure class="ah-hero__foto ah-hero__foto--principal">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML de wp_get_attachment_image().
					echo ah_tema_imagen(
						'puesto-cesteria-palma-huejutla',
						'ah-portada',
						array(
							'loading'       => 'eager',
							'fetchpriority' => 'high',
							'alt'           => __( 'Artesanías de palma y textiles tradicionales de la Huasteca', 'artesanias-huejutla' ),
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					?>
				</figure>
				<figure class="ah-hero__foto ah-hero__foto--secundaria">
					<?php echo ah_tema_imagen( 'bordado-flores-hilo-algodon', 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</figure>
				<div class="ah-hero__sello" aria-hidden="true"><span><?php esc_html_e( 'Hecho', 'artesanias-huejutla' ); ?></span><strong><?php esc_html_e( 'a mano', 'artesanias-huejutla' ); ?></strong><span><?php esc_html_e( 'en Huejutla', 'artesanias-huejutla' ); ?></span></div>
			</div>
		</div>
	</section>

	<section class="ah-beneficios" aria-labelledby="ah-beneficios-titulo">
		<h2 class="screen-reader-text" id="ah-beneficios-titulo"><?php esc_html_e( 'Por qué comprar con nosotros', 'artesanias-huejutla' ); ?></h2>
		<div class="ah-contenedor ah-beneficios__rejilla">
			<div class="ah-beneficio"><span class="ah-beneficio__icono"><?php echo ah_tema_icono( 'manos', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><h3><?php esc_html_e( '100% Hecho a mano', 'artesanias-huejutla' ); ?></h3><p><?php esc_html_e( 'Cada pieza es única e irrepetible.', 'artesanias-huejutla' ); ?></p></div></div>
			<div class="ah-beneficio"><span class="ah-beneficio__icono"><?php echo ah_tema_icono( 'ubicacion', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><h3><?php esc_html_e( 'Origen 100% local', 'artesanias-huejutla' ); ?></h3><p><?php esc_html_e( 'Elaborado por familias de Huejutla.', 'artesanias-huejutla' ); ?></p></div></div>
			<div class="ah-beneficio"><span class="ah-beneficio__icono"><?php echo ah_tema_icono( 'camion', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><h3><?php esc_html_e( 'Envíos a todo México', 'artesanias-huejutla' ); ?></h3><p><?php esc_html_e( 'Empaque protector y seguimiento.', 'artesanias-huejutla' ); ?></p></div></div>
		</div>
	</section>

	<?php if ( $ah_categorias && ! is_wp_error( $ah_categorias ) ) : ?>
		<section class="ah-seccion" aria-labelledby="ah-categorias-titulo">
			<div class="ah-contenedor">
				<div class="ah-encabezado-seccion ah-encabezado-seccion--fila ah-revelar">
					<div>
						<p class="ah-antetitulo"><?php esc_html_e( 'Explora por tradición', 'artesanias-huejutla' ); ?></p>
						<h2 id="ah-categorias-titulo"><?php esc_html_e( 'Categorías artesanales', 'artesanias-huejutla' ); ?></h2>
						<p><?php esc_html_e( 'Técnicas heredadas de generación en generación, listas para llegar a tu hogar.', 'artesanias-huejutla' ); ?></p>
					</div>
					<a class="ah-enlace-flecha" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Ver todo el catálogo', 'artesanias-huejutla' ); ?> <?php echo ah_tema_icono( 'flecha', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
				<div class="ah-categorias">
					<?php foreach ( $ah_categorias as $ah_cat ) : ?>
						<?php $ah_img = (int) get_term_meta( $ah_cat->term_id, 'thumbnail_id', true ); ?>
						<a class="ah-categoria" href="<?php echo esc_url( get_term_link( $ah_cat ) ); ?>">
							<?php
							echo $ah_img ? wp_get_attachment_image(
								$ah_img,
								'medium_large',
								false,
								array(
									'alt'   => '',
									'class' => 'ah-categoria__img',
								)
							) : '';
							?>
							<span class="ah-categoria__info">
								<strong><?php echo esc_html( $ah_cat->name ); ?></strong>
								<small><?php echo esc_html( sprintf( /* translators: %d: piezas. */ _n( '%d pieza', '%d piezas', $ah_cat->count, 'artesanias-huejutla' ), $ah_cat->count ) ); ?></small>
								<span class="ah-categoria__cta"><?php esc_html_e( 'Explorar', 'artesanias-huejutla' ); ?> <?php echo ah_tema_icono( 'flecha', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $ah_wc ) : ?>
		<section class="ah-seccion ah-seccion--crema" aria-labelledby="ah-destacados-titulo">
			<div class="ah-contenedor">
				<div class="ah-encabezado-seccion ah-encabezado-seccion--centrado ah-revelar">
					<p class="ah-antetitulo"><?php esc_html_e( 'Selección de la casa', 'artesanias-huejutla' ); ?></p>
					<h2 id="ah-destacados-titulo"><?php esc_html_e( 'Productos destacados', 'artesanias-huejutla' ); ?></h2>
					<p><?php esc_html_e( 'Artesanías mexicanas hechas a mano, elegidas por su técnica, belleza y significado.', 'artesanias-huejutla' ); ?></p>
				</div>
				<?php echo do_shortcode( '[products limit="6" columns="3" visibility="featured" orderby="menu_order" order="ASC"]' ); ?>
				<p class="ah-centrado"><a class="ah-boton ah-boton--contorno" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Ver toda la colección', 'artesanias-huejutla' ); ?></a></p>
			</div>
		</section>

		<?php
		$ah_ofertas = wc_get_product_ids_on_sale();
		$ah_cupones = get_posts(
			array(
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
				'numberposts' => 3,
				'orderby'     => 'date',
				'order'       => 'ASC',
			)
		);
		?>
		<?php if ( $ah_ofertas || $ah_cupones ) : ?>
			<section class="ah-seccion" id="promociones" aria-labelledby="ah-promos-titulo">
				<div class="ah-contenedor">
					<div class="ah-promo ah-revelar">
						<div class="ah-promo__texto">
							<p class="ah-antetitulo ah-antetitulo--claro"><?php esc_html_e( 'Promociones', 'artesanias-huejutla' ); ?></p>
							<h2 id="ah-promos-titulo"><?php esc_html_e( 'Regala tradición con precios especiales', 'artesanias-huejutla' ); ?></h2>
							<p><?php esc_html_e( 'Piezas seleccionadas con descuento y cupones para tu primera compra. Cada compra reconoce el oficio detrás de una pieza auténtica.', 'artesanias-huejutla' ); ?></p>
							<?php if ( $ah_cupones ) : ?>
								<ul class="ah-cupones">
									<?php foreach ( $ah_cupones as $ah_cupon ) : ?>
										<li><button type="button" class="ah-cupon" data-ah-copiar="<?php echo esc_attr( strtoupper( $ah_cupon->post_title ) ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: código. */ __( 'Copiar el código %s', 'artesanias-huejutla' ), strtoupper( $ah_cupon->post_title ) ) ); ?>"><strong><?php echo esc_html( strtoupper( $ah_cupon->post_title ) ); ?></strong><span><?php echo esc_html( $ah_cupon->post_excerpt ); ?></span></button></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
						<?php echo ah_tema_imagen( 'bolsas-palma-bordadas-artesanales', 'large', array( 'class' => 'ah-promo__img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<?php if ( $ah_ofertas ) : ?>
						<?php echo do_shortcode( '[products limit="4" columns="4" on_sale="true"]' ); ?>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<section class="ah-seccion ah-seccion--oscura" aria-labelledby="ah-artesanos-titulo">
		<div class="ah-contenedor ah-historia">
			<div class="ah-historia__collage ah-revelar">
				<?php echo ah_tema_imagen( 'artesana-tejiendo-en-telar', 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo ah_tema_imagen( 'artesana-bordando-a-mano', 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="ah-historia__texto ah-revelar">
				<p class="ah-antetitulo ah-antetitulo--claro"><?php esc_html_e( 'Manos y memoria', 'artesanias-huejutla' ); ?></p>
				<h2 id="ah-artesanos-titulo"><?php esc_html_e( 'Historia de los artesanos', 'artesanias-huejutla' ); ?></h2>
				<p><?php esc_html_e( 'En las comunidades de Huejutla el tejido de palma y el bordado se aprenden en casa, mirando a las abuelas. Cada canasta, rebozo o mantel guarda horas de trabajo paciente y una historia familiar.', 'artesanias-huejutla' ); ?></p>
				<p><?php esc_html_e( 'Trabajamos directamente con las familias artesanas para que reciban un pago justo y para que sus técnicas sigan vivas.', 'artesanias-huejutla' ); ?></p>
				<?php if ( $ah_historia ) : ?>
					<a class="ah-boton ah-boton--claro" href="<?php echo esc_url( get_permalink( $ah_historia ) ); ?>"><?php esc_html_e( 'Conoce a nuestros artesanos', 'artesanias-huejutla' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	$ah_hay_ventas = $ah_wc && (bool) get_posts(
		array(
			'post_type'   => 'product',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'     => 'total_sales',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	?>
	<?php if ( $ah_hay_ventas ) : ?>
		<section class="ah-seccion" aria-labelledby="ah-vendidos-titulo">
			<div class="ah-contenedor">
				<div class="ah-encabezado-seccion ah-encabezado-seccion--fila ah-revelar">
					<div>
						<p class="ah-antetitulo"><?php esc_html_e( 'Los favoritos de nuestros clientes', 'artesanias-huejutla' ); ?></p>
						<h2 id="ah-vendidos-titulo"><?php esc_html_e( 'Productos más vendidos', 'artesanias-huejutla' ); ?></h2>
					</div>
					<a class="ah-enlace-flecha" href="<?php echo esc_url( add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Ver por popularidad', 'artesanias-huejutla' ); ?> <?php echo ah_tema_icono( 'flecha', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
				<?php echo do_shortcode( '[products limit="4" columns="4" best_selling="true"]' ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$ah_resenas = $ah_wc ? get_comments(
		array(
			'type'      => 'review',
			'status'    => 'approve',
			'number'    => 3,
			'post_type' => 'product',
		)
	) : array();
	?>
	<?php if ( $ah_resenas ) : ?>
		<section class="ah-seccion ah-seccion--crema" aria-labelledby="ah-opiniones-titulo">
			<div class="ah-contenedor">
				<div class="ah-encabezado-seccion ah-encabezado-seccion--centrado"><p class="ah-antetitulo"><?php esc_html_e( 'Opiniones verificadas', 'artesanias-huejutla' ); ?></p><h2 id="ah-opiniones-titulo"><?php esc_html_e( 'Lo que dicen nuestros clientes', 'artesanias-huejutla' ); ?></h2></div>
				<div class="ah-opiniones">
					<?php foreach ( $ah_resenas as $ah_resena ) : ?>
						<?php $ah_cal = (int) get_comment_meta( $ah_resena->comment_ID, 'rating', true ); ?>
						<blockquote class="ah-opinion">
							<?php if ( $ah_cal ) : ?>
								<?php echo wc_get_rating_html( $ah_cal ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
							<p><?php echo esc_html( wp_trim_words( $ah_resena->comment_content, 35 ) ); ?></p>
							<footer><strong><?php echo esc_html( $ah_resena->comment_author ); ?></strong> · <a href="<?php echo esc_url( get_permalink( $ah_resena->comment_post_ID ) ); ?>"><?php echo esc_html( get_the_title( $ah_resena->comment_post_ID ) ); ?></a></footer>
						</blockquote>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( class_exists( 'AH_Servicios' ) ) : ?>
		<?php
		$ah_servicios = get_posts(
			array(
				'post_type'   => AH_Servicios::TIPO,
				'numberposts' => 4,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			)
		);
		?>
		<?php if ( $ah_servicios ) : ?>
			<section class="ah-seccion" aria-labelledby="ah-servicios-titulo">
				<div class="ah-contenedor">
					<div class="ah-encabezado-seccion ah-encabezado-seccion--fila ah-revelar">
						<div>
							<p class="ah-antetitulo"><?php esc_html_e( 'Te acompañamos', 'artesanias-huejutla' ); ?></p>
							<h2 id="ah-servicios-titulo"><?php esc_html_e( 'Servicios de atención', 'artesanias-huejutla' ); ?></h2>
						</div>
						<a class="ah-enlace-flecha" href="<?php echo esc_url( get_post_type_archive_link( AH_Servicios::TIPO ) ); ?>"><?php esc_html_e( 'Catálogo de servicios', 'artesanias-huejutla' ); ?> <?php echo ah_tema_icono( 'flecha', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					</div>
					<div class="ah-servicios-mini">
						<?php foreach ( $ah_servicios as $ah_servicio ) : ?>
							<?php $ah_d = AH_Servicios::datos( $ah_servicio ); ?>
							<a class="ah-servicio-mini ah-revelar" href="<?php echo esc_url( $ah_d['enlace'] ); ?>">
								<span class="ah-servicio-mini__icono"><?php echo ah_tema_icono( $ah_d['icono'] ? $ah_d['icono'] : 'ayuda', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<strong><?php echo esc_html( $ah_d['titulo'] ); ?></strong>
								<span><?php echo esc_html( wp_trim_words( $ah_d['descripcion'], 16 ) ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	$ah_entradas = get_posts(
		array(
			'numberposts' => 3,
			'post_status' => 'publish',
		)
	);
	?>
	<?php if ( $ah_entradas ) : ?>
		<section class="ah-seccion ah-seccion--crema" aria-labelledby="ah-blog-titulo">
			<div class="ah-contenedor">
				<div class="ah-encabezado-seccion ah-encabezado-seccion--fila ah-revelar">
					<div>
						<p class="ah-antetitulo"><?php esc_html_e( 'Del blog', 'artesanias-huejutla' ); ?></p>
						<h2 id="ah-blog-titulo"><?php esc_html_e( 'Historias de la Huasteca', 'artesanias-huejutla' ); ?></h2>
					</div>
					<a class="ah-enlace-flecha" href="<?php echo esc_url( get_permalink( (int) get_option( 'page_for_posts' ) ) ); ?>"><?php esc_html_e( 'Ver todos los artículos', 'artesanias-huejutla' ); ?> <?php echo ah_tema_icono( 'flecha', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
				<div class="ah-rejilla-entradas">
					<?php
					global $post;
					foreach ( $ah_entradas as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						setup_postdata( $post );
						get_template_part( 'template-parts/tarjeta-entrada', null, array( 'nivel' => 3 ) );
					endforeach;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

</main>
<?php
get_footer();
