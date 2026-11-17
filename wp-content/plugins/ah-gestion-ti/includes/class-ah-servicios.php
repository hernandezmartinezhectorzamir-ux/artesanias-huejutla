<?php
/**
 * Catálogo de servicios (ITIL – Service Catalogue Management).
 *
 * Tipo de contenido "ah_servicio" con archivo público en /servicios/ y campos:
 * beneficios, disponibilidad/condiciones, canal de contacto y enlace del canal.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Servicios administrables desde WordPress.
 */
final class AH_Servicios {

	/** Tipo de contenido. */
	const TIPO = 'ah_servicio';

	/** Campos personalizados. */
	const CAMPOS = array( 'beneficios', 'disponibilidad', 'canal', 'canal_url', 'icono' );

	/**
	 * Íconos disponibles (el tema los dibuja como SVG).
	 *
	 * @return array<string,string>
	 */
	public static function iconos() {
		return array(
			'bolsa'      => __( 'Bolsa de compras', 'ah-gestion-ti' ),
			'chat'       => __( 'Atención / chat', 'ah-gestion-ti' ),
			'camion'     => __( 'Envío / camión', 'ah-gestion-ti' ),
			'paquete'    => __( 'Paquete / seguimiento', 'ah-gestion-ti' ),
			'devolucion' => __( 'Devolución', 'ah-gestion-ti' ),
			'soporte'    => __( 'Soporte técnico', 'ah-gestion-ti' ),
			'manos'      => __( 'Artesanos', 'ah-gestion-ti' ),
			'ayuda'      => __( 'Asistencia', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'init', array( __CLASS__, 'registrar_tipo' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post_' . self::TIPO, array( __CLASS__, 'guardar' ), 10, 2 );
		add_shortcode( 'ah_catalogo_servicios', array( __CLASS__, 'shortcode' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'orden_archivo' ) );
	}

	/**
	 * Registra el tipo de contenido.
	 */
	public static function registrar_tipo() {
		register_post_type(
			self::TIPO,
			array(
				'labels'          => array(
					'name'          => __( 'Servicios', 'ah-gestion-ti' ),
					'singular_name' => __( 'Servicio', 'ah-gestion-ti' ),
					'add_new_item'  => __( 'Agregar servicio', 'ah-gestion-ti' ),
					'edit_item'     => __( 'Editar servicio', 'ah-gestion-ti' ),
					'all_items'     => __( 'Catálogo de servicios', 'ah-gestion-ti' ),
				),
				'public'          => true,
				'has_archive'     => 'servicios',
				'rewrite'         => array(
					'slug'       => 'servicios',
					'with_front' => false,
				),
				'menu_icon'       => 'dashicons-clipboard',
				'show_in_menu'    => AH_Tablero::PAGINA,
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
				'capability_type' => 'page',
			)
		);
		foreach ( self::CAMPOS as $campo ) {
			register_post_meta(
				self::TIPO,
				'_ah_' . $campo,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => static fn() => current_user_can( 'edit_pages' ),
				)
			);
		}
	}

	/**
	 * Ordena el archivo por "orden" del menú.
	 *
	 * @param WP_Query $consulta Consulta.
	 */
	public static function orden_archivo( $consulta ) {
		if ( ! is_admin() && $consulta->is_main_query() && $consulta->is_post_type_archive( self::TIPO ) ) {
			$consulta->set(
				'orderby',
				array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				)
			);
			$consulta->set( 'posts_per_page', 50 );
		}
	}

	/**
	 * Caja de campos.
	 */
	public static function meta_box() {
		add_meta_box( 'ah_servicio_datos', __( 'Datos del servicio', 'ah-gestion-ti' ), array( __CLASS__, 'dibujar_meta_box' ), self::TIPO, 'normal', 'high' );
	}

	/**
	 * Dibuja los campos.
	 *
	 * @param WP_Post $post Entrada.
	 */
	public static function dibujar_meta_box( $post ) {
		wp_nonce_field( 'ah_servicio_guardar', 'ah_servicio_nonce' );
		$v = static fn( $c ) => get_post_meta( $post->ID, '_ah_' . $c, true );
		?>
		<p><label for="ah_beneficios"><strong><?php esc_html_e( 'Beneficios (uno por línea)', 'ah-gestion-ti' ); ?></strong></label>
		<textarea id="ah_beneficios" name="ah_beneficios" rows="4" class="widefat"><?php echo esc_textarea( $v( 'beneficios' ) ); ?></textarea></p>
		<p><label for="ah_disponibilidad"><strong><?php esc_html_e( 'Disponibilidad o condiciones de atención', 'ah-gestion-ti' ); ?></strong></label>
		<input id="ah_disponibilidad" name="ah_disponibilidad" class="widefat" value="<?php echo esc_attr( $v( 'disponibilidad' ) ); ?>"></p>
		<p><label for="ah_canal"><strong><?php esc_html_e( 'Canal de contacto', 'ah-gestion-ti' ); ?></strong></label>
		<input id="ah_canal" name="ah_canal" class="widefat" value="<?php echo esc_attr( $v( 'canal' ) ); ?>"></p>
		<p><label for="ah_canal_url"><strong><?php esc_html_e( 'Enlace del canal (página interna o URL)', 'ah-gestion-ti' ); ?></strong></label>
		<input id="ah_canal_url" name="ah_canal_url" class="widefat" value="<?php echo esc_attr( $v( 'canal_url' ) ); ?>"></p>
		<p><label for="ah_icono"><strong><?php esc_html_e( 'Ícono', 'ah-gestion-ti' ); ?></strong></label>
		<select id="ah_icono" name="ah_icono">
			<?php foreach ( self::iconos() as $clave => $nombre ) : ?>
				<option value="<?php echo esc_attr( $clave ); ?>" <?php selected( $v( 'icono' ), $clave ); ?>><?php echo esc_html( $nombre ); ?></option>
			<?php endforeach; ?>
		</select></p>
		<?php
	}

	/**
	 * Guarda los campos con verificación de nonce y permisos.
	 *
	 * @param int     $post_id ID.
	 * @param WP_Post $post    Entrada.
	 */
	public static function guardar( $post_id, $post ) {
		if ( ! isset( $_POST['ah_servicio_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ah_servicio_nonce'] ) ), 'ah_servicio_guardar' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || self::TIPO !== $post->post_type ) {
			return;
		}
		update_post_meta( $post_id, '_ah_beneficios', sanitize_textarea_field( wp_unslash( $_POST['ah_beneficios'] ?? '' ) ) );
		update_post_meta( $post_id, '_ah_disponibilidad', sanitize_text_field( wp_unslash( $_POST['ah_disponibilidad'] ?? '' ) ) );
		update_post_meta( $post_id, '_ah_canal', sanitize_text_field( wp_unslash( $_POST['ah_canal'] ?? '' ) ) );
		update_post_meta( $post_id, '_ah_canal_url', sanitize_text_field( wp_unslash( $_POST['ah_canal_url'] ?? '' ) ) );
		$icono = sanitize_key( wp_unslash( $_POST['ah_icono'] ?? '' ) );
		update_post_meta( $post_id, '_ah_icono', array_key_exists( $icono, self::iconos() ) ? $icono : 'ayuda' );
	}

