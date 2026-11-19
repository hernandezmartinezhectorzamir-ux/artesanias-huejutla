<?php
/**
 * Funciones del tema Artesanías Huejutla.
 *
 * El tema se encarga de la presentación. La lógica de negocio (mesa de ayuda,
 * analítica, SEO técnico, favoritos, monitoreo) vive en el plugin
 * "Artesanías Huejutla – Gestión TI" para que no se pierda al cambiar de tema.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

define( 'AH_TEMA_VERSION', '1.0.0' );
define( 'AH_TEMA_DIR', get_template_directory() );
define( 'AH_TEMA_URI', get_template_directory_uri() );

require_once AH_TEMA_DIR . '/inc/configuracion.php';
require_once AH_TEMA_DIR . '/inc/recursos.php';
require_once AH_TEMA_DIR . '/inc/iconos.php';
require_once AH_TEMA_DIR . '/inc/plantilla.php';
require_once AH_TEMA_DIR . '/inc/personalizador.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once AH_TEMA_DIR . '/inc/woocommerce.php';
	require_once AH_TEMA_DIR . '/inc/checkout.php';
}
