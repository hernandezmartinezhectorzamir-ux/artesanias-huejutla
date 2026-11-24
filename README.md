# Artesanías Huejutla – Plataforma de comercio electrónico

Tienda en línea de artesanías de Huejutla, Hidalgo, en la Huasteca Hidalguense, construida con **WordPress + WooCommerce + PHP + MySQL**.

Proyecto de la materia **Gestiones Tecnológicas Empresariales**, de la carrera de Ingeniería en Sistemas Computacionales (ISC) del Instituto Tecnológico de Huejutla. Cubre los cuatro módulos de la especificación:

| Módulo | Qué se implementó | Documento |
|---|---|---|
| 1. Marketing digital (SEO/SEM) | SEO on-page con Rank Math, Schema.org, Open Graph, sitemap dinámico, robots.txt, imágenes WebP con ALT/TITLE, video SEO y campaña SEM simulada | [docs/modulo-1-marketing-seo-sem.md](docs/modulo-1-marketing-seo-sem.md) |
| 2. Gestión web | WordPress/WooCommerce configurados, 15 productos, 6 categorías, envíos, pagos, cupones, blog, GA4 (eventos de comercio electrónico), Search Console, redes sociales y videoconferencia | [docs/modulo-2-gestion-web.md](docs/modulo-2-gestion-web.md) |
| 3. DevOps | Git (flujo main/develop/feature), CI/CD con GitHub Actions, PHPCS, PHPUnit, prueba de humo, despliegue con respaldo, monitoreo y métricas | [docs/modulo-3-devops.md](docs/modulo-3-devops.md) |
| 4. ITIL | Catálogo de servicios, mesa de ayuda con flujo ITIL (7 etapas, SLA y matriz de prioridad), permisos y mejora continua PDCA | [docs/modulo-4-itil.md](docs/modulo-4-itil.md) |

---

## 1. Arquitectura

```
Navegador ──HTTPS──▶ Servidor web (Apache/Nginx + PHP 8.x)
                        │
                        ├── WordPress (CMS, núcleo sin modificar)
                        │     ├── WooCommerce ............ catálogo, carrito, pagos, pedidos, inventario
                        │     ├── Rank Math SEO ........... títulos, metadatos, Schema, sitemap XML
                        │     ├── Tema "artesanias-huejutla" ........ presentación (este repositorio)
                        │     └── Plugin "ah-gestion-ti" ............ lógica propia (este repositorio)
                        │           ├── Mesa de ayuda ITIL (tablas wp_ah_tickets, wp_ah_ticket_eventos)
                        │           ├── Catálogo de servicios (tipo de contenido ah_servicio)
                        │           ├── Mejora continua PDCA (tipo de contenido ah_reporte_pdca)
                        │           ├── Campaña SEM simulada · GA4 · Search Console
                        │           ├── Monitoreo (WP-Cron + /wp-json/ah/v1/salud)
                        │           └── Favoritos · SEO complementario · reglas de tienda · WP-CLI
                        └── MySQL / MariaDB
GitHub ──Actions──▶ CI (lint, WPCS, PHPUnit, humo) ──▶ Despliegue (pruebas → producción con aprobación)
```

**Principio de diseño:** el tema solo se encarga de la presentación. Todo lo funcional vive en el plugin, para que no se pierda si se cambia de tema. No se modifica el núcleo de WordPress ni el de WooCommerce: todo se integra mediante *hooks*.

## 2. Estructura del repositorio

```
artesanias-huejutla-wp/
├── wp-content/
│   ├── themes/artesanias-huejutla/     Tema personalizado (PHP, CSS, JS, plantillas WooCommerce por hooks)
│   └── plugins/ah-gestion-ti/          Plugin propio (ITIL, SEM, GA4, monitoreo, SEO, favoritos, WP-CLI)
│       └── datos-demo/                 Contenido inicial (productos, páginas, blog, servicios, imágenes)
├── .github/workflows/                  ci.yml · despliegue.yml · monitoreo.yml
├── tests/                              Pruebas unitarias (PHPUnit)
├── scripts/                            prueba-humo.sh · respaldo.sh · router.php
├── config/                             wp-config-produccion.example.php (sin credenciales)
├── docs/                               Documentación por módulo y plantillas
├── composer.json · phpcs.xml.dist · phpunit.xml.dist · .editorconfig · .env.example · .gitignore
└── README.md
```

