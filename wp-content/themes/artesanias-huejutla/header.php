<?php
/**
 * Encabezado: barra de anuncio, logotipo, navegación, buscador, cuenta, favoritos y carrito.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

$ah_anuncio = ah_tema_texto( 'ah_anuncio' );
if ( ! $ah_anuncio && ah_tema_envio_gratis() && function_exists( 'wc_price' ) ) {
	/* translators: %s: monto mínimo. */
	$ah_anuncio = sprintf( __( 'Envío estándar gratis en compras desde %s', 'artesanias-huejutla' ), wp_strip_all_tags( wc_price( ah_tema_envio_gratis(), array( 'decimals' => 0 ) ) ) );
}
$ah_favoritos = (int) get_option( 'ah_gti_pagina_favoritos' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#3F2B21">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="ah-saltar" href="#contenido"><?php esc_html_e( 'Saltar al contenido', 'artesanias-huejutla' ); ?></a>

<header class="ah-encabezado" id="encabezado">
	<div class="ah-anuncio">
		<div class="ah-contenedor ah-anuncio__interior">
			<?php if ( $ah_anuncio ) : ?>
				<p class="ah-anuncio__texto"><?php echo ah_tema_icono( 'camion', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $ah_anuncio ); ?></p>
			<?php endif; ?>
			<?php ah_tema_enlaces_cuenta( 'ah-anuncio__cuenta' ); ?>
		</div>
	</div>

	<div class="ah-cabecera">
		<div class="ah-contenedor ah-cabecera__interior">
			<button type="button" class="ah-boton-menu" aria-expanded="false" aria-controls="ah-navegacion" aria-label="<?php esc_attr_e( 'Abrir menú', 'artesanias-huejutla' ); ?>">
				<?php echo ah_tema_icono( 'menu', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>

			<?php ah_tema_logo(); ?>

			<nav class="ah-navegacion" id="ah-navegacion" aria-label="<?php esc_attr_e( 'Navegación principal', 'artesanias-huejutla' ); ?>">
				<div class="ah-navegacion__cabecera">
					<?php ah_tema_logo( 'ah-logo--pequeno' ); ?>
					<button type="button" class="ah-navegacion__cerrar" aria-label="<?php esc_attr_e( 'Cerrar menú', 'artesanias-huejutla' ); ?>"><?php echo ah_tema_icono( 'cerrar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'principal',
						'container'      => false,
						'menu_class'     => 'ah-menu',
						'depth'          => 2,
						'fallback_cb'    => 'wp_page_menu',
					)
				);
				ah_tema_enlaces_cuenta( 'ah-menu-cuenta' );
				?>
			</nav>

			<form class="ah-buscador" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label for="ah-buscador-global" class="screen-reader-text"><?php esc_html_e( 'Buscar artesanías', 'artesanias-huejutla' ); ?></label>
				<input type="search" id="ah-buscador-global" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Buscar canastas, rebozos, bolsas…', 'artesanias-huejutla' ); ?>" required>
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<input type="hidden" name="post_type" value="product">
				<?php endif; ?>
				<button type="submit" aria-label="<?php esc_attr_e( 'Buscar', 'artesanias-huejutla' ); ?>"><?php echo ah_tema_icono( 'buscar', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</form>

			<div class="ah-cabecera__acciones">
				<?php if ( class_exists( 'WooCommerce' ) && is_user_logged_in() ) : ?>
					<?php
					get_template_part( 'template-parts/menu-cliente' );
					?>
				<?php elseif ( class_exists( 'WooCommerce' ) ) : ?>
					<a class="ah-accion" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php esc_attr_e( 'Iniciar sesión o registrarse', 'artesanias-huejutla' ); ?>">
						<?php echo ah_tema_icono( 'usuario' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="ah-accion__texto"><?php esc_html_e( 'Ingresar', 'artesanias-huejutla' ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $ah_favoritos ) : ?>
					<a class="ah-accion" href="<?php echo esc_url( get_permalink( $ah_favoritos ) ); ?>" aria-label="<?php esc_attr_e( 'Favoritos', 'artesanias-huejutla' ); ?>">
						<?php echo ah_tema_icono( 'corazon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="ah-accion__texto"><?php esc_html_e( 'Favoritos', 'artesanias-huejutla' ); ?></span>
						<span class="ah-contador ah-contador--flotante" data-ah-fav-contador hidden>0</span>
					</a>
				<?php endif; ?>
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<a class="ah-accion ah-accion--carrito" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Carrito de compras', 'artesanias-huejutla' ); ?>">
						<?php echo ah_tema_icono( 'bolsa' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="ah-accion__texto"><?php esc_html_e( 'Carrito', 'artesanias-huejutla' ); ?></span>
						<?php echo ah_tema_contador_carrito(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en la función. ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="ah-velo" hidden></div>
</header>