	/**
	 * Datos listos para mostrar de un servicio.
	 *
	 * @param WP_Post|int $post Servicio.
	 * @return array<string,mixed>
	 */
	public static function datos( $post ) {
		$post = get_post( $post );
		$url  = (string) get_post_meta( $post->ID, '_ah_canal_url', true );
		if ( $url && 0 === strpos( $url, '/' ) ) {
			$url = home_url( $url ); // Enlaces internos relativos.
		}
		return array(
			'titulo'         => get_the_title( $post ),
			'descripcion'    => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ),
			'beneficios'     => array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $post->ID, '_ah_beneficios', true ) ) ) ),
			'disponibilidad' => (string) get_post_meta( $post->ID, '_ah_disponibilidad', true ),
			'canal'          => (string) get_post_meta( $post->ID, '_ah_canal', true ),
			'canal_url'      => $url,
			'icono'          => (string) get_post_meta( $post->ID, '_ah_icono', true ),
			'enlace'         => get_permalink( $post ),
		);
	}

	/**
	 * Tarjeta HTML de un servicio (usada por el shortcode y el tema).
	 *
	 * @param WP_Post|int $post Servicio.
	 * @return string
	 */
	public static function tarjeta( $post ) {
		$d     = self::datos( $post );
		$icono = function_exists( 'ah_tema_icono' ) ? ah_tema_icono( $d['icono'] ? $d['icono'] : 'ayuda' ) : '';
		ob_start();
		?>
		<article class="ah-servicio">
			<?php if ( $icono ) : ?>
				<span class="ah-servicio__icono" aria-hidden="true"><?php echo $icono; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG interno del tema. ?></span>
			<?php endif; ?>
			<h3 class="ah-servicio__titulo"><a href="<?php echo esc_url( $d['enlace'] ); ?>"><?php echo esc_html( $d['titulo'] ); ?></a></h3>
			<p><?php echo esc_html( $d['descripcion'] ); ?></p>
			<?php if ( $d['beneficios'] ) : ?>
				<h4><?php esc_html_e( 'Beneficios', 'ah-gestion-ti' ); ?></h4>
				<ul class="ah-servicio__beneficios">
					<?php foreach ( $d['beneficios'] as $beneficio ) : ?>
						<li><?php echo esc_html( $beneficio ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<dl class="ah-servicio__datos">
				<?php if ( $d['disponibilidad'] ) : ?>
					<div><dt><?php esc_html_e( 'Disponibilidad', 'ah-gestion-ti' ); ?></dt><dd><?php echo esc_html( $d['disponibilidad'] ); ?></dd></div>
				<?php endif; ?>
				<?php if ( $d['canal'] ) : ?>
					<div><dt><?php esc_html_e( 'Canal', 'ah-gestion-ti' ); ?></dt><dd><?php echo esc_html( $d['canal'] ); ?></dd></div>
				<?php endif; ?>
			</dl>
			<?php if ( $d['canal_url'] ) : ?>
				<a class="ah-boton ah-boton--secundario" href="<?php echo esc_url( $d['canal_url'] ); ?>"><?php esc_html_e( 'Solicitar este servicio', 'ah-gestion-ti' ); ?></a>
			<?php endif; ?>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Shortcode [ah_catalogo_servicios].
	 *
	 * @return string
	 */
	public static function shortcode() {
		$servicios = get_posts(
			array(
				'post_type'      => self::TIPO,
				'posts_per_page' => 50,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);
		if ( ! $servicios ) {
			return '<p>' . esc_html__( 'Aún no hay servicios publicados.', 'ah-gestion-ti' ) . '</p>';
		}
		return '<div class="ah-servicios">' . implode( '', array_map( array( __CLASS__, 'tarjeta' ), $servicios ) ) . '</div>';
	}
}
