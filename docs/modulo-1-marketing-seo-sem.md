# Módulo 1 – Marketing digital y posicionamiento SEO/SEM

> **Reporte de analítica y campaña SEM.** Las cifras de la campaña son **proyecciones calculadas** a partir del presupuesto académico. Las métricas de Google Analytics y Search Console se dejan como formato por llenar, porque el sitio aún no tiene dominio público ni propiedades vinculadas. **No se inventan resultados.**

## 1. Estrategia SEO

### 1.1 Objetivo

Posicionar a Artesanías Huejutla en las búsquedas de personas que quieren **comprar artesanías hechas a mano** de la Huasteca, y captar tráfico informativo mediante el blog.

### 1.2 Palabras clave

| Palabra clave | Intención | Página objetivo |
|---|---|---|
| artesanías Huejutla | Marca / navegacional | Portada, historia del blog |
| canasta de palma tejida | Transaccional | Producto *Canasta de Palma Tejida*, categoría *Canastas artesanales*, artículo del proceso |
| artesanías de la Huasteca | Informativa / transaccional | Nosotros, artículo de tradiciones |
| comprar artesanías Hidalgo | Transaccional | Tienda |
| artesanías mexicanas hechas a mano | Transaccional / informativa | Tienda, artículo "Importancia de apoyar…" |
| *Long tail:* rebozo bordado a mano, mantel bordado a mano, tortillero de palma, cómo identificar una artesanía hecha a mano | Transaccional / informativa | Fichas de producto y blog |

### 1.3 SEO on-page implementado

