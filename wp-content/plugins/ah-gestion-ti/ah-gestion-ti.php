<?php
/**
 * Plugin Name:       Artesanías Huejutla – Gestión TI
 * Plugin URI:        https://github.com/
 * Description:       Mesa de ayuda con flujo ITIL, catálogo de servicios, mejora continua (PDCA), campaña SEM simulada, Google Analytics 4 (comercio electrónico), Search Console, monitoreo, favoritos y ajustes de contacto/redes para Artesanías Huejutla.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Equipo ISC – Instituto Tecnológico de Huejutla
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ah-gestion-ti
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

define( 'AH_GTI_VERSION', '1.0.0' );
define( 'AH_GTI_DB_VERSION', '1.1' );
define( 'AH_GTI_ARCHIVO', __FILE__ );
define( 'AH_GTI_DIR', plugin_dir_path( __FILE__ ) );
define( 'AH_GTI_URL', plugin_dir_url( __FILE__ ) );

require_once AH_GTI_DIR . 'includes/class-ah-calculos.php';
require_once AH_GTI_DIR . 'includes/class-ah-instalador.php';
require_once AH_GTI_DIR . 'includes/class-ah-ajustes.php';
require_once AH_GTI_DIR . 'includes/class-ah-tickets.php';
require_once AH_GTI_DIR . 'includes/class-ah-mesa-ayuda-publica.php';
require_once AH_GTI_DIR . 'includes/class-ah-mesa-ayuda-admin.php';
require_once AH_GTI_DIR . 'includes/class-ah-servicios.php';
require_once AH_GTI_DIR . 'includes/class-ah-mejora-continua.php';
require_once AH_GTI_DIR . 'includes/class-ah-sem.php';
require_once AH_GTI_DIR . 'includes/class-ah-analitica.php';
require_once AH_GTI_DIR . 'includes/class-ah-monitoreo.php';
require_once AH_GTI_DIR . 'includes/class-ah-seo.php';
require_once AH_GTI_DIR . 'includes/class-ah-favoritos.php';
require_once AH_GTI_DIR . 'includes/class-ah-tablero.php';
require_once AH_GTI_DIR . 'includes/class-ah-tienda.php';
require_once AH_GTI_DIR . 'includes/class-ah-mis-compras.php';
require_once AH_GTI_DIR . 'includes/class-ah-boletin.php';
require_once AH_GTI_DIR . 'includes/class-ah-correo.php';
require_once AH_GTI_DIR . 'includes/class-ah-verificacion.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once AH_GTI_DIR . 'includes/class-ah-cli.php';
}

register_activation_hook( __FILE__, array( 'AH_Instalador', 'activar' ) );
register_deactivation_hook( __FILE__, array( 'AH_Instalador', 'desactivar' ) );

add_action(
	'plugins_loaded',
	static function () {
		// En init: el rol usa textos traducibles y las traducciones se cargan a partir de init.
		add_action( 'init', array( 'AH_Instalador', 'verificar_version' ), 1 );
		AH_Ajustes::iniciar();
		AH_Mesa_Ayuda_Publica::iniciar();
		AH_Mesa_Ayuda_Admin::iniciar();
		AH_Servicios::iniciar();
		AH_Mejora_Continua::iniciar();
		AH_SEM::iniciar();
		AH_Analitica::iniciar();
		AH_Monitoreo::iniciar();
		AH_SEO::iniciar();
		AH_Favoritos::iniciar();
		AH_Tablero::iniciar();
		AH_Tienda::iniciar();
		AH_Mis_Compras::iniciar();
		AH_Boletin::iniciar();
		AH_Correo::iniciar();
		AH_Verificacion::iniciar();
	}
);

/**
 * Lee un ajuste del plugin (contacto, redes, analítica...).
 *
 * El tema usa esta función para mostrar datos configurables sin
 * escribirlos en el código.
 *
 * @param string $clave       Clave del ajuste.
 * @param mixed  $por_defecto Valor si no existe.
 * @return mixed
 */
function ah_gti_opcion( $clave, $por_defecto = '' ) {
	$opciones = get_option( AH_Ajustes::OPCION, array() );
	return ( isset( $opciones[ $clave ] ) && '' !== $opciones[ $clave ] ) ? $opciones[ $clave ] : $por_defecto;
}

/**
 * URL de WhatsApp con mensaje predeterminado, o cadena vacía si no está configurado.
 *
 * @param string $mensaje Mensaje opcional.
 * @return string
 */
function ah_gti_whatsapp_url( $mensaje = '' ) {
	$numero = AH_Calculos::whatsapp_normalizado( ah_gti_opcion( 'whatsapp' ) );
	if ( '' === $numero ) {
		return '';
	}
	$texto = $mensaje ? $mensaje : ah_gti_opcion( 'whatsapp_mensaje', 'Hola, me interesa una artesanía de Huejutla.' );
	return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $texto );
}

/**
 * Versión de un recurso según su fecha de modificación, para que el navegador
 * descargue la copia nueva en cuanto cambia el archivo.
 *
 * @param string $ruta Ruta relativa a la carpeta del plugin.
 * @return string
 */
function ah_gti_version_archivo( $ruta ) {
	$archivo = AH_GTI_DIR . ltrim( $ruta, '/' );
	return AH_GTI_VERSION . ( file_exists( $archivo ) ? '.' . filemtime( $archivo ) : '' );
}
