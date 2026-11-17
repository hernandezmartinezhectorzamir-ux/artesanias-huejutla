<?php
/**
 * Formulario de búsqueda.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

$ah_id = wp_unique_id( 'ah-busqueda-' );
?>
<form role="search" method="get" class="ah-busqueda" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $ah_id ); ?>" class="screen-reader-text"><?php esc_html_e( 'Buscar', 'artesanias-huejutla' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $ah_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Buscar en el sitio…', 'artesanias-huejutla' ); ?>">
	<button type="submit" class="ah-boton"><?php esc_html_e( 'Buscar', 'artesanias-huejutla' ); ?></button>
</form>
