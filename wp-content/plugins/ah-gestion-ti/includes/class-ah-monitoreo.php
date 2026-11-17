<?php
/**
 * Monitoreo y métricas DevOps.
 *
 * - Chequeo HTTP real cada hora (WP-Cron) de las páginas clave: código y tiempo.
 * - Disponibilidad %, tiempo de respuesta (promedio y p95), errores HTTP y MTTR
 *   calculados SOLO con muestras reales almacenadas.
 * - Endpoint público /wp-json/ah/v1/salud para monitores externos
 *   (UptimeRobot, Better Stack, GitHub Actions).
 * - Estado del servidor, recursos y errores de PHP (debug.log).
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Monitoreo de disponibilidad y rendimiento.
 */
final class AH_Monitoreo {

	/** Evento de WP-Cron. */
	const EVENTO_CRON = 'ah_gti_chequeo_salud';

	/** Opción con las muestras (no se carga automáticamente). */
	const OPCION = 'ah_gti_muestras';

	/** Máximo de muestras guardadas (≈ 30 días cada hora × páginas). */
	const MAXIMO = 2500;

	/** Slug de la página. */
	const PAGINA = 'ah-monitoreo';

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( self::EVENTO_CRON, array( __CLASS__, 'chequear' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'rutas' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 26 );
		add_action( 'admin_post_ah_monitoreo_chequear', array( __CLASS__, 'chequear_manual' ) );
		add_action( 'admin_post_ah_monitoreo_limpiar', array( __CLASS__, 'limpiar' ) );
	}

