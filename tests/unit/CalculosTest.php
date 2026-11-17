<?php
/**
 * Pruebas unitarias de AH_Calculos (sin WordPress).
 *
 * @package AH_Gestion_TI
 */

use PHPUnit\Framework\TestCase;

/**
 * Pruebas de SEM, ITIL y métricas de disponibilidad.
 */
final class CalculosTest extends TestCase {

	public function test_campana_sem_academica_da_667_clics() {
		$e = AH_Calculos::estimar_sem(
			array(
				'presupuesto_mensual' => 3000,
				'cpc'                 => 4.5,
				'ctr'                 => 0.05,
				'conversion'          => 0.02,
				'ticket_promedio'     => 350,
				'cpm'                 => 35,
			)
		);
		$this->assertSame( 667, $e['clics'] );
		$this->assertSame( 100.0, $e['presupuesto_diario'] );
		$this->assertSame( 13340, $e['impresiones'] );
		$this->assertEqualsWithDelta( 13.3, $e['conversiones'], 0.01 );
		$this->assertSame( 85714, $e['impresiones_cpm'] );
	}

	public function test_sem_no_divide_entre_cero() {
		$e = AH_Calculos::estimar_sem( array( 'presupuesto_mensual' => 0, 'cpc' => 0, 'ctr' => 0, 'conversion' => 0 ) );
		$this->assertSame( 0, $e['clics'] );
		$this->assertSame( 0.0, $e['cpa'] );
		$this->assertSame( 0.0, $e['roas'] );
	}

	public function test_matriz_itil_de_prioridad() {
		$this->assertSame( 'critica', AH_Calculos::prioridad_itil( 'alto', 'alta' ) );
		$this->assertSame( 'media', AH_Calculos::prioridad_itil( 'medio', 'media' ) );
		$this->assertSame( 'baja', AH_Calculos::prioridad_itil( 'bajo', 'baja' ) );
		$this->assertSame( 'media', AH_Calculos::prioridad_itil( 'desconocido', 'x' ) );
	}

	public function test_sla_por_prioridad() {
		$this->assertSame( 4 * 3600, AH_Calculos::vencimiento_sla( 0, 'critica' ) );
		$this->assertSame( 72 * 3600, AH_Calculos::vencimiento_sla( 0, 'baja' ) );
		$this->assertSame( 48 * 3600, AH_Calculos::vencimiento_sla( 0, 'inexistente' ) );
	}

	public function test_numero_de_ticket() {
		$this->assertSame( 'AH-2026-000042', AH_Calculos::numero_ticket( 42, 2026 ) );
		$this->assertTrue( AH_Calculos::numero_valido( 'AH-2026-000042' ) );
		$this->assertFalse( AH_Calculos::numero_valido( "AH-2026-42' OR 1=1" ) );
	}

	public function test_disponibilidad_y_mttr() {
		$m = array(
			array( 't' => 0, 'codigo' => 200, 'ms' => 100 ),
			array( 't' => 3600, 'codigo' => 500, 'ms' => 50 ),
			array( 't' => 5400, 'codigo' => 0, 'ms' => 20000 ),
			array( 't' => 7200, 'codigo' => 200, 'ms' => 300 ),
		);
		$r = AH_Calculos::resumen_disponibilidad( $m );
		$this->assertSame( 50.0, $r['disponibilidad'] );
		$this->assertSame( 200.0, $r['promedio_ms'] );
		$this->assertSame( 60.0, $r['mttr_min'] );
		$this->assertSame( 1, $r['incidentes'] );
		$this->assertSame( array( '500' => 1, 'sin respuesta' => 1 ), $r['errores'] );
	}

	public function test_sin_muestras_no_inventa_metricas() {
		$r = AH_Calculos::resumen_disponibilidad( array() );
		$this->assertNull( $r['disponibilidad'] );
		$this->assertNull( $r['promedio_ms'] );
	}

	public function test_validaciones_de_configuracion() {
		$this->assertTrue( AH_Calculos::ga4_id_valido( 'G-ABC123XYZ' ) );
		$this->assertFalse( AH_Calculos::ga4_id_valido( 'UA-12345-1' ) );
		$this->assertSame( '527710000000', AH_Calculos::whatsapp_normalizado( '+52 771 000 0000' ) );
		$this->assertSame( '', AH_Calculos::whatsapp_normalizado( '123' ) );
	}

	public function test_dias_habiles_saltan_fin_de_semana() {
		// Viernes 6 de noviembre de 2026 + 1 día hábil = lunes 9.
		$viernes = new DateTimeImmutable( '2026-11-06 10:00' );
		$this->assertSame( '2026-11-09', AH_Calculos::sumar_dias_habiles( $viernes, 1 )->format( 'Y-m-d' ) );
		$this->assertSame( '2026-11-13', AH_Calculos::sumar_dias_habiles( $viernes, 5 )->format( 'Y-m-d' ) );
		$this->assertSame( '2026-11-06', AH_Calculos::sumar_dias_habiles( $viernes, 0 )->format( 'Y-m-d' ) );
	}

	public function test_ventana_de_entrega_por_tipo_de_envio() {
		$domingo = new DateTimeImmutable( '2026-11-08 21:52' );
		list( $min, $max ) = AH_Calculos::ventana_entrega( $domingo, 'estandar' );
		$this->assertSame( array( '2026-11-11', '2026-11-17' ), array( $min->format( 'Y-m-d' ), $max->format( 'Y-m-d' ) ) );
		list( $min, $max ) = AH_Calculos::ventana_entrega( $domingo, 'express' );
		$this->assertSame( array( '2026-11-09', '2026-11-11' ), array( $min->format( 'Y-m-d' ), $max->format( 'Y-m-d' ) ) );
	}
}
