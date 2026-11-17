<?php
/**
 * Desinstalación del plugin.
 *
 * Por seguridad los tickets NO se borran a menos que se defina
 * la constante AH_GTI_BORRAR_DATOS en wp-config.php.
 *
 * @package AH_Gestion_TI
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_clear_scheduled_hook( 'ah_gti_chequeo_salud' );

if ( defined( 'AH_GTI_BORRAR_DATOS' ) && AH_GTI_BORRAR_DATOS ) {
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ah_ticket_eventos, {$wpdb->prefix}ah_tickets" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange
	foreach ( array( 'ah_gti_ajustes', 'ah_gti_sem', 'ah_gti_muestras', 'ah_gti_db_version', 'ah_gti_pagina_mesa', 'ah_gti_pagina_favoritos' ) as $opcion ) {
		delete_option( $opcion );
	}
	remove_role( 'ah_agente_soporte' );
}
