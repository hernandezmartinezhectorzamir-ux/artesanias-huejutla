# Reporte de mejora continua – Artesanías Huejutla

**Periodo:** 5 al 17 de noviembre de 2026 (entorno local de desarrollo, XAMPP)  **Elaboró:** Equipo de desarrollo  **Fecha:** 17/11/2026

> **Alcance y honestidad de los datos.** Todo valor de esta tabla proviene de una medición real del sistema: el monitoreo del plugin (`ah_gti_muestras`), mediciones de tiempo con `curl`, el registro de errores `wp-content/debug.log` y la tabla `wp_ah_tickets`. El sitio **aún no está publicado**, así que no hay visitantes reales: los tickets y pedidos existentes son **datos de prueba** creados durante la validación. Las fuentes que dependen de una cuenta externa se marcan como *No disponible* con su motivo. No se presentan métricas inventadas.

## 1. Indicadores del periodo

| Fuente | Indicador | Valor anterior | Valor actual | Meta | ¿Cumple? |
|---|---|---|---|---|---|
| Google Analytics 4 | Usuarios / sesiones | — | **No disponible**: falta configurar un ID de medición GA4 real (*Gestión TI → Ajustes*) y publicar el sitio | — | — |
| Google Analytics 4 | Tasa de conversión | — | **No disponible** (mismo motivo) | ≥ 1.5 % | — |
| Google Analytics 4 | Abandono del carrito | — | **No disponible** (mismo motivo) | ≤ 70 % | — |
| Search Console | Clics / impresiones / CTR / posición | — | **No disponible**: el sitio no tiene dominio público ni propiedad verificada | — | — |
| Search Console | Páginas indexadas con errores | — | **No disponible** (mismo motivo) | 0 | — |
| Monitoreo | Disponibilidad % | 100 % (24 muestras, 5–17 nov) | 100 % (9 muestras, 17 nov) | ≥ 99.5 % | Sí |
| Monitoreo | Tiempo de respuesta, páginas públicas (promedio inicio + tienda) | 1 489 ms | **39 ms** | ≤ 800 | Sí |
| Monitoreo | Tiempo de respuesta, endpoint `/wp-json/ah/v1/salud` (dinámico) | 1 564 ms | 2 165 ms (p95: 2 898 ms) | ≤ 800 | **No** |
| Monitoreo | MTTR (min) | Sin incidentes | Sin incidentes | ≤ 30 | Sí |
| Velocidad (TTFB, `curl`, 5 repeticiones) | `/`, `/tienda/`, producto, `/blog/` | 1 910 / 1 747 / 1 710 / 1 625 ms | **25 / 25 / 27 / 25 ms** | ≤ 800 | Sí |
| Errores PHP | Fatal / Warning (ver sección 4) | — | 30 fatales y 63 avisos de *warning*, todos durante la configuración de la caché o por la caída de MySQL; **0 fatales después de corregirla** | 0 / ↓ | Parcial |
| Mesa de ayuda | Tickets del periodo / fuera de SLA | — | 3 tickets de prueba / 2 abiertos fuera de SLA | 0 fuera de SLA | **No** |
| Mesa de ayuda | Cumplimiento de SLA % | — | 33 % (1 de 3) | ≥ 90 % | **No** |
| Mesa de ayuda | Tiempo promedio de resolución | — | < 1 min (un solo ticket cerrado, de prueba) | ≤ 24 h | No representativo |
| Opiniones | Calificación / número de reseñas | — | 0 reseñas | ≥ 4.5 | Sin datos |

Notas de medición:

- El monitoreo corre con WP-Cron cada hora, pero en local solo se ejecuta cuando alguien visita el sitio. Por eso hay pocas muestras. En producción debe usarse un cron real del servidor (ver `docs/modulo-3-devops.md`).
- Valor anterior = muestras previas a la caché. Valor actual = muestras tomadas el 17/11 a las 18:30 UTC, después de corregir la configuración.

## 2. Problemas detectados y propuestas (ciclo PDCA)

