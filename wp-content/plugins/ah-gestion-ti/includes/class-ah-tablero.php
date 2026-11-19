<?php
/**
 * Tablero "Gestión TI": resumen de los cuatro módulos con datos reales
 * y lista de verificación de configuraciones pendientes.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menú principal y tablero.
 */
final class AH_Tablero {

	/** Slug del menú principal. */
	const PAGINA = 'ah-gestion-ti';

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
	}

	/**
	 * Menú de nivel superior.
	 */
	public static function menu() {
		add_menu_page(
			__( 'Gestión TI – Artesanías Huejutla', 'ah-gestion-ti' ),
			__( 'Gestión TI', 'ah-gestion-ti' ),
			AH_Instalador::CAP_TICKETS,
			self::PAGINA,
			array( __CLASS__, 'pagina' ),
			'dashicons-store',
			56
		);
		add_submenu_page( self::PAGINA, __( 'Tablero', 'ah-gestion-ti' ), __( 'Tablero', 'ah-gestion-ti' ), AH_Instalador::CAP_TICKETS, self::PAGINA, array( __CLASS__, 'pagina' ) );
	}

	/**
	 * Verificaciones reales de configuración.
	 *
	 * @return array<int,array{ok:bool,texto:string,ayuda:string,enlace:string}>
	 */
	public static function verificaciones() {
		$pasarelas = array();
		if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
			foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $pasarela ) {
				$pasarelas[] = $pasarela->get_title();
			}
		}
		$redes = array_filter( array( ah_gti_opcion( 'facebook' ), ah_gti_opcion( 'instagram' ), ah_gti_opcion( 'tiktok' ) ) );

		return array(
			array(
				'ok'     => class_exists( 'WooCommerce' ),
				'texto'  => 'WooCommerce activo',
				'ayuda'  => defined( 'WC_VERSION' ) ? 'Versión ' . WC_VERSION : 'Instale y active WooCommerce.',
				'enlace' => admin_url( 'admin.php?page=wc-settings' ),
			),
			array(
				'ok'     => AH_SEO::plugin_seo_activo(),
				'texto'  => 'Plugin SEO activo (Rank Math o Yoast) con sitemap XML dinámico',
				'ayuda'  => AH_SEO::plugin_seo_activo() ? home_url( '/sitemap_index.xml' ) : 'Se usa el sitemap nativo de WordPress: ' . home_url( '/wp-sitemap.xml' ),
				'enlace' => admin_url( 'plugins.php' ),
			),
			array(
				'ok'     => '' !== get_option( 'permalink_structure' ),
				'texto'  => 'URLs amigables',
				'ayuda'  => (string) get_option( 'permalink_structure' ),
				'enlace' => admin_url( 'options-permalink.php' ),
			),
			array(
				'ok'     => (bool) get_option( 'blog_public' ),
				'texto'  => 'Sitio visible para motores de búsqueda',
				'ayuda'  => get_option( 'blog_public' ) ? 'Indexación permitida.' : 'Ajustes → Lectura: la indexación está desactivada.',
				'enlace' => admin_url( 'options-reading.php' ),
			),
			array(
				'ok'     => is_ssl() || 0 === strpos( home_url(), 'https://' ),
				'texto'  => 'HTTPS',
				'ayuda'  => 'Obligatorio en producción (certificado TLS, p. ej. Let\'s Encrypt).',
				'enlace' => admin_url( 'options-general.php' ),
			),
			array(
				'ok'     => AH_Analitica::activa(),
				'texto'  => 'Google Analytics 4 configurado',
				'ayuda'  => AH_Analitica::activa() ? (string) ah_gti_opcion( 'ga4_id' ) : 'Pendiente: capture el ID de medición de la propiedad real.',
				'enlace' => admin_url( 'admin.php?page=' . AH_Ajustes::PAGINA ),
			),
			array(
				'ok'     => '' !== (string) ah_gti_opcion( 'gsc_verificacion' ),
				'texto'  => 'Verificación de Google Search Console',
				'ayuda'  => 'Pendiente hasta tener dominio público. También puede verificarse por DNS.',
				'enlace' => admin_url( 'admin.php?page=' . AH_Ajustes::PAGINA ),
			),
			array(
				'ok'     => (bool) $pasarelas,
				'texto'  => 'Métodos de pago habilitados',
				'ayuda'  => $pasarelas ? implode( ', ', $pasarelas ) : 'Ninguno habilitado.',
				'enlace' => admin_url( 'admin.php?page=wc-settings&tab=checkout' ),
			),
			array(
				'ok'     => (bool) $redes,
				'texto'  => 'Redes sociales oficiales',
				'ayuda'  => $redes ? count( $redes ) . ' configuradas' : 'Pendiente: capture las URL de las cuentas oficiales.',
				'enlace' => admin_url( 'admin.php?page=' . AH_Ajustes::PAGINA ),
			),
			array(
				'ok'     => '' !== AH_Calculos::whatsapp_normalizado( ah_gti_opcion( 'whatsapp' ) ),
				'texto'  => 'WhatsApp Business',
				'ayuda'  => 'Se muestra el botón flotante y en la mesa de ayuda cuando hay número.',
				'enlace' => admin_url( 'admin.php?page=' . AH_Ajustes::PAGINA ),
			),
			array(
				'ok'     => defined( 'WP_CACHE' ) && WP_CACHE,
				'texto'  => 'Caché de página activa (optimización multinivel)',
				'ayuda'  => defined( 'WP_CACHE' ) && WP_CACHE ? 'Páginas públicas en caché; nunca se cachean carrito, pago, cuenta ni usuarios con sesión.' : 'Instale y active WP Super Cache.',
				'enlace' => admin_url( 'options-general.php?page=wpsupercache' ),
			),
			array(
				'ok'     => 'smtp' === AH_Correo::estado(),
				'texto'  => 'Envío de correo por SMTP (confirmaciones de cuenta, boletín y pedidos)',
				'ayuda'  => array(
					'smtp'     => 'SMTP configurado en wp-config.php.',
					'local'    => 'Modo local: los correos se guardan en Gestión TI → Correos (local) y no se envían. En el hosting quite AH_CORREO_LOCAL y defina AH_SMTP_*.',
					'servidor' => 'Sin SMTP: se usa mail() del servidor, que suele no entregar. Defina AH_SMTP_* en wp-config.php.',
				)[ AH_Correo::estado() ],
				'enlace' => AH_Correo::modo_local() ? admin_url( 'admin.php?page=' . AH_Correo::PAGINA ) : admin_url( 'admin.php?page=ah-gestion-ti' ),
			),
			array(
				'ok'     => (bool) wp_next_scheduled( AH_Monitoreo::EVENTO_CRON ),
				'texto'  => 'Chequeo de disponibilidad programado',
				'ayuda'  => 'WP-Cron cada hora. En producción, use un cron real del sistema.',
				'enlace' => admin_url( 'admin.php?page=' . AH_Monitoreo::PAGINA ),
			),
		);
	}

	/**
	 * Página del tablero.
	 */
	public static function pagina() {
		$t  = AH_Tickets::estadisticas( 30 );
		$m  = AH_Monitoreo::resumen( 30 );
		$ve = self::verificaciones();
		$ok = count( array_filter( $ve, static fn( $v ) => $v['ok'] ) );
		?>
		<div class="wrap ah-admin">
			<h1><?php esc_html_e( 'Gestión TI – Artesanías Huejutla', 'ah-gestion-ti' ); ?></h1>
			<p><?php esc_html_e( 'Resumen de los módulos: Marketing digital (SEO/SEM), Gestión web, DevOps e ITIL. Todas las cifras provienen de datos registrados en este sitio.', 'ah-gestion-ti' ); ?></p>

			<div class="ah-kpis">
				<div class="ah-kpi"><span><?php esc_html_e( 'Tickets (30 días)', 'ah-gestion-ti' ); ?></span><strong><?php echo (int) $t['total']; ?></strong><small><?php echo (int) ( ( $t['por_estado']['abierto'] ?? 0 ) + ( $t['por_estado']['en_proceso'] ?? 0 ) ); ?> <?php esc_html_e( 'en atención', 'ah-gestion-ti' ); ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Fuera de SLA', 'ah-gestion-ti' ); ?></span><strong><?php echo (int) $t['vencidos']; ?></strong><small><?php esc_html_e( 'Cumplimiento:', 'ah-gestion-ti' ); ?> <?php echo null === $t['cumplimiento_sla'] ? '—' : esc_html( $t['cumplimiento_sla'] . ' %' ); ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Resolución promedio', 'ah-gestion-ti' ); ?></span><strong><?php echo null === $t['resolucion_promedio_h'] ? '—' : esc_html( $t['resolucion_promedio_h'] . ' h' ); ?></strong><small><?php esc_html_e( '1.ª respuesta:', 'ah-gestion-ti' ); ?> <?php echo null === $t['respuesta_promedio_h'] ? '—' : esc_html( $t['respuesta_promedio_h'] . ' h' ); ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Disponibilidad medida', 'ah-gestion-ti' ); ?></span><strong><?php echo null === $m['disponibilidad'] ? '—' : esc_html( $m['disponibilidad'] . ' %' ); ?></strong><small><?php echo (int) $m['total']; ?> <?php esc_html_e( 'muestras', 'ah-gestion-ti' ); ?></small></div>
				<div class="ah-kpi"><span><?php esc_html_e( 'Configuración', 'ah-gestion-ti' ); ?></span><strong><?php echo (int) $ok; ?>/<?php echo count( $ve ); ?></strong><small><?php esc_html_e( 'verificaciones completas', 'ah-gestion-ti' ); ?></small></div>
			</div>

			<div class="ah-admin__rejilla">
				<div class="postbox"><div class="inside">
					<h2><?php esc_html_e( 'Lista de verificación', 'ah-gestion-ti' ); ?></h2>
					<ul class="ah-checklist">
						<?php foreach ( $ve as $v ) : ?>
							<li class="<?php echo $v['ok'] ? 'ok' : 'pendiente'; ?>">
								<span aria-hidden="true"><?php echo $v['ok'] ? '✔' : '○'; ?></span>
								<div><a href="<?php echo esc_url( $v['enlace'] ); ?>"><strong><?php echo esc_html( $v['texto'] ); ?></strong></a><br><small><?php echo esc_html( $v['ayuda'] ); ?></small></div>
							</li>
						<?php endforeach; ?>
					</ul>
				</div></div>

				<div class="postbox"><div class="inside">
					<h2><?php esc_html_e( 'Accesos por módulo', 'ah-gestion-ti' ); ?></h2>
					<h3><?php esc_html_e( 'Módulo 1 · Marketing digital', 'ah-gestion-ti' ); ?></h3>
					<ul class="ul-disc">
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AH_SEM::PAGINA ) ); ?>"><?php esc_html_e( 'Campaña SEM simulada', 'ah-gestion-ti' ); ?></a></li>
						<?php if ( defined( 'RANK_MATH_VERSION' ) ) : ?>
							<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=rank-math' ) ); ?>">Rank Math SEO</a></li>
						<?php endif; ?>
						<li><a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank">robots.txt</a></li>
					</ul>
					<h3><?php esc_html_e( 'Módulo 2 · Gestión web', 'ah-gestion-ti' ); ?></h3>
					<ul class="ul-disc">
						<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Productos e inventario', 'ah-gestion-ti' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ? 'admin.php?page=wc-orders' : 'edit.php?post_type=shop_order' ) ); ?>"><?php esc_html_e( 'Pedidos', 'ah-gestion-ti' ); ?></a> · <a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"><?php esc_html_e( 'Blog', 'ah-gestion-ti' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AH_Ajustes::PAGINA ) ); ?>"><?php esc_html_e( 'Analytics, Search Console y redes', 'ah-gestion-ti' ); ?></a></li>
					</ul>
					<h3><?php esc_html_e( 'Módulo 3 · DevOps', 'ah-gestion-ti' ); ?></h3>
					<ul class="ul-disc">
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AH_Monitoreo::PAGINA ) ); ?>"><?php esc_html_e( 'Monitoreo y métricas', 'ah-gestion-ti' ); ?></a></li>
						<li><?php esc_html_e( 'Pipeline CI/CD: .github/workflows en el repositorio', 'ah-gestion-ti' ); ?></li>
					</ul>
					<h3><?php esc_html_e( 'Módulo 4 · ITIL', 'ah-gestion-ti' ); ?></h3>
					<ul class="ul-disc">
						<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . AH_Mesa_Ayuda_Admin::PAGINA ) ); ?>"><?php esc_html_e( 'Mesa de ayuda', 'ah-gestion-ti' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . AH_Servicios::TIPO ) ); ?>"><?php esc_html_e( 'Catálogo de servicios', 'ah-gestion-ti' ); ?></a></li>
						<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . AH_Mejora_Continua::TIPO ) ); ?>"><?php esc_html_e( 'Reportes de mejora continua (PDCA)', 'ah-gestion-ti' ); ?></a></li>
					</ul>
				</div></div>
			</div>
		</div>
		<?php
	}
}
