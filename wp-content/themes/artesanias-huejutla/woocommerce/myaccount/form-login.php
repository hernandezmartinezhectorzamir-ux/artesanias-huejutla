<?php
/**
 * Acceso y registro de clientes (sustituye a myaccount/form-login.php de WooCommerce).
 *
 * Diseño: panel de bienvenida con beneficios y cuenta de prueba opcional a la izquierda,
 * y pestañas "Iniciar sesión / Crear cuenta" a la derecha. Conserva los nombres de campos,
 * nonces y ganchos de la plantilla original para no romper el proceso de WooCommerce.
 *
 * @package Artesanias_Huejutla
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

$ah_registro = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Solo decide qué pestaña abrir tras un intento de registro fallido.
$ah_inicial  = ( $ah_registro && isset( $_POST['register'] ) ) ? 'registro' : 'acceder';
$ah_demo     = '1' === ah_tema_opcion( 'demo_activa' ) && ah_tema_opcion( 'demo_correo' ) && ah_tema_opcion( 'demo_clave' );
$ah_rastreo  = get_page_by_path( 'rastrear-pedido' );
$ah_boton    = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
$ah_ventajas = array(
	'paquete' => __( 'Sigue cada compra paso a paso, de tu pedido a tu puerta.', 'artesanias-huejutla' ),
	'corazon' => __( 'Guarda tus artesanías favoritas para después.', 'artesanias-huejutla' ),
	'repetir' => __( 'Vuelve a comprar en un clic y paga más rápido.', 'artesanias-huejutla' ),
	'soporte' => __( 'Atención personalizada desde tu cuenta.', 'artesanias-huejutla' ),
);

do_action( 'woocommerce_before_customer_login_form' );
?>

<div class="ah-acceso" id="customer_login" data-ah-acceso data-inicial="<?php echo esc_attr( $ah_inicial ); ?>">

	<aside class="ah-acceso__marca" aria-labelledby="ah-acceso-bienvenida">
		<div class="ah-acceso__sello" aria-hidden="true"><?php echo ah_tema_logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fijo del tema. ?></div>
		<p class="ah-acceso__antetitulo"><?php esc_html_e( 'Tu cuenta artesanal', 'artesanias-huejutla' ); ?></p>
		<h2 id="ah-acceso-bienvenida" class="ah-acceso__titulo"><?php esc_html_e( 'Bienvenido a la Huasteca hecha a mano', 'artesanias-huejutla' ); ?></h2>
		<p class="ah-acceso__intro"><?php esc_html_e( 'Cada pieza la elaboran familias artesanas de Huejutla. Con tu cuenta, todo queda en un solo lugar.', 'artesanias-huejutla' ); ?></p>

		<ul class="ah-acceso__ventajas">
			<?php foreach ( $ah_ventajas as $ah_icono => $ah_texto ) : ?>
				<li><span class="ah-acceso__icono"><?php echo ah_tema_icono( $ah_icono, 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php echo esc_html( $ah_texto ); ?></li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $ah_demo ) : ?>
			<div class="ah-demo" id="ah-demo" aria-labelledby="ah-demo-titulo">
				<div class="ah-demo__cabecera">
					<span class="ah-demo__icono"><?php echo ah_tema_icono( 'llave', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<p id="ah-demo-titulo" class="ah-demo__titulo"><?php esc_html_e( 'Cuenta de prueba', 'artesanias-huejutla' ); ?></p>
					<span class="ah-demo__etiqueta"><?php esc_html_e( 'Solo para revisión', 'artesanias-huejutla' ); ?></span>
				</div>
				<p class="ah-demo__nota"><?php esc_html_e( 'Entra como cliente y explora Mis compras, favoritos y soporte con pedidos de ejemplo.', 'artesanias-huejutla' ); ?></p>
				<dl class="ah-demo__datos">
					<div>
						<dt><?php esc_html_e( 'Correo', 'artesanias-huejutla' ); ?></dt>
						<dd><code data-ah-demo-correo><?php echo esc_html( ah_tema_opcion( 'demo_correo' ) ); ?></code>
							<button type="button" class="ah-demo__copiar" data-ah-copiar="<?php echo esc_attr( ah_tema_opcion( 'demo_correo' ) ); ?>" aria-label="<?php esc_attr_e( 'Copiar correo', 'artesanias-huejutla' ); ?>"><?php echo ah_tema_icono( 'copiar', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						</dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Contraseña', 'artesanias-huejutla' ); ?></dt>
						<dd><code data-ah-demo-clave><?php echo esc_html( ah_tema_opcion( 'demo_clave' ) ); ?></code>
							<button type="button" class="ah-demo__copiar" data-ah-copiar="<?php echo esc_attr( ah_tema_opcion( 'demo_clave' ) ); ?>" aria-label="<?php esc_attr_e( 'Copiar contraseña', 'artesanias-huejutla' ); ?>"><?php echo ah_tema_icono( 'copiar', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						</dd>
					</div>
				</dl>
				<button type="button" class="ah-boton ah-boton--claro ah-demo__usar" data-ah-demo-usar><?php esc_html_e( 'Entrar con la cuenta de prueba', 'artesanias-huejutla' ); ?></button>
				<p class="ah-demo__aviso" role="status" aria-live="polite" data-ah-demo-aviso></p>
			</div>
		<?php endif; ?>
	</aside>

	<div class="ah-acceso__panel">

		<?php if ( $ah_registro ) : ?>
			<div class="ah-acceso__pestanas" role="tablist" aria-label="<?php esc_attr_e( 'Acceso a tu cuenta', 'artesanias-huejutla' ); ?>">
				<button type="button" role="tab" id="ah-tab-acceder" aria-controls="ah-panel-acceder" aria-selected="true" data-ah-tab="acceder"><?php esc_html_e( 'Iniciar sesión', 'artesanias-huejutla' ); ?></button>
				<button type="button" role="tab" id="ah-tab-registro" aria-controls="ah-panel-registro" aria-selected="false" tabindex="-1" data-ah-tab="registro"><?php esc_html_e( 'Crear cuenta', 'artesanias-huejutla' ); ?></button>
				<span class="ah-acceso__indicador" aria-hidden="true"></span>
			</div>
		<?php endif; ?>

		<?php if ( $ah_demo ) : ?>
			<a class="ah-acceso__atajo-demo" href="#ah-demo"><?php echo ah_tema_icono( 'llave', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( '¿Vas a revisar el sitio? Usa la cuenta de prueba', 'artesanias-huejutla' ); ?></a>
		<?php endif; ?>

		<section class="ah-acceso__seccion" id="ah-panel-acceder" <?php echo $ah_registro ? 'role="tabpanel" aria-labelledby="ah-tab-acceder"' : ''; ?> data-ah-panel="acceder">
			<h2 class="ah-acceso__subtitulo"><?php esc_html_e( 'Qué gusto verte de nuevo', 'artesanias-huejutla' ); ?></h2>
			<p class="ah-acceso__ayuda"><?php esc_html_e( 'Inicia sesión para ver tus compras, favoritos y tickets de soporte.', 'artesanias-huejutla' ); ?></p>

			<form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="username"><?php esc_html_e( 'Correo electrónico o usuario', 'artesanias-huejutla' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Obligatorio', 'artesanias-huejutla' ); ?></span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" placeholder="<?php esc_attr_e( 'tu@correo.com', 'artesanias-huejutla' ); ?>" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore WordPress.Security ?>
				</p>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="password"><?php esc_html_e( 'Contraseña', 'artesanias-huejutla' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Obligatorio', 'artesanias-huejutla' ); ?></span></label>
					<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
				</p>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<div class="ah-acceso__fila">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e( 'Recordarme', 'artesanias-huejutla' ); ?></span>
					</label>
					<a class="ah-acceso__olvido" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( '¿Olvidaste tu contraseña?', 'artesanias-huejutla' ); ?></a>
				</div>

				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit ah-acceso__enviar<?php echo esc_attr( $ah_boton ); ?>" name="login" value="<?php esc_attr_e( 'Acceder', 'artesanias-huejutla' ); ?>"><?php esc_html_e( 'Acceder', 'artesanias-huejutla' ); ?></button>

				<?php do_action( 'woocommerce_login_form_end' ); ?>

			</form>

			<?php if ( $ah_registro ) : ?>
				<p class="ah-acceso__cambio"><?php esc_html_e( '¿Primera vez aquí?', 'artesanias-huejutla' ); ?> <button type="button" class="ah-acceso__enlace" data-ah-ir="registro"><?php esc_html_e( 'Crea tu cuenta', 'artesanias-huejutla' ); ?></button></p>
			<?php endif; ?>
		</section>

		<?php if ( $ah_registro ) : ?>
			<section class="ah-acceso__seccion" id="ah-panel-registro" role="tabpanel" aria-labelledby="ah-tab-registro" data-ah-panel="registro">
				<h2 class="ah-acceso__subtitulo"><?php esc_html_e( 'Crea tu cuenta en un minuto', 'artesanias-huejutla' ); ?></h2>
				<p class="ah-acceso__ayuda"><?php esc_html_e( 'Solo necesitas tu correo y una contraseña.', 'artesanias-huejutla' ); ?></p>

				<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

					<?php do_action( 'woocommerce_register_form_start' ); ?>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_username"><?php esc_html_e( 'Nombre de usuario', 'artesanias-huejutla' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Obligatorio', 'artesanias-huejutla' ); ?></span></label>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore WordPress.Security ?>
						</p>
					<?php endif; ?>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_email"><?php esc_html_e( 'Correo electrónico', 'artesanias-huejutla' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Obligatorio', 'artesanias-huejutla' ); ?></span></label>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" placeholder="<?php esc_attr_e( 'tu@correo.com', 'artesanias-huejutla' ); ?>" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" /><?php // phpcs:ignore WordPress.Security ?>
					</p>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_password"><?php esc_html_e( 'Contraseña', 'artesanias-huejutla' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Obligatorio', 'artesanias-huejutla' ); ?></span></label>
							<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
						</p>
					<?php else : ?>
						<p class="ah-acceso__ayuda"><?php esc_html_e( 'Te enviaremos un enlace a tu correo para que elijas tu contraseña.', 'artesanias-huejutla' ); ?></p>
					<?php endif; ?>

					<?php do_action( 'woocommerce_register_form' ); ?>

					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit ah-acceso__enviar<?php echo esc_attr( $ah_boton ); ?>" name="register" value="<?php esc_attr_e( 'Crear mi cuenta', 'artesanias-huejutla' ); ?>"><?php esc_html_e( 'Crear mi cuenta', 'artesanias-huejutla' ); ?></button>

					<?php do_action( 'woocommerce_register_form_end' ); ?>

				</form>

				<p class="ah-acceso__cambio"><?php esc_html_e( '¿Ya tienes cuenta?', 'artesanias-huejutla' ); ?> <button type="button" class="ah-acceso__enlace" data-ah-ir="acceder"><?php esc_html_e( 'Inicia sesión', 'artesanias-huejutla' ); ?></button></p>
			</section>
		<?php endif; ?>

		<?php if ( $ah_rastreo ) : ?>
			<p class="ah-acceso__invitado">
				<?php echo ah_tema_icono( 'camion', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( '¿Compraste sin cuenta?', 'artesanias-huejutla' ); ?> <a href="<?php echo esc_url( get_permalink( $ah_rastreo ) ); ?>"><?php esc_html_e( 'Rastrea tu pedido', 'artesanias-huejutla' ); ?></a></span>
			</p>
		<?php endif; ?>
	</div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
