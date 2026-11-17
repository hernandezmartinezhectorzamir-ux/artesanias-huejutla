<?php
/**
 * Cálculos puros del plugin (sin dependencias de WordPress).
 *
 * Se mantienen aislados para poder probarlos con PHPUnit en el pipeline de CI.
 *
 * @package AH_Gestion_TI
 */

// Permite cargar la clase tanto en WordPress como en las pruebas unitarias.
if ( ! defined( 'ABSPATH' ) && ! defined( 'AH_GTI_PRUEBAS' ) ) {
	exit;
}

/**
 * Funciones de cálculo para SEM, ITIL (prioridad y SLA) y métricas de disponibilidad.
 */
final class AH_Calculos {

	/**
	 * Horas objetivo de resolución (SLA) según la prioridad del ticket.
	 *
	 * @var array<string,int>
	 */
	const SLA_HORAS = array(
		'critica' => 4,
		'alta'    => 24,
		'media'   => 48,
		'baja'    => 72,
	);

	/**
	 * Matriz ITIL de priorización: impacto × urgencia.
	 *
	 * @var array<string,array<string,string>>
	 */
	const MATRIZ_PRIORIDAD = array(
		'alto'  => array(
			'alta'  => 'critica',
			'media' => 'alta',
			'baja'  => 'media',
		),
		'medio' => array(
			'alta'  => 'alta',
			'media' => 'media',
			'baja'  => 'baja',
		),
		'bajo'  => array(
			'alta'  => 'media',
			'media' => 'baja',
			'baja'  => 'baja',
		),
	);

	/**
	 * Calcula la prioridad ITIL a partir del impacto y la urgencia.
	 *
	 * @param string $impacto  alto|medio|bajo.
	 * @param string $urgencia alta|media|baja.
	 * @return string critica|alta|media|baja
	 */
	public static function prioridad_itil( $impacto, $urgencia ) {
		return self::MATRIZ_PRIORIDAD[ $impacto ][ $urgencia ] ?? 'media';
	}

	/**
	 * Fecha límite de resolución según la prioridad.
	 *
	 * @param int    $creado_ts Marca de tiempo de creación.
	 * @param string $prioridad Prioridad del ticket.
	 * @return int Marca de tiempo de vencimiento.
	 */
	public static function vencimiento_sla( $creado_ts, $prioridad ) {
		$horas = self::SLA_HORAS[ $prioridad ] ?? self::SLA_HORAS['media'];
		return (int) $creado_ts + ( $horas * 3600 );
	}

	/**
	 * Número de seguimiento legible: AH-2026-000123.
	 *
	 * @param int $id   ID interno del ticket.
	 * @param int $anio Año de registro.
	 * @return string
	 */
	public static function numero_ticket( $id, $anio ) {
		return sprintf( 'AH-%04d-%06d', (int) $anio, (int) $id );
	}

	/**
	 * Valida el formato de un número de seguimiento.
	 *
	 * @param string $numero Número a validar.
	 * @return bool
	 */
	public static function numero_valido( $numero ) {
		return (bool) preg_match( '/^AH-\d{4}-\d{6}$/', (string) $numero );
	}

