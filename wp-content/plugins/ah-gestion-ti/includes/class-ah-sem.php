<?php
/**
 * Campaña SEM SIMULADA (Módulo 1).
 *
 * Documenta la estructura de una campaña de Google Ads y calcula proyecciones
 * con supuestos editables. No se conecta a Google Ads ni genera gastos.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Página administrativa de la estrategia SEM.
 */
final class AH_SEM {

	/** Slug de la página. */
	const PAGINA = 'ah-sem';

	/** Opción con los supuestos. */
	const OPCION = 'ah_gti_sem';

	/**
	 * Supuestos por defecto (presupuesto académico).
	 *
	 * @return array<string,float>
	 */
	public static function supuestos_defecto() {
		return array(
			'presupuesto_mensual' => 3000,
			'cpc'                 => 4.5,
			'ctr'                 => 0.05,
			'conversion'          => 0.02,
			'ticket_promedio'     => 350,
			'cpm'                 => 35,
		);
	}

	/**
	 * Supuestos guardados.
	 *
	 * @return array<string,float>
	 */
	public static function supuestos() {
		return wp_parse_args( (array) get_option( self::OPCION, array() ), self::supuestos_defecto() );
	}

	/**
	 * Estructura de la campaña (documentación).
	 *
	 * @return array<string,mixed>
	 */
	public static function estructura() {
		return array(
			'campana'    => array(
				'nombre'     => 'Artesanías Huejutla',
				'tipo'       => __( 'Búsqueda (Search Network) + Display de remarketing', 'ah-gestion-ti' ),
				'objetivo'   => __( 'Ventas en la tienda en línea', 'ah-gestion-ti' ),
				'puja'       => __( 'CPC manual mejorado al inicio; migrar a "Maximizar conversiones" al acumular 30 conversiones', 'ah-gestion-ti' ),
				'calendario' => __( 'Lunes a domingo, 8:00–23:00 (hora del centro de México)', 'ah-gestion-ti' ),
				'idioma'     => __( 'Español', 'ah-gestion-ti' ),
			),
			'grupos'     => array(
				array(
					'nombre'   => __( 'Marca y origen', 'ah-gestion-ti' ),
					'palabras' => array( '"artesanías Huejutla"', '[artesanías Huejutla]', '"artesanías de la Huasteca"', '"comprar artesanías Hidalgo"' ),
					'destino'  => '/',
				),
				array(
					'nombre'   => __( 'Cestería de palma', 'ah-gestion-ti' ),
					'palabras' => array( '"canasta de palma tejida"', '[canasta de palma tejida]', '"cesta de palma"', '"tortillero de palma"' ),
					'destino'  => '/categoria/cesteria-de-palma/',
				),
				array(
					'nombre'   => __( 'Textiles bordados', 'ah-gestion-ti' ),
					'palabras' => array( '"rebozo bordado"', '"mantel bordado a mano"', '"textiles huastecos"' ),
					'destino'  => '/categoria/textiles-bordados/',
				),
				array(
					'nombre'   => __( 'Hecho a mano (genérico)', 'ah-gestion-ti' ),
					'palabras' => array( '"artesanías mexicanas hechas a mano"', '"regalos artesanales mexicanos"' ),
					'destino'  => '/tienda/',
				),
			),
			'negativas'  => array( 'gratis', 'receta', 'curso', 'tutorial', 'cómo hacer', 'empleo', 'pdf', 'molde' ),
			'anuncio'    => array(
				'titulos'       => array( 'Artesanías de Huejutla', 'Directo del Productor', 'Envíos a Todo México', 'Canastas de Palma Tejida', '100% Hechas a Mano' ),
				'descripciones' => array(
					'Canastas y textiles artesanales de la Huasteca, elaborados 100% a mano con materiales naturales.',
					'Apoya a artesanos locales. Calidad garantizada y entrega segura a domicilio.',
					// La descripción 1 de la especificación tiene 96 caracteres (límite de Google Ads: 90).
					// Versión ajustada propuesta para publicar:
					'Canastas y textiles de la Huasteca, elaborados 100% a mano con materiales naturales.',
				),
				'ruta'          => 'artesaniashuejutla.mx/tienda',
			),
			'geografia'  => array(
				__( 'México (país completo)', 'ah-gestion-ti' ),
				__( 'Ajuste de puja +20 %: Hidalgo, Ciudad de México, Estado de México, Puebla', 'ah-gestion-ti' ),
				__( 'Ajuste de puja +10 %: Nuevo León, Jalisco, Querétaro, Veracruz, San Luis Potosí', 'ah-gestion-ti' ),
				__( 'Excluir ubicaciones fuera de México', 'ah-gestion-ti' ),
			),
			'audiencias' => array(
				__( 'Segmentos de mercado: decoración del hogar, regalos, artesanías y cultura mexicana', 'ah-gestion-ti' ),
				__( 'Afinidad: amantes de la cocina mexicana, viajeros culturales', 'ah-gestion-ti' ),
				__( 'Remarketing (Display): visitantes que agregaron al carrito y no compraron (30 días)', 'ah-gestion-ti' ),
				__( 'Edad 25–65, todos los géneros; dispositivos: móvil y escritorio', 'ah-gestion-ti' ),
			),
			'kpis'       => array(
				'CTR'                => __( 'Clics ÷ impresiones. Meta inicial ≥ 5 %', 'ah-gestion-ti' ),
				'CPC'                => __( 'Costo ÷ clics. Meta ≤ $4.50 MXN', 'ah-gestion-ti' ),
				'Tasa de conversión' => __( 'Compras ÷ clics. Meta ≥ 2 %', 'ah-gestion-ti' ),
				'CPA'                => __( 'Costo ÷ conversiones', 'ah-gestion-ti' ),
				'ROAS'               => __( 'Ingresos ÷ costo. Meta ≥ 2.0', 'ah-gestion-ti' ),
				'Nivel de calidad'   => __( 'Calificación de Google (1–10) por palabra clave. Meta ≥ 7', 'ah-gestion-ti' ),
			),
		);
	}

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );
		add_action( 'admin_post_ah_sem_guardar', array( __CLASS__, 'guardar' ) );
	}

	/**
	 * Submenú.
	 */
	public static function menu() {
		add_submenu_page( AH_Tablero::PAGINA, __( 'Campaña SEM (simulación)', 'ah-gestion-ti' ), __( 'Campaña SEM', 'ah-gestion-ti' ), AH_Instalador::CAP_GESTION, self::PAGINA, array( __CLASS__, 'pagina' ) );
	}

	/**
	 * Guarda los supuestos.
	 */
	public static function guardar() {
		if ( ! current_user_can( AH_Instalador::CAP_GESTION ) ) {
			wp_die( esc_html__( 'Sin permiso.', 'ah-gestion-ti' ), 403 );
		}
		check_admin_referer( 'ah_sem_guardar' );
		$nuevos = array();
		foreach ( self::supuestos_defecto() as $clave => $defecto ) {
			$valor = isset( $_POST[ $clave ] ) ? (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST[ $clave ] ) ) ) : $defecto;
			if ( in_array( $clave, array( 'ctr', 'conversion' ), true ) ) {
				$valor = $valor / 100; // Se capturan en porcentaje.
			}
			$nuevos[ $clave ] = max( 0, $valor );
		}
		update_option( self::OPCION, $nuevos, false );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => self::PAGINA,
					'guardado' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Formato de moneda MXN.
	 *
	 * @param float $valor Valor.
	 * @return string
	 */
	private static function mxn( $valor ) {
		return '$' . number_format_i18n( (float) $valor, 2 ) . ' MXN';
	}

	/**
	 * Página de la campaña.
	 */
	public static function pagina() {
		$s = self::supuestos();
		$e = AH_Calculos::estimar_sem( $s );
		$c = self::estructura();
		?>
		<div class="wrap ah-admin">
			<h1><?php esc_html_e( 'Campaña SEM – Artesanías Huejutla', 'ah-gestion-ti' ); ?></h1>
			<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Simulación académica.', 'ah-gestion-ti' ); ?></strong> <?php esc_html_e( 'Esta campaña NO está conectada a Google Ads y no genera gastos. Las cifras son proyecciones calculadas con los supuestos de abajo, no resultados reales.', 'ah-gestion-ti' ); ?></p></div>
			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( isset( $_GET['guardado'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Supuestos actualizados.', 'ah-gestion-ti' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( '1. Presupuesto y proyección', 'ah-gestion-ti' ); ?></h2>
			<div class="ah-admin__rejilla">
				<form class="postbox" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><div class="inside">
					<input type="hidden" name="action" value="ah_sem_guardar">
					<?php wp_nonce_field( 'ah_sem_guardar' ); ?>
					<h3><?php esc_html_e( 'Supuestos', 'ah-gestion-ti' ); ?></h3>
					<table class="form-table" role="presentation">
						<tr><th><label for="presupuesto_mensual"><?php esc_html_e( 'Presupuesto mensual (MXN)', 'ah-gestion-ti' ); ?></label></th><td><input id="presupuesto_mensual" name="presupuesto_mensual" type="number" step="1" value="<?php echo esc_attr( $s['presupuesto_mensual'] ); ?>"></td></tr>
						<tr><th><label for="cpc"><?php esc_html_e( 'CPC promedio (MXN)', 'ah-gestion-ti' ); ?></label></th><td><input id="cpc" name="cpc" type="number" step="0.01" value="<?php echo esc_attr( $s['cpc'] ); ?>"></td></tr>
						<tr><th><label for="ctr"><?php esc_html_e( 'CTR esperado (%)', 'ah-gestion-ti' ); ?></label></th><td><input id="ctr" name="ctr" type="number" step="0.1" value="<?php echo esc_attr( $s['ctr'] * 100 ); ?>"></td></tr>
						<tr><th><label for="conversion"><?php esc_html_e( 'Tasa de conversión (%)', 'ah-gestion-ti' ); ?></label></th><td><input id="conversion" name="conversion" type="number" step="0.1" value="<?php echo esc_attr( $s['conversion'] * 100 ); ?>"></td></tr>
						<tr><th><label for="ticket_promedio"><?php esc_html_e( 'Ticket promedio de compra (MXN)', 'ah-gestion-ti' ); ?></label></th><td><input id="ticket_promedio" name="ticket_promedio" type="number" step="1" value="<?php echo esc_attr( $s['ticket_promedio'] ); ?>"></td></tr>
						<tr><th><label for="cpm"><?php esc_html_e( 'CPM de referencia para Display (MXN)', 'ah-gestion-ti' ); ?></label></th><td><input id="cpm" name="cpm" type="number" step="0.5" value="<?php echo esc_attr( $s['cpm'] ); ?>"></td></tr>
					</table>
					<?php submit_button( __( 'Recalcular', 'ah-gestion-ti' ), 'secondary' ); ?>
				</div></form>

				<div class="postbox"><div class="inside">
					<h3><?php esc_html_e( 'Proyección mensual (estimada)', 'ah-gestion-ti' ); ?></h3>
					<table class="widefat striped">
						<tr><th><?php esc_html_e( 'Presupuesto diario', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( self::mxn( $e['presupuesto_diario'] ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Clics estimados (presupuesto ÷ CPC)', 'ah-gestion-ti' ); ?></th><td><strong><?php echo esc_html( number_format_i18n( $e['clics'] ) ); ?></strong> (≈ <?php echo esc_html( number_format_i18n( $e['clics_diarios'], 1 ) ); ?>/día)</td></tr>
						<tr><th><?php esc_html_e( 'Impresiones estimadas (clics ÷ CTR)', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( number_format_i18n( $e['impresiones'] ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Conversiones esperadas', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( number_format_i18n( $e['conversiones'], 1 ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'CPA (costo por adquisición)', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( self::mxn( $e['cpa'] ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Ingresos estimados', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( self::mxn( $e['ingresos'] ) ); ?></td></tr>
						<tr><th>ROAS</th><td><?php echo esc_html( number_format_i18n( $e['roas'], 2 ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'CPM efectivo de la estrategia CPC', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( self::mxn( $e['cpm_efectivo'] ) ); ?></td></tr>
					</table>
					<h3><?php esc_html_e( 'Comparativa CPC vs. CPM', 'ah-gestion-ti' ); ?></h3>
					<p>
						<?php
						printf(
							/* translators: 1: impresiones, 2: CPM, 3: clics. */
							esc_html__( 'Con el mismo presupuesto en Display por CPM (%2$s) se comprarían ≈ %1$s impresiones; con el mismo CTR producirían ≈ %3$s clics. CPC conviene para búsqueda con intención de compra; CPM para reconocimiento de marca y remarketing.', 'ah-gestion-ti' ),
							esc_html( number_format_i18n( $e['impresiones_cpm'] ) ),
							esc_html( self::mxn( $s['cpm'] ) ),
							esc_html( number_format_i18n( $e['clics_estimados_cpm'] ) )
						);
						?>
					</p>
				</div></div>
			</div>

			<h2><?php esc_html_e( '2. Campaña', 'ah-gestion-ti' ); ?></h2>
			<table class="widefat striped ah-tabla-media">
				<?php foreach ( $c['campana'] as $clave => $valor ) : ?>
					<tr><th style="width:180px"><?php echo esc_html( ucfirst( $clave ) ); ?></th><td><?php echo esc_html( $valor ); ?></td></tr>
				<?php endforeach; ?>
			</table>

			<h2><?php esc_html_e( '3. Grupos de anuncios y palabras clave', 'ah-gestion-ti' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Concordancia: "frase" = de frase, [exacta] = exacta.', 'ah-gestion-ti' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Grupo', 'ah-gestion-ti' ); ?></th><th><?php esc_html_e( 'Palabras clave', 'ah-gestion-ti' ); ?></th><th><?php esc_html_e( 'Página de destino', 'ah-gestion-ti' ); ?></th></tr></thead>
				<?php foreach ( $c['grupos'] as $grupo ) : ?>
					<tr><td><strong><?php echo esc_html( $grupo['nombre'] ); ?></strong></td><td><?php echo esc_html( implode( ' · ', $grupo['palabras'] ) ); ?></td><td><code><?php echo esc_html( $grupo['destino'] ); ?></code></td></tr>
				<?php endforeach; ?>
			</table>
			<p><strong><?php esc_html_e( 'Palabras clave negativas:', 'ah-gestion-ti' ); ?></strong> <?php echo esc_html( implode( ', ', $c['negativas'] ) ); ?></p>

			<h2><?php esc_html_e( '4. Anuncio de búsqueda adaptable', 'ah-gestion-ti' ); ?></h2>
			<div class="ah-anuncio">
				<span class="ah-anuncio__patrocinado"><?php esc_html_e( 'Patrocinado', 'ah-gestion-ti' ); ?></span>
				<span class="ah-anuncio__url"><?php echo esc_html( $c['anuncio']['ruta'] ); ?></span>
				<span class="ah-anuncio__titulo"><?php echo esc_html( implode( ' | ', array_slice( $c['anuncio']['titulos'], 0, 3 ) ) ); ?></span>
				<span class="ah-anuncio__desc"><?php echo esc_html( $c['anuncio']['descripciones'][2] . ' ' . $c['anuncio']['descripciones'][1] ); ?></span>
			</div>
			<table class="widefat striped ah-tabla-media">
				<thead><tr><th><?php esc_html_e( 'Elemento', 'ah-gestion-ti' ); ?></th><th><?php esc_html_e( 'Texto', 'ah-gestion-ti' ); ?></th><th><?php esc_html_e( 'Caracteres', 'ah-gestion-ti' ); ?></th></tr></thead>
				<?php foreach ( $c['anuncio']['titulos'] as $i => $titulo ) : ?>
					<?php $n = mb_strlen( $titulo ); ?>
					<tr><td><?php echo esc_html( sprintf( /* translators: %d: número. */ __( 'Título %d', 'ah-gestion-ti' ), $i + 1 ) ); ?></td><td><?php echo esc_html( $titulo ); ?></td><td class="<?php echo $n > 30 ? 'ah-texto-alerta' : 'ah-ok'; ?>"><?php echo (int) $n; ?>/30</td></tr>
				<?php endforeach; ?>
				<?php foreach ( $c['anuncio']['descripciones'] as $i => $desc ) : ?>
					<?php $n = mb_strlen( $desc ); ?>
					<tr><td><?php echo esc_html( sprintf( /* translators: %d: número. */ __( 'Descripción %d', 'ah-gestion-ti' ), $i + 1 ) ); ?></td><td><?php echo esc_html( $desc ); ?></td><td class="<?php echo $n > 90 ? 'ah-texto-alerta' : 'ah-ok'; ?>"><?php echo (int) $n; ?>/90</td></tr>
				<?php endforeach; ?>
			</table>
			<p class="description"><?php esc_html_e( 'La Descripción 1 de la especificación excede el límite de 90 caracteres de Google Ads; la Descripción 3 es la versión ajustada que se usaría al publicar (la vista previa la utiliza).', 'ah-gestion-ti' ); ?></p>

			<div class="ah-admin__rejilla">
				<div><h2><?php esc_html_e( '5. Segmentación geográfica', 'ah-gestion-ti' ); ?></h2><ul class="ul-disc">
				<?php
				foreach ( $c['geografia'] as $g ) :
					?>
					<li><?php echo esc_html( $g ); ?></li><?php endforeach; ?></ul></div>
				<div><h2><?php esc_html_e( '6. Segmentación por audiencia', 'ah-gestion-ti' ); ?></h2><ul class="ul-disc">
				<?php
				foreach ( $c['audiencias'] as $a ) :
					?>
					<li><?php echo esc_html( $a ); ?></li><?php endforeach; ?></ul></div>
			</div>

			<h2><?php esc_html_e( '7. Indicadores de rendimiento', 'ah-gestion-ti' ); ?></h2>
			<table class="widefat striped ah-tabla-media">
				<?php foreach ( $c['kpis'] as $kpi => $def ) : ?>
					<tr><th style="width:180px"><?php echo esc_html( $kpi ); ?></th><td><?php echo esc_html( $def ); ?></td></tr>
				<?php endforeach; ?>
			</table>
			<p class="description"><?php esc_html_e( 'Medición real: al activar una campaña real, vincular Google Ads con la propiedad de GA4 e importar la conversión "purchase" que este plugin ya envía.', 'ah-gestion-ti' ); ?></p>
		</div>
		<?php
	}
}
