<?php
/**
 * Encabezado del cliente con sesión: campana de notificaciones y menú con sus iniciales.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

$ah_usuario = wp_get_current_user();
$ah_nombre  = $ah_usuario->first_name ? $ah_usuario->first_name : $ah_usuario->display_name;
if ( is_email( $ah_nombre ) ) {
	$ah_nombre = strstr( $ah_nombre, '@', true );
}
$ah_partes    = preg_split( '/[\s._-]+/', trim( $ah_usuario->first_name . ' ' . $ah_usuario->last_name ) ? trim( $ah_usuario->first_name . ' ' . $ah_usuario->last_name ) : $ah_nombre );
$ah_iniciales = mb_strtoupper( mb_substr( $ah_partes[0] ?? '', 0, 1 ) . mb_substr( $ah_partes[1] ?? '', 0, 1 ) );
$ah_iniciales = $ah_iniciales ? $ah_iniciales : mb_strtoupper( mb_substr( $ah_nombre, 0, 2 ) );
$ah_compras   = class_exists( 'AH_Mis_Compras' ) ? wc_get_account_endpoint_url( AH_Mis_Compras::ENDPOINT ) : wc_get_page_permalink( 'myaccount' );
$ah_avisos    = class_exists( 'AH_Mis_Compras' ) ? array_reverse( AH_Mis_Compras::notificaciones( $ah_usuario->ID ) ) : array();
$ah_sin_leer  = class_exists( 'AH_Mis_Compras' ) ? AH_Mis_Compras::sin_leer( $ah_usuario->ID ) : 0;
$ah_favoritos = (int) get_option( 'ah_gti_pagina_favoritos' );
?>
<a class="ah-accion ah-accion--compras" href="<?php echo esc_url( $ah_compras ); ?>" aria-label="<?php esc_attr_e( 'Mis compras', 'artesanias-huejutla' ); ?>" title="<?php esc_attr_e( 'Mis compras', 'artesanias-huejutla' ); ?>">
	<?php echo ah_tema_icono( 'recibo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<span class="ah-accion__etiqueta"><?php esc_html_e( 'Mis compras', 'artesanias-huejutla' ); ?></span>
</a>

<?php if ( class_exists( 'AH_Mis_Compras' ) ) : ?>
	<details class="ah-desplegable ah-campana">
		<summary class="ah-accion" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: notificaciones sin leer. */ _n( 'Notificaciones: %d sin leer', 'Notificaciones: %d sin leer', $ah_sin_leer, 'artesanias-huejutla' ), $ah_sin_leer ) ); ?>">
			<?php echo ah_tema_icono( 'campana' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $ah_sin_leer ) : ?>
				<span class="ah-contador ah-contador--alerta"><?php echo $ah_sin_leer > 9 ? '9+' : (int) $ah_sin_leer; ?></span>
			<?php endif; ?>
		</summary>
		<div class="ah-desplegable__panel ah-campana__panel">
			<div class="ah-campana__cabecera">
				<strong><?php esc_html_e( 'Notificaciones', 'artesanias-huejutla' ); ?></strong>
				<?php if ( $ah_sin_leer ) : ?>
					<form method="post">
						<input type="hidden" name="ah_compras_accion" value="leidas">
						<?php wp_nonce_field( 'ah_notificaciones_leidas', 'ah_nonce' ); ?>
						<button type="submit"><?php esc_html_e( 'Marcar todo como leído', 'artesanias-huejutla' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
			<?php if ( $ah_avisos ) : ?>
				<ul>
					<?php foreach ( array_slice( $ah_avisos, 0, 6 ) as $ah_aviso ) : ?>
						<li class="<?php echo empty( $ah_aviso['leida'] ) ? 'nueva' : ''; ?>">
							<a href="<?php echo esc_url( AH_Mis_Compras::url_compra( (int) $ah_aviso['pedido'] ) ); ?>">
								<?php echo ah_tema_icono( 'paquete', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html( $ah_aviso['texto'] ); ?><small><?php echo esc_html( sprintf( /* translators: %s: tiempo transcurrido. */ __( 'Hace %s', 'artesanias-huejutla' ), human_time_diff( (int) $ah_aviso['t'] ) ) ); ?></small></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="ah-campana__vacio"><?php esc_html_e( 'Aquí verás los avisos de tus compras: preparación, envío y entrega.', 'artesanias-huejutla' ); ?></p>
			<?php endif; ?>
			<a class="ah-campana__todo" href="<?php echo esc_url( $ah_compras ); ?>"><?php esc_html_e( 'Ver mis compras', 'artesanias-huejutla' ); ?></a>
		</div>
	</details>
<?php endif; ?>

<details class="ah-desplegable ah-perfil">
	<summary class="ah-accion" aria-label="<?php esc_attr_e( 'Menú de mi cuenta', 'artesanias-huejutla' ); ?>">
		<span class="ah-perfil__avatar" aria-hidden="true"><?php echo esc_html( $ah_iniciales ); ?></span>
		<span class="ah-perfil__nombre"><?php echo esc_html( $ah_nombre ); ?></span>
		<?php echo ah_tema_icono( 'abajo', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</summary>
	<div class="ah-desplegable__panel ah-perfil__panel">
		<p class="ah-perfil__saludo"><span class="ah-perfil__avatar ah-perfil__avatar--grande" aria-hidden="true"><?php echo esc_html( $ah_iniciales ); ?></span><span><strong><?php echo esc_html( sprintf( /* translators: %s: nombre. */ __( 'Hola, %s', 'artesanias-huejutla' ), $ah_nombre ) ); ?></strong><small><?php echo esc_html( $ah_usuario->user_email ); ?></small></span></p>
		<ul>
			<li><a href="<?php echo esc_url( $ah_compras ); ?>"><?php echo ah_tema_icono( 'recibo', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Mis compras', 'artesanias-huejutla' ); ?></a></li>
			<?php if ( $ah_favoritos ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $ah_favoritos ) ); ?>"><?php echo ah_tema_icono( 'corazon', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Favoritos', 'artesanias-huejutla' ); ?></a></li>
			<?php endif; ?>
			<?php if ( class_exists( 'AH_Mesa_Ayuda_Publica' ) ) : ?>
				<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( AH_Mesa_Ayuda_Publica::ENDPOINT ) ); ?>"><?php echo ah_tema_icono( 'chat', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Soporte', 'artesanias-huejutla' ); ?></a></li>
			<?php endif; ?>
			<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-address' ) ); ?>"><?php echo ah_tema_icono( 'ubicacion', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Direcciones', 'artesanias-huejutla' ); ?></a></li>
			<li><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>"><?php echo ah_tema_icono( 'usuario', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Mis datos', 'artesanias-huejutla' ); ?></a></li>
			<?php if ( current_user_can( 'edit_shop_orders' ) ) : ?>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=ah-gestion-ti' ) ); ?>"><?php echo ah_tema_icono( 'soporte', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Panel de administración', 'artesanias-huejutla' ); ?></a></li>
			<?php endif; ?>
		</ul>
		<a class="ah-perfil__salir" href="<?php echo esc_url( wc_logout_url() ); ?>"><?php esc_html_e( 'Cerrar sesión', 'artesanias-huejutla' ); ?></a>
	</div>
</details>
