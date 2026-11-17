# Módulo 4 – Gestión de servicios de TI (ITIL)

Prácticas ITIL 4 aplicadas: **Gestión del catálogo de servicios**, **Mesa de servicio** (*Service Desk*), **Gestión de incidentes**, **Gestión de solicitudes de servicio**, **Gestión de niveles de servicio (SLA)** y **Mejora continua**. Todas están implementadas en el plugin `ah-gestion-ti` y son visibles en el sitio.

## 1. Catálogo de servicios

- Público: **`/servicios/`** (y una ficha por servicio).
- Administrable: *Gestión TI → Catálogo de servicios* (tipo de contenido `ah_servicio`), con los campos **nombre, descripción, beneficios, disponibilidad o condiciones de atención, canal de contacto y enlace**.

| Servicio | Disponibilidad / condiciones | Canal |
|---|---|---|
| Compra de productos artesanales | Tienda en línea 24/7 | Tienda en línea |
| Atención al cliente | Lunes a sábado; respuesta inicial < 24 h hábiles | Mesa de ayuda, contacto, WhatsApp |
| Seguimiento de pedidos | 24/7 en Mi cuenta | Mi cuenta → Pedidos |
| Asistencia con compras | Lunes a sábado, horario de atención | Mesa de ayuda (Problemas con compras) |
| Información de envíos | Consulta inmediata | Política de envíos y devoluciones |
| Solicitudes de devolución | 15 días naturales posteriores a la entrega | Mesa de ayuda (Problemas con pedidos) |
| Soporte técnico | Incidentes críticos: objetivo de solución en 4 h | Mesa de ayuda (Técnicos / Acceso) |
| Atención a artesanos y proveedores | Lunes a viernes, previa cita | Contacto o videollamada |

### Servicio "Seguimiento de pedidos" implementado

| Etapa visible al cliente | Estado de WooCommerce | Quién lo cambia |
|---|---|---|
| Compra confirmada | Pendiente de pago / En espera | Automático al comprar |
| En preparación | Procesando | Automático al confirmar el pago, o el personal |
| En camino (o *Lista para recoger*) | **En camino** (estado propio) | Personal: captura paquetería y guía en *Envío y seguimiento* |
| Entregado | Completado | Personal |

Cada cambio guarda la fecha y hora reales, notifica al cliente (campana y, al salir a ruta, correo) y alimenta el indicador *A tiempo / Retrasado* (objetivo: estándar 3–7, express 1–3 días hábiles). Si el cliente necesita ayuda, *Ayuda con mi compra* abre un ticket ya clasificado como "Problemas con pedidos" con el número de compra.

## 2. Mesa de ayuda (Help Desk)

### 2.1 Funciones del usuario (`/mesa-de-ayuda/`)

- **Crear un ticket:** nombre, correo, **categoría**, **prioridad**, **asunto**, **descripción** y número de pedido (opcional).
- Recibe un **número de seguimiento** (`AH-2026-000001`) en pantalla y por correo.
- **Consultar** el ticket con número **y** correo, ver las etapas ITIL y el historial público, y **responder**.
- Clientes con cuenta: sección **"Mis tickets"** en la página y en *Mi cuenta → Soporte*.
- El formulario de **Contacto** también genera un ticket (categoría *Solicitudes de información*, canal *contacto*).

### 2.2 Funciones del personal (*Gestión TI → Mesa de ayuda*)

- Listado con filtros por estado, categoría y prioridad, búsqueda, "Asignados a mí" y "Fuera de SLA", con contadores y paginación.
- Detalle con la descripción, datos del solicitante, enlace al pedido de WooCommerce, tipo ITIL, SLA y **barra de avance de 7 etapas**.
- Gestión: **reclasificar, asignar prioridad** (con la matriz ITIL como referencia), **asignar agente, cambiar estado**, registrar **respuesta al cliente** (con notificación por correo) o **nota interna**.
- **Historial completo** y auditable: cada cambio queda registrado con autor, fecha y visibilidad.
- Tablero con indicadores reales: tickets del periodo, fuera de SLA, cumplimiento de SLA y tiempos promedio de primera respuesta y resolución.

### 2.3 Almacenamiento

Base de datos de WordPress, en tablas propias creadas con `dbDelta`:

| Tabla | Contenido |
|---|---|
| `wp_ah_tickets` | número, solicitante, correo, asunto, descripción, categoría, prioridad, estado, canal, pedido, agente, fechas de creación, actualización, vencimiento SLA, primera respuesta, resolución y cierre |
| `wp_ah_ticket_eventos` | historial: registro, clasificación, prioridad, asignación, estado, comentario del cliente, respuesta y nota interna (pública o privada) |

## 3. Flujo de atención ITIL

```
1. Registro ─▶ 2. Clasificación ─▶ 3. Priorización ─▶ 4. Asignación ─▶ 5. Diagnóstico ─▶ 6. Resolución ─▶ 7. Cierre
 (cliente)      (categoría)          (agente confirma)   (agente)          (En proceso /       (Resuelto)       (Cerrado)
                                                                            Pendiente)
```

