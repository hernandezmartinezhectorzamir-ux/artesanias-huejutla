<?php
/**
 * Íconos SVG en línea (sin dependencias externas, accesibles y ligeros).
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Devuelve un ícono SVG.
 *
 * @param string $nombre Nombre del ícono.
 * @param int    $tamano Tamaño en píxeles.
 * @return string SVG seguro (contenido interno del tema).
 */
function ah_tema_icono( $nombre, $tamano = 22 ) {
	$trazos = array(
		'buscar'     => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4.2-4.2"/>',
		'usuario'    => '<circle cx="12" cy="8" r="4"/><path d="M4.5 21c.8-4.4 3.5-7 7.5-7s6.7 2.6 7.5 7"/>',
		'corazon'    => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
		'bolsa'      => '<path d="M5 8h14l-1 12.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20.5Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'menu'       => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'cerrar'     => '<path d="m6 6 12 12M18 6 6 18"/>',
		'flecha'     => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
		'abajo'      => '<path d="m6 9 6 6 6-6"/>',
		'camion'     => '<path d="M3 6h11v10H3zM14 9.5h4l3 3.5v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17.5" cy="18" r="2"/>',
		'manos'      => '<path d="M7 11V6.5a1.5 1.5 0 0 1 3 0V10m0-.5V5a1.5 1.5 0 0 1 3 0v5m0-1.5a1.5 1.5 0 0 1 3 0V11m0-1a1.5 1.5 0 0 1 3 0v4c0 4-2.7 7-7 7-2.6 0-4.4-1.3-6-3.5L4.2 14a1.6 1.6 0 0 1 2.6-1.8L8 13.5"/>',
		'ubicacion'  => '<path d="M20 10c0 5.5-8 12-8 12s-8-6.5-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/>',
		'telefono'   => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
		'correo'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
		'reloj'      => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'video'      => '<rect x="2.5" y="6" width="13" height="12" rx="2"/><path d="m15.5 10.5 6-3.5v10l-6-3.5"/>',
		'estrella'   => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9Z"/>',
		'check'      => '<path d="m5 12.5 4.5 4.5L19 7"/>',
		'escudo'     => '<path d="M12 3 4 6v6c0 5 3.4 8.3 8 9 4.6-.7 8-4 8-9V6Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
		'chat'       => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-5A8 8 0 1 1 21 12Z"/>',
		'regalo'     => '<rect x="3" y="8" width="18" height="5" rx="1"/><path d="M5 13v8h14v-8M12 8v13M12 8c-1.5-3.5-6-4-6-1.2C6 8 8 8 12 8Zm0 0c1.5-3.5 6-4 6-1.2C18 8 16 8 12 8Z"/>',
		'hoja'       => '<path d="M5 19c0-9 6-14 15-15-1 9-6 15-15 15Z"/><path d="M5 19 13 11"/>',
		'paquete'    => '<path d="m3 7.5 9-4.5 9 4.5v9L12 21l-9-4.5Z"/><path d="m3 7.5 9 4.5 9-4.5M12 12v9"/>',
		'devolucion' => '<path d="M4 9h11a5 5 0 0 1 0 10H8"/><path d="m8 5-4 4 4 4"/>',
		'soporte'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m5.6 5.6 3.6 3.6m5.6 5.6 3.6 3.6m0-12.8-3.6 3.6m-5.6 5.6-3.6 3.6"/>',
		'ayuda'      => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.3a2.6 2.6 0 0 1 5 1c0 1.8-2.5 2.2-2.5 3.8M12 17.2v.1"/>',
		'compartir'  => '<circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.2 10.8 7.6-4.5m-7.6 6.9 7.6 4.5"/>',
		'enlace'     => '<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1"/>',
		'tarjeta'    => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19M6 15h4"/>',
		'efectivo'   => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/><path d="M6 9.5v5M18 9.5v5"/>',
		'banco'      => '<path d="m3 9 9-5 9 5M5 10v7m4.5-7v7m5-7v7M19 10v7M3 20h18"/>',
		'arriba'     => '<path d="m6 15 6-6 6 6"/>',
		'casa'       => '<path d="m3 11 9-7 9 7"/><path d="M5 10v10h14V10M10 20v-6h4v6"/>',
		'campana'    => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
		'repetir'    => '<path d="M17 2l4 4-4 4"/><path d="M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4"/><path d="M21 13v2a3 3 0 0 1-3 3H3"/>',
		'imprimir'   => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/>',
		'recibo'     => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2Z"/><path d="M9 8h6M9 12h6"/>',
		'copiar'     => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
		'llave'      => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 9.2-9.2M17 6l3 3M14 9l2 2"/>',
		'facebook'   => '<path d="M14 8.5h3V5h-3a4 4 0 0 0-4 4v2.5H7.5V15H10v7h3.5v-7H16l.8-3.5h-3.3V9.2c0-.4.3-.7.5-.7Z" fill="currentColor" stroke="none"/>',
		'instagram'  => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="1" fill="currentColor"/>',
		'tiktok'     => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5" /><path d="M14 3c.5 2.8 2.3 4.6 5.5 5"/>',
		'whatsapp'   => '<path d="M20.5 11.8a8.5 8.5 0 0 1-12.6 7.5L3.5 20.5l1.3-4.2a8.5 8.5 0 1 1 15.7-4.5Z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2-1-1 .9c-1.2-.5-2.3-1.6-2.8-2.8l.9-1-1-2Z" fill="currentColor" stroke="none"/>',
		'x'          => '<path d="M4 4l16 16M20 4 4 20" />',
		'pinterest'  => '<circle cx="12" cy="12" r="9"/><path d="M10.5 20.5 12.5 12m-1 3.5c3.5 1 6-1.5 6-4.5 0-3-2.5-5-5.5-5S6.5 8.5 6.5 11c0 1.3.5 2.3 1.3 2.8"/>',
	);
	$trazo  = $trazos[ $nombre ] ?? $trazos['ayuda'];
	return sprintf(
		'<svg class="ah-icono ah-icono--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $nombre ),
		(int) $tamano,
		$trazo
	);
}

/**
 * Logotipo: rombo inspirado en las grecas huastecas.
 *
 * @return string
 */
function ah_tema_logo_svg() {
	return '<svg class="ah-logo__marca" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M24 2 46 24 24 46 2 24Z" fill="currentColor"/><path d="M24 10 38 24 24 38 10 24Z" fill="none" stroke="#F7F1E6" stroke-width="2"/><path d="M24 17 31 24 24 31 17 24Z" fill="#F7F1E6"/><path d="M24 21 27 24 24 27 21 24Z" fill="currentColor"/></svg>';
}