## 3. Requisitos

| Componente | Versión probada |
|---|---|
| PHP | 8.2 (mínimo 8.0) con mysqli, curl, gd, intl, mbstring, zip, openssl |
| MySQL / MariaDB | MariaDB 10.4 (local) · 10.11 (CI) |
| WordPress | 7.1.4 (es_MX) |
| WooCommerce | 11.2.2 |
| Rank Math SEO | 1.0.281 |
| WP-CLI | 2.12 |
| Composer | 2.x (solo desarrollo) |

## 4. Instalación

### 4.1 Local (XAMPP en Windows)

1. Inicia Apache y MySQL desde el panel de XAMPP.
   > **Nota sobre este equipo:** la versión de MariaDB de XAMPP falla al arrancar en esta compilación de Windows (`InnoDB: Failing assertion: slot`). Se resuelve agregando `innodb_use_native_aio = 0` en la sección `[mysqld]` de `C:\xampp\mysql\bin\my.ini`.
2. Crea la base de datos `artesanias_huejutla` (utf8mb4).
3. Descarga WordPress en español de México en `C:\xampp\htdocs\artesanias-wp` y crea `wp-config.php`.
4. Enlaza el tema y el plugin del repositorio (uniones de directorio, sin duplicar código):
   ```bat
   mklink /J C:\xampp\htdocs\artesanias-wp\wp-content\themes\artesanias-huejutla  "<repo>\wp-content\themes\artesanias-huejutla"
   mklink /J C:\xampp\htdocs\artesanias-wp\wp-content\plugins\ah-gestion-ti        "<repo>\wp-content\plugins\ah-gestion-ti"
   ```
5. Instala y configura con WP-CLI:
   ```bash
   wp core install --url=http://localhost/artesanias-wp --title="Artesanías Huejutla" --admin_user=<usuario> --admin_email=<correo>
   wp plugin install woocommerce seo-by-rank-math --activate
   wp language plugin install woocommerce seo-by-rank-math es_MX
   wp theme activate artesanias-huejutla
   wp plugin activate ah-gestion-ti
   wp ah sembrar          # configura WooCommerce y Rank Math y carga el contenido inicial (idempotente)
   wp rewrite structure '/%postname%/'
   ```
6. Abre `http://localhost/artesanias-wp/`. La administración está en `/wp-admin/` → menú **Gestión TI**.

### 4.2 Servidor público (producción)
**Hosting gratuito (InfinityFree):** guía paso a paso en [docs/despliegue-infinityfree.md](docs/despliegue-infinityfree.md). La base de datos es la MySQL incluida en InfinityFree (WordPress no funciona con PostgreSQL/Supabase); `scripts/preparar-migracion.sh` genera la base con las URL cambiadas y el despliegue automático funciona por FTP.

