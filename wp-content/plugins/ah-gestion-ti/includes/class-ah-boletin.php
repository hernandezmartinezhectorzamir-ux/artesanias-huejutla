<?php
/**
 * Boletín de correo: suscripción con doble confirmación, baja y lista de suscriptores.
 *
 * El plugin solo administra la lista (alta, confirmación, baja y exportación CSV).
 * El envío de campañas requiere un servicio de correo externo (SMTP, Brevo, Mailchimp…);
 * no se simula ningún envío masivo.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Suscripciones al boletín.
 */
final class AH_Boletin {

	/** Página de administración. */
	const PAGINA = 'ah-boletin';

	/** Acción de admin-post para suscribirse. */
	const ACCION = 'ah_boletin';

	/** Altas permitidas por IP cada 10 minutos. */
	const LIMITE = 5;

	/**
	 * Nombre de la tabla.
	 *
	 * @return string
	 */
	public static function tabla() {
		global $wpdb;
		return $wpdb->prefix . 'ah_suscriptores';
	}

	/**
	 * Estados posibles.
	 *
	 * @return array<string,string>
	 */
	public static function estados() {
		return array(
			'pendiente'  => __( 'Pendiente de confirmar', 'ah-gestion-ti' ),
			'confirmado' => __( 'Confirmado', 'ah-gestion-ti' ),
			'baja'       => __( 'Dado de baja', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_shortcode( 'ah_boletin', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_post_nopriv_' . self::ACCION, array( __CLASS__, 'procesar_alta' ) );
		add_action( 'admin_post_' . self::ACCION, array( __CLASS__, 'procesar_alta' ) );
		add_action( 'template_redirect', array( __CLASS__, 'procesar_enlace' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'admin_post_ah_boletin_csv', array( __CLASS__, 'exportar_csv' ) );
		add_action( 'admin_post_ah_boletin_eliminar', array( __CLASS__, 'eliminar' ) );
		add_action( 'admin_post_ah_boletin_reenviar', array( __CLASS__, 'reenviar' ) );
	}

	/* ---------------------------------------------------------------------
	 * Formulario público
	 * ------------------------------------------------------------------ */

	/**
	 * Shortcode [ah_boletin origen="pagina"].
	 *
	 * @param array|string $atts Atributos.
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts( array( 'origen' => 'pagina' ), $atts, 'ah_boletin' );
		return self::formulario( $atts['origen'] );
	}

	/**
	 * HTML del formulario de suscripción.
	 *
	 * @param string $origen Dónde se muestra (pie, pagina…), para estadística.
	 * @return string
	 */
	public static function formulario( $origen = 'pie' ) {
		$origen     = sanitize_key( $origen );
		$id         = self::ancla( $origen );
		$privacidad = get_privacy_policy_url();
		$aviso      = self::mensaje_resultado();

		ob_start();
		?>
		<form class="ah-boletin__form" id="<?php echo esc_attr( $id ); ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" novalidate>
			<input type="hidden" name="action" value="<?php echo esc_attr( self::ACCION ); ?>">
			<input type="hidden" name="origen" value="<?php echo esc_attr( $origen ); ?>">
			<input type="hidden" name="volver" value="<?php echo esc_attr( self::url_actual() ); ?>">
			<?php wp_nonce_field( self::ACCION, 'ah_boletin_nonce' ); ?>
			<p class="ah-oculto" aria-hidden="true"><label>Sitio web <input type="text" name="ah_sitio_web" tabindex="-1" autocomplete="off"></label></p>

			<div class="ah-boletin__campos">
				<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>-correo"><?php esc_html_e( 'Correo electrónico', 'ah-gestion-ti' ); ?></label>
				<input type="email" id="<?php echo esc_attr( $id ); ?>-correo" name="correo" required maxlength="190" autocomplete="email" placeholder="<?php esc_attr_e( 'tu@correo.com', 'ah-gestion-ti' ); ?>">
				<button type="submit" class="ah-boton ah-boton--primario"><?php esc_html_e( 'Suscribirme', 'ah-gestion-ti' ); ?></button>
			</div>

			<label class="ah-boletin__consentimiento">
				<input type="checkbox" name="consentimiento" value="1" required>
				<span>
					<?php
					if ( $privacidad ) {
						printf(
							/* translators: %s: enlace al aviso de privacidad. */
							wp_kses( __( 'Acepto recibir novedades y promociones por correo y he leído el <a href="%s">aviso de privacidad</a>. Puedo darme de baja cuando quiera.', 'ah-gestion-ti' ), array( 'a' => array( 'href' => array() ) ) ),
							esc_url( $privacidad )
						);
					} else {
						esc_html_e( 'Acepto recibir novedades y promociones por correo. Puedo darme de baja cuando quiera.', 'ah-gestion-ti' );
					}
					?>
				</span>
			</label>

			<?php if ( $aviso ) : ?>
				<p class="ah-boletin__aviso ah-boletin__aviso--<?php echo esc_attr( $aviso[0] ); ?>" role="status"><?php echo esc_html( $aviso[1] ); ?></p>
			<?php endif; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * ID HTML del formulario; el del pie es el ancla de los enlaces del correo.
	 *
	 * @param string $origen Origen.
	 * @return string
	 */
	private static function ancla( $origen ) {
		return 'pie' === $origen ? 'ah-boletin' : 'ah-boletin-' . sanitize_key( $origen );
	}

	/**
	 * URL actual sin parámetros del boletín (para volver tras enviar).
	 *
	 * @return string
	 */
	private static function url_actual() {
		// REQUEST_URI ya incluye la subcarpeta de la instalación; se usa como ruta relativa.
		$ruta = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		return $ruta ? remove_query_arg( array( 'boletin' ), $ruta ) : home_url( '/' );
	}

	/**
	 * Mensaje según el código de la URL (?boletin=…). Solo códigos fijos, nunca datos del usuario.
	 *
	 * @return array{0:string,1:string}|null Tipo (ok|error) y texto.
	 */
	private static function mensaje_resultado() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo se lee un código para mostrar un mensaje fijo.
		$codigo   = isset( $_GET['boletin'] ) ? sanitize_key( wp_unslash( $_GET['boletin'] ) ) : '';
		$mensajes = array(
			'revisa'     => array( 'ok', __( 'Gracias. Si el correo es válido, te enviamos un enlace para confirmar tu suscripción. Revisa también la carpeta de spam.', 'ah-gestion-ti' ) ),
			'confirmado' => array( 'ok', __( '¡Listo! Tu suscripción al boletín quedó confirmada.', 'ah-gestion-ti' ) ),
			'baja'       => array( 'ok', __( 'Te diste de baja del boletín. Ya no recibirás correos promocionales.', 'ah-gestion-ti' ) ),
			'correo'     => array( 'error', __( 'Escribe un correo electrónico válido.', 'ah-gestion-ti' ) ),
			'acepta'     => array( 'error', __( 'Para suscribirte debes aceptar recibir correos y el aviso de privacidad.', 'ah-gestion-ti' ) ),
			'limite'     => array( 'error', __( 'Realizaste demasiados intentos. Espera unos minutos e intenta de nuevo.', 'ah-gestion-ti' ) ),
			'sesion'     => array( 'error', __( 'El formulario expiró. Recarga la página e intenta de nuevo.', 'ah-gestion-ti' ) ),
			'enlace'     => array( 'error', __( 'El enlace no es válido o ya fue utilizado.', 'ah-gestion-ti' ) ),
		);
		return $mensajes[ $codigo ] ?? null;
	}

	/* ---------------------------------------------------------------------
	 * Alta, confirmación y baja
	 * ------------------------------------------------------------------ */

	/**
	 * Procesa el formulario (admin-post.php, patrón PRG).
	 */
	public static function procesar_alta() {
		$volver = isset( $_POST['volver'] ) ? esc_url_raw( wp_unslash( $_POST['volver'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Solo es el destino; se valida con wp_validate_redirect.
		$volver = '' !== $volver ? wp_validate_redirect( $volver, home_url( '/' ) ) : '';
		$volver = '' !== $volver ? $volver : home_url( '/' );
		$ancla  = self::ancla( isset( $_POST['origen'] ) ? sanitize_key( wp_unslash( $_POST['origen'] ) ) : 'pagina' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Solo define el ancla de regreso.
		$ir     = static function ( $codigo ) use ( $volver, $ancla ) {
			wp_safe_redirect( add_query_arg( 'boletin', $codigo, $volver ) . '#' . $ancla );
			exit;
		};

		if ( ! isset( $_POST['ah_boletin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ah_boletin_nonce'] ) ), self::ACCION ) ) {
			$ir( 'sesion' );
		}

		// Honeypot: se responde como si todo saliera bien para no dar pistas al bot.
		if ( ! empty( $_POST['ah_sitio_web'] ) ) {
			$ir( 'revisa' );
		}

		$correo = isset( $_POST['correo'] ) ? sanitize_email( wp_unslash( $_POST['correo'] ) ) : '';
		if ( ! AH_Calculos::correo_boletin_valido( $correo ) ) {
			$ir( 'correo' );
		}
		if ( empty( $_POST['consentimiento'] ) ) {
			$ir( 'acepta' );
		}
		if ( ! self::dentro_de_limite() ) {
			$ir( 'limite' );
		}

		$origen = isset( $_POST['origen'] ) ? sanitize_key( wp_unslash( $_POST['origen'] ) ) : 'pagina';
		self::registrar( strtolower( $correo ), $origen );

		// Mismo mensaje exista o no el correo: no se revela quién está suscrito.
		$ir( 'revisa' );
	}

	/**
	 * Crea o reactiva la suscripción y envía el correo de confirmación.
	 *
	 * @param string $correo Correo normalizado.
	 * @param string $origen Origen del formulario.
	 */
	public static function registrar( $correo, $origen ) {
		global $wpdb;
		$tabla  = self::tabla();
		$actual = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE correo = %s", $correo ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla interno.

		if ( $actual && 'confirmado' === $actual->estado ) {
			return; // Ya suscrito: no se hace nada ni se informa.
		}

		$token  = wp_generate_password( 32, false );
		$ahora  = current_time( 'mysql', true );
		$ip     = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$huella = $ip ? hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ) : '';

		$datos = array(
			'estado'         => 'pendiente',
			'token'          => $token,
			'origen'         => substr( $origen, 0, 40 ),
			'consentimiento' => $ahora,
			'ip_huella'      => $huella,
			'baja'           => null,
		);

		if ( $actual ) {
			$wpdb->update( $tabla, $datos, array( 'id' => $actual->id ) );
			$id = (int) $actual->id;
		} else {
			$datos['correo']  = $correo;
			$datos['user_id'] = get_current_user_id();
			$datos['creado']  = $ahora;
			$wpdb->insert( $tabla, $datos );
			$id = (int) $wpdb->insert_id;
		}

		self::enviar_confirmacion( $id, $correo, $token );
	}

	/**
	 * Envía el correo de doble confirmación y guarda si wp_mail lo aceptó.
	 *
	 * @param int    $id     ID del suscriptor.
	 * @param string $correo Correo.
	 * @param string $token  Token.
	 * @return bool
	 */
	private static function enviar_confirmacion( $id, $correo, $token ) {
		global $wpdb;
		$tienda = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$cuerpo = sprintf(
			/* translators: 1: tienda, 2: enlace de confirmación, 3: enlace de baja. */
			__( "Hola:\n\nRecibimos una solicitud para suscribir este correo al boletín de %1\$s.\n\nPara confirmarla, abre este enlace:\n%2\$s\n\nSi no fuiste tú, ignora este mensaje; no te enviaremos nada más.\n\nPuedes darte de baja en cualquier momento:\n%3\$s\n", 'ah-gestion-ti' ),
			$tienda,
			self::url_enlace( 'confirmar', $token ),
			self::url_enlace( 'baja', $token )
		);
		/* translators: %s: nombre de la tienda. */
		$enviado = wp_mail( $correo, sprintf( __( 'Confirma tu suscripción a %s', 'ah-gestion-ti' ), $tienda ), $cuerpo );
		$wpdb->update( self::tabla(), array( 'correo_enviado' => $enviado ? 1 : 0 ), array( 'id' => $id ) );
		return (bool) $enviado;
	}

	/**
	 * Enlace de confirmación o baja.
	 *
	 * @param string $accion confirmar|baja.
	 * @param string $token  Token.
	 * @return string
	 */
	public static function url_enlace( $accion, $token ) {
		return add_query_arg(
			array(
				'ah_boletin' => $accion,
				't'          => rawurlencode( $token ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Atiende los enlaces de confirmación y baja (?ah_boletin=confirmar|baja&t=…).
	 */
	public static function procesar_enlace() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- El token aleatorio de 32 caracteres hace de credencial del enlace.
		if ( empty( $_GET['ah_boletin'] ) || empty( $_GET['t'] ) ) {
			return;
		}
		$accion = sanitize_key( wp_unslash( $_GET['ah_boletin'] ) );
		$token  = preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_GET['t'] ) ) );
		// phpcs:enable
		if ( ! in_array( $accion, array( 'confirmar', 'baja' ), true ) ) {
			return;
		}

		global $wpdb;
		$tabla = self::tabla();
		$fila  = strlen( $token ) === 32 ? $wpdb->get_row( $wpdb->prepare( "SELECT id, estado FROM {$tabla} WHERE token = %s", $token ) ) : null; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla interno.
		$fin   = home_url( '/' );

		if ( ! $fila ) {
			wp_safe_redirect( add_query_arg( 'boletin', 'enlace', $fin ) . '#ah-boletin' );
			exit;
		}

		$ahora = current_time( 'mysql', true );
		if ( 'confirmar' === $accion && 'baja' !== $fila->estado ) {
			$wpdb->update(
				$tabla,
				array(
					'estado'     => 'confirmado',
					'confirmado' => $ahora,
				),
				array( 'id' => $fila->id )
			);
			$codigo = 'confirmado';
		} elseif ( 'baja' === $accion ) {
			$wpdb->update(
				$tabla,
				array(
					'estado' => 'baja',
					'baja'   => $ahora,
				),
				array( 'id' => $fila->id )
			);
			$codigo = 'baja';
		} else {
			$codigo = 'enlace';
		}

		wp_safe_redirect( add_query_arg( 'boletin', $codigo, $fin ) . '#ah-boletin' );
		exit;
	}

	/**
	 * Límite de altas por IP.
	 *
	 * @return bool
	 */
	private static function dentro_de_limite() {
		$ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'desconocida' ) );
		$clave = 'ah_lim_boletin_' . md5( $ip );
		$usos  = (int) get_transient( $clave );
		if ( $usos >= self::LIMITE ) {
			return false;
		}
		set_transient( $clave, $usos + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}

	/* ---------------------------------------------------------------------
	 * Administración
	 * ------------------------------------------------------------------ */

	/**
	 * Submenú en Gestión TI.
	 */
	public static function menu() {
		add_submenu_page( AH_Tablero::PAGINA, __( 'Boletín de correo', 'ah-gestion-ti' ), __( 'Boletín', 'ah-gestion-ti' ), AH_Instalador::CAP_GESTION, self::PAGINA, array( __CLASS__, 'pagina' ) );
	}

	/**
	 * Conteo por estado.
	 *
	 * @return array<string,int>
	 */
	public static function conteos() {
		global $wpdb;
		$tabla   = self::tabla();
		$conteos = array_fill_keys( array_keys( self::estados() ), 0 );
		foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT estado, COUNT(*) AS n FROM {$tabla} WHERE 1 = %d GROUP BY estado", 1 ) ) as $fila ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla interno.
			$conteos[ $fila->estado ] = (int) $fila->n;
		}
		return $conteos;
	}

	/**
	 * Suscriptores filtrados.
	 *
	 * @param string $estado Estado o cadena vacía.
	 * @param int    $limite Máximo de filas (0 = todas).
	 * @return array<int,object>
	 */
	private static function suscriptores( $estado = '', $limite = 0 ) {
		global $wpdb;
		$tabla = self::tabla();
		$sql   = "SELECT * FROM {$tabla} WHERE " . ( $estado ? 'estado = %s' : '1 = %d' ) . ' ORDER BY creado DESC';
		$args  = array( $estado ? $estado : 1 );
		if ( $limite > 0 ) {
			$sql   .= ' LIMIT %d';
			$args[] = $limite;
		}
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- La consulta se arma solo con fragmentos fijos y se prepara aquí.
	}

	/**
	 * Estado del filtro desde la URL.
	 *
	 * @param string $origen get|post.
	 * @return string
	 */
	private static function estado_filtro( $origen = 'get' ) {
		// phpcs:disable WordPress.Security.NonceVerification -- Solo filtra la vista; las acciones verifican su nonce.
		if ( 'post' === $origen ) {
			$valor = isset( $_POST['estado'] ) ? sanitize_key( wp_unslash( $_POST['estado'] ) ) : '';
		} else {
			$valor = isset( $_GET['estado'] ) ? sanitize_key( wp_unslash( $_GET['estado'] ) ) : '';
		}
		// phpcs:enable
		return array_key_exists( $valor, self::estados() ) ? $valor : '';
	}

	/**
	 * Pantalla de administración.
	 */
	public static function pagina() {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver esta página.', 'ah-gestion-ti' ) );
		}
		$estado   = self::estado_filtro();
		$conteos  = self::conteos();
		$total    = array_sum( $conteos );
		$filas    = self::suscriptores( $estado, 500 );
		$url      = admin_url( 'admin.php?page=' . self::PAGINA );
		$fallidos = count(
			array_filter(
				self::suscriptores( 'pendiente' ),
				static function ( $fila ) {
					return ! $fila->correo_enviado;
				}
			)
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo un código de mensaje fijo.
		$hecho = isset( $_GET['hecho'] ) ? sanitize_key( wp_unslash( $_GET['hecho'] ) ) : '';
		?>
		<div class="wrap ah-admin">
			<h1><?php esc_html_e( 'Boletín de correo', 'ah-gestion-ti' ); ?></h1>

			<?php if ( 'eliminado' === $hecho ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Suscriptor eliminado definitivamente.', 'ah-gestion-ti' ); ?></p></div>
			<?php elseif ( 'reenviado' === $hecho ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'WordPress aceptó el correo de confirmación para su envío.', 'ah-gestion-ti' ); ?></p></div>
			<?php elseif ( 'no_reenviado' === $hecho ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'El servidor no pudo enviar el correo. Configure un servicio SMTP.', 'ah-gestion-ti' ); ?></p></div>
			<?php endif; ?>

			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Cómo funciona:', 'ah-gestion-ti' ); ?></strong>
					<?php esc_html_e( 'los visitantes se suscriben desde el pie de página o con el shortcode [ah_boletin]. Reciben un correo con un enlace de confirmación (doble opt-in) y cada correo incluye un enlace de baja. Solo los suscriptores confirmados deben recibir campañas.', 'ah-gestion-ti' ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Envío de campañas:', 'ah-gestion-ti' ); ?></strong>
					<?php esc_html_e( 'este módulo administra la lista, pero no envía boletines masivos. Para enviarlos, exporte el CSV de confirmados e impórtelo en un servicio de correo (Brevo, Mailchimp, MailerLite…). Para que los correos de confirmación lleguen, configure SMTP en el hosting o con un plugin SMTP.', 'ah-gestion-ti' ); ?>
				</p>
				<?php if ( $fallidos ) : ?>
					<p style="color:#b32d2e">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: número de suscriptores pendientes. */
								_n( 'Hay %d suscriptor pendiente cuyo correo de confirmación no se pudo enviar: el servidor no tiene un servicio de correo funcionando. Configure SMTP y use "Reenviar confirmación".', 'Hay %d suscriptores pendientes cuyo correo de confirmación no se pudo enviar: el servidor no tiene un servicio de correo funcionando. Configure SMTP y use "Reenviar confirmación".', $fallidos, 'ah-gestion-ti' ),
								$fallidos
							)
						);
						?>
					</p>
				<?php endif; ?>
			</div>

			<ul class="subsubsub">
				<li><a href="<?php echo esc_url( $url ); ?>" <?php echo '' === $estado ? 'class="current"' : ''; ?>><?php esc_html_e( 'Todos', 'ah-gestion-ti' ); ?> <span class="count">(<?php echo esc_html( (string) $total ); ?>)</span></a></li>
				<?php foreach ( self::estados() as $clave => $nombre ) : ?>
					<li>| <a href="<?php echo esc_url( add_query_arg( 'estado', $clave, $url ) ); ?>" <?php echo $estado === $clave ? 'class="current"' : ''; ?>><?php echo esc_html( $nombre ); ?> <span class="count">(<?php echo esc_html( (string) $conteos[ $clave ] ); ?>)</span></a></li>
				<?php endforeach; ?>
			</ul>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="float:right;margin:.5em 0">
				<input type="hidden" name="action" value="ah_boletin_csv">
				<input type="hidden" name="estado" value="<?php echo esc_attr( $estado ? $estado : 'confirmado' ); ?>">
				<?php wp_nonce_field( 'ah_boletin_csv' ); ?>
				<button class="button button-primary" <?php disabled( 0 === ( $estado ? $conteos[ $estado ] : $conteos['confirmado'] ) ); ?>>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: estado exportado. */
							__( 'Exportar CSV (%s)', 'ah-gestion-ti' ),
							self::estados()[ $estado ? $estado : 'confirmado' ]
						)
					);
					?>
				</button>
			</form>

			<table class="widefat striped" style="clear:both">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Correo', 'ah-gestion-ti' ); ?></th>
						<th><?php esc_html_e( 'Estado', 'ah-gestion-ti' ); ?></th>
						<th><?php esc_html_e( 'Origen', 'ah-gestion-ti' ); ?></th>
						<th><?php esc_html_e( 'Alta (UTC)', 'ah-gestion-ti' ); ?></th>
						<th><?php esc_html_e( 'Confirmación (UTC)', 'ah-gestion-ti' ); ?></th>
						<th><?php esc_html_e( 'Correo de confirmación', 'ah-gestion-ti' ); ?></th>
						<th><?php esc_html_e( 'Acciones', 'ah-gestion-ti' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $filas ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'Aún no hay suscriptores.', 'ah-gestion-ti' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $filas as $fila ) : ?>
					<tr>
						<td><?php echo esc_html( $fila->correo ); ?></td>
						<td><?php echo esc_html( self::estados()[ $fila->estado ] ?? $fila->estado ); ?></td>
						<td><?php echo esc_html( $fila->origen ); ?></td>
						<td><?php echo esc_html( $fila->creado ); ?></td>
						<td><?php echo esc_html( $fila->confirmado ? $fila->confirmado : '—' ); ?></td>
						<td><?php echo $fila->correo_enviado ? esc_html__( 'Aceptado por el servidor', 'ah-gestion-ti' ) : '<span style="color:#b32d2e">' . esc_html__( 'No se pudo enviar', 'ah-gestion-ti' ) . '</span>'; ?></td>
						<td>
							<?php if ( 'pendiente' === $fila->estado ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<input type="hidden" name="action" value="ah_boletin_reenviar">
									<input type="hidden" name="id" value="<?php echo esc_attr( (string) $fila->id ); ?>">
									<?php wp_nonce_field( 'ah_boletin_reenviar_' . $fila->id ); ?>
									<button class="button button-small"><?php esc_html_e( 'Reenviar confirmación', 'ah-gestion-ti' ); ?></button>
								</form>
							<?php endif; ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( '¿Eliminar definitivamente este correo de la lista? (derecho de cancelación)', 'ah-gestion-ti' ) ); ?>');">
								<input type="hidden" name="action" value="ah_boletin_eliminar">
								<input type="hidden" name="id" value="<?php echo esc_attr( (string) $fila->id ); ?>">
								<?php wp_nonce_field( 'ah_boletin_eliminar_' . $fila->id ); ?>
								<button class="button button-small button-link-delete"><?php esc_html_e( 'Eliminar', 'ah-gestion-ti' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Descarga CSV de suscriptores (por defecto, solo confirmados).
	 */
	public static function exportar_csv() {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'No tienes permiso.', 'ah-gestion-ti' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ah_boletin_csv' );
		$estado = self::estado_filtro( 'post' );
		$estado = $estado ? $estado : 'confirmado';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=suscriptores-' . $estado . '-' . gmdate( 'Y-m-d' ) . '.csv' );

		$salida = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Flujo de salida de la descarga.
		fwrite( $salida, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM para que Excel lea UTF-8.
		fputcsv( $salida, array( 'correo', 'estado', 'origen', 'alta_utc', 'confirmacion_utc', 'consentimiento_utc' ) );
		foreach ( self::suscriptores( $estado ) as $fila ) {
			fputcsv(
				$salida,
				array_map(
					array( 'AH_Calculos', 'csv_seguro' ),
					array( $fila->correo, $fila->estado, $fila->origen, $fila->creado, (string) $fila->confirmado, (string) $fila->consentimiento )
				)
			);
		}
		fclose( $salida ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Flujo de salida de la descarga.
		exit;
	}

	/**
	 * ID del suscriptor enviado en una acción de administración, con su nonce verificado.
	 *
	 * @param string $accion Prefijo del nonce.
	 * @return int
	 */
	private static function id_accion( $accion ) {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'No tienes permiso.', 'ah-gestion-ti' ), '', array( 'response' => 403 ) );
		}
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Se verifica en la línea siguiente con el ID.
		check_admin_referer( $accion . '_' . $id );
		return $id;
	}

	/**
	 * Elimina definitivamente un suscriptor (derecho de cancelación ARCO).
	 */
	public static function eliminar() {
		global $wpdb;
		$id = self::id_accion( 'ah_boletin_eliminar' );
		$wpdb->delete( self::tabla(), array( 'id' => $id ) );
		wp_safe_redirect( add_query_arg( 'hecho', 'eliminado', admin_url( 'admin.php?page=' . self::PAGINA ) ) );
		exit;
	}

	/**
	 * Reenvía el correo de confirmación a un suscriptor pendiente.
	 */
	public static function reenviar() {
		global $wpdb;
		$id    = self::id_accion( 'ah_boletin_reenviar' );
		$tabla = self::tabla();
		$fila  = $wpdb->get_row( $wpdb->prepare( "SELECT id, correo, token, estado FROM {$tabla} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Nombre de tabla interno.
		$ok    = $fila && 'pendiente' === $fila->estado && self::enviar_confirmacion( (int) $fila->id, $fila->correo, $fila->token );
		wp_safe_redirect( add_query_arg( 'hecho', $ok ? 'reenviado' : 'no_reenviado', admin_url( 'admin.php?page=' . self::PAGINA ) ) );
		exit;
	}
}