| Etapa | Se considera completa cuando… |
|---|---|
| 1. Registro | Se crea el ticket (número y evento "registro") |
| 2. Clasificación | Tiene categoría (el agente puede reclasificar) |
| 3. Priorización | Un agente confirma o ajusta la prioridad |
| 4. Asignación | Tiene un agente responsable |
| 5. Diagnóstico | Estado *En proceso* o *Pendiente* (en espera del cliente) |
| 6. Resolución | Estado *Resuelto* |
| 7. Cierre | Estado *Cerrado* (el cliente ya no puede responder) |

**Estados:** Abierto → En proceso → Pendiente → Resuelto → Cerrado. Si el cliente responde a un ticket *Pendiente*, vuelve automáticamente a *En proceso*. Reabrir un ticket limpia la fecha de cierre.

## 4. Clasificación de incidentes y solicitudes

| Categoría | Tipo ITIL | Ejemplos |
|---|---|---|
| Problemas con compras | Incidente | No puedo agregar al carrito, cupón no aplica |
| Problemas con pagos | Incidente | Error al pagar, cargo no reflejado |
| Problemas con pedidos | Incidente | Pieza dañada, retraso, pedido incompleto, devolución |
| Problemas de acceso | Incidente | No puedo iniciar sesión, recuperar contraseña |
| Problemas técnicos | Incidente | Página con error, sitio lento |
| Solicitudes de información | **Solicitud de servicio** | Mayoreo, pedidos personalizados, derechos ARCO, contacto |

## 5. Priorización y SLA

**Matriz impacto × urgencia** (`AH_Calculos::prioridad_itil`, con pruebas unitarias):

| | Urgencia alta | Urgencia media | Urgencia baja |
|---|---|---|---|
| **Impacto alto** | Crítica | Alta | Media |
| **Impacto medio** | Alta | Media | Baja |
| **Impacto bajo** | Media | Baja | Baja |

| Prioridad | Objetivo de resolución (SLA) | Ejemplo |
|---|---|---|
| Crítica | 4 h | El sitio no permite comprar a nadie |
| Alta | 24 h | Pedido pagado que no aparece |
| Media | 48 h | Duda sobre un envío |
| Baja | 72 h | Información de mayoreo |

El cliente propone una prioridad (baja, media o alta) y el agente la confirma o la ajusta, incluida *Crítica*. Al cambiar la prioridad, el vencimiento se recalcula desde la fecha de registro.

## 6. Gestión de solicitudes

1. El cliente registra la solicitud (Mesa de ayuda o Contacto).
2. Se clasifica como *Solicitud de información* (solicitud de servicio, prioridad baja por defecto).
3. Se asigna al área correspondiente (ventas, logística o atención a artesanos).
4. Se atiende con una respuesta pública y se cierra al entregar la información.
5. Las solicitudes recurrentes se convierten en contenido: FAQ, artículo del blog o servicio del catálogo.

## 7. Permisos y privacidad

| Rol | Capacidad | Puede |
|---|---|---|
| Visitante | — | Crear tickets; consultar **solo** con número **y** correo coincidentes |
| Cliente con sesión | — | Ver únicamente sus tickets (por ID de usuario o correo de la cuenta) |
| Agente de mesa de ayuda (rol `ah_agente_soporte`) | `ah_gestionar_tickets` | Gestionar tickets; sin acceso a la configuración |
| Gerente de tienda | `ah_gestionar_tickets` | Ídem, además de WooCommerce |
| Administrador | `ah_gestionar_tickets`, `ah_gestion_ti` | Todo, incluidos SEM, monitoreo, PDCA y ajustes |

Controles: nonces en todos los formularios, límite de intentos por IP en la consulta (10 cada 10 min) y en la creación (5 cada 10 min), mensaje genérico ante datos incorrectos (no revela si un número existe), honeypot anti-spam y patrón PRG (sin reenvíos duplicados).

## 8. Mejora continua (PDCA)

- Módulo: *Gestión TI → Mejora continua* (tipo de contenido privado `ah_reporte_pdca`).
- Cada reporte registra: **periodo, fuentes** (Analytics, Search Console, rendimiento, disponibilidad, errores, tickets, opiniones), **fase PDCA**, problema detectado, evidencia, propuesta, **responsable**, fecha compromiso, indicador/meta, **resultado** y acción siguiente.
- El editor muestra, como referencia, **datos reales** del sistema de los últimos 30 días: tickets, fuera de SLA, cumplimiento de SLA, disponibilidad y tiempo de respuesta.
- Plantilla para el reporte periódico: [plantillas/reporte-mejora-continua.md](plantillas/reporte-mejora-continua.md).
- **Primer reporte con datos reales:** [mejora-continua/reporte-2026-11.md](mejora-continua/reporte-2026-11.md) (monitoreo, velocidad antes y después de la caché, errores de PHP y tickets; GA4 y Search Console marcados como *No disponible* hasta que se publique el sitio). También está capturado en WordPress como reporte PDCA en fase *Verificar*.

| Fase | Qué se hace en Artesanías Huejutla |
|---|---|
| **Planificar** | Revisar mensualmente GA4, Search Console, monitoreo, errores de PHP, tickets y reseñas; elegir de 1 a 3 problemas con un indicador medible |
| **Hacer** | Implementar en una rama `feature/*`, pasar CI y desplegar a pruebas y luego a producción |
| **Verificar** | Comparar el indicador antes y después (mismo periodo y misma fuente) |
| **Actuar** | Si mejoró, estandarizar (documentar, FAQ, servicio); si no, ajustar y repetir el ciclo |