1. Hosting con PHP 8.x, MySQL 8 o MariaDB 10.6+, HTTPS (Let's Encrypt) y acceso SSH con WP-CLI.
2. Usa `config/wp-config-produccion.example.php` como base. Las credenciales se leen de variables de entorno, y debe usarse un usuario de base de datos dedicado, **nunca root**.
3. Repite el paso 5 anterior (sin `wp ah sembrar` si ya existe contenido real).
4. Configura los *secrets* de GitHub (sección 9) para el despliegue automático.
5. Programa un cron real (`DISABLE_WP_CRON`) y el respaldo diario con `scripts/respaldo.sh`.

## 5. Configuración

| Qué | Dónde |
|---|---|
| Datos de contacto, redes sociales, WhatsApp Business, videollamada, métodos de pago visibles, GA4 y Search Console | **Gestión TI → Ajustes** |
| Textos de la portada (H1, descripción, barra de anuncio) | Apariencia → Personalizar → *Portada de Artesanías Huejutla* |
| Menús (principal, pie de página: Tienda, Ayuda, Legal) | Apariencia → Menús |
| Productos, categorías, inventario, precios y ofertas | Productos |
| Pedidos y estados | WooCommerce → Pedidos |
| Clientes | WooCommerce → Clientes / Usuarios |
| Cupones de descuento | Marketing → Cupones |
| Envíos y pagos | WooCommerce → Ajustes → Envío / Pagos |
| SEO por página, producto, entrada y categoría | Caja *Rank Math* en cada editor |
| Tickets, servicios, PDCA, SEM y monitoreo | Menú **Gestión TI** |

> Las redes sociales, el WhatsApp, el ID de GA4 y la verificación de Search Console quedan **vacíos a propósito**: no se inventan cuentas ni identificadores. Mientras estén vacíos, los íconos y la medición no aparecen.

## 6. Plugins utilizados

| Plugin | Uso | Origen |
|---|---|---|
| WooCommerce | Tienda, carrito, checkout, pedidos, inventario, envíos, pagos, cupones | wordpress.org |
| Mercado Pago (oficial) | Tarjetas, OXXO, meses sin intereses y saldo de Mercado Pago. Instalado; la pasarela se activa al capturar credenciales | wordpress.org |
| WooCommerce PayPal Payments (oficial) | PayPal y tarjetas mediante PayPal. Instalado; se activa al conectar la cuenta | wordpress.org |
| Rank Math SEO | Títulos y descripciones, Schema.org, Open Graph, sitemap XML, SEO de imágenes, migas de pan | wordpress.org |
| **Artesanías Huejutla – Gestión TI** | Desarrollo propio (este repositorio) | — |

Pasarelas con tarjeta **instaladas y preparadas, sin credenciales**: Mercado Pago y PayPal Payments (plugins oficiales). Se activan en *WooCommerce → Ajustes → Pagos* con las credenciales **de prueba (sandbox)** de la cuenta del negocio para demostrar el flujo sin cobros, y con las de producción al lanzar. Mientras tanto solo están activos **transferencia (SPEI)** y **pago contra entrega**, que no procesan dinero ni piden datos de tarjeta.

**Finalizar compra:** datos de envío → *Forma de entrega* en tarjetas con fecha estimada en días hábiles (envío a domicilio / recoger en taller, aviso de cuánto falta para el envío gratis) → *Medios de pago* en tarjetas; a la derecha, *Resumen de compra* fijo con miniaturas, envío, total, ahorro y botón **Pagar y finalizar** (ahí también se muestran los botones propios de PayPal). *Pedido recibido* muestra agradecimiento, etapas y datos del pedido.

## 7. Funcionalidades principales

- **Tienda:** catálogo con búsqueda, filtros por categoría y rango de precio, ordenamiento, fichas con galería y zoom, atributos (materiales, lugar de elaboración y medidas), existencias, relacionados, favoritos y botones para compartir.
- **Compra:** carrito con cupones y barra de envío gratis, checkout clásico, envíos (estándar $120, express $220, gratis desde $999, recoger en taller) y Mi cuenta con la sección *Soporte*.
- **Mis compras:** seguimiento de cada pedido (Compra confirmada → En preparación → En camino → Entregado) con fecha y hora de cada etapa, fecha estimada en días hábiles e indicador *A tiempo/Retrasado*, panel lateral con línea de tiempo, paquetería y número de guía, notificaciones en la campana del encabezado, correo al salir a ruta, botones *Ayuda con mi compra*, *Volver a comprar*, *Opinar* y *Comprobante*. Quien compra sin cuenta usa **Rastrear pedido** (número + correo). El personal captura paquetería y guía en el pedido y lo marca **En camino** (estado nuevo de WooCommerce).
- **Boletín de correo:** formulario en el pie de todas las páginas y shortcode `[ah_boletin]`, con consentimiento explícito, doble confirmación por correo y enlace de baja en cada mensaje. La lista se administra en *Gestión TI → Boletín* (conteos por estado, reenviar confirmación, eliminar y exportar a CSV). **El envío de campañas no está incluido:** se exporta la lista de confirmados a un servicio de correo (Brevo, Mailchimp…), y los correos de confirmación necesitan SMTP.
- **Cuentas con correo verificado:** con *Gestión TI → Ajustes → Cuentas de clientes* activo, quien se registra recibe un correo con un enlace único (vigente 48 h, de un solo uso; en la base de datos solo se guarda su HMAC). Hasta confirmar no puede iniciar sesión, y el mensaje de error ofrece reenviar el enlace (máximo uno cada 2 minutos). Al confirmar, la sesión se inicia sola. El administrador ve la columna *Correo confirmado* en Usuarios y puede reenviar el enlace o marcar la cuenta como confirmada. Las cuentas creadas antes de activar la opción no cambian.
- **Correo saliente:** por SMTP con las constantes `AH_SMTP_*` de `wp-config.php` (ver [config/wp-config-produccion.example.php](config/wp-config-produccion.example.php) y `.env.example`). En local, `define( 'AH_CORREO_LOCAL', true );` guarda los correos en *Gestión TI → Correos (local)* en lugar de enviarlos, para probar los flujos sin servidor de correo.
- **Contenido:** blog con 5 artículos SEO (imagen destacada, categoría, fecha, autor, compartir y productos relacionados), además de Nosotros, Historia de los artesanos, Contacto, FAQ, Mesa de ayuda, Servicios, Aviso de privacidad, Términos y Envíos y devoluciones.
- **Gestión TI:** tablero con indicadores reales, mesa de ayuda, servicios, PDCA, SEM, monitoreo y ajustes.

## 8. SEO, Google Analytics y Search Console (resumen)

- La portada usa exactamente el **title**, la **meta description** y el **H1** de la especificación.
- Sitemap dinámico: `/sitemap_index.xml` (productos, categorías, páginas, entradas y servicios). Se actualiza automáticamente.
- robots.txt virtual que excluye carrito, pago, cuenta, favoritos, búsquedas y administración (ver [módulo 1](docs/modulo-1-marketing-seo-sem.md)).
- GA4: se captura el ID `G-…` en *Gestión TI → Ajustes* y se envían los eventos `view_item_list`, `view_item`, `add_to_cart`, `view_cart`, `begin_checkout` y `purchase` ([módulo 2](docs/modulo-2-gestion-web.md)).
- Search Console: verificación por meta etiqueta (campo en Ajustes) o por DNS, y envío del sitemap.

## 9. GitHub y CI/CD (resumen)

- Ramas: `main` (producción), `develop` (pruebas) y `feature/*`, `hotfix/*`. Commits con *Conventional Commits* ([CONTRIBUTING.md](CONTRIBUTING.md)).
- **CI** (`.github/workflows/ci.yml`): sintaxis PHP (8.1–8.3), validación de archivos y secretos, WPCS, PHPUnit, prueba de humo en WordPress + WooCommerce reales y artefacto de despliegue.
- **Despliegue** (`despliegue.yml`): `develop` va a *pruebas* automáticamente; `main` va a *producción* con aprobación manual. Antes de cada despliegue se hace un respaldo de la base de datos y del código, se activa el modo mantenimiento, se ejecuta un rsync acotado al tema y al plugin, y después una prueba de humo.
- **Secrets por entorno:** `SSH_HOST`, `SSH_USER`, `SSH_KEY`, `SSH_KNOWN_HOSTS`, `SSH_PORT` (opcional), `WP_PATH`; variable `SITE_URL`.

## 10. Monitoreo, mesa de ayuda e ITIL (resumen)

- **Monitoreo:** *Gestión TI → Monitoreo* muestra disponibilidad, tiempo de respuesta (promedio y p95), errores HTTP, MTTR, errores de PHP y recursos del servidor, todo calculado **con muestras reales**. El endpoint `/wp-json/ah/v1/salud` sirve para monitores externos, y el workflow `monitoreo.yml` corre cada 30 minutos junto con una auditoría Lighthouse semanal.
- **Mesa de ayuda:** página pública `/mesa-de-ayuda/` y panel *Gestión TI → Mesa de ayuda*. El flujo es Registro → Clasificación → Priorización → Asignación → Diagnóstico → Resolución → Cierre, con SLA por prioridad. Los clientes solo pueden ver sus propios tickets.
- **Mejora continua:** reportes PDCA en *Gestión TI → Mejora continua*, plantilla en [docs/plantillas/reporte-mejora-continua.md](docs/plantillas/reporte-mejora-continua.md) y primer reporte con datos reales en [docs/mejora-continua/reporte-2026-11.md](docs/mejora-continua/reporte-2026-11.md).
- **Caché:** WP Super Cache sirve las páginas públicas en unos 25 ms (antes, 1.6–1.9 s) y excluye carrito, pago, cuenta, Mis compras y usuarios con sesión ([módulo 3, §5.4](docs/modulo-3-devops.md)).

## 11. Pruebas

```bash
composer install
composer test            # PHPUnit (12 pruebas, 42 aserciones)
vendor/bin/phpcs         # WordPress Coding Standards + seguridad + compatibilidad PHP
bash scripts/prueba-humo.sh http://localhost/artesanias-wp
```

## 12. Seguridad

- Nonces de WordPress en todos los formularios y acciones administrativas.
- Sanitización de entradas y escape de salidas, verificados por PHPCS (`WordPress.Security`).
- Consultas a las tablas propias únicamente con `$wpdb->prepare()`.
- Permisos por capacidad: `ah_gestionar_tickets` (administrador, gerente de tienda y *Agente de mesa de ayuda*) y `ah_gestion_ti` (administrador).
- La consulta pública de tickets exige número **y** correo, tiene límite de intentos por IP y da un mensaje genérico. Los formularios tienen honeypot anti-spam.
- Contraseñas gestionadas por WordPress. No se almacenan datos bancarios. `DISALLOW_FILE_EDIT` está activo.
- `.gitignore` excluye `wp-config.php`, `.env`, respaldos `.sql` y llaves, y el CI falla si detecta archivos sensibles.

## 13. Pendientes de configuración externa

Estos puntos requieren cuentas o servicios reales y **no se simularon**:

1. Dominio y hosting públicos con HTTPS.
2. Propiedad de GA4 (ID `G-…`) y propiedad de Search Console (verificación y envío del sitemap).
3. Cuentas oficiales de Facebook, Instagram, TikTok y WhatsApp Business, y enlace de videollamada.
4. Pasarela de pago con tarjeta (llaves reales) y datos bancarios reales para transferencia.
5. Servidor de correo transaccional (SMTP) para que lleguen las confirmaciones de cuenta, el boletín y las notificaciones de pedidos: crear una cuenta gratuita (por ejemplo Brevo), verificar el remitente, generar una clave SMTP y capturarla en las variables `AH_SMTP_*` del hosting.
6. Secrets de GitHub para el despliegue y la variable `SITE_URL` para el monitoreo.
7. Campaña real en Google Ads (fuera del alcance: la campaña está documentada como simulación).

## 14. Créditos

- Fotografías de referencia: [Unsplash](https://unsplash.com) (licencia Unsplash). Se recomienda sustituirlas por fotografías propias de los productos y artesanos reales.
- Tipografías: Fraunces y Manrope (Google Fonts, licencia OFL).
