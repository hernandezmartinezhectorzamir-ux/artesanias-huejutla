<?php
/**
 * Activación: tablas de la mesa de ayuda, roles, permisos y tareas programadas.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Instalación y actualización del esquema del plugin.
 */
final class AH_Instalador {

	/** Permiso para atender tickets. */
	const CAP_TICKETS = 'ah_gestionar_tickets';

	/** Permiso para reportes de mejora continua, SEM y monitoreo. */
	const CAP_GESTION = 'ah_gestion_ti';

	/** Rol de agente de mesa de ayuda. */
	const ROL_AGENTE = 'ah_agente_soporte';

	/**
	 * Al activar el plugin.
	 */
	public static function activar() {
		self::crear_tablas();
		self::crear_roles();
		if ( ! wp_next_scheduled( AH_Monitoreo::EVENTO_CRON ) ) {
			wp_schedule_event( time() + 60, 'hourly', AH_Monitoreo::EVENTO_CRON );
		}
		AH_Servicios::registrar_tipo();
		AH_Mejora_Continua::registrar_tipo();
		AH_Mesa_Ayuda_Publica::registrar_endpoint();
		flush_rewrite_rules();
	}

	/**
	 * Al desactivar: solo detiene tareas programadas (no borra datos).
	 */
	public static function desactivar() {
		wp_clear_scheduled_hook( AH_Monitoreo::EVENTO_CRON );
		flush_rewrite_rules();
	}

	/**
	 * Actualiza el esquema si cambió la versión de base de datos.
	 */
	public static function verificar_version() {
		if ( get_option( 'ah_gti_db_version' ) !== AH_GTI_DB_VERSION ) {
			self::crear_tablas();
			self::crear_roles();
		}
	}

	/**
	 * Crea o actualiza las tablas con dbDelta.
	 */
	public static function crear_tablas() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$tickets = $wpdb->prefix . 'ah_tickets';
		$eventos = $wpdb->prefix . 'ah_ticket_eventos';

		dbDelta(
			"CREATE TABLE {$tickets} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				numero varchar(20) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				nombre varchar(120) NOT NULL DEFAULT '',
				correo varchar(190) NOT NULL DEFAULT '',
				asunto varchar(200) NOT NULL DEFAULT '',
				descripcion longtext NOT NULL,
				categoria varchar(40) NOT NULL DEFAULT 'informacion',
				prioridad varchar(20) NOT NULL DEFAULT 'media',
				estado varchar(20) NOT NULL DEFAULT 'abierto',
				canal varchar(20) NOT NULL DEFAULT 'web',
				pedido_id bigint(20) unsigned NOT NULL DEFAULT 0,
				asignado_a bigint(20) unsigned NOT NULL DEFAULT 0,
				creado datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				actualizado datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				vence datetime NULL DEFAULT NULL,
				primera_respuesta datetime NULL DEFAULT NULL,
				resuelto datetime NULL DEFAULT NULL,
				cerrado datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY numero (numero),
				KEY correo (correo),
				KEY user_id (user_id),
				KEY estado (estado)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$eventos} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				ticket_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				autor varchar(120) NOT NULL DEFAULT '',
				tipo varchar(20) NOT NULL DEFAULT 'comentario',
				contenido longtext NOT NULL,
				publico tinyint(1) NOT NULL DEFAULT 1,
				creado datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (id),
				KEY ticket_id (ticket_id)
			) {$charset};"
		);

		$suscriptores = $wpdb->prefix . 'ah_suscriptores';
		dbDelta(
			"CREATE TABLE {$suscriptores} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				correo varchar(190) NOT NULL DEFAULT '',
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				estado varchar(20) NOT NULL DEFAULT 'pendiente',
				token char(32) NOT NULL DEFAULT '',
				origen varchar(40) NOT NULL DEFAULT '',
				ip_huella char(64) NOT NULL DEFAULT '',
				correo_enviado tinyint(1) NOT NULL DEFAULT 0,
				creado datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				consentimiento datetime NULL DEFAULT NULL,
				confirmado datetime NULL DEFAULT NULL,
				baja datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY correo (correo),
				KEY token (token),
				KEY estado (estado)
			) {$charset};"
		);

		update_option( 'ah_gti_db_version', AH_GTI_DB_VERSION, false );
	}

	/**
	 * Rol de agente y permisos para administradores y gerentes de tienda.
	 */
	public static function crear_roles() {
		add_role(
			self::ROL_AGENTE,
			__( 'Agente de mesa de ayuda', 'ah-gestion-ti' ),
			array(
				'read'            => true,
				self::CAP_TICKETS => true,
			)
		);

		$administrador = get_role( 'administrator' );
		if ( $administrador ) {
			$administrador->add_cap( self::CAP_TICKETS );
			$administrador->add_cap( self::CAP_GESTION );
		}

		$gerente = get_role( 'shop_manager' );
		if ( $gerente ) {
			$gerente->add_cap( self::CAP_TICKETS );
		}
	}
}