	/**
	 * Estimaciones de la campaña SEM simulada.
	 *
	 * Todos los valores son proyecciones basadas en los supuestos recibidos,
	 * NO resultados reales de Google Ads.
	 *
	 * @param array<string,float> $s Supuestos: presupuesto_mensual, cpc, ctr, conversion, ticket_promedio, cpm.
	 * @return array<string,float|int>
	 */
	public static function estimar_sem( array $s ) {
		$presupuesto = max( 0.0, (float) ( $s['presupuesto_mensual'] ?? 0 ) );
		$cpc         = max( 0.01, (float) ( $s['cpc'] ?? 0.01 ) );
		$ctr         = min( 1.0, max( 0.0001, (float) ( $s['ctr'] ?? 0.05 ) ) );
		$conversion  = min( 1.0, max( 0.0, (float) ( $s['conversion'] ?? 0.02 ) ) );
		$ticket      = max( 0.0, (float) ( $s['ticket_promedio'] ?? 0 ) );
		$cpm         = max( 0.01, (float) ( $s['cpm'] ?? 0.01 ) );

		$clics           = (int) round( $presupuesto / $cpc );
		$impresiones     = (int) round( $clics / $ctr );
		$conversiones    = round( $clics * $conversion, 1 );
		$ingresos        = round( $conversiones * $ticket, 2 );
		$cpa             = $conversiones > 0 ? round( $presupuesto / $conversiones, 2 ) : 0.0;
		$roas            = $presupuesto > 0 ? round( $ingresos / $presupuesto, 2 ) : 0.0;
		$cpm_efectivo    = $impresiones > 0 ? round( ( $presupuesto / $impresiones ) * 1000, 2 ) : 0.0;
		$impresiones_cpm = (int) round( ( $presupuesto / $cpm ) * 1000 );

		return array(
			'presupuesto_diario'  => round( $presupuesto / 30, 2 ),
			'clics'               => $clics,
			'clics_diarios'       => round( $clics / 30, 1 ),
			'impresiones'         => $impresiones,
			'conversiones'        => $conversiones,
			'ingresos'            => $ingresos,
			'cpa'                 => $cpa,
			'roas'                => $roas,
			'cpm_efectivo'        => $cpm_efectivo,
			'impresiones_cpm'     => $impresiones_cpm,
			'clics_estimados_cpm' => (int) round( $impresiones_cpm * $ctr ),
		);
	}

	/**
	 * Resume muestras de monitoreo reales.
	 *
	 * @param array<int,array{t:int,codigo:int,ms:float}> $muestras Muestras ordenadas por tiempo.
	 * @return array<string,mixed>
	 */
	public static function resumen_disponibilidad( array $muestras ) {
		$total = count( $muestras );
		if ( 0 === $total ) {
			return array(
				'total'          => 0,
				'disponibilidad' => null,
				'promedio_ms'    => null,
				'p95_ms'         => null,
				'errores'        => array(),
				'tasa_errores'   => null,
				'mttr_min'       => null,
				'incidentes'     => 0,
			);
		}

		$ok      = 0;
		$tiempos = array();
		$errores = array();
		foreach ( $muestras as $m ) {
			if ( self::es_exitoso( (int) $m['codigo'] ) ) {
				++$ok;
				$tiempos[] = (float) $m['ms'];
			} else {
				$clave             = (int) $m['codigo'] > 0 ? (string) (int) $m['codigo'] : 'sin respuesta';
				$errores[ $clave ] = ( $errores[ $clave ] ?? 0 ) + 1;
			}
		}

		sort( $tiempos );
		$p95 = null;
		if ( $tiempos ) {
			$indice = (int) ceil( 0.95 * count( $tiempos ) ) - 1;
			$p95    = round( $tiempos[ max( 0, $indice ) ], 1 );
		}

		$mttr = self::mttr( $muestras );

		return array(
			'total'          => $total,
			'disponibilidad' => round( ( $ok / $total ) * 100, 2 ),
			'promedio_ms'    => $tiempos ? round( array_sum( $tiempos ) / count( $tiempos ), 1 ) : null,
			'p95_ms'         => $p95,
			'errores'        => $errores,
			'tasa_errores'   => round( ( ( $total - $ok ) / $total ) * 100, 2 ),
			'mttr_min'       => $mttr['mttr_min'],
			'incidentes'     => $mttr['incidentes'],
		);
	}

