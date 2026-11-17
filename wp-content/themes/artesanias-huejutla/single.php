<?php
/**
 * Entrada del blog: título, imagen destacada, categoría, fecha, autor,
 * contenido, botones para compartir y productos relacionados.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$ah_cats         = get_the_category();
	$ah_relacionados = array_filter( array_map( 'absint', (array) get_post_meta( get_the_ID(), '_ah_productos_relacionados', true ) ) );
	?>
	<main id="contenido">
		<article <?php post_class( 'ah-articulo' ); ?>>
			<header class="ah-articulo__cabecera">
				<div class="ah-contenedor ah-contenedor--estrecho">
					<?php ah_tema_migas(); ?>
					<?php if ( $ah_cats ) : ?>
						<a class="ah-etiqueta" href="<?php echo esc_url( get_category_link( $ah_cats[0] ) ); ?>"><?php echo esc_html( $ah_cats[0]->name ); ?></a>
					<?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<p class="ah-articulo__meta">
						<?php esc_html_e( 'Por', 'artesanias-huejutla' ); ?> <strong><?php the_author(); ?></strong> ·
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<?php if ( get_the_modified_date( 'U' ) > get_the_date( 'U' ) + DAY_IN_SECONDS ) : ?>
							· <?php esc_html_e( 'Actualizado el', 'artesanias-huejutla' ); ?> <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time>
						<?php endif; ?>
					</p>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="ah-contenedor ah-articulo__imagen">
					<?php
					the_post_thumbnail(
						'large',
						array(
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
					?>
					<?php if ( get_the_post_thumbnail_caption() ) : ?>
						<figcaption><?php the_post_thumbnail_caption(); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="ah-contenedor ah-contenedor--estrecho">
				<div class="ah-contenido">
					<?php the_content(); ?>
				</div>
				<?php ah_tema_compartir(); ?>
			</div>
		</article>

		<?php if ( $ah_relacionados && class_exists( 'WooCommerce' ) ) : ?>
			<section class="ah-seccion ah-seccion--crema" aria-labelledby="ah-relacionados-titulo">
				<div class="ah-contenedor">
					<div class="ah-encabezado-seccion">
						<p class="ah-antetitulo"><?php esc_html_e( 'Llévate la tradición a casa', 'artesanias-huejutla' ); ?></p>
						<h2 id="ah-relacionados-titulo"><?php esc_html_e( 'Productos relacionados', 'artesanias-huejutla' ); ?></h2>
					</div>
					<?php echo do_shortcode( '[products ids="' . esc_attr( implode( ',', $ah_relacionados ) ) . '" columns="3" orderby="post__in"]' ); ?>
				</div>
			</section>
		<?php endif; ?>

		<nav class="ah-contenedor ah-contenedor--estrecho ah-navegacion-entradas" aria-label="<?php esc_attr_e( 'Más artículos', 'artesanias-huejutla' ); ?>">
			<?php
			the_post_navigation(
				array(
					'prev_text' => '<span>' . __( 'Anterior', 'artesanias-huejutla' ) . '</span>%title',
					'next_text' => '<span>' . __( 'Siguiente', 'artesanias-huejutla' ) . '</span>%title',
				)
			);
			?>
		</nav>
	</main>
	<?php
endwhile;

get_footer();