| # | Problema u oportunidad | Evidencia (fuente y dato) | Propuesta de mejora | Responsable | Fecha compromiso | Indicador / meta | Fase PDCA |
|---|---|---|---|---|---|---|---|
| 1 | Páginas públicas lentas | Monitoreo y `curl`: TTFB de 1.6 a 1.9 s en las páginas principales | Caché de página (WP Super Cache), excluyendo carrito, pago, cuenta, Mis compras y usuarios con sesión | Desarrollo | 17/11/2026 | TTFB ≤ 800 ms | **Verificar** (ver sección 3) |
| 2 | Las peticiones dinámicas siguen tardando ~1.8 s (REST, carrito, usuarios con sesión) | Endpoint de salud: 1 770 a 2 898 ms; `/carrito/`: 1 581 ms | En el hosting: activar OPcache, compresión (`mod_deflate`) y caché de navegador (`mod_expires`), además de una caché de objetos (Redis) si el plan lo permite. No se cambió en XAMPP porque afecta a todos los proyectos del equipo | Desarrollo / hosting | Al contratar el hosting | p95 dinámico ≤ 800 ms | Planificar |
| 3 | Tickets sin atender fuera de SLA | `wp_ah_tickets`: AH-2026-000001 (alta, vencía el 06/11) y AH-2026-000002 (baja, vencía el 08/11) siguen abiertos | Asignar un agente de guardia y revisar diariamente el filtro "Fuera de SLA" de la mesa de ayuda. Cerrar los tickets de prueba antes de publicar | Atención al cliente | Antes del lanzamiento | Cumplimiento de SLA ≥ 90 % | Planificar |
| 4 | Aviso repetido de `ob_end_flush()` en el registro de PHP | `debug.log`: 669 avisos causados por `zlib.output_compression` en XAMPP; no es un error del tema ni del plugin | En producción, comprimir con el servidor (`mod_deflate`) en lugar de `zlib.output_compression`, y `WP_DEBUG_DISPLAY` en falso (ya está así) | Desarrollo | Al contratar el hosting | 0 avisos repetidos | Planificar |
| 5 | Sin datos de tráfico ni de búsqueda | GA4 y Search Console no disponibles | Al publicar: capturar el ID de GA4 y el código de verificación en *Gestión TI → Ajustes*, enviar `sitemap_index.xml` y repetir este reporte a los 30 días | Marketing | 30 días después de publicar | Primer reporte con datos de GA4 y Search Console | Planificar |

## 3. Verificación de acciones del periodo anterior

| # | Acción implementada | Indicador antes | Indicador después | Resultado | Decisión |
|---|---|---|---|---|---|
| 1 | Caché de página con WP Super Cache (modo PHP). Excluye carrito, pago, Mi cuenta, Mis compras, rastreo, mesa de ayuda, favoritos, contacto, REST y sitemaps, además de las cookies de sesión y de carrito | TTFB 1 625 – 1 910 ms | TTFB 25 – 27 ms (monitoreo: 27 – 52 ms) | Las páginas públicas responden unas **60 veces más rápido**. Se verificó que un visitante con carrito, un usuario con sesión y `/carrito/` reciben siempre una página dinámica (1.5 – 1.9 s) | **Estandarizar**: queda documentado en `docs/modulo-3-devops.md` y el tablero de Gestión TI lo verifica |
| 1a | Corrección durante la verificación: la regla de cookies había quedado fuera del bloque PHP de `wp-cache-config.php` y faltaban `$wp_cache_slash_check` y `$wp_cache_home_path` | La regla de cookies no se aplicaba y aparecía un texto suelto en la salida | Regla aplicada, sin salida extra, caché servida | Corregido y medido de nuevo | Estandarizar: configurar el plugin desde su pantalla de ajustes, no editando el archivo a mano |

## 4. Errores registrados y categorías de tickets

**Errores de PHP (`debug.log`, 5–17 nov):**

| Tipo | Cantidad | Causa raíz | Acción |
|---|---|---|---|
| Notice `ob_end_flush()` (núcleo de WordPress) | 669 | `zlib.output_compression` activo en el PHP de XAMPP | Problema 4 |
| Warning "headers already sent" | 50 | Línea fuera del bloque PHP en `wp-cache-config.php` (17/11, 18:02–18:04) | Corregido (acción 1a) |
| Fatal `wp_rand()` en WP Super Cache | 30 | Ocurre al activar el registro de depuración del plugin antes de que WordPress cargue (fallo del plugin); se activó solo para diagnosticar | Depuración desactivada; no volvió a ocurrir |
| Warning `mysqli_real_connect` | 11 | MySQL detenido por la tabla `mysql.db` dañada (06/11 y 17/11) | Tabla reparada con respaldo previo |
| Otros (actualizaciones sin internet, carga de traducciones de Rank Math, una consulta de diagnóstico) | 4 | Entorno local o diagnóstico | Ninguna |

**Tickets (todos de prueba):**

| Categoría | Cantidad | Estado | Acción preventiva |
|---|---|---|---|
| Problemas con pedidos | 1 | Abierto (prioridad alta), fuera de SLA | Problema 3 |
| Solicitudes de información | 2 | 1 abierto (baja), fuera de SLA; 1 cerrado | Convertir las preguntas recurrentes en FAQ cuando haya tickets reales |

## 5. Acuerdos y próximos pasos

- Mantener la caché activa y vaciarla después de cada despliegue: el plugin lo hace al guardar contenido, y en despliegues se hace desde su pantalla de ajustes.
- Al contratar el hosting: OPcache, `mod_deflate`, `mod_expires` y un cron real del servidor para el monitoreo.
- Cerrar los tickets de prueba y asignar un responsable de la mesa de ayuda antes de abrir la tienda.
- Al publicar: conectar GA4 y Search Console. El siguiente reporte debe llenar las filas marcadas como *No disponible*.

**Revisó:** ____________  **Fecha de la próxima revisión:** 30 días después de la publicación