	/**
	 * Tiempo medio de recuperación (MTTR) a partir de periodos de caída consecutivos.
	 *
	 * Un incidente inicia con la primera muestra fallida y termina con la siguiente
	 * muestra exitosa. Los incidentes aún abiertos no se cuentan en el promedio.
	 *
	 * @param array<int,array{t:int,codigo:int,ms:float}> $muestras Muestras ordenadas por tiempo.
	 * @return array{mttr_min:float|null,incidentes:int}
	 */
	public static function mttr( array $muestras ) {
		$inicio     = null;
		$duraciones = array();
		$incidentes = 0;
		foreach ( $muestras as $m ) {
			$exitoso = self::es_exitoso( (int) $m['codigo'] );
			if ( ! $exitoso && null === $inicio ) {
				$inicio = (int) $m['t'];
				++$incidentes;
			} elseif ( $exitoso && null !== $inicio ) {
				$duraciones[] = ( (int) $m['t'] - $inicio ) / 60;
				$inicio       = null;
			}
		}
		return array(
			'mttr_min'   => $duraciones ? round( array_sum( $duraciones ) / count( $duraciones ), 1 ) : null,
			'incidentes' => $incidentes,
		);
	}

	/**
	 * Indica si un código HTTP representa una respuesta disponible.
	 *
	 * @param int $codigo Código HTTP (0 = sin respuesta).
	 * @return bool
	 */
	public static function es_exitoso( $codigo ) {
		return $codigo >= 200 && $codigo < 400;
	}

	/**
	 * Valida un ID de medición de Google Analytics 4 (G-XXXXXXX).
	 *
	 * @param string $id ID a validar.
	 * @return bool
	 */
	public static function ga4_id_valido( $id ) {
		return (bool) preg_match( '/^G-[A-Z0-9]{4,15}$/', (string) $id );
	}

	/**
	 * Normaliza un número de WhatsApp a solo dígitos con lada internacional.
	 *
	 * @param string $numero Número capturado por el administrador.
	 * @return string Dígitos (vacío si no es válido).
	 */
	public static function whatsapp_normalizado( $numero ) {
		$digitos = preg_replace( '/\D+/', '', (string) $numero );
		return ( strlen( $digitos ) >= 10 && strlen( $digitos ) <= 15 ) ? $digitos : '';
	}

	/**
	 * Suma días hábiles (lunes a viernes) a una fecha.
	 *
	 * @param DateTimeImmutable $fecha Fecha de inicio.
	 * @param int               $dias  Días hábiles a sumar.
	 * @return DateTimeImmutable
	 */
	public static function sumar_dias_habiles( DateTimeImmutable $fecha, $dias ) {
		$resultado = $fecha;
		$restantes = max( 0, (int) $dias );
		while ( $restantes > 0 ) {
			$resultado = $resultado->modify( '+1 day' );
			if ( (int) $resultado->format( 'N' ) <= 5 ) {
				--$restantes;
			}
		}
		return $resultado;
	}

	/**
	 * Ventana estimada de entrega según el tipo de envío.
	 *
	 * @param DateTimeImmutable $desde Fecha de la compra.
	 * @param string            $tipo  estandar|express|recoger.
	 * @return array{0:DateTimeImmutable,1:DateTimeImmutable}
	 */
	public static function ventana_entrega( DateTimeImmutable $desde, $tipo ) {
		$rangos                  = array(
			'express' => array( 1, 3 ),
			'recoger' => array( 1, 1 ),
		);
		list( $minimo, $maximo ) = $rangos[ $tipo ] ?? array( 3, 7 );
		return array( self::sumar_dias_habiles( $desde, $minimo ), self::sumar_dias_habiles( $desde, $maximo ) );
	}

	/**
	 * Valida un correo para el boletín (formato y longitud de la columna).
	 *
	 * @param string $correo Correo.
	 * @return bool
	 */
	public static function correo_boletin_valido( $correo ) {
		$correo = trim( (string) $correo );
		return '' !== $correo && strlen( $correo ) <= 190 && false !== filter_var( $correo, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Evita la inyección de fórmulas al abrir un CSV en Excel o Sheets.
	 *
	 * @param string $valor Celda.
	 * @return string
	 */
	public static function csv_seguro( $valor ) {
		$valor = (string) $valor;
		return '' !== $valor && in_array( $valor[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ? "'" . $valor : $valor;
	}
}
