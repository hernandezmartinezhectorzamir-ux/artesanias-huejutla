<?php
/**
 * Mejora continua (ITIL – Continual Improvement) con el ciclo PDCA.
 *
 * Tipo de contenido privado "ah_reporte_pdca" para registrar reportes
 * periódicos: problema detectado, fuente, propuesta, responsable, fase PDCA,
 * indicador y resultado. Muestra datos REALES del periodo como referencia.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reportes PDCA.
 */
final class AH_Mejora_Continua {

	/** Tipo de contenido. */
	const TIPO = 'ah_reporte_pdca';

	/**
	 * Fuentes de información para detectar mejoras.
	 *
	 * @return array<string,string>
	 */
	public static function fuentes() {
		return array(
			'analytics'      => 'Google Analytics 4',
			'search_console' => 'Google Search Console',
			'rendimiento'    => __( 'Rendimiento del sitio', 'ah-gestion-ti' ),
			'disponibilidad' => __( 'Disponibilidad', 'ah-gestion-ti' ),
			'errores'        => __( 'Errores registrados', 'ah-gestion-ti' ),
			'tickets'        => __( 'Tickets de soporte', 'ah-gestion-ti' ),
			'opiniones'      => __( 'Opiniones de usuarios', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Fases del ciclo PDCA.
	 *
	 * @return array<string,string>
	 */
	public static function fases() {
		return array(
			'planificar' => __( 'Planificar (Plan)', 'ah-gestion-ti' ),
			'hacer'      => __( 'Hacer (Do)', 'ah-gestion-ti' ),
			'verificar'  => __( 'Verificar (Check)', 'ah-gestion-ti' ),
			'actuar'     => __( 'Actuar (Act)', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Campos de texto del reporte.
	 *
	 * @return array<string,array{0:string,1:string}> clave => [etiqueta, tipo]
	 */
	public static function campos() {
		return array(
			'periodo'     => array( __( 'Periodo del reporte (ej. Noviembre 2026)', 'ah-gestion-ti' ), 'text' ),
			'problema'    => array( __( 'Problema u oportunidad detectada', 'ah-gestion-ti' ), 'textarea' ),
			'evidencia'   => array( __( 'Evidencia / dato medido', 'ah-gestion-ti' ), 'textarea' ),
			'propuesta'   => array( __( 'Propuesta de mejora', 'ah-gestion-ti' ), 'textarea' ),
			'responsable' => array( __( 'Responsable', 'ah-gestion-ti' ), 'text' ),
			'fecha'       => array( __( 'Fecha compromiso', 'ah-gestion-ti' ), 'date' ),
			'indicador'   => array( __( 'Indicador y meta (KPI)', 'ah-gestion-ti' ), 'text' ),
			'resultado'   => array( __( 'Resultado obtenido (Verificar)', 'ah-gestion-ti' ), 'textarea' ),
			'accion'      => array( __( 'Acción siguiente (Actuar: estandarizar o ajustar)', 'ah-gestion-ti' ), 'textarea' ),
		);
	}

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'init', array( __CLASS__, 'registrar_tipo' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . self::TIPO, array( __CLASS__, 'guardar' ), 10, 2 );
		add_filter( 'manage_' . self::TIPO . '_posts_columns', array( __CLASS__, 'columnas' ) );
		add_action( 'manage_' . self::TIPO . '_posts_custom_column', array( __CLASS__, 'columna' ), 10, 2 );
	}

	/**
	 * Tipo de contenido privado (solo administración).
	 */
	public static function registrar_tipo() {
		register_post_type(
			self::TIPO,
			array(
				'labels'          => array(
					'name'          => __( 'Reportes de mejora (PDCA)', 'ah-gestion-ti' ),
					'singular_name' => __( 'Reporte de mejora', 'ah-gestion-ti' ),
					'add_new_item'  => __( 'Nuevo reporte de mejora', 'ah-gestion-ti' ),
					'edit_item'     => __( 'Editar reporte de mejora', 'ah-gestion-ti' ),
					'all_items'     => __( 'Mejora continua', 'ah-gestion-ti' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => AH_Tablero::PAGINA,
				'supports'        => array( 'title', 'editor', 'author', 'revisions' ),
				'capability_type' => 'post',
				'capabilities'    => array(
					'edit_posts'         => AH_Instalador::CAP_GESTION,
					'edit_others_posts'  => AH_Instalador::CAP_GESTION,
					'publish_posts'      => AH_Instalador::CAP_GESTION,
					'read_private_posts' => AH_Instalador::CAP_GESTION,
					'delete_posts'       => AH_Instalador::CAP_GESTION,
					'create_posts'       => AH_Instalador::CAP_GESTION,
				),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Cajas de edición.
	 */
	public static function meta_boxes() {
		add_meta_box( 'ah_pdca', __( 'Ciclo PDCA', 'ah-gestion-ti' ), array( __CLASS__, 'caja_pdca' ), self::TIPO, 'normal', 'high' );
		add_meta_box( 'ah_pdca_datos', __( 'Datos reales del sistema (últimos 30 días)', 'ah-gestion-ti' ), array( __CLASS__, 'caja_datos' ), self::TIPO, 'side' );
	}

	/**
	 * Campos del reporte.
	 *
	 * @param WP_Post $post Reporte.
	 */
	public static function caja_pdca( $post ) {
		wp_nonce_field( 'ah_pdca_guardar', 'ah_pdca_nonce' );
		$fase    = get_post_meta( $post->ID, '_ah_fase', true );
		$fuentes = (array) get_post_meta( $post->ID, '_ah_fuentes', true );

		echo '<p><strong>' . esc_html__( 'Fase actual', 'ah-gestion-ti' ) . '</strong><br>';
		foreach ( self::fases() as $clave => $nombre ) {
			echo '<label style="margin-right:14px"><input type="radio" name="ah_fase" value="' . esc_attr( $clave ) . '" ' . checked( $fase ? $fase : 'planificar', $clave, false ) . '> ' . esc_html( $nombre ) . '</label>';
		}
		echo '</p><p><strong>' . esc_html__( 'Fuentes de información', 'ah-gestion-ti' ) . '</strong><br>';
		foreach ( self::fuentes() as $clave => $nombre ) {
			echo '<label style="margin-right:14px"><input type="checkbox" name="ah_fuentes[]" value="' . esc_attr( $clave ) . '" ' . checked( in_array( $clave, $fuentes, true ), true, false ) . '> ' . esc_html( $nombre ) . '</label>';
		}
		echo '</p>';

		foreach ( self::campos() as $clave => list( $etiqueta, $tipo ) ) {
			$valor = (string) get_post_meta( $post->ID, '_ah_' . $clave, true );
			echo '<p><label for="ah_' . esc_attr( $clave ) . '"><strong>' . esc_html( $etiqueta ) . '</strong></label><br>';
			if ( 'textarea' === $tipo ) {
				echo '<textarea id="ah_' . esc_attr( $clave ) . '" name="ah_' . esc_attr( $clave ) . '" rows="3" class="widefat">' . esc_textarea( $valor ) . '</textarea>';
			} else {
				echo '<input type="' . esc_attr( $tipo ) . '" id="ah_' . esc_attr( $clave ) . '" name="ah_' . esc_attr( $clave ) . '" value="' . esc_attr( $valor ) . '" class="widefat">';
			}
			echo '</p>';
		}
	}

	/**
	 * Datos reales para sustentar el reporte (no se inventan).
	 */
	public static function caja_datos() {
		$t = AH_Tickets::estadisticas( 30 );
		$m = AH_Monitoreo::resumen( 30 );
		echo '<ul>';
		echo '<li>' . esc_html__( 'Tickets registrados:', 'ah-gestion-ti' ) . ' <strong>' . (int) $t['total'] . '</strong></li>';
		echo '<li>' . esc_html__( 'Fuera de SLA (abiertos):', 'ah-gestion-ti' ) . ' <strong>' . (int) $t['vencidos'] . '</strong></li>';
		echo '<li>' . esc_html__( 'Cumplimiento de SLA:', 'ah-gestion-ti' ) . ' <strong>' . ( null === $t['cumplimiento_sla'] ? '—' : esc_html( $t['cumplimiento_sla'] . ' %' ) ) . '</strong></li>';
		echo '<li>' . esc_html__( 'Disponibilidad medida:', 'ah-gestion-ti' ) . ' <strong>' . ( null === $m['disponibilidad'] ? esc_html__( 'sin muestras', 'ah-gestion-ti' ) : esc_html( $m['disponibilidad'] . ' %' ) ) . '</strong></li>';
		echo '<li>' . esc_html__( 'Tiempo de respuesta promedio:', 'ah-gestion-ti' ) . ' <strong>' . ( null === $m['promedio_ms'] ? '—' : esc_html( $m['promedio_ms'] . ' ms' ) ) . '</strong></li>';
		echo '</ul><p class="description">' . esc_html__( 'Los datos de Analytics y Search Console se capturan manualmente desde sus paneles una vez vinculada la propiedad.', 'ah-gestion-ti' ) . '</p>';
	}

	/**
	 * Guarda el reporte.
	 *
	 * @param int     $post_id ID.
	 * @param WP_Post $post    Entrada.
	 */
	public static function guardar( $post_id, $post ) {
		if ( ! isset( $_POST['ah_pdca_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ah_pdca_nonce'] ) ), 'ah_pdca_guardar' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || self::TIPO !== $post->post_type ) {
			return;
		}
		$fase = sanitize_key( wp_unslash( $_POST['ah_fase'] ?? 'planificar' ) );
		update_post_meta( $post_id, '_ah_fase', array_key_exists( $fase, self::fases() ) ? $fase : 'planificar' );
		$fuentes = isset( $_POST['ah_fuentes'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['ah_fuentes'] ) ) : array();
		update_post_meta( $post_id, '_ah_fuentes', array_values( array_intersect( $fuentes, array_keys( self::fuentes() ) ) ) );
		foreach ( self::campos() as $clave => list( , $tipo ) ) {
			$nombre = 'ah_' . $clave;
			if ( ! isset( $_POST[ $nombre ] ) ) {
				continue;
			}
			$valor = 'textarea' === $tipo ? sanitize_textarea_field( wp_unslash( $_POST[ $nombre ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $nombre ] ) );
			update_post_meta( $post_id, '_ah_' . $clave, $valor );
		}
	}

	/**
	 * Columnas del listado.
	 *
	 * @param array<string,string> $columnas Columnas.
	 * @return array<string,string>
	 */
	public static function columnas( $columnas ) {
		return array(
			'cb'          => $columnas['cb'],
			'title'       => __( 'Reporte', 'ah-gestion-ti' ),
			'periodo'     => __( 'Periodo', 'ah-gestion-ti' ),
			'fase'        => __( 'Fase PDCA', 'ah-gestion-ti' ),
			'responsable' => __( 'Responsable', 'ah-gestion-ti' ),
			'fecha'       => __( 'Compromiso', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Contenido de las columnas.
	 *
	 * @param string $columna Columna.
	 * @param int    $post_id ID.
	 */
	public static function columna( $columna, $post_id ) {
		if ( 'fase' === $columna ) {
			$fase = get_post_meta( $post_id, '_ah_fase', true );
			echo esc_html( self::fases()[ $fase ] ?? '—' );
			return;
		}
		echo esc_html( (string) get_post_meta( $post_id, '_ah_' . $columna, true ) );
	}
}
