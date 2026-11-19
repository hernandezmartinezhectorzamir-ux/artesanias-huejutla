<?php
/**
 * Menú de Mi cuenta con íconos (sustituye a myaccount/navigation.php de WooCommerce).
 *
 * @package Artesanias_Huejutla
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$ah_iconos = array(
	'dashboard'       => 'casa',
	'mis-compras'     => 'paquete',
	'orders'          => 'paquete',
	'downloads'       => 'recibo',
	'edit-address'    => 'ubicacion',
	'payment-methods' => 'tarjeta',
	'edit-account'    => 'usuario',
	'soporte'         => 'soporte',
	'customer-logout' => 'salir',
);

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation ah-cuenta-menu" aria-label="<?php esc_attr_e( 'Secciones de mi cuenta', 'artesanias-huejutla' ); ?>">
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $ah_endpoint => $ah_etiqueta ) : ?>
			<li class="<?php echo esc_attr( wc_get_account_menu_item_classes( $ah_endpoint ) ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $ah_endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $ah_endpoint ) ? 'aria-current="page"' : ''; ?>>
					<span class="ah-cuenta-menu__icono"><?php echo ah_tema_icono( $ah_iconos[ $ah_endpoint ] ?? 'enlace', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo del tema. ?></span>
					<span class="ah-cuenta-menu__texto"><?php echo esc_html( $ah_etiqueta ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