| Elemento | Implementación | Dónde verificar |
|---|---|---|
| **Title** de la portada | `Artesanías de Huejutla, Hidalgo \| 100% Hechas a Mano` | Rank Math en la página *Inicio* |
| **Meta description** | `Compra artesanías originales de Huejutla: cestería y textiles elaborados a mano por artesanos de la Huasteca. Envíos a todo México.` | Ídem |
| **H1** | `Artesanías Auténticas de la Huasteca Hidalguense` (editable en el Personalizador) | `front-page.php` |
| H2/H3 | Jerarquía semántica por sección; un solo H1 por página | Todas las plantillas |
| URLs amigables | `/producto/canasta-de-palma-tejida/`, `/categoria/textiles-bordados/`, `/historia-de-las-artesanias-de-huejutla/` | Ajustes → Enlaces permanentes |
| Slugs descriptivos | Todos los productos, categorías, páginas y entradas en español y sin palabras vacías | — |
| Enlaces internos | Artículos con enlaces a productos y categorías; productos relacionados; migas de pan | Blog |
| Títulos y descripciones únicos | Cada producto, categoría, página y entrada tiene `rank_math_title` y `rank_math_description` propios | Caja Rank Math |
| Canonical | Automático (Rank Math) | `<link rel="canonical">` |
| Open Graph / Twitter | Automático, con imagen por defecto para páginas sin imagen destacada | `<meta property="og:*">` |
| Datos estructurados | `Organization`, `WebSite`+`SearchAction`, `Product`+`Offer`, `BreadcrumbList`, `BlogPosting`, `CollectionPage`, `FAQPage` (shortcode `[ah_faq]`) y `VideoObject` (shortcode `[ah_video]`) | [Prueba de resultados enriquecidos](https://search.google.com/test/rich-results) |
| Idioma | `lang="es-MX"` y `og:locale es_MX` | — |

Verificación automatizada en local (resultado real del 05/11/2026):

| URL | Title | H1 | Schema |
|---|---|---|---|
| `/` | Artesanías de Huejutla, Hidalgo \| 100% Hechas a Mano | Artesanías Auténticas de la Huasteca Hidalguense | Organization, WebSite, WebPage, SearchAction |
| `/producto/canasta-de-palma-tejida/` | Canasta de Palma Tejida \| Artesanía hecha a mano en Huejutla | Canasta de Palma Tejida | Product, Offer, BreadcrumbList |
| `/preguntas-frecuentes/` | Preguntas frecuentes \| Artesanías Huejutla | Preguntas frecuentes | FAQPage, Question, Answer |
| `/carrito/` | — | — | `noindex, follow` |

### 1.4 robots.txt y sitemap XML

**Sitemap dinámico (Rank Math):** `/sitemap_index.xml` incluye `post-sitemap.xml`, `page-sitemap.xml`, `product-sitemap.xml`, `product_cat-sitemap.xml`, `category-sitemap.xml` y `ah_servicio-sitemap.xml`. Se regenera automáticamente al publicar o editar contenido. Se excluyen el carrito, el pago, Mi cuenta, Favoritos y los adjuntos.

**robots.txt** (generado por el plugin con el filtro `robots_txt`):

```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Disallow: /*?add-to-cart=
Disallow: /*?orderby=
Disallow: /*?s=
Disallow: /wp-json/
Disallow: /carrito/
Disallow: /finalizar-compra/
Disallow: /mi-cuenta/
Disallow: /favoritos/

Sitemap: https://<dominio>/sitemap_index.xml
```

Además, las páginas privadas emiten `<meta name="robots" content="noindex, follow">` (carrito, pago, cuenta, favoritos, búsquedas y consultas de tickets).

> En local, WordPress está en `/artesanias-wp/` y no en la raíz, así que `robots.txt` se consulta en `http://localhost/artesanias-wp/?robots=1`. En producción, con dominio propio, se sirve en `/robots.txt`.

### 1.5 SEO de imágenes y video

- Nombres de archivo descriptivos (`canasta-de-palma-tejida`, `bordado-flores-hilo-algodon`…).
- **ALT, TITLE y pie de foto** en cada imagen importada. El plugin agrega TITLE y ALT de respaldo, y Rank Math completa los faltantes.
- **WebP:** el plugin convierte los tamaños intermedios a WebP (filtro `image_editor_output_format`). Verificado: todas las imágenes del sitio se sirven como `.webp`.
- **Lazy loading** en todas las imágenes excepto la principal de la portada, que usa `fetchpriority="high"` y precarga para mejorar el LCP.
- **Video:** el shortcode `[ah_video url="" titulo="" descripcion="" miniatura="" duracion="PT2M" fecha="2026-11-01"]` incrusta YouTube, Vimeo o MP4 y genera `VideoObject` con título, descripción, miniatura, duración y fecha.

## 2. Campaña SEM (simulación)

> **Simulación documentada.** No se creó ninguna cuenta ni se activó ninguna campaña en Google Ads, y no se generaron gastos. La misma información está disponible de forma interactiva en *Gestión TI → Campaña SEM*, donde los supuestos se pueden editar.

### 2.1 Campaña

| Concepto | Valor |
|---|---|
| Nombre | Artesanías Huejutla |
| Tipo | Búsqueda + Display de remarketing |
| Objetivo | Ventas en la tienda en línea |
| Estrategia de puja | CPC manual mejorado al inicio; "Maximizar conversiones" al acumular 30 conversiones |
| Calendario | Lunes a domingo, 8:00–23:00 (hora del centro) |
| Idioma | Español |

### 2.2 Grupos de anuncios y palabras clave

| Grupo | Palabras clave (concordancia) | Página de destino |
|---|---|---|
| Marca y origen | "artesanías Huejutla", [artesanías Huejutla], "artesanías de la Huasteca", "comprar artesanías Hidalgo" | `/` |
| Cestería de palma | "canasta de palma tejida", [canasta de palma tejida], "cesta de palma", "tortillero de palma" | `/categoria/cesteria-de-palma/` |
| Textiles bordados | "rebozo bordado", "mantel bordado a mano", "textiles huastecos" | `/categoria/textiles-bordados/` |
| Hecho a mano | "artesanías mexicanas hechas a mano", "regalos artesanales mexicanos" | `/tienda/` |

**Palabras clave negativas:** gratis, receta, curso, tutorial, cómo hacer, empleo, pdf, molde.

### 2.3 Anuncio de búsqueda adaptable

| Elemento | Texto | Caracteres |
|---|---|---|
| Título 1 | Artesanías de Huejutla | 22/30 |
| Título 2 | Directo del Productor | 21/30 |
| Título 3 | Envíos a Todo México | 20/30 |
| Título 4 | Canastas de Palma Tejida | 24/30 |
| Título 5 | 100% Hechas a Mano | 18/30 |
| Descripción 1 (especificación) | Canastas y textiles artesanales de la Huasteca, elaborados 100% a mano con materiales naturales. | **96/90 – excede el límite** |
| Descripción 2 | Apoya a artesanos locales. Calidad garantizada y entrega segura a domicilio. | 76/90 |
| **Descripción 1 ajustada (para publicar)** | Canastas y textiles de la Huasteca, elaborados 100% a mano con materiales naturales. | 84/90 |

> **Hallazgo:** la Descripción 1 de la especificación tiene 96 caracteres y Google Ads admite máximo 90 por descripción, por lo que sería rechazada. Se conserva como referencia y se propone la versión ajustada, que mantiene el mensaje. El panel *Campaña SEM* valida automáticamente la longitud de cada texto.

Vista previa:

> **Patrocinado** · artesaniashuejutla.mx/tienda
> **Artesanías de Huejutla | Directo del Productor | Envíos a Todo México**
> Canastas y textiles de la Huasteca, elaborados 100% a mano con materiales naturales. Apoya a artesanos locales…

### 2.4 Segmentación

**Geográfica:** todo México, con ajuste de puja +20 % en Hidalgo, CDMX, Estado de México y Puebla, y +10 % en Nuevo León, Jalisco, Querétaro, Veracruz y San Luis Potosí. Se excluyen ubicaciones fuera de México.

**Audiencias:** segmentos de mercado (decoración del hogar, regalos, artesanía y cultura mexicana), afinidad (cocina mexicana, viajeros culturales), remarketing Display (visitantes que agregaron al carrito sin comprar, ventana de 30 días), edad 25–65, móvil y escritorio.

### 2.5 Presupuesto y proyección (CPC vs. CPM)

Supuestos: presupuesto **$3,000 MXN/mes**, **CPC $4.50**, CTR 5 %, conversión 2 %, ticket promedio $350, CPM de referencia $35.

| Indicador | Fórmula | Proyección |
|---|---|---|
| Presupuesto diario | 3,000 ÷ 30 | **$100.00 MXN** |
| Clics estimados | 3,000 ÷ 4.50 | **≈ 667 / mes** (≈ 22.2 / día) |
| Impresiones | 667 ÷ 0.05 | ≈ 13,340 |
| Conversiones esperadas | 667 × 0.02 | ≈ 13.3 |
| CPA | 3,000 ÷ 13.3 | ≈ $225.56 |
| Ingresos estimados | 13.3 × 350 | ≈ $4,655 |
| ROAS | 4,655 ÷ 3,000 | ≈ 1.55 |
| CPM efectivo de la estrategia CPC | 3,000 ÷ 13,340 × 1,000 | ≈ $224.89 |
| Impresiones con estrategia CPM ($35) | 3,000 ÷ 35 × 1,000 | ≈ 85,714 |

**Lectura:** el CPC conviene en Búsqueda, donde hay intención de compra; el CPM conviene en Display para reconocimiento de marca y remarketing. Con un ROAS proyectado de 1.55, la meta de mejora es subir la conversión (fichas de producto, confianza y envío gratis) para superar un ROAS de 2.0.

Estas cifras se calculan con `AH_Calculos::estimar_sem()`, cubierta por pruebas unitarias.

### 2.6 Indicadores de rendimiento

| KPI | Definición | Meta inicial |
|---|---|---|
| CTR | Clics ÷ impresiones | ≥ 5 % |
| CPC | Costo ÷ clics | ≤ $4.50 |
| Tasa de conversión | Compras ÷ clics | ≥ 2 % |
| CPA | Costo ÷ conversiones | ≤ $225 |
| ROAS | Ingresos ÷ costo | ≥ 2.0 |
| Nivel de calidad | Calificación de Google por palabra clave | ≥ 7/10 |

## 3. Métricas de Google Analytics 4 (formato por llenar)

La medición se activa al capturar el ID de una propiedad real (ver [módulo 2](modulo-2-gestion-web.md#3-google-analytics-4)).

| Métrica (GA4) | Informe | Periodo 1 | Periodo 2 | Variación |
|---|---|---|---|---|
| Usuarios | Adquisición → Descripción general | _pendiente_ | | |
| Sesiones | Adquisición → Adquisición de tráfico | _pendiente_ | | |
| Páginas vistas | Interacción → Páginas y pantallas | _pendiente_ | | |
| Tiempo medio de interacción | Interacción → Descripción general | _pendiente_ | | |
| Fuentes de tráfico | Adquisición de tráfico (canal predeterminado) | _pendiente_ | | |
| Dispositivos | Tecnología → Descripción general | _pendiente_ | | |
| Productos vistos (`view_item`) | Monetización → Compras de comercio electrónico | _pendiente_ | | |
| Agregados al carrito (`add_to_cart`) | Ídem | _pendiente_ | | |
| Pagos iniciados (`begin_checkout`) | Ídem | _pendiente_ | | |
| Compras (`purchase`) e ingresos | Monetización → Descripción general | _pendiente_ | | |
| Tasa de conversión del embudo | Exploración de embudo | _pendiente_ | | |

## 4. Métricas de Google Search Console (formato por llenar)

| Métrica | Informe | Periodo 1 | Periodo 2 | Variación |
|---|---|---|---|---|
| Clics | Rendimiento → Resultados de búsqueda | _pendiente_ | | |
| Impresiones | Ídem | _pendiente_ | | |
| CTR | Ídem | _pendiente_ | | |
| Posición promedio | Ídem | _pendiente_ | | |
| Páginas indexadas | Indexación → Páginas | _pendiente_ | | |
| URLs del sitemap enviadas e indexadas | Sitemaps | _pendiente_ | | |
| Consultas principales | Rendimiento → Consultas | _pendiente_ | | |
| Core Web Vitals | Experiencia → Métricas web principales | _pendiente_ | | |

## 5. Recomendaciones de mejora

1. **Fotografías propias** de cada producto (varios ángulos y escala con una mano u objeto conocido) para mejorar la conversión y el SEO de imágenes.
2. **Reseñas verificadas:** activar el correo de solicitud de reseña tras la entrega; las estrellas reales aumentan el CTR en buscadores.
3. **Contenido:** un artículo mensual (por ejemplo "Xantolo: canastas para la ofrenda" o "Guía de regalos artesanales") con enlace a productos.
4. **SEO local:** perfil de empresa en Google con la dirección real del taller y las fotografías.
5. **Velocidad:** caché de página y CDN en producción; revisar Core Web Vitals en Search Console.
6. **SEM:** iniciar solo con el grupo *Cestería de palma* (mayor intención de compra), medir dos semanas y ampliar.
7. **Remarketing:** solo después de cumplir el aviso de privacidad y el consentimiento de cookies.
