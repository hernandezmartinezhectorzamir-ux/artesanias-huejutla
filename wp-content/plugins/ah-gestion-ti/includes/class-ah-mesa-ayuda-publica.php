<?php
/**
 * Mesa de ayuda – interfaz pública.
 *
 * Shortcodes:
 *  [ah_mesa_ayuda]          Crear ticket, consultar por número + correo y "Mis tickets".
 *  [ah_formulario_contacto] Formulario de contacto que se registra como solicitud de información.
 *
 * También agrega la sección "Soporte" en Mi cuenta de WooCommerce.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formularios públicos y consulta segura de tickets.
 */
final class AH_Mesa_Ayuda_Publica {

	/** Endpoint de Mi cuenta. */
	const ENDPOINT = 'soporte';

	/**
	 * Errores de validación del envío actual.
	 *
	 * @var array<string,string>
	 */
	private static $errores = array();

	/**
	 * Ticket mostrado tras una consulta válida.
	 *
	 * @var array|null
	 */
	private static $ticket = null;

	/**
	 * Mensaje de éxito.
	 *
	 * @var string
	 */
	private static $aviso = '';

	/**
	 * Formulario que originó el envío (crear|consultar|contacto|responder).
	 *
	 * @var string
	 */
	private static $formulario = '';

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_shortcode( 'ah_mesa_ayuda', array( __CLASS__, 'shortcode_mesa' ) );
		add_shortcode( 'ah_formulario_contacto', array( __CLASS__, 'shortcode_contacto' ) );
		add_action( 'template_redirect', array( __CLASS__, 'procesar' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'estilos' ) );

