<?php
/**
 * Plantilla de wp-config.php para PRODUCCIÓN (sin credenciales).
 *
 * Copiar como wp-config.php en el servidor y leer los valores desde
 * variables de entorno (.env fuera de la carpeta pública). Nunca versionar
 * el archivo real.
 *
 * @package Artesanias_Huejutla
 */

$ah_env = static function ( $clave, $defecto = '' ) {
	$valor = getenv( $clave );
	return false === $valor ? $defecto : $valor;
};

define( 'DB_NAME', $ah_env( 'DB_NAME' ) );
define( 'DB_USER', $ah_env( 'DB_USER' ) );
define( 'DB_PASSWORD', $ah_env( 'DB_PASSWORD' ) );
define( 'DB_HOST', $ah_env( 'DB_HOST', 'localhost' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $ah_clave ) {
	define( $ah_clave, $ah_env( $ah_clave ) );
}

$table_prefix = 'ah_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Prefijo propio en lugar de wp_.

define( 'WP_ENVIRONMENT_TYPE', $ah_env( 'WP_ENVIRONMENT_TYPE', 'production' ) );
define( 'WP_HOME', $ah_env( 'WP_HOME' ) );
define( 'WP_SITEURL', $ah_env( 'WP_SITEURL' ) );

// Seguridad.
define( 'DISALLOW_FILE_EDIT', true );      // Sin editor de archivos en el administrador.
define( 'FORCE_SSL_ADMIN', true );         // Administración solo por HTTPS.
define( 'WP_AUTO_UPDATE_CORE', 'minor' );  // Actualizaciones de seguridad automáticas.

// Errores: se registran fuera de la carpeta pública y nunca se muestran.
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', '/var/log/artesanias/php-wp.log' );
define( 'WP_DEBUG_DISPLAY', false );

// WP-Cron real del sistema: */5 * * * * wp cron event run --due-now --path=/var/www/artesanias
define( 'DISABLE_WP_CRON', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
