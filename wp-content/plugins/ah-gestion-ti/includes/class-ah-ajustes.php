<?php
/**
 * Ajustes administrables: contacto, redes sociales, WhatsApp, videoconferencia,
 * métodos de pago visibles, Google Analytics 4 y Search Console.
 *
 * Nada se escribe en el código: todo se captura desde el administrador.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Página de ajustes con la Settings API de WordPress.
 */
final class AH_Ajustes {

	/** Nombre de la opción en la base de datos. */
	const OPCION = 'ah_gti_ajustes';

	/** Slug de la página de ajustes. */
	const PAGINA = 'ah-gti-ajustes';

	/**
	 * Métodos de pago que se pueden mostrar en el pie de página (solo informativo).
	 *
	 * @return array<string,string>
	 */
	public static function metodos_pago() {
		return array(
			'visa'          => 'Visa',
			'mastercard'    => 'Mastercard',
			'amex'          => 'American Express',
			'transferencia' => __( 'Transferencia / SPEI', 'ah-gestion-ti' ),
			'oxxo'          => 'OXXO',
			'contraentrega' => __( 'Pago contra entrega', 'ah-gestion-ti' ),
			'paypal'        => 'PayPal',
			'mercadopago'   => 'Mercado Pago',
		);
	}

	/**
	 * Definición de campos agrupados por sección.
	 *
	 * @return array<string,array{titulo:string,descripcion:string,campos:array<string,array<string,mixed>>}>
	 */
	public static function secciones() {
		return array(
			'contacto'     => array(
				'titulo'      => __( 'Información comercial y de contacto', 'ah-gestion-ti' ),
				'descripcion' => __( 'Se muestra en el pie de página, la página de contacto y los datos estructurados.', 'ah-gestion-ti' ),
				'campos'      => array(
					'telefono'  => array(
						'etiqueta' => __( 'Teléfono', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
					'correo'    => array(
						'etiqueta' => __( 'Correo de atención', 'ah-gestion-ti' ),
						'tipo'     => 'email',
					),
					'direccion' => array(
						'etiqueta' => __( 'Dirección', 'ah-gestion-ti' ),
						'tipo'     => 'textarea',
					),
					'horario'   => array(
						'etiqueta' => __( 'Horario de atención', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
				),
			),
			'redes'        => array(
				'titulo'      => __( 'Redes sociales y WhatsApp Business', 'ah-gestion-ti' ),
				'descripcion' => __( 'Captura solo cuentas oficiales reales. Si un campo queda vacío, su ícono no se muestra.', 'ah-gestion-ti' ),
				'campos'      => array(
					'facebook'         => array(
						'etiqueta' => 'Facebook (URL)',
						'tipo'     => 'url',
					),
					'instagram'        => array(
						'etiqueta' => 'Instagram (URL)',
						'tipo'     => 'url',
					),
					'tiktok'           => array(
						'etiqueta' => 'TikTok (URL)',
						'tipo'     => 'url',
					),
					'whatsapp'         => array(
						'etiqueta' => __( 'WhatsApp Business (número con lada, ej. 52 771 000 0000)', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
					'whatsapp_mensaje' => array(
						'etiqueta' => __( 'Mensaje predeterminado de WhatsApp', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
				),
			),
			'videollamada' => array(
				'titulo'      => __( 'Conferencias web / atención por videollamada', 'ah-gestion-ti' ),
				'descripcion' => __( 'Enlace a una agenda o sala (Google Meet, Zoom, Microsoft Teams, Calendly). Se muestra en Contacto y Mesa de ayuda.', 'ah-gestion-ti' ),
				'campos'      => array(
					'videollamada_url'   => array(
						'etiqueta' => __( 'URL de la sala o agenda', 'ah-gestion-ti' ),
						'tipo'     => 'url',
					),
					'videollamada_texto' => array(
						'etiqueta' => __( 'Descripción del servicio', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
				),
			),
			'pagos'        => array(
				'titulo'      => __( 'Métodos de pago visibles en el pie de página', 'ah-gestion-ti' ),
				'descripcion' => __( 'Solo es informativo. Marca únicamente los métodos que realmente estén configurados en WooCommerce → Ajustes → Pagos.', 'ah-gestion-ti' ),
				'campos'      => array(
					'metodos_pago' => array(
						'etiqueta' => __( 'Métodos', 'ah-gestion-ti' ),
						'tipo'     => 'checkboxes',
						'opciones' => self::metodos_pago(),
					),
				),
			),
			'analitica'    => array(
				'titulo'      => __( 'Google Analytics 4 y Google Search Console', 'ah-gestion-ti' ),
				'descripcion' => __( 'La medición solo se activa cuando se captura un ID de medición válido de una propiedad real.', 'ah-gestion-ti' ),
				'campos'      => array(
					'ga4_id'            => array(
						'etiqueta' => __( 'ID de medición GA4 (G-XXXXXXXXXX)', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
					'ga4_excluir_admin' => array(
						'etiqueta' => __( 'No medir a usuarios administradores', 'ah-gestion-ti' ),
						'tipo'     => 'checkbox',
					),
					'ga4_debug'         => array(
						'etiqueta' => __( 'Modo depuración (DebugView de GA4)', 'ah-gestion-ti' ),
						'tipo'     => 'checkbox',
					),
					'gsc_verificacion'  => array(
						'etiqueta' => __( 'Código de verificación de Search Console (solo el valor content)', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
				),
			),
			'cuentas'      => array(
				'titulo'      => __( 'Cuentas de clientes', 'ah-gestion-ti' ),
				'descripcion' => __( 'Con la verificación activa, quien crea una cuenta recibe un correo con un enlace y no puede iniciar sesión hasta confirmarlo. Requiere que el sitio envíe correos (SMTP en el hosting). Las cuentas creadas antes de activarla no se ven afectadas.', 'ah-gestion-ti' ),
				'campos'      => array(
					'verificar_correo' => array(
						'etiqueta' => __( 'Exigir confirmación del correo al crear una cuenta', 'ah-gestion-ti' ),
						'tipo'     => 'checkbox',
					),
				),
			),
			'demo'         => array(
				'titulo'      => __( 'Cuenta de prueba en la pantalla de acceso', 'ah-gestion-ti' ),
				'descripcion' => __( 'Muestra a los visitantes un correo y una contraseña de una cuenta de CLIENTE para revisar el sitio (por ejemplo, durante la evaluación). Solo se aceptan cuentas con rol de cliente: nunca una cuenta de administrador o del personal. Desactívela cuando la tienda opere con clientes reales.', 'ah-gestion-ti' ),
				'campos'      => array(
					'demo_activa' => array(
						'etiqueta' => __( 'Mostrar la cuenta de prueba', 'ah-gestion-ti' ),
						'tipo'     => 'checkbox',
					),
					'demo_correo' => array(
						'etiqueta' => __( 'Correo de la cuenta de prueba', 'ah-gestion-ti' ),
						'tipo'     => 'email',
					),
					'demo_clave'  => array(
						'etiqueta' => __( 'Contraseña de la cuenta de prueba', 'ah-gestion-ti' ),
						'tipo'     => 'text',
					),
				),
			),
		);
	}

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'admin_init', array( __CLASS__, 'registrar' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
	}

	/**
	 * Submenú dentro de "Gestión TI".
	 */
	public static function menu() {
		add_submenu_page(
			AH_Tablero::PAGINA,
			__( 'Ajustes de contacto y analítica', 'ah-gestion-ti' ),
			__( 'Ajustes', 'ah-gestion-ti' ),
			'manage_options',
			self::PAGINA,
			array( __CLASS__, 'pagina' )
		);
	}

	/**
	 * Registra la opción y sus campos.
	 */
	public static function registrar() {
		register_setting(
			self::PAGINA,
			self::OPCION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitizar' ),
				'default'           => array(),
			)
		);

		foreach ( self::secciones() as $id => $seccion ) {
			add_settings_section(
				'ah_' . $id,
				$seccion['titulo'],
				static function () use ( $seccion ) {
					echo '<p>' . esc_html( $seccion['descripcion'] ) . '</p>';
				},
				self::PAGINA
			);
			foreach ( $seccion['campos'] as $clave => $campo ) {
				add_settings_field(
					$clave,
					esc_html( $campo['etiqueta'] ),
					array( __CLASS__, 'campo' ),
					self::PAGINA,
					'ah_' . $id,
					array_merge(
						$campo,
						array(
							'clave'     => $clave,
							'label_for' => 'ah_' . $clave,
						)
					)
				);
			}
		}
	}

	/**
	 * Dibuja un campo.
	 *
	 * @param array<string,mixed> $args Definición del campo.
	 */
	public static function campo( $args ) {
		$valor  = ah_gti_opcion( $args['clave'], '' );
		$nombre = self::OPCION . '[' . $args['clave'] . ']';
		$id     = 'ah_' . $args['clave'];

		switch ( $args['tipo'] ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="%2$s" rows="3" class="large-text">%3$s</textarea>', esc_attr( $id ), esc_attr( $nombre ), esc_textarea( $valor ) );
				break;
			case 'checkbox':
				printf( '<input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s>', esc_attr( $id ), esc_attr( $nombre ), checked( '1', $valor, false ) );
				break;
			case 'checkboxes':
				$valores = is_array( $valor ) ? $valor : array();
				foreach ( $args['opciones'] as $opcion => $etiqueta ) {
					printf(
						'<label style="display:inline-block;margin-right:16px"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s> %4$s</label>',
						esc_attr( $nombre ),
						esc_attr( $opcion ),
						checked( in_array( $opcion, $valores, true ), true, false ),
						esc_html( $etiqueta )
					);
				}
				break;
			default:
				printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text">', esc_attr( $args['tipo'] ), esc_attr( $id ), esc_attr( $nombre ), esc_attr( $valor ) );
		}

		if ( 'ga4_id' === $args['clave'] && $valor && ! AH_Calculos::ga4_id_valido( $valor ) ) {
			echo '<p class="description" style="color:#b32d2e">' . esc_html__( 'El formato no es válido; la medición permanece desactivada.', 'ah-gestion-ti' ) . '</p>';
		}
	}

	/**
	 * Sanitiza todos los valores antes de guardarlos.
	 *
	 * @param mixed $entrada Datos enviados.
	 * @return array<string,mixed>
	 */
	public static function sanitizar( $entrada ) {
		$entrada = is_array( $entrada ) ? $entrada : array();
		$limpio  = array();

		foreach ( self::secciones() as $seccion ) {
			foreach ( $seccion['campos'] as $clave => $campo ) {
				$valor = $entrada[ $clave ] ?? '';
				switch ( $campo['tipo'] ) {
					case 'url':
						$limpio[ $clave ] = esc_url_raw( trim( (string) $valor ), array( 'https', 'http' ) );
						break;
					case 'email':
						$limpio[ $clave ] = sanitize_email( (string) $valor );
						break;
					case 'textarea':
						$limpio[ $clave ] = sanitize_textarea_field( (string) $valor );
						break;
					case 'checkbox':
						$limpio[ $clave ] = $valor ? '1' : '';
						break;
					case 'checkboxes':
						$valores          = is_array( $valor ) ? array_map( 'sanitize_key', $valor ) : array();
						$limpio[ $clave ] = array_values( array_intersect( $valores, array_keys( $campo['opciones'] ) ) );
						break;
					default:
						$limpio[ $clave ] = sanitize_text_field( (string) $valor );
				}
			}
		}

		$limpio['ga4_id'] = strtoupper( $limpio['ga4_id'] );
		if ( $limpio['ga4_id'] && ! AH_Calculos::ga4_id_valido( $limpio['ga4_id'] ) ) {
			add_settings_error( self::OPCION, 'ga4', __( 'El ID de medición de GA4 no tiene el formato G-XXXXXXXXXX. La medición no se activará.', 'ah-gestion-ti' ) );
		}
		// Si se pegó la etiqueta completa de Search Console, conserva solo el valor "content".
		if ( preg_match( '/content=["\']([^"\']+)["\']/', $entrada['gsc_verificacion'] ?? '', $coincide ) ) {
			$limpio['gsc_verificacion'] = sanitize_text_field( $coincide[1] );
		}
		// La cuenta de prueba debe existir y ser solo de cliente: nunca se publican credenciales del personal.
		if ( $limpio['demo_correo'] ) {
			$demo = get_user_by( 'email', $limpio['demo_correo'] );
			if ( ! $demo ) {
				add_settings_error( self::OPCION, 'demo', __( 'No existe un usuario con el correo de la cuenta de prueba.', 'ah-gestion-ti' ) );
				$limpio['demo_activa'] = '';
			} elseif ( array_diff( (array) $demo->roles, array( 'customer', 'subscriber' ) ) || user_can( $demo, 'edit_posts' ) ) {
				add_settings_error( self::OPCION, 'demo', __( 'Por seguridad, la cuenta de prueba solo puede ser de un cliente. Se borraron los datos capturados.', 'ah-gestion-ti' ) );
				$limpio['demo_activa'] = '';
				$limpio['demo_correo'] = '';
				$limpio['demo_clave']  = '';
			}
		}
		if ( $limpio['demo_activa'] && ( ! $limpio['demo_correo'] || ! $limpio['demo_clave'] ) ) {
			$limpio['demo_activa'] = '';
		}
		if ( $limpio['whatsapp'] && '' === AH_Calculos::whatsapp_normalizado( $limpio['whatsapp'] ) ) {
			add_settings_error( self::OPCION, 'whatsapp', __( 'El número de WhatsApp debe tener entre 10 y 15 dígitos con lada internacional.', 'ah-gestion-ti' ) );
		}
		return $limpio;
	}

	/**
	 * Página de ajustes.
	 */
	public static function pagina() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Ajustes de Artesanías Huejutla', 'ah-gestion-ti' ) . '</h1>';
		settings_errors( self::OPCION );
		echo '<form method="post" action="options.php">';
		settings_fields( self::PAGINA );
		do_settings_sections( self::PAGINA );
		submit_button();
		echo '</form></div>';
	}
}
