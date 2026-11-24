<?php
/**
 * Comandos WP-CLI del proyecto.
 *
 *   wp ah sembrar   Configura WooCommerce/Rank Math y carga el contenido inicial (idempotente).
 *   wp ah chequeo   Ejecuta un chequeo de disponibilidad y muestra el resultado.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Herramientas de línea de comandos de Artesanías Huejutla.
 */
class AH_CLI {

	/**
	 * Mapa slug => ID de imágenes importadas.
	 *
	 * @var array<string,int>
	 */
	private $imagenes = array();

	/**
	 * Carga la configuración y el contenido inicial de la tienda.
	 *
	 * ## EJEMPLOS
	 *
	 *     wp ah sembrar
	 *
	 * @when after_wp_load
	 */
	public function sembrar() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::error( 'WooCommerce debe estar activo.' );
		}
		$contenido = require AH_GTI_DIR . 'datos-demo/contenido.php';
		$paginas   = require AH_GTI_DIR . 'datos-demo/paginas.php';

		$this->ajustes_generales();
		$this->ajustes_woocommerce();
		$this->envios();
		$this->pagos();
		$this->importar_imagenes( $contenido['imagenes'] );
		$categorias = $this->categorias( $contenido['categorias'] );
		$this->productos( $contenido['productos'], $categorias );
		$this->cupones( $contenido['cupones'] );
		$this->paginas_woocommerce();
		$ids_paginas = $this->paginas( $paginas['paginas'] );
		$this->blog( $paginas['blog'] );
		$this->servicios( $contenido['servicios'] );
		$this->lectura( $ids_paginas );
		$this->menus( $ids_paginas, $categorias );
		$this->rank_math();
		$this->ajustes_plugin();

		flush_rewrite_rules();
		WP_CLI::success( 'Contenido y configuración cargados.' );
	}

	/**
	 * Sincroniza solo el catálogo (imágenes, categorías y productos) con datos-demo/contenido.php.
	 *
	 * Crea los productos nuevos y actualiza nombre, precios, descripciones e imágenes de los
	 * existentes, sin tocar sus existencias (ventas reales), páginas ni ajustes.
	 *
	 * ## EJEMPLOS
	 *
	 *     wp ah productos
	 *
	 * @subcommand productos
	 *
	 * @when after_wp_load
	 */
	public function productos_cmd() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			WP_CLI::error( 'WooCommerce debe estar activo.' );
		}
		$contenido = require AH_GTI_DIR . 'datos-demo/contenido.php';
		$this->importar_imagenes( $contenido['imagenes'] );
		$categorias = $this->categorias( $contenido['categorias'] );
		$this->productos( $contenido['productos'], $categorias, true );
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}
		WP_CLI::success( 'Catálogo sincronizado (existencias conservadas).' );
	}

	/**
	 * Ejecuta un chequeo de disponibilidad real.
	 *
	 * @when after_wp_load
	 */
	public function chequeo() {
		foreach ( AH_Monitoreo::chequear() as $m ) {
			WP_CLI::line( sprintf( '%-8s HTTP %s  %s ms %s', $m['url'], $m['codigo'], $m['ms'], $m['error'] ) );
		}
	}

	/* ------------------------------------------------------------------ */

	/**
	 * Ajustes generales de WordPress.
	 */
	private function ajustes_generales() {
		update_option( 'blogname', 'Artesanías Huejutla' );
		update_option( 'blogdescription', 'Artesanías auténticas de la Huasteca Hidalguense, 100% hechas a mano' );
		update_option( 'timezone_string', 'America/Mexico_City' );
		update_option( 'date_format', 'j \d\e F \d\e Y' );
		update_option( 'time_format', 'H:i' );
		update_option( 'start_of_week', 1 );
		update_option( 'permalink_structure', '/%postname%/' );
		update_option( 'posts_per_page', 9 );
		update_option( 'default_comment_status', 'closed' );
		update_option(
			'woocommerce_permalinks',
			array(
				'product_base'           => 'producto',
				'category_base'          => 'categoria',
				'tag_base'               => 'etiqueta-producto',
				'attribute_base'         => '',
				'use_verbose_page_rules' => false,
			)
		);

		// Elimina el contenido de ejemplo de WordPress.
		foreach ( array( 'hola-mundo', 'pagina-ejemplo' ) as $slug ) {
			$post = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );
			if ( $post ) {
				wp_delete_post( $post->ID, true );
			}
		}
		$admin = get_user_by( 'login', 'admin_ah' );
		if ( $admin ) {
			wp_update_user(
				array(
					'ID'           => $admin->ID,
					'display_name' => 'Equipo Artesanías Huejutla',
					'first_name'   => 'Equipo',
					'last_name'    => 'Artesanías Huejutla',
				)
			);
		}
		WP_CLI::log( '✔ Ajustes generales' );
	}

	/**
	 * Moneda, ubicación, inventario y cuentas.
	 */
	private function ajustes_woocommerce() {
		$opciones = array(
			'woocommerce_default_country'                  => 'MX:HG',
			'woocommerce_store_city'                       => 'Huejutla de Reyes',
			'woocommerce_store_postcode'                   => '43000',
			'woocommerce_currency'                         => 'MXN',
			'woocommerce_currency_pos'                     => 'left',
			'woocommerce_price_thousand_sep'               => ',',
			'woocommerce_price_decimal_sep'                => '.',
			'woocommerce_price_num_decimals'               => 2,
			'woocommerce_allowed_countries'                => 'specific',
			'woocommerce_specific_allowed_countries'       => array( 'MX' ),
			'woocommerce_ship_to_countries'                => '',
			'woocommerce_calc_taxes'                       => 'no',
			'woocommerce_manage_stock'                     => 'yes',
			'woocommerce_notify_low_stock_amount'          => 3,
			'woocommerce_notify_no_stock_amount'           => 0,
			'woocommerce_hide_out_of_stock_items'          => 'no',
			'woocommerce_stock_format'                     => '',
			'woocommerce_enable_reviews'                   => 'yes',
			'woocommerce_review_rating_verification_required' => 'yes',
			'woocommerce_review_rating_verification_label' => 'yes',
			'woocommerce_enable_guest_checkout'            => 'yes',
			'woocommerce_enable_checkout_login_reminder'   => 'yes',
			'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
			'woocommerce_enable_myaccount_registration'    => 'yes',
			'woocommerce_registration_generate_password'   => 'no',
			'woocommerce_coming_soon'                      => 'no',
			'woocommerce_store_pages_only'                 => 'no',
			'woocommerce_task_list_hidden'                 => 'yes',
			'woocommerce_onboarding_profile'               => array( 'skipped' => true ),
			'woocommerce_registration_privacy_policy_text' => 'Usaremos tus datos personales para administrar tu cuenta y mejorar tu experiencia en este sitio. Consulta nuestra [privacy_policy].',
			'woocommerce_checkout_privacy_policy_text'     => 'Usaremos tus datos personales para procesar tu pedido y mejorar tu experiencia en este sitio. Consulta nuestra [privacy_policy].',
			'woocommerce_email_from_name'                  => 'Artesanías Huejutla',
			'woocommerce_weight_unit'                      => 'kg',
			'woocommerce_dimension_unit'                   => 'cm',
		);
		foreach ( $opciones as $clave => $valor ) {
			update_option( $clave, $valor );
		}
		WP_CLI::log( '✔ WooCommerce: MXN, Huejutla (Hidalgo), inventario y cuentas' );
	}

	/**
	 * Zona de envío México con estándar, express, gratis y recolección.
	 */
	private function envios() {
		$zona = null;
		foreach ( WC_Shipping_Zones::get_zones() as $datos ) {
			if ( 'México' === $datos['zone_name'] ) {
				$zona = new WC_Shipping_Zone( $datos['id'] );
			}
		}
		if ( $zona ) {
			WP_CLI::log( '• Zona de envío "México" ya existe' );
			return;
		}
		$zona = new WC_Shipping_Zone();
		$zona->set_zone_name( 'México' );
		$zona->set_zone_order( 1 );
		$zona->add_location( 'MX', 'country' );
		$zona->save();

		$metodos = array(
			array(
				'flat_rate',
				array(
					'title'      => 'Envío estándar (3 a 7 días hábiles)',
					'cost'       => '120',
					'tax_status' => 'none',
				),
			),
			array(
				'flat_rate',
				array(
					'title'      => 'Envío express (1 a 3 días hábiles)',
					'cost'       => '220',
					'tax_status' => 'none',
				),
			),
			array(
				'free_shipping',
				array(
					'title'            => 'Envío estándar gratis',
					'requires'         => 'either',
					'min_amount'       => '299',
					'ignore_discounts' => 'no',
				),
			),
			array(
				'local_pickup',
				array(
					'title'      => 'Recoger en taller (Huejutla de Reyes)',
					'cost'       => '0',
					'tax_status' => 'none',
				),
			),
		);
		foreach ( $metodos as list( $tipo, $ajustes ) ) {
			$instancia = $zona->add_shipping_method( $tipo );
			$opcion    = 'woocommerce_' . $tipo . '_' . $instancia . '_settings';
			update_option( $opcion, array_merge( (array) get_option( $opcion, array() ), $ajustes ) );
		}
		WP_CLI::log( '✔ Envíos: estándar $120, express $220, gratis desde $299, recoger en taller' );
	}

	/**
	 * Métodos de pago fuera de línea (no procesan dinero). Las pasarelas con
	 * tarjeta se dejan documentadas para configurarse con credenciales reales.
	 */
	private function pagos() {
		update_option(
			'woocommerce_bacs_settings',
			array_merge(
				(array) get_option( 'woocommerce_bacs_settings', array() ),
				array(
					'enabled'      => 'yes',
					'title'        => 'Transferencia bancaria (SPEI)',
					'description'  => 'Recibirás los datos bancarios por correo. Tu pedido se enviará al confirmar el pago.',
					'instructions' => 'Realiza la transferencia usando tu número de pedido como referencia.',
				)
			)
		);
		update_option(
			'woocommerce_cod_settings',
			array_merge(
				(array) get_option( 'woocommerce_cod_settings', array() ),
				array(
					'enabled'      => 'yes',
					'title'        => 'Pago contra entrega',
					'description'  => 'Paga en efectivo al recibir tu pedido.',
					'instructions' => 'Ten listo el importe exacto al recibir tu paquete.',
				)
			)
		);
		update_option( 'woocommerce_cheque_settings', array_merge( (array) get_option( 'woocommerce_cheque_settings', array() ), array( 'enabled' => 'no' ) ) );
		WP_CLI::log( '✔ Pagos: transferencia y contra entrega (sin procesamiento de tarjetas)' );
	}

	/**
	 * Importa las fotografías con título, ALT y pie de foto.
	 *
	 * @param array $imagenes Definición de imágenes.
	 */
	private function importar_imagenes( array $imagenes ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $imagenes as $archivo => list( $titulo, $alt, $pie ) ) {
			$slug      = sanitize_title( pathinfo( $archivo, PATHINFO_FILENAME ) );
			$existente = get_posts(
				array(
					'post_type'   => 'attachment',
					'name'        => $slug,
					'numberposts' => 1,
					'post_status' => 'inherit',
				)
			);
			if ( $existente ) {
				$this->imagenes[ $archivo ] = $existente[0]->ID;
				continue;
			}
			$temporal = wp_tempnam( $archivo );
			copy( AH_GTI_DIR . 'datos-demo/img/' . $archivo, $temporal );
			$id = media_handle_sideload(
				array(
					'name'     => $archivo,
					'tmp_name' => $temporal,
				),
				0,
				$titulo,
				array(
					'post_name'    => $slug,
					'post_excerpt' => $pie,
					'post_content' => $alt,
				)
			);
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( $archivo . ': ' . $id->get_error_message() );
				continue;
			}
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			$this->imagenes[ $archivo ] = $id;
		}
		WP_CLI::log( '✔ Imágenes: ' . count( $this->imagenes ) . ' con título, ALT y pie de foto' );
	}

	/**
	 * Categorías de producto con imagen y SEO.
	 *
	 * @param array $categorias Definición.
	 * @return array<string,int> slug => term_id
	 */
	private function categorias( array $categorias ) {
		$ids = array();
		foreach ( $categorias as $slug => list( $nombre, $descripcion, $imagen ) ) {
			$termino = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $termino ) {
				$id = $termino->term_id;
				wp_update_term(
					$id,
					'product_cat',
					array(
						'name'        => $nombre,
						'description' => $descripcion,
					)
				);
			} else {
				$nuevo = wp_insert_term(
					$nombre,
					'product_cat',
					array(
						'slug'        => $slug,
						'description' => $descripcion,
					)
				);
				$id    = $nuevo['term_id'];
			}
			update_term_meta( $id, 'thumbnail_id', $this->imagenes[ $imagen ] ?? 0 );
			update_term_meta( $id, 'rank_math_title', $nombre . ' hechos a mano en Huejutla | Artesanías Huejutla' );
			update_term_meta( $id, 'rank_math_description', $descripcion . ' Envíos a todo México.' );
			update_term_meta( $id, 'rank_math_focus_keyword', mb_strtolower( $nombre ) );
			$ids[ $slug ] = (int) $id;
		}
		WP_CLI::log( '✔ Categorías: ' . count( $ids ) );
		return $ids;
	}

	/**
	 * Productos con inventario, atributos, galería y SEO.
	 *
	 * @param array             $productos  Definición.
	 * @param array<string,int> $categorias Categorías.
	 * @param bool              $conservar_existencias Si el producto ya existe, no cambia sus existencias.
	 */
	private function productos( array $productos, array $categorias, $conservar_existencias = false ) {
		$creados = 0;
		foreach ( $productos as $orden => $p ) {
			list( $sku, $nombre, $slug, $categoria, $precio, $oferta, $stock, $destacado, $imagenes, $corta, $larga, $materiales, $origen, $medidas, $clave ) = $p;

			$id       = wc_get_product_id_by_sku( $sku );
			$producto = $id ? wc_get_product( $id ) : new WC_Product_Simple();
			$producto->set_name( $nombre );
			$producto->set_slug( $slug );
			$producto->set_sku( $sku );
			$producto->set_status( 'publish' );
			$producto->set_catalog_visibility( 'visible' );
			$producto->set_menu_order( $orden );
			$producto->set_featured( $destacado );
			$producto->set_regular_price( (string) $precio );
			$producto->set_sale_price( $oferta ? (string) $oferta : '' );
			$producto->set_manage_stock( true );
			if ( ! ( $id && $conservar_existencias ) ) {
				$producto->set_stock_quantity( $stock );
			}
			$producto->set_low_stock_amount( 3 );
			$producto->set_backorders( 'no' );
			$producto->set_short_description( $corta );
			$producto->set_description(
				'<p>' . $larga . '</p>' .
				'<h3>Cuidados</h3><ul><li>Mantener en un lugar seco y ventilado.</li><li>Limpiar con un paño seco o una brocha suave.</li>' .
				( false !== strpos( $categoria, 'textil' ) ? '<li>Lavar a mano con agua fría y jabón neutro; secar a la sombra.</li>' : '<li>Evitar mojar la palma; si se humedece, secar a la sombra.</li>' ) .
				'</ul><p>Al ser una pieza hecha a mano, puede presentar pequeñas variaciones de color, forma y medida respecto a las fotografías.</p>'
			);
			$producto->set_category_ids( array( $categorias[ $categoria ] ) );
			$producto->set_reviews_allowed( true );

			$ids_img = array_values( array_filter( array_map( fn( $i ) => $this->imagenes[ $i ] ?? 0, $imagenes ) ) );
			if ( $ids_img ) {
				$producto->set_image_id( $ids_img[0] );
				$producto->set_gallery_image_ids( array_slice( $ids_img, 1 ) );
			}

			$atributos = array();
			foreach ( array(
				'Materiales'           => $materiales,
				'Lugar de elaboración' => $origen,
				'Medidas'              => $medidas,
			) as $etiqueta => $valor ) {
				$atributo = new WC_Product_Attribute();
				$atributo->set_name( $etiqueta );
				$atributo->set_options( array( $valor ) );
				$atributo->set_visible( true );
				$atributo->set_variation( false );
				$atributos[] = $atributo;
			}
			$producto->set_attributes( $atributos );
			$nuevo_id = $producto->save();

			update_post_meta( $nuevo_id, 'rank_math_title', $nombre . ' | Artesanía hecha a mano en Huejutla' );
			update_post_meta( $nuevo_id, 'rank_math_description', mb_substr( $corta . ' Hecho en ' . $origen . '. Envíos a todo México.', 0, 160 ) );
			update_post_meta( $nuevo_id, 'rank_math_focus_keyword', $clave . ',artesanías Huejutla' );
			++$creados;
		}
		WP_CLI::log( '✔ Productos: ' . $creados );
	}

	/**
	 * Cupones de descuento.
	 *
	 * @param array $cupones Definición.
	 */
	private function cupones( array $cupones ) {
		foreach ( $cupones as list( $codigo, $tipo, $monto, $minimo, $envio, $descripcion ) ) {
			$id    = wc_get_coupon_id_by_code( $codigo );
			$cupon = new WC_Coupon( $id ? $id : 0 );
			$cupon->set_code( $codigo );
			$cupon->set_discount_type( $tipo );
			$cupon->set_amount( $monto );
			$cupon->set_minimum_amount( $minimo );
			$cupon->set_free_shipping( $envio );
			$cupon->set_description( $descripcion );
			$cupon->set_individual_use( true );
			$cupon->save();
		}
		WP_CLI::log( '✔ Cupones: HUASTECA10, ENVIOGRATIS, ARTESANO50' );
	}

	/**
	 * Traduce las páginas de WooCommerce y usa los shortcodes clásicos
	 * (integración total con el tema y los eventos de GA4).
	 */
	private function paginas_woocommerce() {
		$paginas = array(
			'shop'      => array( 'Tienda', 'tienda', '' ),
			'cart'      => array( 'Carrito', 'carrito', '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->' ),
			'checkout'  => array( 'Finalizar compra', 'finalizar-compra', '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->' ),
			'myaccount' => array( 'Mi cuenta', 'mi-cuenta', '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->' ),
		);
		foreach ( $paginas as $clave => list( $titulo, $slug, $contenido ) ) {
			$id = wc_get_page_id( $clave );
			if ( $id > 0 ) {
				$datos = array(
					'ID'         => $id,
					'post_title' => $titulo,
					'post_name'  => $slug,
				);
				if ( $contenido ) {
					$datos['post_content'] = $contenido;
				}
				wp_update_post( $datos );
			}
		}
		update_post_meta( wc_get_page_id( 'shop' ), 'rank_math_title', 'Tienda de artesanías mexicanas hechas a mano | Artesanías Huejutla' );
		update_post_meta( wc_get_page_id( 'shop' ), 'rank_math_description', 'Compra artesanías de la Huasteca: canastas de palma tejida, textiles bordados, decoración y regalos mexicanos hechos a mano. Envíos a todo México.' );
		update_post_meta( wc_get_page_id( 'shop' ), 'rank_math_focus_keyword', 'comprar artesanías Hidalgo' );

		// La página de ejemplo de reembolsos en inglés se reemplaza por la política propia.
		$reembolsos = get_page_by_path( 'refund_returns' );
		if ( $reembolsos ) {
			wp_delete_post( $reembolsos->ID, true );
		}
		WP_CLI::log( '✔ Páginas de WooCommerce en español con shortcodes clásicos' );
	}

	/**
	 * Reemplaza los marcadores de enlaces internos.
	 *
	 * @param string $html Contenido.
	 * @return string
	 */
	private function enlaces( $html ) {
		return preg_replace_callback(
			'/\{\{(producto|categoria|pagina):([a-z0-9-]+)\}\}|\{\{tienda\}\}/',
			static function ( $m ) {
				if ( '{{tienda}}' === $m[0] ) {
					return wc_get_page_permalink( 'shop' );
				}
				if ( 'producto' === $m[1] ) {
					$post = get_page_by_path( $m[2], OBJECT, 'product' );
					return $post ? get_permalink( $post ) : home_url( '/' );
				}
				if ( 'categoria' === $m[1] ) {
					$termino = get_term_by( 'slug', $m[2], 'product_cat' );
					return $termino ? get_term_link( $termino ) : home_url( '/' );
				}
				$pagina = get_page_by_path( $m[2] );
				return $pagina ? get_permalink( $pagina ) : home_url( '/' . $m[2] . '/' );
			},
			$html
		);
	}

	/**
	 * Convierte HTML simple a bloques de Gutenberg (editable en el editor).
	 *
	 * @param string $html HTML con un elemento por línea.
	 * @return string
	 */
	private function bloques( $html ) {
		$lineas = preg_split( '/\n(?=<h2|<p|<ul|\[)/', trim( $html ) );
		$salida = '';
		foreach ( $lineas as $bloque ) {
			$bloque = trim( $bloque );
			if ( 0 === strpos( $bloque, '<h2' ) ) {
				$salida .= "<!-- wp:heading -->\n" . str_replace( '<h2>', '<h2 class="wp-block-heading">', $bloque ) . "\n<!-- /wp:heading -->\n\n";
			} elseif ( 0 === strpos( $bloque, '<ul' ) ) {
				$items   = preg_replace( '/<li>(.*?)<\/li>/s', "<!-- wp:list-item -->\n<li>$1</li>\n<!-- /wp:list-item -->", $bloque );
				$salida .= "<!-- wp:list -->\n" . str_replace( '<ul>', '<ul class="wp-block-list">', $items ) . "\n<!-- /wp:list -->\n\n";
			} elseif ( 0 === strpos( $bloque, '[' ) ) {
				$salida .= "<!-- wp:shortcode -->\n" . $bloque . "\n<!-- /wp:shortcode -->\n\n";
			} else {
				$salida .= "<!-- wp:paragraph -->\n" . $bloque . "\n<!-- /wp:paragraph -->\n\n";
			}
		}
		return $salida;
	}

	/**
	 * Crea o actualiza una entrada por slug.
	 *
	 * @param string $tipo  Tipo de contenido.
	 * @param string $slug  Slug.
	 * @param array  $datos Datos de wp_insert_post.
	 * @return int
	 */
	private function guardar_entrada( $tipo, $slug, array $datos ) {
		$existente = get_page_by_path( $slug, OBJECT, $tipo );
		$datos     = array_merge(
			array(
				'post_type'   => $tipo,
				'post_name'   => $slug,
				'post_status' => 'publish',
				'post_author' => (int) ( get_user_by( 'login', 'admin_ah' )->ID ?? 1 ),
			),
			$datos
		);
		if ( $existente ) {
			$datos['ID'] = $existente->ID;
			return (int) wp_update_post( $datos );
		}
		return (int) wp_insert_post( $datos );
	}

	/**
	 * Páginas institucionales.
	 *
	 * @param array $paginas Definición.
	 * @return array<string,int>
	 */
	private function paginas( array $paginas ) {
		$ids = array();

		// Primero se crean todas para que los enlaces internos se resuelvan.
		foreach ( $paginas as $slug => $p ) {
			if ( 'politica-de-privacidad' === $slug && (int) get_option( 'wp_page_for_privacy_policy' ) ) {
				$ids[ $slug ] = (int) get_option( 'wp_page_for_privacy_policy' );
				wp_update_post(
					array(
						'ID'          => $ids[ $slug ],
						'post_name'   => $slug,
						'post_status' => 'publish',
					)
				);
				continue;
			}
			$ids[ $slug ] = $this->guardar_entrada( 'page', $slug, array( 'post_title' => $p[0] ) );
		}
		foreach ( $paginas as $slug => list( $titulo, $contenido, $plantilla, $meta_titulo, $meta_desc, $clave ) ) {
			wp_update_post(
				array(
					'ID'           => $ids[ $slug ],
					'post_title'   => $titulo,
					'post_content' => $this->bloques( $this->enlaces( $contenido ) ),
				)
			);
			update_post_meta( $ids[ $slug ], '_wp_page_template', $plantilla ? $plantilla : 'default' );
			update_post_meta( $ids[ $slug ], 'rank_math_title', $meta_titulo );
			update_post_meta( $ids[ $slug ], 'rank_math_description', $meta_desc );
			if ( $clave ) {
				update_post_meta( $ids[ $slug ], 'rank_math_focus_keyword', $clave );
			}
		}
		update_option( 'ah_gti_pagina_mesa', $ids['mesa-de-ayuda'] );
		update_option( 'ah_gti_pagina_favoritos', $ids['favoritos'] );
		update_option( 'ah_gti_pagina_rastreo', $ids['rastrear-pedido'] );
		update_post_meta( $ids['rastrear-pedido'], 'rank_math_robots', array( 'noindex', 'follow' ) );
		update_option( 'wp_page_for_privacy_policy', $ids['politica-de-privacidad'] );
		update_option( 'woocommerce_terms_page_id', $ids['terminos-y-condiciones'] );
		update_post_meta( $ids['favoritos'], 'rank_math_robots', array( 'noindex', 'follow' ) );

		// Portada y blog.
		$ids['inicio'] = $this->guardar_entrada(
			'page',
			'inicio',
			array(
				'post_title'   => 'Inicio',
				'post_content' => '',
			)
		);
		$ids['blog']   = $this->guardar_entrada(
			'page',
			'blog',
			array(
				'post_title'   => 'Blog',
				'post_content' => '',
			)
		);
		update_post_meta( $ids['inicio'], 'rank_math_title', 'Artesanías de Huejutla, Hidalgo | 100% Hechas a Mano' );
		update_post_meta( $ids['inicio'], 'rank_math_description', 'Compra artesanías originales de Huejutla: cestería y textiles elaborados a mano por artesanos de la Huasteca. Envíos a todo México.' );
		update_post_meta( $ids['inicio'], 'rank_math_focus_keyword', 'artesanías Huejutla,canasta de palma tejida,artesanías de la Huasteca,comprar artesanías Hidalgo,artesanías mexicanas hechas a mano' );
		update_post_meta( $ids['blog'], 'rank_math_title', 'Blog de artesanías de la Huasteca | Artesanías Huejutla' );
		update_post_meta( $ids['blog'], 'rank_math_description', 'Historias, tradiciones y guías sobre artesanías mexicanas hechas a mano en Huejutla y la Huasteca Hidalguense.' );

		WP_CLI::log( '✔ Páginas: ' . count( $ids ) );
		return $ids;
	}

	/**
	 * Artículos del blog con imagen destacada, categoría, SEO y productos relacionados.
	 *
	 * @param array $articulos Definición.
	 */
	private function blog( array $articulos ) {
		$fecha = strtotime( '-40 days' );
		foreach ( $articulos as $i => list( $titulo, $slug, $categoria, $imagen, $extracto, $contenido, $relacionados, $meta_titulo, $meta_desc, $clave ) ) {
			$termino = term_exists( $categoria, 'category' );
			if ( ! $termino ) {
				$termino = wp_insert_term( $categoria, 'category' );
			}
			$id = $this->guardar_entrada(
				'post',
				$slug,
				array(
					'post_title'    => $titulo,
					'post_excerpt'  => $extracto,
					'post_content'  => $this->bloques( $this->enlaces( $contenido ) ),
					'post_category' => array( (int) $termino['term_id'] ),
					'post_date'     => gmdate( 'Y-m-d 10:00:00', $fecha + $i * 7 * DAY_IN_SECONDS ),
				)
			);
			set_post_thumbnail( $id, $this->imagenes[ $imagen ] ?? 0 );
			$ids_rel = array();
			foreach ( $relacionados as $slug_producto ) {
				$producto_rel = get_page_by_path( $slug_producto, OBJECT, 'product' );
				if ( $producto_rel ) {
					$ids_rel[] = $producto_rel->ID;
				}
			}
			update_post_meta( $id, '_ah_productos_relacionados', $ids_rel );
			update_post_meta( $id, 'rank_math_title', $meta_titulo . ' | Artesanías Huejutla' );
			update_post_meta( $id, 'rank_math_description', $meta_desc );
			update_post_meta( $id, 'rank_math_focus_keyword', $clave );
		}
		$sin = get_term_by( 'slug', 'sin-categoria', 'category' );
		if ( $sin && 0 === (int) $sin->count ) {
			update_option( 'default_category', (int) term_exists( 'Cultura huasteca', 'category' )['term_id'] );
		}
		WP_CLI::log( '✔ Blog: ' . count( $articulos ) . ' artículos' );
	}

	/**
	 * Catálogo de servicios ITIL.
	 *
	 * @param array $servicios Definición.
	 */
	private function servicios( array $servicios ) {
		foreach ( $servicios as $orden => list( $titulo, $slug, $descripcion, $beneficios, $disponibilidad, $canal, $enlace, $icono ) ) {
			$id = $this->guardar_entrada(
				AH_Servicios::TIPO,
				$slug,
				array(
					'post_title'   => $titulo,
					'post_excerpt' => $descripcion,
					'post_content' => "<!-- wp:paragraph -->\n<p>" . $descripcion . "</p>\n<!-- /wp:paragraph -->",
					'menu_order'   => $orden,
				)
			);
			update_post_meta( $id, '_ah_beneficios', $beneficios );
			update_post_meta( $id, '_ah_disponibilidad', $disponibilidad );
			update_post_meta( $id, '_ah_canal', $canal );
			update_post_meta( $id, '_ah_canal_url', $enlace );
			update_post_meta( $id, '_ah_icono', $icono );
		}
		WP_CLI::log( '✔ Servicios: ' . count( $servicios ) );
	}

	/**
	 * Portada estática y página de entradas.
	 *
	 * @param array<string,int> $ids Páginas.
	 */
	private function lectura( array $ids ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['inicio'] );
		update_option( 'page_for_posts', $ids['blog'] );
		WP_CLI::log( '✔ Portada estática y blog' );
	}

	/**
	 * Menús del encabezado y del pie.
	 *
	 * @param array<string,int> $paginas    Páginas.
	 * @param array<string,int> $categorias Categorías.
	 */
	private function menus( array $paginas, array $categorias ) {
		$crear  = static function ( $nombre ) {
			$menu = wp_get_nav_menu_object( $nombre );
			if ( $menu ) {
				foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
					wp_delete_post( $item->ID, true );
				}
				return (int) $menu->term_id;
			}
			return (int) wp_create_nav_menu( $nombre );
		};
		$pagina = static function ( $menu, $id, $padre = 0, $titulo = '' ) {
			return wp_update_nav_menu_item(
				$menu,
				0,
				array(
					'menu-item-object-id' => $id,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $padre,
					'menu-item-title'     => $titulo,
				)
			);
		};
		$enlace = static function ( $menu, $url, $titulo, $padre = 0 ) {
			return wp_update_nav_menu_item(
				$menu,
				0,
				array(
					'menu-item-url'       => $url,
					'menu-item-title'     => $titulo,
					'menu-item-type'      => 'custom',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $padre,
				)
			);
		};

		$principal = $crear( 'Principal' );
		$pagina( $principal, $paginas['inicio'] );
		$pagina( $principal, wc_get_page_id( 'shop' ), 0, 'Tienda' );
		$cats = $enlace( $principal, wc_get_page_permalink( 'shop' ), 'Categorías' );
		foreach ( $categorias as $id ) {
			wp_update_nav_menu_item(
				$principal,
				0,
				array(
					'menu-item-object-id' => $id,
					'menu-item-object'    => 'product_cat',
					'menu-item-type'      => 'taxonomy',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $cats,
				)
			);
		}
		$nosotros = $pagina( $principal, $paginas['nosotros'] );
		$pagina( $principal, $paginas['historia-de-los-artesanos'], $nosotros );
		$pagina( $principal, $paginas['blog'] );
		$enlace( $principal, get_post_type_archive_link( AH_Servicios::TIPO ), 'Servicios' );
		$pagina( $principal, $paginas['contacto'] );

		$tienda = $crear( 'Pie – Tienda' );
		$pagina( $tienda, wc_get_page_id( 'shop' ), 0, 'Todos los productos' );
		foreach ( array_slice( $categorias, 0, 4, true ) as $id ) {
			wp_update_nav_menu_item(
				$tienda,
				0,
				array(
					'menu-item-object-id' => $id,
					'menu-item-object'    => 'product_cat',
					'menu-item-type'      => 'taxonomy',
					'menu-item-status'    => 'publish',
				)
			);
		}
		$pagina( $tienda, $paginas['favoritos'] );

		$ayuda = $crear( 'Pie – Ayuda' );
		$pagina( $ayuda, $paginas['preguntas-frecuentes'] );
		$pagina( $ayuda, $paginas['rastrear-pedido'] );
		$pagina( $ayuda, $paginas['mesa-de-ayuda'] );
		$enlace( $ayuda, get_post_type_archive_link( AH_Servicios::TIPO ), 'Catálogo de servicios' );
		$pagina( $ayuda, $paginas['envios-y-devoluciones'], 0, 'Envíos y devoluciones' );
		$pagina( $ayuda, wc_get_page_id( 'myaccount' ) );
		$pagina( $ayuda, $paginas['contacto'] );

		$legal = $crear( 'Pie – Legal' );
		$pagina( $legal, $paginas['politica-de-privacidad'] );
		$pagina( $legal, $paginas['terminos-y-condiciones'] );
		$pagina( $legal, $paginas['envios-y-devoluciones'], 0, 'Envíos y devoluciones' );

		set_theme_mod(
			'nav_menu_locations',
			array(
				'principal'  => $principal,
				'pie_tienda' => $tienda,
				'pie_ayuda'  => $ayuda,
				'pie_legal'  => $legal,
			)
		);
		WP_CLI::log( '✔ Menús principal y de pie de página' );
	}

	/**
	 * Configuración de Rank Math (sitemap, títulos, imágenes y migas de pan).
	 */
	private function rank_math() {
		if ( ! defined( 'RANK_MATH_VERSION' ) ) {
			WP_CLI::warning( 'Rank Math no está activo; se usará el respaldo SEO del plugin.' );
			return;
		}
		update_option( 'rank_math_modules', array( 'link-counter', 'seo-analysis', 'sitemap', 'rich-snippet', 'woocommerce', 'image-seo' ) );

		$general = (array) get_option( 'rank-math-options-general', array() );
		$general = array_merge(
			$general,
			array(
				'add_img_alt'              => 'on',
				'img_alt_format'           => '%title% %count(alt)%',
				'add_img_title'            => 'on',
				'img_title_format'         => '%title% %count(title)%',
				'breadcrumbs'              => 'on',
				'breadcrumbs_home_label'   => 'Inicio',
				'wc_remove_generator'      => 'on',
				'strip_category_base'      => 'off',
				'setup_mode'               => 'advanced',
				'attachment_redirect_urls' => 'on',
			)
		);
		update_option( 'rank-math-options-general', $general );

		$titulos = (array) get_option( 'rank-math-options-titles', array() );
		$titulos = array_merge(
			$titulos,
			array(
				'title_separator'                 => '|',
				'knowledgegraph_type'             => 'company',
				'knowledgegraph_name'             => 'Artesanías Huejutla',
				'pt_product_title'                => '%title% %sep% %sitename%',
				'pt_product_default_rich_snippet' => 'product',
				'pt_post_default_rich_snippet'    => 'article',
				'pt_ah_servicio_title'            => '%title% %sep% Servicios %sep% %sitename%',
				'noindex_empty_taxonomies'        => 'on',
				'disable_author_archives'         => 'on',
				'noindex_search'                  => 'on',
				'pt_page_default_rich_snippet'    => 'off',
				'open_graph_image'                => wp_get_attachment_image_url( $this->imagenes['puesto-cesteria-palma-huejutla.jpg'] ?? 0, 'large' ),
				'open_graph_image_id'             => $this->imagenes['puesto-cesteria-palma-huejutla.jpg'] ?? 0,
			)
		);
		update_option( 'rank-math-options-titles', $titulos );

		$sitemap = (array) get_option( 'rank-math-options-sitemap', array() );
		$sitemap = array_merge(
			$sitemap,
			array(
				'include_images'          => 'on',
				'include_featured_image'  => 'on',
				'authors_sitemap'         => 'off',
				'pt_post_sitemap'         => 'on',
				'pt_page_sitemap'         => 'on',
				'pt_product_sitemap'      => 'on',
				'pt_ah_servicio_sitemap'  => 'on',
				'pt_attachment_sitemap'   => 'off',
				'tax_category_sitemap'    => 'on',
				'tax_product_cat_sitemap' => 'on',
				'tax_post_tag_sitemap'    => 'off',
				'tax_product_tag_sitemap' => 'off',
				'exclude_posts'           => (string) get_option( 'ah_gti_pagina_favoritos' ) . ',' . wc_get_page_id( 'cart' ) . ',' . wc_get_page_id( 'checkout' ) . ',' . wc_get_page_id( 'myaccount' ),
			)
		);
		update_option( 'rank-math-options-sitemap', $sitemap );
		update_option( 'rank_math_wizard_completed', true );
		update_option( 'rank_math_registration_skip', true );
		WP_CLI::log( '✔ Rank Math: sitemap dinámico, títulos, imágenes y migas de pan' );
	}

	/**
	 * Ajustes iniciales del plugin (solo datos reales y verificables).
	 */
	private function ajustes_plugin() {
		$actual = (array) get_option( AH_Ajustes::OPCION, array() );
		$base   = array(
			'direccion'    => 'Huejutla de Reyes, Hidalgo, México. C.P. 43000',
			'metodos_pago' => array( 'transferencia', 'contraentrega' ),
		);
		update_option( AH_Ajustes::OPCION, array_merge( $base, array_filter( $actual ) ) );
		WP_CLI::log( '✔ Ajustes del plugin (redes, GA4 y Search Console quedan pendientes de datos reales)' );
	}
}

WP_CLI::add_command( 'ah', 'AH_CLI' );
