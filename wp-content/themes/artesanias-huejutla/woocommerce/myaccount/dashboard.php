<?php
/**
 * Escritorio de Mi cuenta (sustituye a myaccount/dashboard.php de WooCommerce).
 *
 * Muestra datos reales del cliente: compras por etapa, favoritos, tickets abiertos y la última compra.
 *
 * @package Artesanias_Huejutla
 * @version 4.4.0
 */

defined( 'ABSPATH' ) || exit;

$ah_usuario = wp_get_current_user();
$ah_nombre  = $ah_usuario->first_name ? $ah_usuario->first_name : $ah_usuario->display_name;
$ah_inicial = strtoupper( mb_substr( (string) $ah_usuario->first_name, 0, 1 ) . mb_substr( (string) $ah_usuario->last_name, 0, 1 ) );
$ah_inicial = $ah_inicial ? $ah_inicial : strtoupper( mb_substr( (string) $ah_usuario->display_name, 0, 1 ) );

// Compras del cliente agrupadas por etapa.
$ah_pedidos = wc_get_orders(
	array(
		'customer_id' => $ah_usuario->ID,
		'limit'       => 100,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'status'      => array_keys( wc_get_order_statuses() ),
	)
);
$ah_etapas  = array(
	'preparacion' => 0,
	'camino'      => 0,
	'entregadas'  => 0,
);
foreach ( $ah_pedidos as $ah_pedido ) {
	$ah_estado = $ah_pedido->get_status();
	if ( in_array( $ah_estado, array( 'pending', 'on-hold', 'processing' ), true ) ) {
		++$ah_etapas['preparacion'];
	} elseif ( 'en-camino' === $ah_estado ) {
		++$ah_etapas['camino'];
	} elseif ( 'completed' === $ah_estado ) {
		++$ah_etapas['entregadas'];
	}
}
$ah_ultimo    = $ah_pedidos ? $ah_pedidos[0] : null;
$ah_favoritos = class_exists( 'AH_Favoritos' ) ? count( AH_Favoritos::del_usuario( $ah_usuario->ID ) ) : 0;
$ah_pag_fav   = get_page_by_path( 'favoritos' );
$ah_tickets   = 0;
$ah_hay_mesa  = class_exists( 'AH_Tickets' );
if ( $ah_hay_mesa ) {
	global $wpdb;
	$ah_tabla   = AH_Tickets::tabla();
	$ah_tickets = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$ah_tabla} WHERE user_id = %d AND estado NOT IN ('resuelto', 'cerrado')", $ah_usuario->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla interno.
}
$ah_compras    = wc_get_account_endpoint_url( class_exists( 'AH_Mis_Compras' ) ? AH_Mis_Compras::ENDPOINT : 'orders' );
$ah_url_ultimo = $ah_ultimo ? ( class_exists( 'AH_Mis_Compras' ) ? AH_Mis_Compras::url_compra( $ah_ultimo->get_id() ) : $ah_ultimo->get_view_order_url() ) : '';

$ah_resumen = array(
	array( 'paquete', $ah_etapas['preparacion'], __( 'En preparación', 'artesanias-huejutla' ), $ah_compras ),
	array( 'camion', $ah_etapas['camino'], __( 'En camino', 'artesanias-huejutla' ), $ah_compras ),
	array( 'check', $ah_etapas['entregadas'], __( 'Entregadas', 'artesanias-huejutla' ), $ah_compras ),
	array( 'corazon', $ah_favoritos, __( 'Favoritos', 'artesanias-huejutla' ), $ah_pag_fav ? get_permalink( $ah_pag_fav ) : wc_get_page_permalink( 'shop' ) ),
);

$ah_accesos = array(
	array( 'paquete', __( 'Mis compras', 'artesanias-huejutla' ), __( 'Seguimiento, comprobantes y volver a comprar.', 'artesanias-huejutla' ), $ah_compras ),
	array( 'ubicacion', __( 'Direcciones', 'artesanias-huejutla' ), __( 'Dónde enviamos tus artesanías y tus datos de facturación.', 'artesanias-huejutla' ), wc_get_account_endpoint_url( 'edit-address' ) ),
	array( 'usuario', __( 'Datos y contraseña', 'artesanias-huejutla' ), __( 'Tu nombre, correo y contraseña de acceso.', 'artesanias-huejutla' ), wc_get_account_endpoint_url( 'edit-account' ) ),
);
if ( $ah_hay_mesa ) {
	$ah_accesos[] = array(
		'soporte',
		__( 'Soporte', 'artesanias-huejutla' ),
		$ah_tickets
			/* translators: %d: tickets abiertos. */
			? sprintf( _n( 'Tienes %d solicitud abierta.', 'Tienes %d solicitudes abiertas.', $ah_tickets, 'artesanias-huejutla' ), $ah_tickets )
			: __( '¿Algún problema con tu compra? Te ayudamos.', 'artesanias-huejutla' ),
		wc_get_account_endpoint_url( 'soporte' ),
	);
}
?>

