<?php
/**
 * Verificación del correo electrónico al crear una cuenta de cliente.
 *
 * Flujo: registro → correo con enlace único (48 h) → la persona confirma → se inicia su sesión.
 * Mientras no confirme, no puede iniciar sesión y puede pedir que se reenvíe el enlace.
 * Solo aplica a cuentas creadas con la opción activa; las cuentas existentes no se ven afectadas.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Confirmación de correo de clientes.
 */
final class AH_Verificacion {

	/** Meta: '0' pendiente, '1' verificado (sin meta = cuenta anterior, se considera verificada). */
	const META = '_ah_correo_verificado';

	/** Meta con el HMAC del token. */
	const META_HASH = '_ah_verificacion_hash';

	/** Meta con la fecha de vencimiento (timestamp). */
	const META_VENCE = '_ah_verificacion_vence';

	/** Vigencia del enlace. */
	const HORAS = 48;

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'al_registrar' ), 20 );
		add_filter( 'woocommerce_registration_auth_new_customer', array( __CLASS__, 'iniciar_sesion_al_registrar' ), 10, 2 );
		add_filter( 'woocommerce_email_enabled_customer_new_account', array( __CLASS__, 'correo_bienvenida' ) );
		add_filter( 'wp_authenticate_user', array( __CLASS__, 'bloquear_sin_verificar' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'procesar_enlaces' ), 5 );
		add_action( 'woocommerce_before_customer_login_form', array( __CLASS__, 'avisos' ), 5 );
		add_action( 'woocommerce_account_content', array( __CLASS__, 'avisos' ), 1 );
		add_filter( 'manage_users_columns', array( __CLASS__, 'columna' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'valor_columna' ), 10, 3 );
		add_filter( 'user_row_actions', array( __CLASS__, 'accion_fila' ), 10, 2 );
		add_action( 'admin_post_ah_marcar_verificado', array( __CLASS__, 'marcar_verificado' ) );
	}

	/**
	 * ¿Está activa la verificación para cuentas nuevas?
	 *
	 * @return bool
	 */
	public static function activa() {
		return '1' === ah_gti_opcion( 'verificar_correo' );
	}

	/**
	 * ¿La cuenta está pendiente de confirmar?
	 *
	 * @param int $user_id Usuario.
	 * @return bool
	 */
	public static function pendiente( $user_id ) {
		return '0' === get_user_meta( $user_id, self::META, true );
	}

	/**
	 * Al crear la cuenta: marca pendiente y envía el enlace.
	 *
	 * @param int   $user_id            Usuario.
	 */
	public static function al_registrar( $user_id ) {
		if ( ! self::activa() ) {
			return;
		}
		update_user_meta( $user_id, self::META, '0' );
		self::enviar( $user_id );
	}

	/**
	 * Si la cuenta debe confirmarse: no inicia sesión y redirige a Mi cuenta con el aviso
	 * "revisa tu correo" (patrón PRG). WooCommerce, sin sesión, mostraría en la misma petición
	 * su propio aviso "tus datos de acceso se enviaron", que aquí no aplica.
	 *
	 * @param bool $iniciar Valor original.
	 * @return bool
	 */
	public static function iniciar_sesion_al_registrar( $iniciar ) {
		if ( ! self::activa() ) {
			return $iniciar;
		}
		if ( function_exists( 'wc_clear_notices' ) ) {
			wc_clear_notices();
		}
		wp_safe_redirect( add_query_arg( 'ah_cuenta', 'enviado', wc_get_page_permalink( 'myaccount' ) ) );
		exit;
	}

	/**
	 * Con la verificación activa, el correo de bienvenida de WooCommerce sobra: el cliente
	 * recibe el de confirmación. Se conserva si WooCommerce genera la contraseña (lleva el enlace para crearla).
	 *
	 * @param bool $activo Valor original.
	 * @return bool
	 */
	public static function correo_bienvenida( $activo ) {
		return ( self::activa() && 'yes' !== get_option( 'woocommerce_registration_generate_password' ) ) ? false : $activo;
	}

	/**
	 * Genera un token nuevo y envía el correo de confirmación.
	 *
	 * @param int $user_id Usuario.
	 * @return bool Si wp_mail aceptó el correo.
	 */
	public static function enviar( $user_id ) {
		$usuario = get_userdata( $user_id );
		if ( ! $usuario ) {
			return false;
		}
		$token = wp_generate_password( 40, false );
		update_user_meta( $user_id, self::META_HASH, self::hash( $token ) );
		update_user_meta( $user_id, self::META_VENCE, time() + self::HORAS * HOUR_IN_SECONDS );

		$enlace = add_query_arg(
			array(
				'ah_verificar' => $user_id,
				't'            => $token,
			),
			wc_get_page_permalink( 'myaccount' )
		);
		$tienda = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$nombre = $usuario->first_name ? $usuario->first_name : __( 'Hola', 'ah-gestion-ti' );
		/* translators: %s: nombre de la tienda. */
		$asunto = sprintf( __( 'Confirma tu correo para activar tu cuenta en %s', 'ah-gestion-ti' ), $tienda );
		$cuerpo = '<p>' . esc_html( $usuario->first_name ? sprintf( /* translators: %s: nombre. */ __( 'Hola, %s:', 'ah-gestion-ti' ), $nombre ) : __( 'Hola:', 'ah-gestion-ti' ) ) . '</p>'
			/* translators: %s: nombre de la tienda. */
			. '<p>' . esc_html( sprintf( __( 'Gracias por crear tu cuenta en %s. Para activarla, confirma que este correo es tuyo:', 'ah-gestion-ti' ), $tienda ) ) . '</p>'
			. '<p style="margin:24px 0"><a href="' . esc_url( $enlace ) . '" style="background:#b5532f;color:#ffffff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:bold;display:inline-block">' . esc_html__( 'Confirmar mi correo', 'ah-gestion-ti' ) . '</a></p>'
			/* translators: %d: horas de vigencia. */
			. '<p>' . esc_html( sprintf( __( 'El enlace es válido durante %d horas y solo se puede usar una vez.', 'ah-gestion-ti' ), self::HORAS ) ) . '</p>'
			. '<p style="font-size:13px;color:#6e655d">' . esc_html__( 'Si el botón no funciona, copia y pega esta dirección en tu navegador:', 'ah-gestion-ti' ) . '<br>' . esc_html( $enlace ) . '</p>'
			. '<p style="font-size:13px;color:#6e655d">' . esc_html__( 'Si no creaste esta cuenta, ignora este mensaje: sin la confirmación no se podrá usar.', 'ah-gestion-ti' ) . '</p>';

		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			$mailer = WC()->mailer();
			return (bool) $mailer->send( $usuario->user_email, $asunto, $mailer->wrap_message( __( 'Confirma tu correo', 'ah-gestion-ti' ), $cuerpo ) );
		}
		return wp_mail( $usuario->user_email, $asunto, $cuerpo, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * HMAC del token (en la base de datos nunca se guarda el token en claro).
	 *
	 * @param string $token Token.
	 * @return string
	 */
	private static function hash( $token ) {
		return hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	/**
	 * Impide iniciar sesión mientras el correo no esté confirmado.
	 *
	 * @param WP_User|WP_Error $usuario Usuario autenticado hasta ahora.
	 * @return WP_User|WP_Error
	 */
	public static function bloquear_sin_verificar( $usuario ) {
		if ( ! ( $usuario instanceof WP_User ) || ! self::pendiente( $usuario->ID ) ) {
			return $usuario;
		}
		$reenviar = wp_nonce_url(
			add_query_arg( 'ah_reenviar_verificacion', $usuario->ID, wc_get_page_permalink( 'myaccount' ) ),
			'ah_reenviar_' . $usuario->ID
		);
		return new WP_Error(
			'ah_correo_sin_verificar',
			sprintf(
				/* translators: %s: enlace para reenviar el correo. */
				__( 'Tu cuenta aún no está activada: abre el enlace que te enviamos por correo para confirmarla. ¿No te llegó? <a href="%s">Reenviar el correo de confirmación</a>.', 'ah-gestion-ti' ),
				esc_url( $reenviar )
			)
		);
	}

	/**
	 * Enlaces del correo (?ah_verificar=ID&t=TOKEN) y reenvío (?ah_reenviar_verificacion=ID).
	 */
	public static function procesar_enlaces() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- El token del correo es la credencial; el reenvío verifica su nonce abajo.
		$mi_cuenta = wc_get_page_permalink( 'myaccount' );

		if ( isset( $_GET['ah_verificar'], $_GET['t'] ) ) {
			$user_id = absint( $_GET['ah_verificar'] );
			$token   = preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_GET['t'] ) ) );
			$vence   = (int) get_user_meta( $user_id, self::META_VENCE, true );
			$hash    = (string) get_user_meta( $user_id, self::META_HASH, true );

			if ( ! $user_id || ! self::pendiente( $user_id ) ) {
				$codigo = is_user_logged_in() ? 'ya' : 'ya_verificada';
			} elseif ( ! $hash || ! hash_equals( $hash, self::hash( $token ) ) ) {
				$codigo = 'invalido';
			} elseif ( ! AH_Calculos::verificacion_vigente( $vence, time() ) ) {
				$codigo = 'vencido';
			} else {
				update_user_meta( $user_id, self::META, '1' );
				delete_user_meta( $user_id, self::META_HASH );
				delete_user_meta( $user_id, self::META_VENCE );
				// La persona demostró que el correo es suyo: se inicia su sesión.
				wp_clear_auth_cookie();
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );
				$codigo = 'confirmado';
			}
			$args = array( 'ah_cuenta' => $codigo );
			if ( in_array( $codigo, array( 'invalido', 'vencido' ), true ) && $user_id && self::pendiente( $user_id ) ) {
				$args['ah_reenviar_verificacion'] = $user_id;
				$args['_wpnonce']                 = wp_create_nonce( 'ah_reenviar_' . $user_id );
				// El reenvío se ofrece como enlace; no se ejecuta solo.
				$args['ah_ofrecer'] = 1;
			}
			wp_safe_redirect( add_query_arg( $args, $mi_cuenta ) );
			exit;
		}

		if ( isset( $_GET['ah_reenviar_verificacion'] ) && empty( $_GET['ah_ofrecer'] ) ) {
			$user_id = absint( $_GET['ah_reenviar_verificacion'] );
			$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'ah_reenviar_' . $user_id ) ) {
				wp_safe_redirect( add_query_arg( 'ah_cuenta', 'invalido', $mi_cuenta ) );
				exit;
			}
			// Límite: un reenvío cada 2 minutos por cuenta. Mismo mensaje en todos los casos.
			if ( $user_id && self::pendiente( $user_id ) && ! get_transient( 'ah_reenvio_' . $user_id ) ) {
				set_transient( 'ah_reenvio_' . $user_id, 1, 2 * MINUTE_IN_SECONDS );
				self::enviar( $user_id );
			}
			wp_safe_redirect( add_query_arg( 'ah_cuenta', 'reenviado', $mi_cuenta ) );
			exit;
		}
		// phpcs:enable
	}

	/**
	 * Avisos con el diseño de la tienda según ?ah_cuenta=código (solo textos fijos).
	 */
	public static function avisos() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Solo selecciona un mensaje fijo.
		$codigo = isset( $_GET['ah_cuenta'] ) ? sanitize_key( wp_unslash( $_GET['ah_cuenta'] ) ) : '';
		$textos = array(
			'enviado'       => array( 'success', __( '¡Cuenta creada! Te enviamos un correo con un enlace para confirmar tu dirección. Ábrelo para activar tu cuenta (revisa también la carpeta de spam).', 'ah-gestion-ti' ) ),
			'reenviado'     => array( 'success', __( 'Si la cuenta está pendiente de confirmar, te enviamos un nuevo enlace. Revisa tu correo.', 'ah-gestion-ti' ) ),
			'confirmado'    => array( 'success', __( '¡Listo! Tu correo quedó confirmado y tu cuenta está activa.', 'ah-gestion-ti' ) ),
			'ya_verificada' => array( 'notice', __( 'Esta cuenta ya estaba confirmada. Inicia sesión con tu correo y contraseña.', 'ah-gestion-ti' ) ),
			'ya'            => array( 'notice', __( 'Tu cuenta ya estaba confirmada.', 'ah-gestion-ti' ) ),
			'invalido'      => array( 'error', __( 'El enlace de confirmación no es válido o ya se usó.', 'ah-gestion-ti' ) ),
			'vencido'       => array( 'error', __( 'El enlace de confirmación venció.', 'ah-gestion-ti' ) ),
		);
		if ( ! isset( $textos[ $codigo ] ) || ! function_exists( 'wc_print_notice' ) ) {
			return;
		}
		$mensaje = esc_html( $textos[ $codigo ][1] );
		if ( ! empty( $_GET['ah_ofrecer'] ) && isset( $_GET['ah_reenviar_verificacion'], $_GET['_wpnonce'] ) ) {
			$reenviar = add_query_arg(
				array(
					'ah_reenviar_verificacion' => absint( $_GET['ah_reenviar_verificacion'] ),
					'_wpnonce'                 => sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
				),
				wc_get_page_permalink( 'myaccount' )
			);
			$mensaje .= ' <a href="' . esc_url( $reenviar ) . '">' . esc_html__( 'Enviarme un enlace nuevo', 'ah-gestion-ti' ) . '</a>';
		}
		// phpcs:enable
		wc_print_notice( $mensaje, $textos[ $codigo ][0] );
	}

	/**
	 * Columna "Correo" en la lista de usuarios.
	 *
	 * @param array<string,string> $columnas Columnas.
	 * @return array<string,string>
	 */
	public static function columna( $columnas ) {
		$columnas['ah_correo'] = __( 'Correo confirmado', 'ah-gestion-ti' );
		return $columnas;
	}

	/**
	 * Valor de la columna.
	 *
	 * @param string $salida  Salida previa.
	 * @param string $columna Columna.
	 * @param int    $user_id Usuario.
	 * @return string
	 */
	public static function valor_columna( $salida, $columna, $user_id ) {
		if ( 'ah_correo' !== $columna ) {
			return $salida;
		}
		$estado = get_user_meta( $user_id, self::META, true );
		if ( '0' === $estado ) {
			return '<span style="color:#b32d2e">' . esc_html__( 'Pendiente', 'ah-gestion-ti' ) . '</span>';
		}
		return '1' === $estado ? esc_html__( 'Sí', 'ah-gestion-ti' ) : '<span style="color:#646970">' . esc_html__( 'Cuenta anterior', 'ah-gestion-ti' ) . '</span>';
	}

	/**
	 * Acción "Marcar correo como confirmado" (por ejemplo, si el cliente lo confirma por teléfono).
	 *
	 * @param array<string,string> $acciones Acciones.
	 * @param WP_User              $usuario  Usuario.
	 * @return array<string,string>
	 */
	public static function accion_fila( $acciones, $usuario ) {
		if ( self::pendiente( $usuario->ID ) && current_user_can( 'edit_user', $usuario->ID ) ) {
			$url                      = wp_nonce_url( admin_url( 'admin-post.php?action=ah_marcar_verificado&user_id=' . $usuario->ID ), 'ah_marcar_verificado_' . $usuario->ID );
			$acciones['ah_verificar'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Marcar correo como confirmado', 'ah-gestion-ti' ) . '</a>';
			$reenviar                 = wp_nonce_url( add_query_arg( 'ah_reenviar_verificacion', $usuario->ID, wc_get_page_permalink( 'myaccount' ) ), 'ah_reenviar_' . $usuario->ID );
			$acciones['ah_reenviar']  = '<a href="' . esc_url( $reenviar ) . '">' . esc_html__( 'Reenviar confirmación', 'ah-gestion-ti' ) . '</a>';
		}
		return $acciones;
	}

	/**
	 * Marca manualmente el correo como confirmado.
	 */
	public static function marcar_verificado() {
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
		check_admin_referer( 'ah_marcar_verificado_' . $user_id );
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			wp_die( esc_html__( 'No tienes permiso.', 'ah-gestion-ti' ), '', array( 'response' => 403 ) );
		}
		update_user_meta( $user_id, self::META, '1' );
		delete_user_meta( $user_id, self::META_HASH );
		delete_user_meta( $user_id, self::META_VENCE );
		wp_safe_redirect( admin_url( 'users.php' ) );
		exit;
	}
}
