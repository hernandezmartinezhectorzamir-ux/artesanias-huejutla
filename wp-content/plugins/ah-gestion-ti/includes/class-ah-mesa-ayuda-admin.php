<?php
/**
 * Mesa de ayuda – interfaz administrativa para el personal.
 *
 * Requiere el permiso ah_gestionar_tickets (administradores, gerentes de
 * tienda y el rol "Agente de mesa de ayuda").
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Listado, detalle y gestión de tickets.
 */
final class AH_Mesa_Ayuda_Admin {

	/** Slug de la página. */
	const PAGINA = 'ah-mesa-ayuda';

	/**
	 * Registra hooks.
	 */
	public static function iniciar() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_ah_ticket_gestionar', array( __CLASS__, 'guardar' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'estilos' ) );
	}

	/**
	 * Estilos del administrador.
	 *
	 * @param string $hook Página actual.
	 */
	public static function estilos( $hook ) {
		if ( false !== strpos( $hook, 'ah-' ) || false !== strpos( $hook, 'ah_' ) ) {
			wp_enqueue_style( 'ah-gti-admin', AH_GTI_URL . 'assets/css/admin.css', array(), AH_GTI_VERSION );
		}
	}

	/**
	 * Submenú con contador de tickets abiertos.
	 */
	public static function menu() {
		global $wpdb;
		$tabla    = AH_Tickets::tabla();
		$abiertos = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla} WHERE estado IN ('abierto','en_proceso')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Consulta sin datos externos.
		$burbuja  = $abiertos ? ' <span class="awaiting-mod">' . $abiertos . '</span>' : '';

		add_submenu_page(
			AH_Tablero::PAGINA,
			__( 'Mesa de ayuda', 'ah-gestion-ti' ),
			__( 'Mesa de ayuda', 'ah-gestion-ti' ) . $burbuja,
			AH_Instalador::CAP_TICKETS,
			self::PAGINA,
			array( __CLASS__, 'pagina' )
		);
	}

	/**
	 * Enruta entre listado y detalle.
	 */
	public static function pagina() {
		if ( ! current_user_can( AH_Instalador::CAP_TICKETS ) ) {
			wp_die( esc_html__( 'No tienes permiso para ver los tickets.', 'ah-gestion-ti' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navegación de solo lectura.
		$id = isset( $_GET['ticket'] ) ? absint( $_GET['ticket'] ) : 0;
		echo '<div class="wrap ah-admin">';
		if ( $id ) {
			self::detalle( $id );
		} else {
			self::listado();
		}
		echo '</div>';
	}

	/**
	 * Listado con filtros.
	 */
	private static function listado() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Filtros de solo lectura.
		$filtros = array(
			'estado'     => isset( $_GET['estado'] ) ? sanitize_key( $_GET['estado'] ) : '',
			'categoria'  => isset( $_GET['categoria'] ) ? sanitize_key( $_GET['categoria'] ) : '',
			'prioridad'  => isset( $_GET['prioridad'] ) ? sanitize_key( $_GET['prioridad'] ) : '',
			'buscar'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'vencidos'   => ! empty( $_GET['vencidos'] ),
			'pagina'     => isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1,
			'por_pagina' => 20,
		);
		if ( isset( $_GET['mios'] ) ) {
			$filtros['asignado_a'] = get_current_user_id();
		}
		// phpcs:enable
		$resultado = AH_Tickets::listar( $filtros );
		$estados   = AH_Tickets::estados();
		$cats      = AH_Tickets::categorias();
		$prios     = AH_Tickets::prioridades();

		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Mesa de ayuda', 'ah-gestion-ti' ) . '</h1>';
		echo '<a class="page-title-action" href="' . esc_url( AH_Mesa_Ayuda_Publica::url_pagina() ) . '" target="_blank">' . esc_html__( 'Ver formulario público', 'ah-gestion-ti' ) . '</a><hr class="wp-header-end">';

		$stats = AH_Tickets::estadisticas( 0 );
		echo '<ul class="subsubsub">';
		echo '<li><a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Todos', 'ah-gestion-ti' ) . ' <span class="count">(' . (int) $stats['total'] . ')</span></a> | </li>';
		foreach ( $estados as $clave => $nombre ) {
			echo '<li><a href="' . esc_url( self::url( array( 'estado' => $clave ) ) ) . '"' . ( $filtros['estado'] === $clave ? ' class="current"' : '' ) . '>' . esc_html( $nombre ) . ' <span class="count">(' . (int) ( $stats['por_estado'][ $clave ] ?? 0 ) . ')</span></a> | </li>';
		}
		echo '<li><a href="' . esc_url( self::url( array( 'mios' => 1 ) ) ) . '">' . esc_html__( 'Asignados a mí', 'ah-gestion-ti' ) . '</a> | </li>';
		echo '<li><a href="' . esc_url( self::url( array( 'vencidos' => 1 ) ) ) . '" class="ah-texto-alerta">' . esc_html__( 'Fuera de SLA', 'ah-gestion-ti' ) . ' (' . (int) $stats['vencidos'] . ')</a></li>';
		echo '</ul>';

		?>
		<form method="get" class="ah-filtros">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGINA ); ?>">
			<?php
			self::filtro_select( 'categoria', __( 'Todas las categorías', 'ah-gestion-ti' ), $cats, $filtros['categoria'] );
			self::filtro_select( 'prioridad', __( 'Todas las prioridades', 'ah-gestion-ti' ), $prios, $filtros['prioridad'] );
			self::filtro_select( 'estado', __( 'Todos los estados', 'ah-gestion-ti' ), $estados, $filtros['estado'] );
			?>
			<input type="search" name="s" value="<?php echo esc_attr( $filtros['buscar'] ); ?>" placeholder="<?php esc_attr_e( 'Número, asunto o correo', 'ah-gestion-ti' ); ?>">
			<?php submit_button( __( 'Filtrar', 'ah-gestion-ti' ), 'secondary', '', false ); ?>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead><tr>
				<th style="width:140px"><?php esc_html_e( 'Número', 'ah-gestion-ti' ); ?></th>
				<th><?php esc_html_e( 'Asunto', 'ah-gestion-ti' ); ?></th>
				<th><?php esc_html_e( 'Categoría', 'ah-gestion-ti' ); ?></th>
				<th style="width:90px"><?php esc_html_e( 'Prioridad', 'ah-gestion-ti' ); ?></th>
				<th style="width:110px"><?php esc_html_e( 'Estado', 'ah-gestion-ti' ); ?></th>
				<th><?php esc_html_e( 'Asignado a', 'ah-gestion-ti' ); ?></th>
				<th style="width:150px"><?php esc_html_e( 'SLA', 'ah-gestion-ti' ); ?></th>
			</tr></thead>
			<tbody>
			<?php if ( ! $resultado['filas'] ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No hay tickets con estos filtros.', 'ah-gestion-ti' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $resultado['filas'] as $t ) : ?>
				<?php $agente = $t['asignado_a'] ? get_userdata( (int) $t['asignado_a'] ) : null; ?>
				<tr>
					<td><strong><a href="<?php echo esc_url( self::url( array( 'ticket' => (int) $t['id'] ) ) ); ?>"><?php echo esc_html( $t['numero'] ); ?></a></strong></td>
					<td><?php echo esc_html( $t['asunto'] ); ?><br><small><?php echo esc_html( $t['nombre'] . ' · ' . $t['correo'] ); ?></small></td>
					<td><?php echo esc_html( $cats[ $t['categoria'] ] ?? $t['categoria'] ); ?><br><small><?php echo esc_html( AH_Tickets::tipo_itil( $t['categoria'] ) ); ?></small></td>
					<td><span class="ah-pill ah-pill--<?php echo esc_attr( $t['prioridad'] ); ?>"><?php echo esc_html( $prios[ $t['prioridad'] ] ?? '' ); ?></span></td>
					<td><span class="ah-pill ah-pill--<?php echo esc_attr( $t['estado'] ); ?>"><?php echo esc_html( $estados[ $t['estado'] ] ?? '' ); ?></span></td>
					<td><?php echo $agente ? esc_html( $agente->display_name ) : '<em>' . esc_html__( 'Sin asignar', 'ah-gestion-ti' ) . '</em>'; ?></td>
					<td><?php self::sla( $t ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$paginas = (int) ceil( $resultado['total'] / $filtros['por_pagina'] );
		if ( $paginas > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $filtros['pagina'],
						'total'   => $paginas,
					)
				)
			);
			echo '</div></div>';
		}
	}

	/**
	 * Indicador de SLA.
	 *
	 * @param array $t Ticket.
	 */
	private static function sla( array $t ) {
		if ( in_array( $t['estado'], array( 'resuelto', 'cerrado' ), true ) ) {
			$en_tiempo = $t['resuelto'] && $t['vence'] && strtotime( $t['resuelto'] ) <= strtotime( $t['vence'] );
			echo $en_tiempo ? '<span class="ah-ok">✔ ' . esc_html__( 'Dentro de SLA', 'ah-gestion-ti' ) . '</span>' : '<span class="ah-texto-alerta">' . esc_html__( 'Resuelto fuera de SLA', 'ah-gestion-ti' ) . '</span>';
			return;
		}
		if ( AH_Tickets::vencido( $t ) ) {
			echo '<span class="ah-texto-alerta">⚠ ' . esc_html__( 'Vencido', 'ah-gestion-ti' ) . '</span>';
		} else {
			/* translators: %s: tiempo restante. */
			echo esc_html( sprintf( __( 'Vence en %s', 'ah-gestion-ti' ), human_time_diff( strtotime( current_time( 'mysql' ) ), strtotime( (string) $t['vence'] ) ) ) );
		}
	}

	/**
	 * Select de filtro.
	 *
	 * @param string               $nombre   Campo.
	 * @param string               $todos    Etiqueta "todos".
	 * @param array<string,string> $opciones Opciones.
	 * @param string               $actual   Valor actual.
	 */
	private static function filtro_select( $nombre, $todos, $opciones, $actual ) {
		echo '<select name="' . esc_attr( $nombre ) . '"><option value="">' . esc_html( $todos ) . '</option>';
		foreach ( $opciones as $clave => $texto ) {
			echo '<option value="' . esc_attr( $clave ) . '" ' . selected( $actual, $clave, false ) . '>' . esc_html( $texto ) . '</option>';
		}
		echo '</select> ';
	}

	/**
	 * Detalle y formulario de gestión.
	 *
	 * @param int $id ID del ticket.
	 */
	private static function detalle( $id ) {
		$t = AH_Tickets::obtener( $id );
		if ( ! $t ) {
			echo '<h1>' . esc_html__( 'Ticket no encontrado', 'ah-gestion-ti' ) . '</h1><p><a href="' . esc_url( self::url() ) . '">← ' . esc_html__( 'Volver', 'ah-gestion-ti' ) . '</a></p>';
			return;
		}
		$agentes = get_users(
			array(
				'capability' => AH_Instalador::CAP_TICKETS,
				'fields'     => array( 'ID', 'display_name' ),
			)
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Mensaje tras redirección.
		if ( isset( $_GET['actualizado'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Ticket actualizado.', 'ah-gestion-ti' ) . '</p></div>';
		}

		echo '<p><a href="' . esc_url( self::url() ) . '">← ' . esc_html__( 'Todos los tickets', 'ah-gestion-ti' ) . '</a></p>';
		echo '<h1>' . esc_html( $t['numero'] . ' · ' . $t['asunto'] ) . '</h1>';

		echo '<ol class="ah-etapas">';
		foreach ( AH_Tickets::etapas( $t ) as $n => $etapa ) {
			echo '<li class="' . ( $etapa['completa'] ? 'completa' : '' ) . '"><span>' . ( (int) $n + 1 ) . '</span>' . esc_html( $etapa['nombre'] ) . '</li>';
		}
		echo '</ol>';
		?>
		<div class="ah-admin__rejilla">
			<div>
				<div class="postbox"><div class="inside">
					<h2><?php esc_html_e( 'Descripción del cliente', 'ah-gestion-ti' ); ?></h2>
					<p><?php echo nl2br( esc_html( $t['descripcion'] ) ); ?></p>
					<table class="ah-datos">
						<tr><th><?php esc_html_e( 'Solicitante', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( $t['nombre'] ); ?> &lt;<a href="mailto:<?php echo esc_attr( $t['correo'] ); ?>"><?php echo esc_html( $t['correo'] ); ?></a>&gt;<?php echo $t['user_id'] ? ' · ' . esc_html__( 'cliente registrado', 'ah-gestion-ti' ) : ''; ?></td></tr>
						<tr><th><?php esc_html_e( 'Tipo ITIL', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( AH_Tickets::tipo_itil( $t['categoria'] ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Canal', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( $t['canal'] ); ?></td></tr>
						<?php if ( $t['pedido_id'] ) : ?>
							<tr><th><?php esc_html_e( 'Pedido', 'ah-gestion-ti' ); ?></th><td>
								<?php
								$pedido = function_exists( 'wc_get_order' ) ? wc_get_order( (int) $t['pedido_id'] ) : null;
								echo $pedido ? '<a href="' . esc_url( $pedido->get_edit_order_url() ) . '">#' . esc_html( $pedido->get_order_number() ) . '</a>' : '#' . (int) $t['pedido_id'] . ' ' . esc_html__( '(no encontrado)', 'ah-gestion-ti' );
								?>
							</td></tr>
						<?php endif; ?>
						<tr><th><?php esc_html_e( 'Registrado', 'ah-gestion-ti' ); ?></th><td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $t['creado'] ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'Primera respuesta', 'ah-gestion-ti' ); ?></th><td><?php echo $t['primera_respuesta'] ? esc_html( mysql2date( 'd/m/Y H:i', $t['primera_respuesta'] ) ) : '—'; ?></td></tr>
						<tr><th><?php esc_html_e( 'Objetivo SLA', 'ah-gestion-ti' ); ?></th><td><?php echo $t['vence'] ? esc_html( mysql2date( 'd/m/Y H:i', $t['vence'] ) ) . ' · ' : ''; ?><?php self::sla( $t ); ?></td></tr>
					</table>
				</div></div>

				<div class="postbox"><div class="inside">
					<h2><?php esc_html_e( 'Historial', 'ah-gestion-ti' ); ?></h2>
					<ul class="ah-historial">
						<?php foreach ( AH_Tickets::eventos( $id ) as $e ) : ?>
							<li class="ah-historial__item ah-historial__item--<?php echo esc_attr( $e['tipo'] ); ?><?php echo $e['publico'] ? '' : ' ah-historial__item--interno'; ?>">
								<span class="ah-historial__meta"><?php echo esc_html( $e['autor'] . ' · ' . mysql2date( 'd/m/Y H:i', $e['creado'] ) . ' · ' . $e['tipo'] . ( $e['publico'] ? '' : ' · ' . __( 'nota interna', 'ah-gestion-ti' ) ) ); ?></span>
								<?php echo nl2br( esc_html( $e['contenido'] ) ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div></div>
			</div>

			<form class="postbox ah-admin__gestion" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><div class="inside">
				<h2><?php esc_html_e( 'Gestión del ticket', 'ah-gestion-ti' ); ?></h2>
				<input type="hidden" name="action" value="ah_ticket_gestionar">
				<input type="hidden" name="ticket" value="<?php echo (int) $id; ?>">
				<?php wp_nonce_field( 'ah_ticket_gestionar_' . $id ); ?>

				<p><label for="ah-categoria"><strong><?php esc_html_e( 'Clasificación', 'ah-gestion-ti' ); ?></strong></label><br>
				<?php self::select_admin( 'categoria', AH_Tickets::categorias(), $t['categoria'] ); ?></p>

				<p><label for="ah-prioridad"><strong><?php esc_html_e( 'Prioridad', 'ah-gestion-ti' ); ?></strong></label><br>
				<?php self::select_admin( 'prioridad', AH_Tickets::prioridades(), $t['prioridad'] ); ?></p>
				<details class="ah-matriz"><summary><?php esc_html_e( 'Matriz ITIL impacto × urgencia', 'ah-gestion-ti' ); ?></summary>
					<table><tr><th></th><th><?php esc_html_e( 'Urgencia alta', 'ah-gestion-ti' ); ?></th><th><?php esc_html_e( 'Media', 'ah-gestion-ti' ); ?></th><th><?php esc_html_e( 'Baja', 'ah-gestion-ti' ); ?></th></tr>
					<?php
					foreach ( array(
						'alto'  => __( 'Impacto alto', 'ah-gestion-ti' ),
						'medio' => __( 'Impacto medio', 'ah-gestion-ti' ),
						'bajo'  => __( 'Impacto bajo', 'ah-gestion-ti' ),
					) as $impacto => $etiqueta ) :
						?>
						<tr><th><?php echo esc_html( $etiqueta ); ?></th>
												<?php foreach ( array( 'alta', 'media', 'baja' ) as $urgencia ) : ?>
													<?php $p = AH_Calculos::prioridad_itil( $impacto, $urgencia ); ?>
							<td><span class="ah-pill ah-pill--<?php echo esc_attr( $p ); ?>"><?php echo esc_html( AH_Tickets::prioridades()[ $p ] ); ?></span><br><small><?php echo (int) AH_Calculos::SLA_HORAS[ $p ]; ?> h</small></td>
						<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
					</table>
				</details>

				<p><label for="ah-asignado_a"><strong><?php esc_html_e( 'Asignar a', 'ah-gestion-ti' ); ?></strong></label><br>
				<select id="ah-asignado_a" name="asignado_a">
					<option value="0"><?php esc_html_e( 'Sin asignar', 'ah-gestion-ti' ); ?></option>
					<?php foreach ( $agentes as $agente ) : ?>
						<option value="<?php echo (int) $agente->ID; ?>" <?php selected( (int) $t['asignado_a'], (int) $agente->ID ); ?>><?php echo esc_html( $agente->display_name ); ?></option>
					<?php endforeach; ?>
				</select></p>

				<p><label for="ah-estado"><strong><?php esc_html_e( 'Estado', 'ah-gestion-ti' ); ?></strong></label><br>
				<?php self::select_admin( 'estado', AH_Tickets::estados(), $t['estado'] ); ?></p>

				<p><label for="ah-comentario"><strong><?php esc_html_e( 'Comentario / diagnóstico', 'ah-gestion-ti' ); ?></strong></label><br>
				<textarea id="ah-comentario" name="comentario" rows="5" class="widefat"></textarea></p>
				<p><label><input type="radio" name="visibilidad" value="respuesta" checked> <?php esc_html_e( 'Respuesta al cliente (se le notifica)', 'ah-gestion-ti' ); ?></label><br>
				<label><input type="radio" name="visibilidad" value="nota"> <?php esc_html_e( 'Nota interna (solo personal)', 'ah-gestion-ti' ); ?></label></p>

				<?php submit_button( __( 'Guardar cambios', 'ah-gestion-ti' ), 'primary', 'submit', false ); ?>
			</div></form>
		</div>
		<?php
	}

	/**
	 * Select del formulario de gestión.
	 *
	 * @param string               $nombre   Campo.
	 * @param array<string,string> $opciones Opciones.
	 * @param string               $actual   Valor.
	 */
	private static function select_admin( $nombre, $opciones, $actual ) {
		echo '<select id="ah-' . esc_attr( $nombre ) . '" name="' . esc_attr( $nombre ) . '">';
		foreach ( $opciones as $clave => $texto ) {
			echo '<option value="' . esc_attr( $clave ) . '" ' . selected( $actual, $clave, false ) . '>' . esc_html( $texto ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Guarda la gestión (admin-post.php).
	 */
	public static function guardar() {
		$id = isset( $_POST['ticket'] ) ? absint( $_POST['ticket'] ) : 0;
		if ( ! current_user_can( AH_Instalador::CAP_TICKETS ) ) {
			wp_die( esc_html__( 'No tienes permiso para gestionar tickets.', 'ah-gestion-ti' ), 403 );
		}
		check_admin_referer( 'ah_ticket_gestionar_' . $id );

		AH_Tickets::actualizar(
			$id,
			array(
				'categoria'  => isset( $_POST['categoria'] ) ? sanitize_key( $_POST['categoria'] ) : '',
				'prioridad'  => isset( $_POST['prioridad'] ) ? sanitize_key( $_POST['prioridad'] ) : '',
				'asignado_a' => isset( $_POST['asignado_a'] ) ? absint( $_POST['asignado_a'] ) : 0,
				'estado'     => isset( $_POST['estado'] ) ? sanitize_key( $_POST['estado'] ) : '',
			)
		);

		$comentario = isset( $_POST['comentario'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comentario'] ) ) : '';
		if ( '' !== trim( $comentario ) ) {
			$es_nota = isset( $_POST['visibilidad'] ) && 'nota' === $_POST['visibilidad'];
			AH_Tickets::comentar( $id, $comentario, ! $es_nota, $es_nota ? 'nota' : 'respuesta' );
		}

		wp_safe_redirect(
			self::url(
				array(
					'ticket'      => $id,
					'actualizado' => 1,
				)
			)
		);
		exit;
	}

	/**
	 * URL de la página con argumentos.
	 *
	 * @param array<string,mixed> $args Argumentos.
	 * @return string
	 */
	public static function url( array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGINA ), $args ), admin_url( 'admin.php' ) );
	}
}
