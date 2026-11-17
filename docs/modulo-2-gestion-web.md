# Módulo 2 – Herramientas de productividad y gestión web

## 1. WordPress y WooCommerce

### 1.1 Configuración aplicada (`wp ah sembrar`)

| Área | Configuración |
|---|---|
| Sitio | Título *Artesanías Huejutla*, zona horaria America/Mexico_City, idioma es_MX, enlaces `/%postname%/` |
| Tienda | Moneda **MXN** ($, 2 decimales), ubicación Huejutla de Reyes, Hidalgo (C.P. 43000), venta solo en México, sin impuestos calculados (precios finales) |
| Inventario | Gestión de existencias activa, aviso de existencias bajas en 3 piezas; productos agotados visibles con insignia |
| Cuentas | Compra como invitado, registro en Mi cuenta y en el pago; contraseña elegida por el cliente (gestionada por WordPress) |
| Reseñas | Activas, **solo de compradores verificados** (no se cargaron reseñas ficticias) |
| URLs | Producto `/producto/…`, categoría `/categoria/…` |
| Páginas | Tienda, Carrito, Finalizar compra, Mi cuenta (shortcodes clásicos para integrar el tema y los eventos de GA4) |

### 1.2 Catálogo inicial

**Categorías:** Cestería de palma · Textiles bordados · Canastas artesanales · Decoración tradicional · Accesorios artesanales · Regalos mexicanos. Cada una tiene imagen, descripción y SEO propio.

**Productos de la especificación:**

| SKU | Producto | Precio | Categoría |
|---|---|---|---|
| AH-001 | Tazón Huasteco Decorado | **$150** (antes $180) | Decoración tradicional |
| AH-002 | Rebozo Tenango Bordado | **$850** | Textiles bordados |
| AH-003 | Cesta de Palma con Tapa | **$120** | Cestería de palma |
| AH-004 | Canasta de Palma Tejida | **$250** (antes $290) | Canastas artesanales |
| AH-005 | Bolsa Artesanal de Palma | **$320** | Accesorios artesanales |
| AH-006 | Mantel Bordado a Mano | **$450** (antes $520) | Textiles bordados |

Además hay 9 productos complementarios (AH-007 a AH-015) para que todas las categorías tengan contenido, incluido uno **agotado** para mostrar ese estado. Cada producto tiene descripción corta y larga, cuidados, atributos visibles (**Materiales, Lugar de elaboración, Medidas**), galería, existencias y SEO propio.

### 1.3 Operación desde el administrador

| Tarea | Ruta en el administrador |
|---|---|
| Registrar / editar / eliminar producto | Productos → Añadir nuevo / editar / Papelera |
| Crear categorías | Productos → Categorías |
| Inventario | Productos → editar → pestaña *Inventario* (o edición rápida) |
| Precios y ofertas programadas | Productos → editar → *General* (precio rebajado y fechas) |
| Descuentos | Marketing → Cupones (`HUASTECA10` 10 %, `ENVIOGRATIS`, `ARTESANO50` $50 desde $500) |
| Pedidos y estados | WooCommerce → Pedidos (Pendiente, Procesando, En espera, Completado, Cancelado, Reembolsado) |
| Clientes | WooCommerce → Clientes |
| Envíos | WooCommerce → Ajustes → Envío → zona **México**: estándar $120 (3–7 días), express $220 (1–3 días), **gratis desde $999**, recoger en taller (sin costo) |
| Pagos | WooCommerce → Ajustes → Pagos |

### 1.4 Métodos de pago

| Método | Estado | Comentario |
|---|---|---|
| Transferencia bancaria (SPEI) | Activo | No procesa dinero. **Pendiente:** capturar los datos bancarios reales del negocio. |
| Pago contra entrega | Activo | No procesa dinero. |
| Tarjeta (Mercado Pago / Stripe / PayPal) | **Preparado, sin activar** | Instalar el plugin oficial y capturar las llaves **reales** del negocio. Primero en modo *sandbox* y después en producción. No se usaron credenciales ficticias. |

En entornos que no son de producción, el checkout muestra un aviso de que no se procesan cobros (`AH_Tienda::aviso_pago_demo`).

### 1.5 Integración visual

El tema `artesanias-huejutla` integra WooCommerce **solo con hooks**, sin copiar plantillas, para que las actualizaciones de WooCommerce no rompan el diseño:

- Contenedores propios y panel de filtros (búsqueda, categorías, rango de precio con `min_price`/`max_price` nativos).
- Tarjetas con categoría, insignia de descuento con porcentaje real, insignia de agotado y botón de favoritos.
- Ficha con "Pieza hecha a mano", favoritos, compartir e información de envío.
- Contador del carrito actualizado por AJAX (fragmentos de WooCommerce).

## 2. Blog

