<?php
/**
 * Router para el servidor embebido de PHP (solo CI): sirve archivos
 * existentes y envía todo lo demás a WordPress (URLs amigables, sitemaps).
 *
 * @package Artesanias_Huejutla
 */

$ah_ruta = wp_parse_url_local( $_SERVER['REQUEST_URI'] ?? '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
if ( '/' !== $ah_ruta && is_file( getcwd() . $ah_ruta ) ) {
	return false;
}
require getcwd() . '/index.php';

/**
 * Ruta de la URL sin consulta.
 *
 * @param string $url URL solicitada.
 * @return string
 */
function wp_parse_url_local( $url ) {
	return (string) parse_url( $url, PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
}
