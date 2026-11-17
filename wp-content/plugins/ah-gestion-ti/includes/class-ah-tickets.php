<?php
/**
 * Modelo de datos de la mesa de ayuda (tickets e historial).
 *
 * Todas las consultas usan $wpdb->prepare() o los métodos insert/update
 * de $wpdb para evitar inyección SQL.
 *
 * @package AH_Gestion_TI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Operaciones sobre tickets siguiendo el flujo ITIL.
 */
final class AH_Tickets {

	/**
	 * Categorías de incidentes y solicitudes.
	 *
	 * @return array<string,string>
	 */
	public static function categorias() {
		return array(
			'compras'     => __( 'Problemas con compras', 'ah-gestion-ti' ),
			'pagos'       => __( 'Problemas con pagos', 'ah-gestion-ti' ),
			'pedidos'     => __( 'Problemas con pedidos', 'ah-gestion-ti' ),
			'acceso'      => __( 'Problemas de acceso', 'ah-gestion-ti' ),
			'tecnico'     => __( 'Problemas técnicos', 'ah-gestion-ti' ),
			'informacion' => __( 'Solicitudes de información', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Tipo ITIL de cada categoría: incidente (algo falla) o solicitud de servicio.
	 *
	 * @param string $categoria Clave de la categoría.
	 * @return string
	 */
	public static function tipo_itil( $categoria ) {
		return 'informacion' === $categoria ? __( 'Solicitud de servicio', 'ah-gestion-ti' ) : __( 'Incidente', 'ah-gestion-ti' );
	}

	/**
	 * Prioridades.
	 *
	 * @return array<string,string>
	 */
	public static function prioridades() {
		return array(
			'baja'    => __( 'Baja', 'ah-gestion-ti' ),
			'media'   => __( 'Media', 'ah-gestion-ti' ),
			'alta'    => __( 'Alta', 'ah-gestion-ti' ),
			'critica' => __( 'Crítica', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Estados del ciclo de vida.
	 *
	 * @return array<string,string>
	 */
	public static function estados() {
		return array(
			'abierto'    => __( 'Abierto', 'ah-gestion-ti' ),
			'en_proceso' => __( 'En proceso', 'ah-gestion-ti' ),
			'pendiente'  => __( 'Pendiente', 'ah-gestion-ti' ),
			'resuelto'   => __( 'Resuelto', 'ah-gestion-ti' ),
			'cerrado'    => __( 'Cerrado', 'ah-gestion-ti' ),
		);
	}

	/**
	 * Nombre de la tabla de tickets.
	 *
	 * @return string
	 */
	public static function tabla() {
		global $wpdb;
		return $wpdb->prefix . 'ah_tickets';
	}

	/**
	 * Nombre de la tabla de eventos.
	 *
	 * @return string
	 */
	public static function tabla_eventos() {
		global $wpdb;
		return $wpdb->prefix . 'ah_ticket_eventos';
	}

	/**
	 * 1. REGISTRO + 2. CLASIFICACIÓN: crea un ticket nuevo.
	 *
	 * @param array<string,mixed> $datos nombre, correo, asunto, descripcion, categoria, prioridad, canal, pedido_id, user_id.
	 * @return array|WP_Error Ticket creado.
	 */
	public static function crear( array $datos ) {
		global $wpdb;

		$categoria = array_key_exists( $datos['categoria'] ?? '', self::categorias() ) ? $datos['categoria'] : 'informacion';
		$prioridad = in_array( $datos['prioridad'] ?? '', array( 'baja', 'media', 'alta' ), true ) ? $datos['prioridad'] : 'media';
		$ahora     = current_time( 'mysql' );

		$insertado = $wpdb->insert(
			self::tabla(),
			array(
				'numero'      => 'tmp-' . wp_generate_password( 12, false ),
				'user_id'     => absint( $datos['user_id'] ?? 0 ),
				'nombre'      => sanitize_text_field( $datos['nombre'] ?? '' ),
				'correo'      => sanitize_email( $datos['correo'] ?? '' ),
				'asunto'      => sanitize_text_field( $datos['asunto'] ?? '' ),
				'descripcion' => sanitize_textarea_field( $datos['descripcion'] ?? '' ),
				'categoria'   => $categoria,
				'prioridad'   => $prioridad,
				'estado'      => 'abierto',
				'canal'       => sanitize_key( $datos['canal'] ?? 'web' ),
				'pedido_id'   => absint( $datos['pedido_id'] ?? 0 ),
				'creado'      => $ahora,
				'actualizado' => $ahora,
				'vence'       => self::fecha_vencimiento( $ahora, $prioridad ),
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( ! $insertado ) {
			return new WP_Error( 'ah_ticket', __( 'No fue posible registrar el ticket. Intenta de nuevo.', 'ah-gestion-ti' ) );
		}

		$id     = (int) $wpdb->insert_id;
		$numero = AH_Calculos::numero_ticket( $id, (int) wp_date( 'Y' ) );
		$wpdb->update( self::tabla(), array( 'numero' => $numero ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );

		self::registrar_evento(
			$id,
			'registro',
			sprintf(
				/* translators: 1: categoría, 2: prioridad, 3: canal. */
				__( 'Ticket registrado. Clasificación: %1$s. Prioridad solicitada: %2$s. Canal: %3$s.', 'ah-gestion-ti' ),
				self::categorias()[ $categoria ],
				self::prioridades()[ $prioridad ],
				sanitize_key( $datos['canal'] ?? 'web' )
			),
			true,
			0,
			__( 'Sistema', 'ah-gestion-ti' )
		);

		$ticket = self::obtener( $id );
		self::notificar_registro( $ticket );
		return $ticket;
	}

	/**
	 * Obtiene un ticket por ID.
	 *
	 * @param int $id ID del ticket.
	 * @return array|null
	 */
	public static function obtener( $id ) {
		global $wpdb;
		$tabla = self::tabla();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- El nombre de tabla es interno.
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE id = %d", $id ), ARRAY_A );
	}

	/**
	 * Busca un ticket verificando número Y correo (consulta pública segura).
	 *
	 * @param string $numero Número de seguimiento.
	 * @param string $correo Correo del solicitante.
	 * @return array|null
	 */
	public static function buscar_publico( $numero, $correo ) {
		global $wpdb;
		if ( ! AH_Calculos::numero_valido( $numero ) || ! is_email( $correo ) ) {
			return null;
		}
		$tabla = self::tabla();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- El nombre de tabla es interno.
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE numero = %s AND correo = %s", $numero, strtolower( $correo ) ), ARRAY_A );
	}

	/**
	 * Tickets de un usuario registrado (por ID o por su correo).
	 *
	 * @param WP_User $usuario Usuario actual.
	 * @return array<int,array>
	 */
	public static function del_usuario( WP_User $usuario ) {
		global $wpdb;
		$tabla = self::tabla();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- El nombre de tabla es interno.
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE user_id = %d OR correo = %s ORDER BY creado DESC LIMIT 50", $usuario->ID, strtolower( $usuario->user_email ) ), ARRAY_A );
	}

	/**
	 * Verifica si el usuario actual puede ver un ticket.
	 *
	 * @param array $ticket Ticket.
	 * @return bool
	 */
	public static function puede_ver( array $ticket ) {
		if ( current_user_can( AH_Instalador::CAP_TICKETS ) ) {
			return true;
		}
		$usuario = wp_get_current_user();
		if ( ! $usuario->exists() ) {
			return false;
		}
		return (int) $ticket['user_id'] === (int) $usuario->ID || strtolower( $ticket['correo'] ) === strtolower( $usuario->user_email );
	}

	/**
	 * Listado administrativo con filtros y paginación.
	 *
	 * @param array<string,mixed> $filtros estado, categoria, prioridad, asignado_a, buscar, pagina, por_pagina, vencidos.
	 * @return array{filas:array,total:int}
	 */
	public static function listar( array $filtros ) {
		global $wpdb;
		$tabla = self::tabla();
		// La primera condición siempre lleva marcador para que toda consulta pase por prepare().
		$where  = array( '1 = %d' );
		$params = array( 1 );

		foreach ( array( 'estado', 'categoria', 'prioridad' ) as $campo ) {
			if ( ! empty( $filtros[ $campo ] ) ) {
				$where[]  = "{$campo} = %s";
				$params[] = $filtros[ $campo ];
			}
		}
		if ( isset( $filtros['asignado_a'] ) && '' !== $filtros['asignado_a'] ) {
			$where[]  = 'asignado_a = %d';
			$params[] = (int) $filtros['asignado_a'];
		}
		if ( ! empty( $filtros['vencidos'] ) ) {
			$where[]  = "estado NOT IN ('resuelto','cerrado') AND vence < %s";
			$params[] = current_time( 'mysql' );
		}
		if ( ! empty( $filtros['buscar'] ) ) {
			$like    = '%' . $wpdb->esc_like( $filtros['buscar'] ) . '%';
			$where[] = '(numero LIKE %s OR asunto LIKE %s OR correo LIKE %s OR nombre LIKE %s)';
			$params  = array_merge( $params, array( $like, $like, $like, $like ) );
		}

		$por_pagina = max( 1, (int) ( $filtros['por_pagina'] ?? 20 ) );
		$offset     = max( 0, ( (int) ( $filtros['pagina'] ?? 1 ) - 1 ) * $por_pagina );
		$sql_where  = implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Condiciones construidas con marcadores.
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE {$sql_where}", $params ) );
		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$tabla} WHERE {$sql_where} ORDER BY FIELD(estado,'abierto','en_proceso','pendiente','resuelto','cerrado'), FIELD(prioridad,'critica','alta','media','baja'), creado DESC LIMIT %d OFFSET %d",
				array_merge( $params, array( $por_pagina, $offset ) )
			),
			ARRAY_A
		);
		// phpcs:enable

		return array(
			'filas' => $filas,
			'total' => $total,
		);
	}

	/**
	 * Actualiza campos de gestión y registra cada cambio en el historial.
	 *
	 * @param int                 $id      ID del ticket.
	 * @param array<string,mixed> $cambios estado, prioridad, categoria, asignado_a.
	 * @return bool
	 */
	public static function actualizar( $id, array $cambios ) {
		global $wpdb;
		$ticket = self::obtener( $id );
		if ( ! $ticket ) {
			return false;
		}

		$datos = array();
		$ahora = current_time( 'mysql' );

		if ( isset( $cambios['categoria'] ) && array_key_exists( $cambios['categoria'], self::categorias() ) && $cambios['categoria'] !== $ticket['categoria'] ) {
			$datos['categoria'] = $cambios['categoria'];
			/* translators: 1: categoría anterior, 2: nueva categoría. */
			self::registrar_evento( $id, 'clasificacion', sprintf( __( 'Reclasificado de "%1$s" a "%2$s".', 'ah-gestion-ti' ), self::categorias()[ $ticket['categoria'] ], self::categorias()[ $cambios['categoria'] ] ), false );
		}

		if ( isset( $cambios['prioridad'] ) && array_key_exists( $cambios['prioridad'], self::prioridades() ) && ( $cambios['prioridad'] !== $ticket['prioridad'] || ! self::tiene_evento( $id, 'prioridad' ) ) ) {
			$datos['prioridad'] = $cambios['prioridad'];
			$datos['vence']     = self::fecha_vencimiento( $ticket['creado'], $cambios['prioridad'] );
			/* translators: %s: prioridad. */
			self::registrar_evento( $id, 'prioridad', sprintf( __( 'Prioridad confirmada por el equipo: %s.', 'ah-gestion-ti' ), self::prioridades()[ $cambios['prioridad'] ] ), true );
		}

		if ( isset( $cambios['asignado_a'] ) && (int) $cambios['asignado_a'] !== (int) $ticket['asignado_a'] ) {
			$agente = get_userdata( (int) $cambios['asignado_a'] );
			if ( 0 === (int) $cambios['asignado_a'] || ( $agente && user_can( $agente, AH_Instalador::CAP_TICKETS ) ) ) {
				$datos['asignado_a'] = (int) $cambios['asignado_a'];
				$texto               = $agente ? sprintf( /* translators: %s: agente. */ __( 'Asignado a %s.', 'ah-gestion-ti' ), $agente->display_name ) : __( 'Asignación retirada.', 'ah-gestion-ti' );
				self::registrar_evento( $id, 'asignacion', $texto, false );
			}
		}

		if ( isset( $cambios['estado'] ) && array_key_exists( $cambios['estado'], self::estados() ) && $cambios['estado'] !== $ticket['estado'] ) {
			$datos['estado'] = $cambios['estado'];
			if ( 'resuelto' === $cambios['estado'] && empty( $ticket['resuelto'] ) ) {
				$datos['resuelto'] = $ahora;
			}
			if ( 'cerrado' === $cambios['estado'] ) {
				$datos['cerrado'] = $ahora;
				if ( empty( $ticket['resuelto'] ) ) {
					$datos['resuelto'] = $ahora;
				}
			}
			if ( in_array( $cambios['estado'], array( 'abierto', 'en_proceso', 'pendiente' ), true ) ) {
				$datos['cerrado'] = null; // Reapertura.
			}
			/* translators: 1: estado anterior, 2: nuevo estado. */
			self::registrar_evento( $id, 'estado', sprintf( __( 'Estado cambiado de "%1$s" a "%2$s".', 'ah-gestion-ti' ), self::estados()[ $ticket['estado'] ], self::estados()[ $cambios['estado'] ] ), true );
			self::notificar_cliente( $ticket, sprintf( /* translators: %s: estado. */ __( 'El estado de tu ticket cambió a: %s.', 'ah-gestion-ti' ), self::estados()[ $cambios['estado'] ] ) );
		}

		if ( ! $datos ) {
			return true;
		}
		$datos['actualizado'] = $ahora;
		return false !== $wpdb->update( self::tabla(), $datos, array( 'id' => (int) $id ) );
	}

	/**
	 * Agrega un comentario (respuesta pública o nota interna).
	 *
	 * @param int    $id        ID del ticket.
	 * @param string $contenido Texto.
	 * @param bool   $publico   Visible para el cliente.
	 * @param string $tipo      comentario|respuesta|nota.
	 * @return bool
	 */
	public static function comentar( $id, $contenido, $publico, $tipo = 'comentario' ) {
		global $wpdb;
		$ticket    = self::obtener( $id );
		$contenido = sanitize_textarea_field( $contenido );
		if ( ! $ticket || '' === trim( $contenido ) ) {
			return false;
		}
		self::registrar_evento( $id, $tipo, $contenido, $publico );

		$datos = array( 'actualizado' => current_time( 'mysql' ) );
		if ( 'respuesta' === $tipo && $publico && empty( $ticket['primera_respuesta'] ) ) {
			$datos['primera_respuesta'] = current_time( 'mysql' );
		}
		// Si el cliente responde a un ticket pendiente, vuelve a "En proceso".
		if ( 'comentario' === $tipo && 'pendiente' === $ticket['estado'] ) {
			$datos['estado'] = 'en_proceso';
			self::registrar_evento( $id, 'estado', __( 'El cliente respondió; el ticket vuelve a "En proceso".', 'ah-gestion-ti' ), true, 0, __( 'Sistema', 'ah-gestion-ti' ) );
		}
		$wpdb->update( self::tabla(), $datos, array( 'id' => (int) $id ) );

		if ( 'respuesta' === $tipo && $publico ) {
			self::notificar_cliente( $ticket, $contenido );
		}
		return true;
	}

	/**
	 * Guarda un evento en el historial.
	 *
	 * @param int    $ticket_id ID del ticket.
	 * @param string $tipo      Tipo de evento.
	 * @param string $contenido Texto.
	 * @param bool   $publico   Visible para el cliente.
	 * @param int    $user_id   Autor (0 = usuario actual).
	 * @param string $autor     Nombre del autor (opcional).
	 */
	public static function registrar_evento( $ticket_id, $tipo, $contenido, $publico = true, $user_id = 0, $autor = '' ) {
		global $wpdb;
		$usuario = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
		if ( '' === $autor ) {
			$autor = ( $usuario && $usuario->exists() ) ? $usuario->display_name : __( 'Cliente', 'ah-gestion-ti' );
		}
		$wpdb->insert(
			self::tabla_eventos(),
			array(
				'ticket_id' => (int) $ticket_id,
				'user_id'   => ( $usuario && $usuario->exists() ) ? (int) $usuario->ID : 0,
				'autor'     => sanitize_text_field( $autor ),
				'tipo'      => sanitize_key( $tipo ),
				'contenido' => sanitize_textarea_field( $contenido ),
				'publico'   => $publico ? 1 : 0,
				'creado'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Historial de un ticket.
	 *
	 * @param int  $ticket_id ID del ticket.
	 * @param bool $solo_publicos Excluye notas internas.
	 * @return array<int,array>
	 */
	public static function eventos( $ticket_id, $solo_publicos = false ) {
		global $wpdb;
		$tabla = self::tabla_eventos();
		$extra = $solo_publicos ? ' AND publico = 1' : '';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabla y filtro internos.
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE ticket_id = %d{$extra} ORDER BY creado ASC, id ASC", $ticket_id ), ARRAY_A );
	}

	/**
	 * Indica si existe un evento de cierto tipo.
	 *
	 * @param int    $ticket_id ID del ticket.
	 * @param string $tipo      Tipo.
	 * @return bool
	 */
	public static function tiene_evento( $ticket_id, $tipo ) {
		global $wpdb;
		$tabla = self::tabla_eventos();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- El nombre de tabla es interno.
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE ticket_id = %d AND tipo = %s", $ticket_id, $tipo ) );
	}

	/**
	 * Etapas del flujo ITIL y si ya se completaron.
	 *
	 * @param array $ticket Ticket.
	 * @return array<int,array{clave:string,nombre:string,completa:bool}>
	 */
	public static function etapas( array $ticket ) {
		$estado = $ticket['estado'];
		return array(
			array(
				'clave'    => 'registro',
				'nombre'   => __( 'Registro', 'ah-gestion-ti' ),
				'completa' => true,
			),
			array(
				'clave'    => 'clasificacion',
				'nombre'   => __( 'Clasificación', 'ah-gestion-ti' ),
				'completa' => '' !== $ticket['categoria'],
			),
			array(
				'clave'    => 'priorizacion',
				'nombre'   => __( 'Priorización', 'ah-gestion-ti' ),
				'completa' => self::tiene_evento( (int) $ticket['id'], 'prioridad' ),
			),
			array(
				'clave'    => 'asignacion',
				'nombre'   => __( 'Asignación', 'ah-gestion-ti' ),
				'completa' => (int) $ticket['asignado_a'] > 0,
			),
			array(
				'clave'    => 'diagnostico',
				'nombre'   => __( 'Diagnóstico', 'ah-gestion-ti' ),
				'completa' => in_array( $estado, array( 'en_proceso', 'pendiente', 'resuelto', 'cerrado' ), true ),
			),
			array(
				'clave'    => 'resolucion',
				'nombre'   => __( 'Resolución', 'ah-gestion-ti' ),
				'completa' => in_array( $estado, array( 'resuelto', 'cerrado' ), true ),
			),
			array(
				'clave'    => 'cierre',
				'nombre'   => __( 'Cierre', 'ah-gestion-ti' ),
				'completa' => 'cerrado' === $estado,
			),
		);
	}

	/**
	 * Fecha límite (hora local) a partir de la fecha de creación y la prioridad.
	 *
	 * @param string $creado    Fecha local Y-m-d H:i:s.
	 * @param string $prioridad Prioridad.
	 * @return string
	 */
	public static function fecha_vencimiento( $creado, $prioridad ) {
		return gmdate( 'Y-m-d H:i:s', AH_Calculos::vencimiento_sla( strtotime( $creado ), $prioridad ) );
	}

	/**
	 * Indica si el ticket superó su tiempo objetivo (SLA).
	 *
	 * @param array $ticket Ticket.
	 * @return bool
	 */
	public static function vencido( array $ticket ) {
		return ! in_array( $ticket['estado'], array( 'resuelto', 'cerrado' ), true )
			&& ! empty( $ticket['vence'] )
			&& strtotime( $ticket['vence'] ) < strtotime( current_time( 'mysql' ) );
	}

	/**
	 * Estadísticas reales para el tablero y los reportes PDCA.
	 *
	 * @param int $dias Ventana en días (0 = todo).
	 * @return array<string,mixed>
	 */
	public static function estadisticas( $dias = 30 ) {
		global $wpdb;
		$tabla = self::tabla();
		$desde = $dias ? gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) ) - $dias * DAY_IN_SECONDS ) : '1970-01-01 00:00:00';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- El nombre de tabla es interno.
		$total      = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE creado >= %s", $desde ) );
		$por_estado = $wpdb->get_results( $wpdb->prepare( "SELECT estado, COUNT(*) n FROM {$tabla} WHERE creado >= %s GROUP BY estado", $desde ), OBJECT_K );
		$por_cat    = $wpdb->get_results( $wpdb->prepare( "SELECT categoria, COUNT(*) n FROM {$tabla} WHERE creado >= %s GROUP BY categoria", $desde ), OBJECT_K );
		$por_prio   = $wpdb->get_results( $wpdb->prepare( "SELECT prioridad, COUNT(*) n FROM {$tabla} WHERE creado >= %s GROUP BY prioridad", $desde ), OBJECT_K );
		$resolucion = $wpdb->get_var( $wpdb->prepare( "SELECT AVG(TIMESTAMPDIFF(MINUTE, creado, resuelto)) FROM {$tabla} WHERE resuelto IS NOT NULL AND creado >= %s", $desde ) );
		$respuesta  = $wpdb->get_var( $wpdb->prepare( "SELECT AVG(TIMESTAMPDIFF(MINUTE, creado, primera_respuesta)) FROM {$tabla} WHERE primera_respuesta IS NOT NULL AND creado >= %s", $desde ) );
		$vencidos   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE estado NOT IN ('resuelto','cerrado') AND vence < %s", current_time( 'mysql' ) ) );
		$resueltos  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE resuelto IS NOT NULL AND creado >= %s", $desde ) );
		$en_sla     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla} WHERE resuelto IS NOT NULL AND creado >= %s AND resuelto <= vence", $desde ) );
		// phpcs:enable

		$conteo = static function ( $filas ) {
			return array_map( static fn( $f ) => (int) $f->n, (array) $filas );
		};

		return array(
			'total'                 => $total,
			'por_estado'            => $conteo( $por_estado ),
			'por_categoria'         => $conteo( $por_cat ),
			'por_prioridad'         => $conteo( $por_prio ),
			'resolucion_promedio_h' => null === $resolucion ? null : round( (float) $resolucion / 60, 1 ),
			'respuesta_promedio_h'  => null === $respuesta ? null : round( (float) $respuesta / 60, 1 ),
			'vencidos'              => $vencidos,
			'cumplimiento_sla'      => $resueltos ? round( $en_sla / $resueltos * 100, 1 ) : null,
		);
	}

	/**
	 * Correo de confirmación al cliente y aviso al equipo.
	 *
	 * @param array $ticket Ticket.
	 */
	private static function notificar_registro( array $ticket ) {
		$asunto = sprintf( /* translators: %s: número de ticket. */ __( '[Artesanías Huejutla] Ticket %s registrado', 'ah-gestion-ti' ), $ticket['numero'] );
		$cuerpo = sprintf(
			/* translators: 1: nombre, 2: número, 3: asunto, 4: URL de consulta. */
			__( "Hola %1\$s:\n\nRegistramos tu solicitud con el número de seguimiento %2\$s.\nAsunto: %3\$s\n\nPuedes consultar su avance en %4\$s usando este número y tu correo.\n\nArtesanías Huejutla", 'ah-gestion-ti' ),
			$ticket['nombre'],
			$ticket['numero'],
			$ticket['asunto'],
			AH_Mesa_Ayuda_Publica::url_pagina()
		);
		wp_mail( $ticket['correo'], $asunto, $cuerpo );

		$destino = ah_gti_opcion( 'correo', get_option( 'admin_email' ) );
		wp_mail( $destino, $asunto, sprintf( "%s\n\n%s\n\n%s", $ticket['asunto'], $ticket['descripcion'], admin_url( 'admin.php?page=' . AH_Mesa_Ayuda_Admin::PAGINA . '&ticket=' . $ticket['id'] ) ) );
	}

	/**
	 * Aviso al cliente por actualización del ticket.
	 *
	 * @param array  $ticket  Ticket.
	 * @param string $mensaje Mensaje.
	 */
	private static function notificar_cliente( array $ticket, $mensaje ) {
		wp_mail(
			$ticket['correo'],
			sprintf( /* translators: %s: número. */ __( '[Artesanías Huejutla] Actualización del ticket %s', 'ah-gestion-ti' ), $ticket['numero'] ),
			$mensaje . "\n\n" . AH_Mesa_Ayuda_Publica::url_pagina()
		);
	}
}
