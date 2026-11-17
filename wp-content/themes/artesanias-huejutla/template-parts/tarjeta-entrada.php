<?php
/**
 * Tarjeta de entrada del blog.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

$ah_nivel = isset( $args['nivel'] ) ? (int) $args['nivel'] : 2;
$ah_cats  = get_the_category();
?>
<article <?php post_class( 'ah-tarjeta-entrada ah-revelar' ); ?>>
	<a class="ah-tarjeta-entrada__img" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'ah-tarjeta', array( 'alt' => '' ) ); ?>
		<?php endif; ?>
	</a>
	<div class="ah-tarjeta-entrada__cuerpo">
		<p class="ah-tarjeta-entrada__meta">
			<?php if ( $ah_cats ) : ?>
				<span class="ah-etiqueta"><?php echo esc_html( $ah_cats[0]->name ); ?></span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</p>
		<?php printf( '<h%1$d class="ah-tarjeta-entrada__titulo"><a href="%2$s">%3$s</a></h%1$d>', (int) $ah_nivel, esc_url( get_permalink() ), esc_html( get_the_title() ) ); ?>
		<p><?php echo esc_html( get_the_excerpt() ); ?></p>
		<a class="ah-enlace-flecha" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Leer artículo', 'artesanias-huejutla' ); ?><span class="screen-reader-text">: <?php the_title(); ?></span> <?php echo ah_tema_icono( 'flecha', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
	</div>
</article>
