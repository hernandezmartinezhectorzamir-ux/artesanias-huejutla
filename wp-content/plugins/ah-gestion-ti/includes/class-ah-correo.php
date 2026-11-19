<?php
/**
 * Envío de correo: SMTP configurable desde wp-config.php y bandeja local de pruebas.
 *
 * - Producción: define AH_SMTP_HOST, AH_SMTP_PORT, AH_SMTP_USER, AH_SMTP_PASS (y opcionalmente
 *   AH_SMTP_SECURE, AH_SMTP_FROM, AH_SMTP_FROM_NAME) en wp-config.php. Las credenciales nunca
 *   se guardan en la base de datos ni en el repositorio.
 * - Entorno local: con define( 'AH_CORREO_LOCAL', true ); los correos NO se envían; se guardan
 *   en Gestión TI → Correos (modo local) para poder probar los flujos sin un servidor de correo.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Configuración del correo saliente.
 */
final class AH_Correo {

	/** Opción con los correos capturados en modo local. */
	const OPCION = 'ah_gti_correos_locales';

	/** Correos que se conservan en la bandeja local. */
	const MAXIMO = 40;

	/** Página de administración. */
	const PAGINA = 'ah-correos';

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		if ( self::modo_local() ) {
			add_filter( 'pre_wp_mail', array( __CLASS__, 'capturar' ), 10, 2 );
		} elseif ( self::smtp_configurado() ) {
			add_action( 'phpmailer_init', array( __CLASS__, 'configurar_smtp' ) );
		}
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 40 );
		add_action( 'admin_post_ah_correos_vaciar', array( __CLASS__, 'vaciar' ) );
	}

	/**
	 * ¿Está activa la bandeja local (sin envío real)?
	 *
	 * @return bool
	 */
	public static function modo_local() {
		return defined( 'AH_CORREO_LOCAL' ) && AH_CORREO_LOCAL;
	}

	/**
	 * ¿Hay datos SMTP en wp-config.php?
	 *
	 * @return bool
	 */
	public static function smtp_configurado() {
		return defined( 'AH_SMTP_HOST' ) && AH_SMTP_HOST && defined( 'AH_SMTP_USER' ) && defined( 'AH_SMTP_PASS' );
	}

	/**
	 * Estado legible para el tablero y otras pantallas.
	 *
	 * @return string local|smtp|servidor
	 */
	public static function estado() {
		if ( self::modo_local() ) {
			return 'local';
		}
		return self::smtp_configurado() ? 'smtp' : 'servidor';
	}

	/**
	 * Configura PHPMailer con las constantes de wp-config.php.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $mailer Instancia de PHPMailer.
	 */
	public static function configurar_smtp( $mailer ) {
		$mailer->isSMTP();
		$mailer->Host       = AH_SMTP_HOST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Propiedad de PHPMailer.
		$mailer->Port       = defined( 'AH_SMTP_PORT' ) ? (int) AH_SMTP_PORT : 587; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->SMTPAuth   = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->Username   = AH_SMTP_USER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->Password   = AH_SMTP_PASS; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->SMTPSecure = defined( 'AH_SMTP_SECURE' ) ? AH_SMTP_SECURE : 'tls'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( defined( 'AH_SMTP_FROM' ) && is_email( AH_SMTP_FROM ) ) {
			$mailer->setFrom( AH_SMTP_FROM, defined( 'AH_SMTP_FROM_NAME' ) ? AH_SMTP_FROM_NAME : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), false );
		}
	}

	/**
	 * Modo local: guarda el correo en la bandeja y lo da por "entregado" sin enviarlo.
	 *
	 * @param null|bool $resultado Resultado previo del filtro.
	 * @param array     $atts      Datos del correo (to, subject, message, headers, attachments).
	 * @return bool
	 */
	public static function capturar( $resultado, $atts ) {
		if ( null !== $resultado ) {
			return $resultado;
		}
		$bandeja = (array) get_option( self::OPCION, array() );
		array_unshift(
			$bandeja,
			array(
				't'       => time(),
				'para'    => implode( ', ', (array) $atts['to'] ),
				'asunto'  => (string) $atts['subject'],
				'mensaje' => (string) $atts['message'],
				'html'    => self::es_html( $atts ),
			)
		);
		update_option( self::OPCION, array_slice( $bandeja, 0, self::MAXIMO ), false );
		return true;
	}

	/**
	 * ¿El correo es HTML?
	 *
	 * @param array $atts Datos del correo.
	 * @return bool
	 */
	private static function es_html( $atts ) {
		$cabeceras = is_array( $atts['headers'] ) ? implode( "\n", $atts['headers'] ) : (string) $atts['headers'];
		$tipo      = apply_filters( 'wp_mail_content_type', 'text/plain' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Filtro del núcleo.
		return false !== stripos( $cabeceras, 'text/html' ) || 'text/html' === $tipo || false !== stripos( (string) $atts['message'], '<html' );
	}

	/**
	 * Submenú (solo en modo local).
	 */
	public static function menu() {
		if ( self::modo_local() ) {
			add_submenu_page( AH_Tablero::PAGINA, __( 'Correos (modo local)', 'ah-gestion-ti' ), __( 'Correos (local)', 'ah-gestion-ti' ), AH_Instalador::CAP_GESTION, self::PAGINA, array( __CLASS__, 'pagina' ) );
		}
	}

	/**
	 * Bandeja local.
	 */
	public static function pagina() {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver esta página.', 'ah-gestion-ti' ) );
		}
		$bandeja = (array) get_option( self::OPCION, array() );
		?>
		<div class="wrap ah-admin">
			<h1><?php esc_html_e( 'Correos (modo local)', 'ah-gestion-ti' ); ?></h1>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'Modo local activo (AH_CORREO_LOCAL en wp-config.php): ningún correo sale del equipo. Aquí se guardan los últimos 40 para probar registros, confirmaciones, boletín y pedidos. En el hosting quite esa constante y configure SMTP.', 'ah-gestion-ti' ); ?></p>
			</div>
			<?php if ( $bandeja ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:1em 0">
					<input type="hidden" name="action" value="ah_correos_vaciar">
					<?php wp_nonce_field( 'ah_correos_vaciar' ); ?>
					<button class="button"><?php esc_html_e( 'Vaciar bandeja', 'ah-gestion-ti' ); ?></button>
				</form>
			<?php else : ?>
				<p><?php esc_html_e( 'Todavía no se ha enviado ningún correo.', 'ah-gestion-ti' ); ?></p>
			<?php endif; ?>
			<?php foreach ( $bandeja as $i => $correo ) : ?>
				<details class="ah-correo-local" <?php echo 0 === $i ? 'open' : ''; ?> style="background:#fff;border:1px solid #dcdcde;border-radius:6px;margin:0 0 .8em;padding:.6em 1em">
					<summary style="cursor:pointer">
						<strong><?php echo esc_html( $correo['asunto'] ); ?></strong>
						— <?php echo esc_html( $correo['para'] ); ?>
						<span style="color:#646970">· <?php echo esc_html( wp_date( 'd/m/Y H:i:s', (int) $correo['t'] ) ); ?></span>
					</summary>
					<div style="margin-top:.8em;max-height:520px;overflow:auto;border-top:1px solid #f0f0f1;padding-top:.8em">
						<?php
						if ( $correo['html'] ) {
							echo wp_kses_post( $correo['mensaje'] );
						} else {
							echo '<pre style="white-space:pre-wrap">' . make_clickable( esc_html( $correo['mensaje'] ) ) . '</pre>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Texto escapado antes de convertir enlaces.
						}
						?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Vacía la bandeja local.
	 */
	public static function vaciar() {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'No tienes permiso.', 'ah-gestion-ti' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'ah_correos_vaciar' );
		delete_option( self::OPCION );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGINA ) );
		exit;
	}
}
