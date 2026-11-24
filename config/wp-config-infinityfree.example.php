<?php
/**
 * Plantilla de wp-config.php para hosting compartido sin variables de entorno (InfinityFree).
 *
 * Cómo usarla:
 * 1. Copia este archivo como `wp-config.php` en la carpeta `htdocs/` del hosting (por FTP o el
 *    administrador de archivos). NUNCA lo subas al repositorio con los datos reales.
 * 2. Reemplaza cada valor ENTRE_SIGNOS por el dato del panel de InfinityFree:
 *    Panel de control → MySQL Databases (nombre, usuario, servidor) y la contraseña de la cuenta de hosting.
 * 3. Genera claves nuevas en https://api.wordpress.org/secret-key/1.1/salt/ y pégalas abajo.
 *
 * @package Artesanias_Huejutla
 */

// ** Base de datos MySQL del hosting (InfinityFree la incluye gratis; WordPress necesita MySQL/MariaDB). ** //
define( 'DB_NAME', '<NOMBRE_BD>' );          // p. ej. if0_12345678_artesanias
define( 'DB_USER', '<USUARIO_BD>' );         // p. ej. if0_12345678
define( 'DB_PASSWORD', '<CONTRASENA_BD>' );  // la contraseña de la cuenta de hosting
define( 'DB_HOST', '<SERVIDOR_BD>' );        // p. ej. sql123.infinityfree.com
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// ** Claves únicas: pega aquí las 8 líneas generadas en api.wordpress.org/secret-key/1.1/salt/ ** //
// define( 'AUTH_KEY', '...' ); ... define( 'NONCE_SALT', '...' );

$table_prefix = 'wp_'; // Debe coincidir con el prefijo de la base importada (la local usa wp_).

// ** Dirección pública del sitio (con https cuando el certificado SSL esté activo). ** //
define( 'WP_HOME', 'https://<TU_SUBDOMINIO>.infinityfreeapp.com' );
define( 'WP_SITEURL', 'https://<TU_SUBDOMINIO>.infinityfreeapp.com' );

// ** Seguridad y operación ** //
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'FORCE_SSL_ADMIN', true );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_MEMORY_LIMIT', '256M' );
define( 'WP_CACHE', true ); // WP Super Cache; desactívalo (false) si el hosting da errores de caché.

// ** Correo saliente (opcional): SMTP de Brevo u otro servicio. Sin estas líneas no se envían correos. ** //
// define( 'AH_SMTP_HOST', 'smtp-relay.brevo.com' );
// define( 'AH_SMTP_PORT', 587 );
// define( 'AH_SMTP_SECURE', 'tls' );
// define( 'AH_SMTP_USER', '<USUARIO_SMTP>' );
// define( 'AH_SMTP_PASS', '<CLAVE_SMTP>' );
// define( 'AH_SMTP_FROM', '<CORREO_REMITENTE_VERIFICADO>' );
// No definas AH_CORREO_LOCAL aquí: solo es para el equipo local.

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