<div class="ah-escritorio">

	<section class="ah-escritorio__bienvenida" aria-labelledby="ah-escritorio-hola">
		<span class="ah-escritorio__avatar" aria-hidden="true"><?php echo esc_html( $ah_inicial ); ?></span>
		<div class="ah-escritorio__saludo">
			<p class="ah-escritorio__ante"><?php esc_html_e( 'Tu cuenta', 'artesanias-huejutla' ); ?></p>
			<h2 id="ah-escritorio-hola">
				<?php
				/* translators: %s: nombre del cliente. */
				echo esc_html( sprintf( __( '¡Hola, %s!', 'artesanias-huejutla' ), $ah_nombre ) );
				?>
			</h2>
			<p class="ah-escritorio__meta">
				<?php echo esc_html( $ah_usuario->user_email ); ?>
				<span aria-hidden="true">·</span>
				<?php
				/* translators: %s: fecha de registro. */
				echo esc_html( sprintf( __( 'Cliente desde %s', 'artesanias-huejutla' ), wp_date( 'F \d\e Y', strtotime( $ah_usuario->user_registered ) ) ) );
				?>
			</p>
		</div>
		<a class="ah-boton ah-boton--claro ah-escritorio__tienda" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php echo ah_tema_icono( 'bolsa', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Seguir comprando', 'artesanias-huejutla' ); ?></a>
	</section>

	<ul class="ah-escritorio__resumen" aria-label="<?php esc_attr_e( 'Resumen de tu cuenta', 'artesanias-huejutla' ); ?>">
		<?php foreach ( $ah_resumen as list( $ah_icono, $ah_valor, $ah_texto, $ah_url ) ) : ?>
			<li>
				<a href="<?php echo esc_url( $ah_url ); ?>" class="ah-escritorio__dato<?php echo $ah_valor ? ' is-activo' : ''; ?>">
					<span class="ah-escritorio__dato-icono"><?php echo ah_tema_icono( $ah_icono, 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<strong><?php echo esc_html( number_format_i18n( $ah_valor ) ); ?></strong>
					<span><?php echo esc_html( $ah_texto ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $ah_ultimo ) : ?>
		<section class="ah-escritorio__ultima" aria-labelledby="ah-escritorio-ultima">
			<div>
				<h3 id="ah-escritorio-ultima"><?php esc_html_e( 'Tu compra más reciente', 'artesanias-huejutla' ); ?></h3>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: número de pedido, 2: fecha, 3: total. */
							__( 'Compra #%1$s · %2$s · %3$s', 'artesanias-huejutla' ),
							$ah_ultimo->get_order_number(),
							wc_format_datetime( $ah_ultimo->get_date_created() ),
							wp_strip_all_tags( wc_price( $ah_ultimo->get_total(), array( 'currency' => $ah_ultimo->get_currency() ) ) )
						)
					);
					?>
				</p>
			</div>
			<span class="ah-escritorio__estado ah-escritorio__estado--<?php echo esc_attr( $ah_ultimo->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $ah_ultimo->get_status() ) ); ?></span>
			<a class="ah-boton" href="<?php echo esc_url( $ah_url_ultimo ); ?>"><?php esc_html_e( 'Ver seguimiento', 'artesanias-huejutla' ); ?></a>
		</section>
	<?php else : ?>
		<section class="ah-escritorio__ultima ah-escritorio__ultima--vacia">
			<div>
				<h3><?php esc_html_e( 'Aún no tienes compras', 'artesanias-huejutla' ); ?></h3>
				<p><?php esc_html_e( 'Descubre canastas de palma, rebozos bordados y más piezas hechas a mano en la Huasteca.', 'artesanias-huejutla' ); ?></p>
			</div>
			<a class="ah-boton" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Explorar la tienda', 'artesanias-huejutla' ); ?></a>
		</section>
	<?php endif; ?>

	<h3 class="ah-escritorio__subtitulo"><?php esc_html_e( 'Accesos rápidos', 'artesanias-huejutla' ); ?></h3>
	<ul class="ah-escritorio__accesos">
		<?php foreach ( $ah_accesos as list( $ah_icono, $ah_titulo, $ah_texto, $ah_url ) ) : ?>
			<li>
				<a class="ah-escritorio__acceso" href="<?php echo esc_url( $ah_url ); ?>">
					<span class="ah-escritorio__acceso-icono"><?php echo ah_tema_icono( $ah_icono, 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="ah-escritorio__acceso-texto">
						<strong><?php echo esc_html( $ah_titulo ); ?></strong>
						<span><?php echo esc_html( $ah_texto ); ?></span>
					</span>
					<span class="ah-escritorio__flecha" aria-hidden="true">→</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php
	/**
	 * Ganchos de la plantilla original de WooCommerce.
	 */
	do_action( 'woocommerce_account_dashboard' );
	do_action( 'woocommerce_before_my_account' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Gancho de WooCommerce (obsoleto, se conserva por compatibilidad).
	do_action( 'woocommerce_after_my_account' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	?>
</div>