	/**
	 * URLs que se monitorean.
	 *
	 * @return array<string,string>
	 */
	public static function objetivos() {
		$objetivos = array( 'inicio' => home_url( '/' ) );
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$objetivos['tienda'] = wc_get_page_permalink( 'shop' );
		}
		$objetivos['salud'] = rest_url( 'ah/v1/salud' );
		return $objetivos;
	}

	/**
	 * Realiza el chequeo y guarda las muestras.
	 *
	 * @return array<int,array<string,mixed>> Muestras nuevas.
	 */
	public static function chequear() {
		$muestras = (array) get_option( self::OPCION, array() );
		$nuevas   = array();
		foreach ( self::objetivos() as $nombre => $url ) {
			$inicio    = microtime( true );
			$respuesta = wp_remote_get(
				$url,
				array(
					'timeout'     => 20,
					'redirection' => 3,
					'sslverify'   => 'local' !== wp_get_environment_type(),
					'headers'     => array( 'Cache-Control' => 'no-cache' ),
					'user-agent'  => 'AH-Monitoreo/' . AH_GTI_VERSION,
				)
			);
			$ms        = round( ( microtime( true ) - $inicio ) * 1000, 1 );
			$codigo    = is_wp_error( $respuesta ) ? 0 : (int) wp_remote_retrieve_response_code( $respuesta );
			$nuevas[]  = array(
				't'      => time(),
				'url'    => $nombre,
				'codigo' => $codigo,
				'ms'     => $ms,
				'error'  => is_wp_error( $respuesta ) ? $respuesta->get_error_message() : '',
			);
		}
		$muestras = array_slice( array_merge( $muestras, $nuevas ), -self::MAXIMO );
		update_option( self::OPCION, $muestras, false );
		return $nuevas;
	}

	/**
	 * Resumen de las muestras de los últimos N días.
	 *
	 * @param int    $dias   Ventana.
	 * @param string $url    Filtra por objetivo (vacío = página de inicio).
	 * @return array<string,mixed>
	 */
	public static function resumen( $dias = 30, $url = 'inicio' ) {
		$desde               = time() - $dias * DAY_IN_SECONDS;
		$muestras            = array_values(
			array_filter(
				(array) get_option( self::OPCION, array() ),
				static fn( $m ) => $m['t'] >= $desde && ( '' === $url || $m['url'] === $url )
			)
		);
		$resumen             = AH_Calculos::resumen_disponibilidad( $muestras );
		$resumen['muestras'] = $muestras;
		return $resumen;
	}

	/**
	 * Endpoint de salud.
	 */
	public static function rutas() {
		register_rest_route(
			'ah/v1',
			'/salud',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'salud' ),
			)
		);
	}

	/**
	 * Respuesta del endpoint (sin información sensible).
	 *
	 * @return WP_REST_Response
	 */
	public static function salud() {
		global $wpdb;
		$inicio    = microtime( true );
		$bd        = (bool) $wpdb->get_var( 'SELECT 1' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Prueba de conexión.
		$datos     = array(
			'estado'       => $bd ? 'ok' : 'error',
			'base_datos'   => $bd,
			'woocommerce'  => class_exists( 'WooCommerce' ),
			'tiempo_bd_ms' => round( ( microtime( true ) - $inicio ) * 1000, 2 ),
			'hora'         => gmdate( 'c' ),
		);
		$respuesta = new WP_REST_Response( $datos, $bd ? 200 : 503 );
		$respuesta->header( 'Cache-Control', 'no-store' );
		return $respuesta;
	}

	/**
	 * Submenú.
	 */
	public static function menu() {
		add_submenu_page( AH_Tablero::PAGINA, __( 'Monitoreo', 'ah-gestion-ti' ), __( 'Monitoreo', 'ah-gestion-ti' ), AH_Instalador::CAP_GESTION, self::PAGINA, array( __CLASS__, 'pagina' ) );
	}

	/**
	 * Chequeo bajo demanda.
	 */
	public static function chequear_manual() {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'Sin permiso.', 'ah-gestion-ti' ), 403 );
		}
		check_admin_referer( 'ah_monitoreo_chequear' );
		self::chequear();
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::PAGINA,
					'chequeado' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Borra el historial de muestras.
	 */
	public static function limpiar() {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'Sin permiso.', 'ah-gestion-ti' ), 403 );
		}
		check_admin_referer( 'ah_monitoreo_limpiar' );
		delete_option( self::OPCION );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGINA ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Errores recientes de PHP en debug.log (sin exponerlos públicamente).
	 *
	 * @return array{archivo:string,existe:bool,conteo:array<string,int>,lineas:array<int,string>}
	 */
	public static function errores_php() {
		$archivo = WP_CONTENT_DIR . '/debug.log';
		if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
			$archivo = WP_DEBUG_LOG;
		}
		$conteo = array(
			'Fatal'      => 0,
			'Warning'    => 0,
			'Notice'     => 0,
			'Deprecated' => 0,
		);
		if ( ! is_readable( $archivo ) ) {
			return array(
				'archivo' => $archivo,
				'existe'  => false,
				'conteo'  => $conteo,
				'lineas'  => array(),
			);
		}
		// Lee solo los últimos 256 KB para no cargar archivos enormes.
		$tamano = filesize( $archivo );
		$fp     = fopen( $archivo, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( $tamano > 262144 ) {
			fseek( $fp, -262144, SEEK_END );
		}
		$contenido = (string) stream_get_contents( $fp );
		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		$lineas = array_values( array_filter( explode( "\n", $contenido ), static fn( $l ) => 0 === strpos( $l, '[' ) ) );
		foreach ( $lineas as $linea ) {
			foreach ( array_keys( $conteo ) as $tipo ) {
				if ( false !== strpos( $linea, 'PHP ' . $tipo ) ) {
					++$conteo[ $tipo ];
				}
			}
		}
		return array(
			'archivo' => $archivo,
			'existe'  => true,
			'conteo'  => $conteo,
			'lineas'  => array_slice( $lineas, -15 ),
		);
	}

	/**
	 * Estado del servidor y la aplicación.
	 *
	 * @return array<string,string>
	 */
	public static function servidor() {
		global $wpdb;
		$tam_bd             = $wpdb->get_var( $wpdb->prepare( 'SELECT ROUND(SUM(data_length + index_length) / 1048576, 2) FROM information_schema.tables WHERE table_schema = %s', DB_NAME ) );
		$libre              = function_exists( 'disk_free_space' ) ? @disk_free_space( ABSPATH ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Puede estar deshabilitada.
		$plugins_pendientes = get_site_transient( 'update_plugins' );
		$cron_siguiente     = wp_next_scheduled( self::EVENTO_CRON );

		return array(
			__( 'Versión de PHP', 'ah-gestion-ti' )        => PHP_VERSION,
			__( 'Límite de memoria PHP', 'ah-gestion-ti' ) => (string) ini_get( 'memory_limit' ),
			__( 'Memoria pico de esta petición', 'ah-gestion-ti' ) => size_format( memory_get_peak_usage( true ) ),
			__( 'WordPress', 'ah-gestion-ti' )             => get_bloginfo( 'version' ),
			'WooCommerce'                                  => defined( 'WC_VERSION' ) ? WC_VERSION : __( 'no activo', 'ah-gestion-ti' ),
			__( 'Base de datos', 'ah-gestion-ti' )         => $wpdb->db_server_info() . ' · ' . ( $tam_bd ? $tam_bd . ' MB' : '—' ),
			__( 'Espacio libre en disco', 'ah-gestion-ti' ) => $libre ? size_format( $libre ) : '—',
			__( 'Caché de objetos persistente', 'ah-gestion-ti' ) => wp_using_ext_object_cache() ? __( 'Sí', 'ah-gestion-ti' ) : __( 'No (recomendado Redis en producción)', 'ah-gestion-ti' ),
			__( 'Entorno', 'ah-gestion-ti' )               => wp_get_environment_type(),
			__( 'Plugins con actualización', 'ah-gestion-ti' ) => isset( $plugins_pendientes->response ) ? (string) count( (array) $plugins_pendientes->response ) : '0',
			__( 'Próximo chequeo automático', 'ah-gestion-ti' ) => $cron_siguiente ? wp_date( 'd/m/Y H:i', $cron_siguiente ) : __( 'no programado', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Página de monitoreo.
	 */
	public static function pagina() {
		$r        = self::resumen( 30, 'inicio' );
		$r_tienda = self::resumen( 30, 'tienda' );
		$errores  = self::errores_php();
		$fmt      = static fn( $v, $suf = '' ) => null === $v ? '—' : esc_html( number_format_i18n( (float) $v, 1 ) . $suf );
		?>
		<div class="wrap ah-admin">
			<h1><?php esc_html_e( 'Monitoreo y métricas DevOps', 'ah-gestion-ti' ); ?></h1>
			<p><?php esc_html_e( 'Todas las métricas se calculan con muestras reales tomadas por el propio sitio (WP-Cron cada hora o bajo demanda). Para disponibilidad 24/7 independiente del servidor, configure un monitor externo al endpoint de salud.', 'ah-gestion-ti' ); ?></p>
			<p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
					<input type="hidden" name="action" value="ah_monitoreo_chequear"><?php wp_nonce_field( 'ah_monitoreo_chequear' ); ?>
					<?php submit_button( __( 'Ejecutar chequeo ahora', 'ah-gestion-ti' ), 'primary', 'submit', false ); ?>
				</form>
				<a class="button" href="<?php echo esc_url( 'https://pagespeed.web.dev/analysis?url=' . rawurlencode( home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Analizar en PageSpeed Insights', 'ah-gestion-ti' ); ?></a>
				<code><?php echo esc_html( rest_url( 'ah/v1/salud' ) ); ?></code>
			</p>

			<?php if ( ! $r['total'] ) : ?>
				<div class="notice notice-info inline"><p><?php esc_html_e( 'Aún no hay muestras. Ejecuta un chequeo o espera la tarea programada.', 'ah-gestion-ti' ); ?></p></div>
			<?php endif; ?>

			<div class="ah-kpis">
				<div class="ah-kpi"><span><?php esc_html_e( 'Disponibilidad (30 días)', 'ah-gestion-ti' ); ?></span><strong><?php echo $fmt( $r['disponibilidad'], ' %' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escapado en $fmt. ?></strong><small><?php echo (int) $r['total']; ?> <?php esc_html_e( 'muestras', 'ah-gestion-ti' ); ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Tiempo de respuesta promedio', 'ah-gestion-ti' ); ?></span><strong><?php echo $fmt( $r['promedio_ms'], ' ms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><small>p95: <?php echo $fmt( $r['p95_ms'], ' ms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Frecuencia de errores', 'ah-gestion-ti' ); ?></span><strong><?php echo $fmt( $r['tasa_errores'], ' %' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><small><?php echo esc_html( $r['errores'] ? wp_json_encode( $r['errores'] ) : __( 'sin errores HTTP', 'ah-gestion-ti' ) ); ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Tiempo medio de recuperación (MTTR)', 'ah-gestion-ti' ); ?></span><strong><?php echo $fmt( $r['mttr_min'], ' min' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><small><?php echo (int) $r['incidentes']; ?> <?php esc_html_e( 'incidentes', 'ah-gestion-ti' ); ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Tienda: tiempo promedio', 'ah-gestion-ti' ); ?></span><strong><?php echo $fmt( $r_tienda['promedio_ms'], ' ms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong><small><?php echo $fmt( $r_tienda['disponibilidad'], ' %' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php esc_html_e( 'disponible', 'ah-gestion-ti' ); ?></small></div>
			</div>

			<div class="ah-admin__rejilla">
				<div class="postbox"><div class="inside">
					<h2><?php esc_html_e( 'Últimas muestras', 'ah-gestion-ti' ); ?></h2>
					<table class="widefat striped">
						<thead><tr><th><?php esc_html_e( 'Fecha', 'ah-gestion-ti' ); ?></th><th><?php esc_html_e( 'Página', 'ah-gestion-ti' ); ?></th><th>HTTP</th><th><?php esc_html_e( 'Tiempo', 'ah-gestion-ti' ); ?></th></tr></thead>
						<?php foreach ( array_reverse( array_slice( (array) get_option( self::OPCION, array() ), -15 ) ) as $m ) : ?>
							<tr>
								<td><?php echo esc_html( wp_date( 'd/m H:i:s', $m['t'] ) ); ?></td>
								<td><?php echo esc_html( $m['url'] ); ?></td>
								<td class="<?php echo AH_Calculos::es_exitoso( (int) $m['codigo'] ) ? 'ah-ok' : 'ah-texto-alerta'; ?>"><?php echo $m['codigo'] ? (int) $m['codigo'] : esc_html( $m['error'] ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $m['ms'], 0 ) ); ?> ms</td>
							</tr>
						<?php endforeach; ?>
					</table>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px">
						<input type="hidden" name="action" value="ah_monitoreo_limpiar"><?php wp_nonce_field( 'ah_monitoreo_limpiar' ); ?>
						<?php submit_button( __( 'Borrar historial de muestras', 'ah-gestion-ti' ), 'delete small', 'submit', false ); ?>
					</form>
				</div></div>

				<div class="postbox"><div class="inside">
					<h2><?php esc_html_e( 'Servidor y aplicación', 'ah-gestion-ti' ); ?></h2>
					<table class="widefat striped">
						<?php foreach ( self::servidor() as $clave => $valor ) : ?>
							<tr><th><?php echo esc_html( $clave ); ?></th><td><?php echo esc_html( $valor ); ?></td></tr>
						<?php endforeach; ?>
					</table>
				</div></div>
			</div>

			<div class="postbox"><div class="inside">
				<h2><?php esc_html_e( 'Errores de PHP', 'ah-gestion-ti' ); ?></h2>
				<?php if ( ! $errores['existe'] ) : ?>
					<p><?php esc_html_e( 'No hay registro de errores (debug.log) o WP_DEBUG_LOG está desactivado.', 'ah-gestion-ti' ); ?></p>
				<?php else : ?>
					<p>
						<?php foreach ( $errores['conteo'] as $tipo => $n ) : ?>
							<span class="ah-pill <?php echo ( 'Fatal' === $tipo && $n ) ? 'ah-pill--critica' : ''; ?>"><?php echo esc_html( $tipo . ': ' . $n ); ?></span>
						<?php endforeach; ?>
					</p>
					<pre class="ah-log"><?php echo esc_html( implode( "\n", $errores['lineas'] ) ); ?></pre>
				<?php endif; ?>
			</div></div>
		</div>
		<?php
	}
}
