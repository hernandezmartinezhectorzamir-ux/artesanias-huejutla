<?php
/**
 * Textos editables desde Apariencia → Personalizar.
 *
 * @package Artesanias_Huejutla
 */

defined( 'ABSPATH' ) || exit;

/**
 * Textos por defecto de la portada.
 *
 * @return array<string,string>
 */
function ah_tema_textos_defecto() {
	return array(
		'ah_hero_titulo'    => 'Artesanías Auténticas de la Huasteca Hidalguense',
		'ah_hero_destacado' => 'Huasteca Hidalguense',
		'ah_hero_texto'     => 'Tradición, cultura y talento artesanal en cada pieza. Descubre productos únicos elaborados a mano por artesanos de Huejutla.',
		'ah_anuncio'        => '',
	);
}

/**
 * Lee un texto del personalizador.
 *
 * @param string $clave Clave.
 * @return string
 */
function ah_tema_texto( $clave ) {
	$defecto = ah_tema_textos_defecto();
	return (string) get_theme_mod( $clave, $defecto[ $clave ] ?? '' );
}

add_action(
	'customize_register',
	static function ( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_section(
			'ah_portada',
			array(
				'title'    => __( 'Portada de Artesanías Huejutla', 'artesanias-huejutla' ),
				'priority' => 30,
			)
		);
		$campos  = array(
			'ah_hero_titulo'    => array( __( 'Título principal (H1)', 'artesanias-huejutla' ), 'text' ),
			'ah_hero_destacado' => array( __( 'Parte del título resaltada en cursiva', 'artesanias-huejutla' ), 'text' ),
			'ah_hero_texto'     => array( __( 'Descripción', 'artesanias-huejutla' ), 'textarea' ),
			'ah_anuncio'        => array( __( 'Barra de anuncio (vacío = mensaje de envío gratis automático)', 'artesanias-huejutla' ), 'text' ),
		);
		$defecto = ah_tema_textos_defecto();
		foreach ( $campos as $clave => list( $etiqueta, $tipo ) ) {
			$wp_customize->add_setting(
				$clave,
				array(
					'default'           => $defecto[ $clave ],
					'sanitize_callback' => 'textarea' === $tipo ? 'sanitize_textarea_field' : 'sanitize_text_field',
				)
			);
			$wp_customize->add_control(
				$clave,
				array(
					'label'   => $etiqueta,
					'section' => 'ah_portada',
					'type'    => $tipo,
				)
			);
		}
	}
);