		add_action( 'init', array( __CLASS__, 'registrar_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'menu_cuenta' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'contenido_cuenta' ) );
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'query_vars_wc' ) );
	}

	/**
	 * Endpoint /mi-cuenta/soporte/.
	 */
	public static function registrar_endpoint() {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Informa a WooCommerce del endpoint (títulos y navegación).
	 *
	 * @param array $vars Variables.
	 * @return array
	 */
	public static function query_vars_wc( $vars ) {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Agrega "Soporte" al menú de Mi cuenta.
	 *
	 * @param array<string,string> $items Elementos.
	 * @return array<string,string>
	 */
	public static function menu_cuenta( $items ) {
		$nuevo = array();
		foreach ( $items as $clave => $etiqueta ) {
			if ( 'customer-logout' === $clave ) {
				$nuevo[ self::ENDPOINT ] = __( 'Soporte', 'ah-gestion-ti' );
			}
			$nuevo[ $clave ] = $etiqueta;
		}
		return $nuevo;
	}

	/**
	 * URL pública de la mesa de ayuda.
	 *
	 * @return string
	 */
	public static function url_pagina() {
		$id = (int) get_option( 'ah_gti_pagina_mesa' );
		return $id ? get_permalink( $id ) : home_url( '/mesa-de-ayuda/' );
	}

	/**
	 * Hoja de estilos mínima (el tema la complementa).
	 */
	public static function estilos() {
		wp_register_style( 'ah-gti-publico', AH_GTI_URL . 'assets/css/publico.css', array(), AH_GTI_VERSION );
		global $post;
		if ( is_account_page() || ( $post && ( has_shortcode( $post->post_content, 'ah_mesa_ayuda' ) || has_shortcode( $post->post_content, 'ah_formulario_contacto' ) || has_shortcode( $post->post_content, 'ah_catalogo_servicios' ) ) ) || is_post_type_archive( AH_Servicios::TIPO ) ) {
			wp_enqueue_style( 'ah-gti-publico' );
		}
	}

	/**
	 * Procesa los formularios antes de dibujar la página (patrón PRG).
	 */
	public static function procesar() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Cada acción verifica su nonce abajo.
		$metodo = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'POST' !== $metodo || empty( $_POST['ah_accion'] ) ) {
			return;
		}
		$accion = sanitize_key( wp_unslash( $_POST['ah_accion'] ) );
		// phpcs:enable

		if ( ! in_array( $accion, array( 'crear', 'consultar', 'responder', 'contacto' ), true ) ) {
			return;
		}
		self::$formulario = $accion;

		if ( ! isset( $_POST['ah_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ah_nonce'] ) ), 'ah_mesa_' . $accion ) ) {
			self::$errores['general'] = __( 'La sesión del formulario expiró. Recarga la página e intenta de nuevo.', 'ah-gestion-ti' );
			return;
		}

		// Honeypot anti-spam: un campo oculto que las personas no llenan.
		if ( ! empty( $_POST['ah_sitio_web'] ) ) {
			self::$errores['general'] = __( 'No fue posible procesar el formulario.', 'ah-gestion-ti' );
			return;
		}

		$limite = 'consultar' === $accion ? 10 : 5;
		if ( ! self::dentro_de_limite( $accion, $limite ) ) {
			self::$errores['general'] = __( 'Realizaste demasiados intentos. Espera unos minutos e intenta de nuevo.', 'ah-gestion-ti' );
			return;
		}

		switch ( $accion ) {
			case 'crear':
			case 'contacto':
				self::procesar_creacion( $accion );
				break;
			case 'consultar':
				self::procesar_consulta();
				break;
			case 'responder':
				self::procesar_respuesta();
				break;
		}
	}

	/**
	 * Limita intentos por IP (protección contra abuso y enumeración de tickets).
	 *
	 * @param string $accion Acción.
	 * @param int    $maximo Intentos por 10 minutos.
	 * @return bool
	 */
	private static function dentro_de_limite( $accion, $maximo ) {
		$ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? 'desconocida' ) );
		$clave = 'ah_lim_' . $accion . '_' . md5( $ip );
		$usos  = (int) get_transient( $clave );
		if ( $usos >= $maximo ) {
			return false;
		}
		set_transient( $clave, $usos + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Valor POST saneado.
	 *
	 * @param string $campo  Campo.
	 * @param bool   $largo  Texto multilínea.
	 * @return string
	 */
	private static function post( $campo, $largo = false ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verificado en procesar().
		if ( ! isset( $_POST[ $campo ] ) ) {
			return '';
		}
		return $largo ? sanitize_textarea_field( wp_unslash( $_POST[ $campo ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $campo ] ) );
		// phpcs:enable
	}

	/**
	 * Crea un ticket desde Mesa de ayuda o Contacto.
	 *
	 * @param string $origen crear|contacto.
	 */
	private static function procesar_creacion( $origen ) {
		$datos = array(
			'nombre'      => self::post( 'nombre' ),
			'correo'      => strtolower( sanitize_email( self::post( 'correo' ) ) ),
			'asunto'      => self::post( 'asunto' ),
			'descripcion' => self::post( 'descripcion', true ),
			'categoria'   => 'contacto' === $origen ? 'informacion' : sanitize_key( self::post( 'categoria' ) ),
			'prioridad'   => 'contacto' === $origen ? 'baja' : sanitize_key( self::post( 'prioridad' ) ),
			'pedido_id'   => absint( self::post( 'pedido' ) ),
			'canal'       => 'contacto' === $origen ? 'contacto' : 'web',
			'user_id'     => get_current_user_id(),
		);

		if ( mb_strlen( $datos['nombre'] ) < 3 ) {
			self::$errores['nombre'] = __( 'Escribe tu nombre (mínimo 3 caracteres).', 'ah-gestion-ti' );
		}
		if ( ! is_email( $datos['correo'] ) ) {
			self::$errores['correo'] = __( 'Escribe un correo electrónico válido.', 'ah-gestion-ti' );
		}
		if ( mb_strlen( $datos['asunto'] ) < 5 || mb_strlen( $datos['asunto'] ) > 200 ) {
			self::$errores['asunto'] = __( 'El asunto debe tener entre 5 y 200 caracteres.', 'ah-gestion-ti' );
		}
		if ( mb_strlen( $datos['descripcion'] ) < 20 || mb_strlen( $datos['descripcion'] ) > 5000 ) {
			self::$errores['descripcion'] = __( 'Describe tu caso con al menos 20 caracteres (máximo 5000).', 'ah-gestion-ti' );
		}
		if ( 'crear' === $origen && ! array_key_exists( $datos['categoria'], AH_Tickets::categorias() ) ) {
			self::$errores['categoria'] = __( 'Selecciona una categoría.', 'ah-gestion-ti' );
		}
		if ( 'crear' === $origen && ! in_array( $datos['prioridad'], array( 'baja', 'media', 'alta' ), true ) ) {
			self::$errores['prioridad'] = __( 'Selecciona una prioridad.', 'ah-gestion-ti' );
		}
		if ( self::$errores ) {
			return;
		}

		$ticket = AH_Tickets::crear( $datos );
		if ( is_wp_error( $ticket ) ) {
			self::$errores['general'] = $ticket->get_error_message();
			return;
		}

		// Patrón PRG: el número se guarda temporalmente y se muestra tras redirigir.
		$token = wp_generate_password( 20, false );
		set_transient( 'ah_ok_' . $token, $ticket['numero'], 15 * MINUTE_IN_SECONDS );
		// wp_get_referer() devuelve false si el formulario se envió a la misma página,
		// por eso se usa la página actual como destino principal.
		$pagina  = get_queried_object_id();
		$destino = $pagina ? get_permalink( $pagina ) : wp_get_referer();
		if ( ! $destino ) {
			$destino = self::url_pagina();
		}
		wp_safe_redirect( add_query_arg( 'ah_ok', $token, remove_query_arg( array( 'ah_ok', 'ver_ticket' ), $destino ) ) . '#ah-resultado' );
		exit;
	}

	/**
	 * Consulta un ticket por número y correo.
	 */
	private static function procesar_consulta() {
		$numero = strtoupper( self::post( 'numero' ) );
		$correo = strtolower( sanitize_email( self::post( 'correo_consulta' ) ) );
		$ticket = AH_Tickets::buscar_publico( $numero, $correo );
		if ( ! $ticket ) {
			// Mensaje genérico: no revela si el número existe.
			self::$errores['consulta'] = __( 'No encontramos un ticket con ese número y correo. Verifica los datos.', 'ah-gestion-ti' );
			return;
		}
		self::$ticket = $ticket;
	}

	/**
	 * Respuesta del cliente a su ticket.
	 */
	private static function procesar_respuesta() {
		$numero = strtoupper( self::post( 'numero' ) );
		$correo = strtolower( sanitize_email( self::post( 'correo_consulta' ) ) );
		$ticket = AH_Tickets::buscar_publico( $numero, $correo );
		if ( ! $ticket ) {
			self::$errores['consulta'] = __( 'No fue posible verificar el ticket.', 'ah-gestion-ti' );
			return;
		}
		$mensaje = self::post( 'mensaje', true );
		if ( 'cerrado' === $ticket['estado'] ) {
			self::$errores['respuesta'] = __( 'Este ticket está cerrado. Crea uno nuevo si necesitas más ayuda.', 'ah-gestion-ti' );
		} elseif ( mb_strlen( $mensaje ) < 5 ) {
			self::$errores['respuesta'] = __( 'Escribe tu mensaje (mínimo 5 caracteres).', 'ah-gestion-ti' );
		} else {
			AH_Tickets::comentar( (int) $ticket['id'], $mensaje, true, 'comentario' );
			self::$aviso = __( 'Tu mensaje se agregó al ticket.', 'ah-gestion-ti' );
		}
		self::$ticket = AH_Tickets::obtener( (int) $ticket['id'] );
	}

	/**
	 * Shortcode [ah_mesa_ayuda].
	 *
	 * @return string
	 */
	public static function shortcode_mesa() {
		ob_start();
		echo '<div class="ah-mesa" id="ah-resultado">';
		self::aviso_creacion();

		// Ticket propio desde "Mis tickets" (usuario con sesión).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo lectura; se valida la propiedad.
		$ver = isset( $_GET['ver_ticket'] ) ? absint( $_GET['ver_ticket'] ) : 0;
		if ( $ver && is_user_logged_in() ) {
			$propio = AH_Tickets::obtener( $ver );
			if ( $propio && AH_Tickets::puede_ver( $propio ) ) {
				self::$ticket = $propio;
			}
		}

		if ( self::$ticket ) {
			self::vista_ticket( self::$ticket );
		}

		echo '<div class="ah-mesa__rejilla">';
		echo '<section class="ah-tarjeta" aria-labelledby="ah-titulo-crear">';
		echo '<h2 id="ah-titulo-crear">' . esc_html__( 'Crear un ticket', 'ah-gestion-ti' ) . '</h2>';
		echo '<p class="ah-texto-suave">' . esc_html__( 'Reporta un problema o solicita información. Te daremos un número de seguimiento.', 'ah-gestion-ti' ) . '</p>';
		self::formulario_ticket( 'crear' );
		echo '</section>';

		echo '<aside class="ah-mesa__lateral">';
		echo '<section class="ah-tarjeta" aria-labelledby="ah-titulo-consultar">';
		echo '<h2 id="ah-titulo-consultar">' . esc_html__( 'Consultar un ticket', 'ah-gestion-ti' ) . '</h2>';
		self::formulario_consulta();
		echo '</section>';
		if ( is_user_logged_in() ) {
			echo '<section class="ah-tarjeta">';
			echo '<h2>' . esc_html__( 'Mis tickets', 'ah-gestion-ti' ) . '</h2>';
			self::lista_usuario( self::url_pagina() );
			echo '</section>';
		}
		self::bloque_canales();
		echo '</aside></div></div>';
		return (string) ob_get_clean();
	}

	/**
	 * Shortcode [ah_formulario_contacto].
	 *
	 * @return string
	 */
	public static function shortcode_contacto() {
		ob_start();
		echo '<div class="ah-mesa ah-contacto" id="ah-resultado">';
		self::aviso_creacion( true );
		self::formulario_ticket( 'contacto' );
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * Mensaje tras crear un ticket (lee el token del patrón PRG).
	 *
	 * @param bool $contacto Viene del formulario de contacto.
	 */
	private static function aviso_creacion( $contacto = false ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Token de un solo uso, solo lectura.
		$token  = isset( $_GET['ah_ok'] ) ? sanitize_key( wp_unslash( $_GET['ah_ok'] ) ) : '';
		$numero = $token ? get_transient( 'ah_ok_' . $token ) : false;
		if ( ! $numero ) {
			return;
		}
		echo '<div class="ah-aviso ah-aviso--exito" role="status"><strong>';
		echo $contacto ? esc_html__( '¡Mensaje enviado!', 'ah-gestion-ti' ) : esc_html__( '¡Ticket registrado!', 'ah-gestion-ti' );
		echo '</strong> ';
		printf(
			/* translators: %s: número de seguimiento. */
			esc_html__( 'Tu número de seguimiento es %s. Guárdalo junto con tu correo para consultar el avance.', 'ah-gestion-ti' ),
			'<span class="ah-numero">' . esc_html( $numero ) . '</span>'
		);
		echo '</div>';
	}

	/**
	 * Formulario de creación (mesa de ayuda o contacto).
	 *
	 * @param string $tipo crear|contacto.
	 */
	private static function formulario_ticket( $tipo ) {
		$usuario  = wp_get_current_user();
		$es_mismo = self::$formulario === $tipo;
		// Precarga desde "Ayuda con mi compra" (?categoria=pedidos&pedido=123); solo valores permitidos.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Precarga de solo lectura.
		$precarga = array();
		if ( 'crear' === $tipo && isset( $_GET['categoria'] ) && array_key_exists( sanitize_key( $_GET['categoria'] ), AH_Tickets::categorias() ) ) {
			$precarga['categoria'] = sanitize_key( $_GET['categoria'] );
		}
		if ( 'crear' === $tipo && isset( $_GET['pedido'] ) && absint( $_GET['pedido'] ) ) {
			$precarga['pedido'] = (string) absint( $_GET['pedido'] );
			/* translators: %s: número de pedido. */
			$precarga['asunto'] = sprintf( __( 'Ayuda con mi compra #%s', 'ah-gestion-ti' ), $precarga['pedido'] );
		}
		// phpcs:enable
		$valor = static function ( $campo, $defecto = '' ) use ( $es_mismo, $precarga ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Solo repinta lo enviado tras validar el nonce.
			if ( $es_mismo && isset( $_POST[ $campo ] ) ) {
				return sanitize_textarea_field( wp_unslash( $_POST[ $campo ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}
			return $precarga[ $campo ] ?? $defecto;
		};

		if ( $es_mismo && isset( self::$errores['general'] ) ) {
			echo '<div class="ah-aviso ah-aviso--error" role="alert">' . esc_html( self::$errores['general'] ) . '</div>';
		}
		?>
		<form class="ah-form" method="post" novalidate>
			<input type="hidden" name="ah_accion" value="<?php echo esc_attr( $tipo ); ?>">
			<?php wp_nonce_field( 'ah_mesa_' . $tipo, 'ah_nonce' ); ?>
			<p class="ah-oculto" aria-hidden="true"><label>Sitio web <input type="text" name="ah_sitio_web" tabindex="-1" autocomplete="off"></label></p>
			<div class="ah-form__fila">
				<?php
				self::campo( $tipo, 'nombre', __( 'Nombre completo', 'ah-gestion-ti' ), 'text', $valor( 'nombre', $usuario->exists() ? $usuario->display_name : '' ), 'name' );
				self::campo( $tipo, 'correo', __( 'Correo electrónico', 'ah-gestion-ti' ), 'email', $valor( 'correo', $usuario->exists() ? $usuario->user_email : '' ), 'email' );
				?>
			</div>
			<?php if ( 'crear' === $tipo ) : ?>
				<div class="ah-form__fila">
					<?php
					self::select( $tipo, 'categoria', __( 'Categoría', 'ah-gestion-ti' ), AH_Tickets::categorias(), $valor( 'categoria' ) );
					self::select(
						$tipo,
						'prioridad',
						__( 'Prioridad', 'ah-gestion-ti' ),
						array(
							'baja'  => __( 'Baja – consulta general', 'ah-gestion-ti' ),
							'media' => __( 'Media – afecta mi compra', 'ah-gestion-ti' ),
							'alta'  => __( 'Alta – no puedo comprar o recibir mi pedido', 'ah-gestion-ti' ),
						),
						$valor( 'prioridad', 'media' )
					);
					?>
				</div>
			<?php endif; ?>
			<?php self::campo( $tipo, 'asunto', __( 'Asunto', 'ah-gestion-ti' ), 'text', $valor( 'asunto' ) ); ?>
			<?php if ( 'crear' === $tipo ) : ?>
				<?php self::campo( $tipo, 'pedido', __( 'Número de pedido (opcional)', 'ah-gestion-ti' ), 'number', $valor( 'pedido' ), '', false ); ?>
			<?php endif; ?>
			<div class="ah-campo<?php echo isset( self::$errores['descripcion'] ) && $es_mismo ? ' ah-campo--error' : ''; ?>">
				<label for="ah-<?php echo esc_attr( $tipo ); ?>-descripcion"><?php echo 'crear' === $tipo ? esc_html__( 'Describe tu problema o solicitud', 'ah-gestion-ti' ) : esc_html__( 'Mensaje', 'ah-gestion-ti' ); ?> <span class="ah-req" aria-hidden="true">*</span></label>
				<textarea id="ah-<?php echo esc_attr( $tipo ); ?>-descripcion" name="descripcion" rows="6" required minlength="20" maxlength="5000"><?php echo esc_textarea( $valor( 'descripcion' ) ); ?></textarea>
				<?php self::error( $tipo, 'descripcion' ); ?>
			</div>
			<button type="submit" class="ah-boton"><?php echo 'crear' === $tipo ? esc_html__( 'Enviar ticket', 'ah-gestion-ti' ) : esc_html__( 'Enviar mensaje', 'ah-gestion-ti' ); ?></button>
			<p class="ah-texto-suave ah-texto-pequeno">
				<?php
				printf(
					/* translators: %s: enlace al aviso de privacidad. */
					esc_html__( 'Usaremos tus datos solo para atender tu solicitud. Consulta el %s.', 'ah-gestion-ti' ),
					'<a href="' . esc_url( get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/' ) ) . '">' . esc_html__( 'aviso de privacidad', 'ah-gestion-ti' ) . '</a>'
				);
				?>
			</p>
		</form>
		<?php
	}

	/**
	 * Campo de texto con etiqueta y error.
	 *
	 * @param string $tipo         Formulario.
	 * @param string $nombre       Nombre del campo.
	 * @param string $etiqueta     Etiqueta.
	 * @param string $input        Tipo de input.
	 * @param string $valor        Valor.
	 * @param string $autocompletar Atributo autocomplete.
	 * @param bool   $requerido    Obligatorio.
	 */
	private static function campo( $tipo, $nombre, $etiqueta, $input, $valor, $autocompletar = '', $requerido = true ) {
		$id    = 'ah-' . $tipo . '-' . $nombre;
		$error = self::$formulario === $tipo && isset( self::$errores[ $nombre ] );
		printf(
			'<div class="ah-campo%1$s"><label for="%2$s">%3$s%4$s</label><input type="%5$s" id="%2$s" name="%6$s" value="%7$s"%8$s%9$s%10$s>',
			$error ? ' ah-campo--error' : '',
			esc_attr( $id ),
			esc_html( $etiqueta ),
			$requerido ? ' <span class="ah-req" aria-hidden="true">*</span>' : '',
			esc_attr( $input ),
			esc_attr( $nombre ),
			esc_attr( $valor ),
			$autocompletar ? ' autocomplete="' . esc_attr( $autocompletar ) . '"' : '',
			$requerido ? ' required' : '',
			$error ? ' aria-invalid="true" aria-describedby="' . esc_attr( $id ) . '-error"' : ''
		);
		if ( $error ) {
			echo '<p class="ah-error" id="' . esc_attr( $id ) . '-error">' . esc_html( self::$errores[ $nombre ] ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Lista desplegable.
	 *
	 * @param string               $tipo     Formulario.
	 * @param string               $nombre   Campo.
	 * @param string               $etiqueta Etiqueta.
	 * @param array<string,string> $opciones Opciones.
	 * @param string               $valor    Valor seleccionado.
	 */
	private static function select( $tipo, $nombre, $etiqueta, $opciones, $valor ) {
		$id    = 'ah-' . $tipo . '-' . $nombre;
		$error = self::$formulario === $tipo && isset( self::$errores[ $nombre ] );
		echo '<div class="ah-campo' . ( $error ? ' ah-campo--error' : '' ) . '">';
		echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $etiqueta ) . ' <span class="ah-req" aria-hidden="true">*</span></label>';
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $nombre ) . '" required>';
		echo '<option value="">' . esc_html__( 'Selecciona una opción', 'ah-gestion-ti' ) . '</option>';
		foreach ( $opciones as $clave => $texto ) {
			echo '<option value="' . esc_attr( $clave ) . '" ' . selected( $valor, $clave, false ) . '>' . esc_html( $texto ) . '</option>';
		}
		echo '</select>';
		self::error( $tipo, $nombre );
		echo '</div>';
	}

	/**
	 * Mensaje de error de un campo.
	 *
	 * @param string $tipo  Formulario.
	 * @param string $campo Campo.
	 */
	private static function error( $tipo, $campo ) {
		if ( self::$formulario === $tipo && isset( self::$errores[ $campo ] ) ) {
			echo '<p class="ah-error">' . esc_html( self::$errores[ $campo ] ) . '</p>';
		}
	}

	/**
	 * Formulario de consulta pública.
	 */
	private static function formulario_consulta() {
		if ( isset( self::$errores['consulta'] ) ) {
			echo '<div class="ah-aviso ah-aviso--error" role="alert">' . esc_html( self::$errores['consulta'] ) . '</div>';
		}
		if ( 'consultar' === self::$formulario && isset( self::$errores['general'] ) ) {
			echo '<div class="ah-aviso ah-aviso--error" role="alert">' . esc_html( self::$errores['general'] ) . '</div>';
		}
		?>
		<form class="ah-form" method="post">
			<input type="hidden" name="ah_accion" value="consultar">
			<?php wp_nonce_field( 'ah_mesa_consultar', 'ah_nonce' ); ?>
			<div class="ah-campo">
				<label for="ah-consulta-numero"><?php esc_html_e( 'Número de seguimiento', 'ah-gestion-ti' ); ?></label>
				<input type="text" id="ah-consulta-numero" name="numero" placeholder="AH-2026-000001" pattern="AH-\d{4}-\d{6}" required>
			</div>
			<div class="ah-campo">
				<label for="ah-consulta-correo"><?php esc_html_e( 'Correo con el que lo registraste', 'ah-gestion-ti' ); ?></label>
				<input type="email" id="ah-consulta-correo" name="correo_consulta" autocomplete="email" required>
			</div>
			<button type="submit" class="ah-boton ah-boton--secundario"><?php esc_html_e( 'Consultar', 'ah-gestion-ti' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Detalle público de un ticket con su avance ITIL.
	 *
	 * @param array $ticket Ticket.
	 */
	private static function vista_ticket( array $ticket ) {
		$estados = AH_Tickets::estados();
		echo '<section class="ah-tarjeta ah-ticket" aria-labelledby="ah-ticket-titulo">';
		echo '<p class="ah-ticket__numero">' . esc_html( $ticket['numero'] ) . ' <span class="ah-estado ah-estado--' . esc_attr( $ticket['estado'] ) . '">' . esc_html( $estados[ $ticket['estado'] ] ) . '</span></p>';
		echo '<h2 id="ah-ticket-titulo">' . esc_html( $ticket['asunto'] ) . '</h2>';
		echo '<p class="ah-texto-suave">' . esc_html( AH_Tickets::categorias()[ $ticket['categoria'] ] ?? '' ) . ' · ' . esc_html__( 'Prioridad', 'ah-gestion-ti' ) . ': ' . esc_html( AH_Tickets::prioridades()[ $ticket['prioridad'] ] ?? '' ) . ' · ' . esc_html( mysql2date( get_option( 'date_format' ) . ' H:i', $ticket['creado'] ) ) . '</p>';

		echo '<ol class="ah-etapas" aria-label="' . esc_attr__( 'Avance del ticket', 'ah-gestion-ti' ) . '">';
		foreach ( AH_Tickets::etapas( $ticket ) as $etapa ) {
			echo '<li class="' . ( $etapa['completa'] ? 'completa' : '' ) . '">' . esc_html( $etapa['nombre'] ) . '</li>';
		}
		echo '</ol>';

		echo '<h3>' . esc_html__( 'Descripción', 'ah-gestion-ti' ) . '</h3><p>' . nl2br( esc_html( $ticket['descripcion'] ) ) . '</p>';
		echo '<h3>' . esc_html__( 'Historial', 'ah-gestion-ti' ) . '</h3><ul class="ah-historial">';
		foreach ( AH_Tickets::eventos( (int) $ticket['id'], true ) as $evento ) {
			echo '<li class="ah-historial__item ah-historial__item--' . esc_attr( $evento['tipo'] ) . '"><span class="ah-historial__meta">' . esc_html( $evento['autor'] ) . ' · ' . esc_html( mysql2date( 'd/m/Y H:i', $evento['creado'] ) ) . '</span>' . nl2br( esc_html( $evento['contenido'] ) ) . '</li>';
		}
		echo '</ul>';

		if ( self::$aviso ) {
			echo '<div class="ah-aviso ah-aviso--exito" role="status">' . esc_html( self::$aviso ) . '</div>';
		}
		if ( isset( self::$errores['respuesta'] ) ) {
			echo '<div class="ah-aviso ah-aviso--error" role="alert">' . esc_html( self::$errores['respuesta'] ) . '</div>';
		}
		if ( 'cerrado' !== $ticket['estado'] ) {
			?>
			<form class="ah-form" method="post">
				<input type="hidden" name="ah_accion" value="responder">
				<input type="hidden" name="numero" value="<?php echo esc_attr( $ticket['numero'] ); ?>">
				<input type="hidden" name="correo_consulta" value="<?php echo esc_attr( $ticket['correo'] ); ?>">
				<?php wp_nonce_field( 'ah_mesa_responder', 'ah_nonce' ); ?>
				<div class="ah-campo">
					<label for="ah-respuesta"><?php esc_html_e( 'Agregar información o responder', 'ah-gestion-ti' ); ?></label>
					<textarea id="ah-respuesta" name="mensaje" rows="3" required minlength="5"></textarea>
				</div>
				<button type="submit" class="ah-boton ah-boton--secundario"><?php esc_html_e( 'Enviar respuesta', 'ah-gestion-ti' ); ?></button>
			</form>
			<?php
		}
		echo '</section>';
	}

	/**
	 * Tickets del usuario con sesión.
	 *
	 * @param string $url_base URL donde se muestra el detalle.
	 */
	private static function lista_usuario( $url_base ) {
		$tickets = AH_Tickets::del_usuario( wp_get_current_user() );
		if ( ! $tickets ) {
			echo '<p class="ah-texto-suave">' . esc_html__( 'Aún no tienes tickets registrados.', 'ah-gestion-ti' ) . '</p>';
			return;
		}
		echo '<ul class="ah-lista-tickets">';
		foreach ( $tickets as $t ) {
			printf(
				'<li><a href="%1$s"><strong>%2$s</strong> %3$s</a><span class="ah-estado ah-estado--%4$s">%5$s</span></li>',
				esc_url( add_query_arg( 'ver_ticket', (int) $t['id'], $url_base ) . '#ah-resultado' ),
				esc_html( $t['numero'] ),
				esc_html( $t['asunto'] ),
				esc_attr( $t['estado'] ),
				esc_html( AH_Tickets::estados()[ $t['estado'] ] )
			);
		}
		echo '</ul>';
	}

	/**
	 * Canales alternos configurados (WhatsApp y videollamada).
	 */
	private static function bloque_canales() {
		$whatsapp = ah_gti_whatsapp_url( __( 'Hola, necesito ayuda con mi compra.', 'ah-gestion-ti' ) );
		$video    = ah_gti_opcion( 'videollamada_url' );
		if ( ! $whatsapp && ! $video ) {
			return;
		}
		echo '<section class="ah-tarjeta"><h2>' . esc_html__( 'Otros canales de atención', 'ah-gestion-ti' ) . '</h2><ul class="ah-canales">';
		if ( $whatsapp ) {
			echo '<li><a class="ah-boton ah-boton--whatsapp" href="' . esc_url( $whatsapp ) . '" target="_blank" rel="noopener">' . esc_html__( 'Escríbenos por WhatsApp', 'ah-gestion-ti' ) . '</a></li>';
		}
		if ( $video ) {
			echo '<li><a class="ah-boton ah-boton--secundario" href="' . esc_url( $video ) . '" target="_blank" rel="noopener">' . esc_html__( 'Agendar videollamada', 'ah-gestion-ti' ) . '</a><p class="ah-texto-suave ah-texto-pequeno">' . esc_html( ah_gti_opcion( 'videollamada_texto' ) ) . '</p></li>';
		}
		echo '</ul></section>';
	}

	/**
	 * Contenido de Mi cuenta → Soporte.
	 */
	public static function contenido_cuenta() {
		wp_enqueue_style( 'ah-gti-publico' );
		echo '<div class="ah-mesa">';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Solo lectura; se valida la propiedad.
		$ver = isset( $_GET['ver_ticket'] ) ? absint( $_GET['ver_ticket'] ) : 0;
		if ( $ver ) {
			$ticket = AH_Tickets::obtener( $ver );
			if ( $ticket && AH_Tickets::puede_ver( $ticket ) ) {
				self::vista_ticket( $ticket );
			}
		}
		echo '<h2>' . esc_html__( 'Mis tickets de soporte', 'ah-gestion-ti' ) . '</h2>';
		self::lista_usuario( wc_get_account_endpoint_url( self::ENDPOINT ) );
		echo '<p><a class="ah-boton" href="' . esc_url( self::url_pagina() ) . '">' . esc_html__( 'Crear un ticket nuevo', 'ah-gestion-ti' ) . '</a></p>';
		echo '</div>';
	}
}