Entradas publicadas (todas con título, imagen destacada, contenido, categoría, fecha, autor, meta description, botones para compartir y productos relacionados):

| Artículo | Categoría | Palabra clave | Productos relacionados |
|---|---|---|---|
| Historia de las artesanías de Huejutla | Cultura huasteca | artesanías Huejutla | Canasta, tortillero, mantel |
| El proceso de elaboración de las canastas de palma | Guías | canasta de palma tejida | Canasta, cesta con tapa, tortillero |
| Tradiciones artesanales de la Huasteca Hidalguense | Cultura huasteca | artesanías de la Huasteca | Rebozo, blusa, sombrero |
| Importancia de apoyar a los artesanos locales | Comercio justo | artesanías mexicanas hechas a mano | Caja de regalo, bolsa, monedero |
| Cómo identificar una artesanía hecha a mano | Guías | artesanía hecha a mano | Mantel, servilletas, rebozo |

Los productos relacionados se guardan en el campo `_ah_productos_relacionados` de cada entrada.

## 3. Google Analytics 4

### 3.1 Activación

1. Crear una propiedad GA4 en [analytics.google.com](https://analytics.google.com) con un flujo web para el dominio real.
2. Copiar el **ID de medición** (`G-XXXXXXXXXX`).
3. Pegarlo en **Gestión TI → Ajustes → ID de medición GA4**. Opcionalmente, marcar "No medir a administradores" y "Modo depuración" (para usar *DebugView*).
4. Validar en GA4 → Administrar → DebugView mientras se navega el sitio.

Sin un ID válido **no se inserta ningún código de Google** y el tablero indica "Pendiente".

### 3.2 Eventos implementados (`class-ah-analitica.php`)

| Evento | Cuándo se envía | Parámetros |
|---|---|---|
| `page_view` | Cada página (automático de gtag) | — |
| `view_item_list` | Catálogo y categorías | `item_list_name`, `items[]` |
| `view_item` | Ficha de producto | `currency`, `value`, `items[]` |
| `add_to_cart` | Al agregar desde la ficha (servidor) o desde listados por AJAX (`ga4.js`), sin duplicar | `currency`, `value`, `items[]` |
| `view_cart` | Página del carrito | `currency`, `value`, `items[]` |
| `begin_checkout` | Finalizar compra | `currency`, `value`, `items[]` |
| `purchase` | Pedido recibido, **una sola vez por pedido** (meta `_ah_ga4_purchase`) | `transaction_id`, `value`, `shipping`, `tax`, `coupon`, `items[]` |

Cada `item` incluye `item_id` (SKU), `item_name`, `item_brand`, `item_category`, `price` y `quantity`. Los eventos también quedan en `window.dataLayer`, por lo que son compatibles con Google Tag Manager.

### 3.3 Métricas a consultar

Usuarios, sesiones, páginas vistas, tiempo de interacción, fuentes de tráfico, dispositivos, productos consultados, agregados al carrito y compras. El formato del reporte está en el [módulo 1, sección 3](modulo-1-marketing-seo-sem.md#3-métricas-de-google-analytics-4-formato-por-llenar).

## 4. Google Search Console

| Paso | Cómo |
|---|---|
| 1. Verificación de propiedad | **Dominio** (recomendado): registro TXT en el DNS. **Prefijo de URL**: pegar el valor `content` de la meta etiqueta en *Gestión TI → Ajustes → Search Console*; el plugin imprime `<meta name="google-site-verification">`. |
| 2. Envío del sitemap | Search Console → Sitemaps → `sitemap_index.xml` |
| 3. Inspección de URLs | Inspeccionar `/`, `/tienda/` y un producto, y solicitar indexación |
| 4. Estado de indexación | Indexación → Páginas: revisar "Excluidas" (deben ser carrito, pago, cuenta y búsquedas, por `noindex`) |
| 5. Rendimiento | Rendimiento → clics, impresiones, CTR y posición promedio por consulta y página |
| 6. Vincular con GA4 | GA4 → Administrar → Vínculos de Search Console |

La verificación **no se simuló**: requiere el dominio público.

## 5. Redes sociales y conferencias web

| Elemento | Implementación |
|---|---|
| Perfiles (Facebook, Instagram, TikTok) | URLs configurables en *Gestión TI → Ajustes*; los íconos del pie aparecen solo si hay URL |
| WhatsApp Business | Número configurable, con botón flotante, en la mesa de ayuda y en contacto (`wa.me` con mensaje predeterminado) |
| Compartir | Facebook, X, WhatsApp, Pinterest y copiar enlace, en productos y entradas (no requieren cuentas) |
| Conferencias web | URL de sala o agenda (Google Meet, Zoom, Teams o Calendly) configurable, con botón "Agendar videollamada" en contacto y en la mesa de ayuda |

No se inventaron cuentas oficiales: los campos quedan vacíos hasta que el negocio capture las reales.
