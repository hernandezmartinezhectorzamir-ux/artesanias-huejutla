<?php
/**
 * Arranque de pruebas unitarias (no requiere WordPress ni base de datos).
 *
 * @package AH_Gestion_TI
 */

define( 'AH_GTI_PRUEBAS', true );

$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

require_once dirname( __DIR__ ) . '/wp-content/plugins/ah-gestion-ti/includes/class-ah-calculos.php';
