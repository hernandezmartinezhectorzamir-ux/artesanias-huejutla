# Módulo 3 – Infraestructura, automatización y DevOps

## 1. Arquitectura del sistema

```
┌────────────┐    HTTPS     ┌──────────────────────────────────────────────┐
│ Navegador  │ ───────────▶ │ Servidor web (Apache o Nginx) + PHP 8.x-FPM  │
└────────────┘              │  WordPress 7.x (núcleo sin modificar)        │
                            │   ├─ WooCommerce 11.x                        │
                            │   ├─ Rank Math SEO                           │
                            │   ├─ Tema artesanias-huejutla  (repo)        │
                            │   └─ Plugin ah-gestion-ti      (repo)        │
                            │  WP-Cron real (cron del sistema)             │
                            └───────────────┬──────────────────────────────┘
                                            │
                                ┌───────────▼───────────┐
                                │ MySQL 8 / MariaDB 10.6+│
                                │ wp_* + wp_ah_tickets   │
                                │       + wp_ah_ticket_eventos
                                └────────────────────────┘
GitHub (repo) ─▶ Actions: CI ─▶ Despliegue SSH/rsync ─▶ pruebas → producción
                 Actions: Monitoreo (cada 30 min) ─▶ /wp-json/ah/v1/salud
```

### Entornos

| Entorno | URL | Rama | Datos |
|---|---|---|---|
| Local | `http://localhost/artesanias-wp` (XAMPP) | cualquiera | Contenido inicial (`wp ah sembrar`) |
| CI | Contenedor efímero de GitHub Actions | push / PR | Instalación limpia en cada ejecución |
| Pruebas (staging) | `https://pruebas.<dominio>` | `develop` | Copia anonimizada o contenido inicial |
| Producción | `https://www.<dominio>` | `main` | Reales |

### Tecnologías

PHP 8.2 · WordPress 7.1 · WooCommerce 11.2 · Rank Math · MariaDB/MySQL · HTML5/CSS3/JavaScript sin frameworks · WP-CLI · Composer · PHPUnit 10 · PHP_CodeSniffer con WordPress Coding Standards 3 · GitHub Actions · rsync/SSH · Lighthouse CI.

## 2. Control de versiones (Git y GitHub)

- **Repositorio centralizado** en GitHub con el código propio: tema, plugin, pruebas, workflows y documentación. El núcleo de WordPress, los plugins de terceros, `uploads/` y `wp-config.php` **no** se versionan (ver `.gitignore`).
- **Ramas:** `main` (producción, protegida), `develop` (integración, se despliega a pruebas), `feature/*` y `hotfix/*`.
- **Convención de commits:** Conventional Commits (`feat`, `fix`, `docs`, `ci`, `refactor`, `test`, `chore`, `seo`).
- Flujo detallado e instrucciones de inicialización: [CONTRIBUTING.md](../CONTRIBUTING.md).

> En el equipo de desarrollo no está instalado Git, por lo que el repositorio todavía no se inicializa. Los comandos están en CONTRIBUTING.md.

## 3. Pipeline CI (`.github/workflows/ci.yml`)

Se activa en `push` a `main`, `develop`, `feature/**` y `hotfix/**`, en *pull requests* hacia `main` y `develop`, y manualmente.

| # | Job | Qué valida | Bloquea |
|---|---|---|---|
| 1 | Sintaxis PHP | `php -l` en PHP 8.1, 8.2 y 8.3 | Sí |
| 2 | Validación de archivos | Archivos obligatorios, cabeceras de tema y plugin, **ningún secreto ni `wp-config.php`/`.sql`/llaves versionados**, JSON y YAML válidos, `node --check` en JS | Sí |
| 3 | Estándares de código | PHPCS con WordPress-Extra, Security y PHPCompatibilityWP. **Las reglas de seguridad (escape, nonces, sanitización, SQL preparado) bloquean.** | Seguridad: sí |
| 4 | Pruebas unitarias | PHPUnit sobre `AH_Calculos` (SEM, matriz ITIL, SLA, número de ticket, disponibilidad, MTTR, validaciones) | Sí |
| 5 | Prueba de humo | Instala WordPress, WooCommerce y Rank Math reales sobre MariaDB, activa el tema y el plugin, ejecuta `wp ah sembrar` y comprueba 12 URL (`scripts/prueba-humo.sh`) | Sí |
| 6 | Preparar despliegue | Empaqueta tema y plugin (`.zip` con versión fecha+commit) como artefacto | — |

**Resultados:** cada job publica artefactos (`reporte-phpcs`, `reporte-phpunit` en JUnit, `reporte-humo`, `paquete-despliegue`), visibles en la pestaña *Actions*.

**Resultado local verificado** (05/11/2026): PHPUnit **8 pruebas / 29 aserciones OK**; PHPCS completo **0 errores, 0 advertencias**; `php -l` sin errores en todos los archivos.

## 4. Despliegue continuo (`.github/workflows/despliegue.yml`)

```
CI verde en develop ──▶ Environment "pruebas"     (automático)
CI verde en main    ──▶ Environment "produccion"  (requiere aprobación manual)
```

Pasos:

1. Verifica que existan los secretos. **Si faltan, no despliega** (solo emite una advertencia).
2. **Respaldo previo:** `wp db export` comprimido y `tar` del tema y plugin actuales (conserva 14).
3. Activa el modo mantenimiento.
4. `rsync --delete` **acotado** a `wp-content/themes/artesanias-huejutla/` y `wp-content/plugins/ah-gestion-ti/`. No toca `wp-config.php`, `uploads/`, el núcleo ni otros plugins.
5. Reactiva el plugin (aplica migraciones con `dbDelta`), limpia la caché, regenera los enlaces permanentes y sale de mantenimiento (aunque un paso falle).
6. Ejecuta la prueba de humo contra la URL del entorno.

### Secretos y variables

Se configuran en *Settings → Environments → pruebas / produccion*:

| Nombre | Tipo | Descripción |
|---|---|---|
| `SSH_HOST` | Secret | Servidor |
| `SSH_USER` | Secret | Usuario de despliegue (sin privilegios de root) |
| `SSH_KEY` | Secret | Llave privada SSH dedicada al despliegue |
| `SSH_KNOWN_HOSTS` | Secret | Huella del servidor (`ssh-keyscan`) |
| `SSH_PORT` | Secret (opcional) | Puerto SSH (22 por defecto) |
| `WP_PATH` | Secret | Ruta de WordPress en el servidor |
| `SITE_URL` | Variable | URL pública del entorno (también la usa el monitoreo) |

Las credenciales de base de datos y las *salts* **no** pasan por GitHub: viven en el servidor (`.env` o variables del entorno PHP), y se leen con `config/wp-config-produccion.example.php`.

### Reversión (rollback)

1. Volver a ejecutar el workflow con el commit anterior (*Re-run* o *workflow_dispatch* sobre la etiqueta previa).
2. Si hubo cambios de datos: `gunzip -c ~/respaldos/bd-<fecha>.sql.gz | wp db import -`.
3. Si es necesario, restaurar el código: `tar -xzf ~/respaldos/codigo-<fecha>.tar.gz -C <WP_PATH>`.

## 5. Monitoreo y métricas

### 5.1 Mecanismos

| Mecanismo | Qué mide | Dónde |
|---|---|---|
| Chequeo interno (WP-Cron cada hora, o "Ejecutar chequeo ahora") | Código HTTP y tiempo de respuesta de inicio, tienda y endpoint de salud | *Gestión TI → Monitoreo* |
| Endpoint `/wp-json/ah/v1/salud` | Estado de la base de datos, WooCommerce y latencia de la BD; sin datos sensibles; `no-store` | Monitores externos (UptimeRobot, Better Stack) |
| Workflow `monitoreo.yml` | Cada 30 min: código, tiempo total y TTFB de 4 URL; semanal: **Lighthouse** (rendimiento, accesibilidad, buenas prácticas, SEO) | *Actions* y resumen del job |
| Errores de PHP | Conteo de Fatal, Warning, Notice y Deprecated, y últimas líneas del log | *Gestión TI → Monitoreo* |
| Servidor y aplicación | Versión de PHP, memoria, versiones de WP/WC, tamaño de la BD, disco libre, caché de objetos, plugins con actualización, próximo cron | *Gestión TI → Monitoreo* |
| PageSpeed Insights | Core Web Vitals (botón directo) | Externo |

### 5.2 Métricas calculadas (solo con muestras reales)

| Métrica | Cálculo |
|---|---|
| Disponibilidad % | Muestras 2xx/3xx ÷ total de muestras (30 días) |
| Tiempo de respuesta | Promedio y percentil 95 de las muestras exitosas |
| Frecuencia de errores | % de muestras fallidas, desglosadas por código HTTP o "sin respuesta" |
| MTTR (tiempo medio de recuperación) | Promedio de la duración entre la primera muestra fallida y la siguiente exitosa |
| Incidentes | Número de periodos de caída |

Sin muestras, el panel muestra "—" en lugar de cifras inventadas (verificado con pruebas unitarias).

**Línea base local** (XAMPP en un equipo de desarrollo, 05/11/2026; **no representa producción**): las páginas principales generaron HTML en aproximadamente 0.85–1.2 s sin caché, con `WP_DEBUG` activo.

### 5.3 Umbrales sugeridos

| Indicador | Objetivo |
|---|---|
| Disponibilidad mensual | ≥ 99.5 % |
| Tiempo de respuesta p95 | ≤ 800 ms con caché |
| Frecuencia de errores | ≤ 1 % |
| MTTR | ≤ 30 min |
| Lighthouse rendimiento / SEO | ≥ 80 / ≥ 95 |

### 5.4 Optimización multinivel (caché)

| Nivel | Estado en local | Producción |
|---|---|---|
| Caché de página | **Activa**: WP Super Cache en modo PHP, con expiración de 1 h | Igual, o caché del servidor (LiteSpeed/Nginx) |
| Exclusiones | `/carrito/`, `/finalizar-compra/`, `/mi-cuenta/`, `/mis-compras/`, `/rastrear-pedido/`, `/mesa-de-ayuda/`, `/favoritos/`, `/contacto/`, `/wp-json/`, sitemaps y robots, además de las cookies `wordpress_logged_in`, `woocommerce_items_in_cart`, `wp_woocommerce_session` y `comment_author` | Igual |
| Caché de navegador y compresión | No activas (`mod_expires`/`mod_deflate` apagados en XAMPP; no se tocaron porque afectan a todos los proyectos) | Activar |
| Caché de código PHP (OPcache) | No activa en XAMPP | Activar |
| Caché de objetos | No | Redis, si el plan lo permite |
| Recursos estáticos | Versionados por fecha de modificación (`ah_tema_version_archivo`), imágenes WebP con carga diferida | Igual, más CDN opcional |

**Medición real (TTFB con `curl`, 5 repeticiones, 17/11/2026):**

| Página | Sin caché | Con caché |
|---|---|---|
| `/` | 1 910 ms | 25 ms |
| `/tienda/` | 1 747 ms | 25 ms |
| `/producto/canasta-de-palma-tejida/` | 1 710 ms | 27 ms |
| `/blog/` | 1 625 ms | 25 ms |

Con cookie de carrito o de sesión, y en `/carrito/`, la respuesta sigue siendo dinámica (1.5–1.9 s). Así se verifica que nadie recibe la página personalizada de otro usuario. El tablero de *Gestión TI* comprueba que la caché esté activa. El análisis completo está en [mejora-continua/reporte-2026-11.md](mejora-continua/reporte-2026-11.md).

## 6. Respaldos

| Qué | Frecuencia | Retención | Cómo |
|---|---|---|---|
| Base de datos | Diario (03:00) y antes de cada despliegue | 14 copias | `scripts/respaldo.sh` + `despliegue.yml` |
| `wp-content/uploads` | Diario | 7 copias | `scripts/respaldo.sh` |
| Código | En cada commit | Historial de Git + respaldo previo al despliegue | GitHub |
| Copia externa | Diario | 30 días | `rclone` a almacenamiento en la nube (recomendado) |

Restauración probada sugerida: una vez al mes, restaurar el respaldo en el entorno de pruebas (verificación del procedimiento).

## 7. Configuración de producción recomendada

- HTTPS obligatorio (`FORCE_SSL_ADMIN`), HSTS en el servidor web.
- `DISALLOW_FILE_EDIT`, actualizaciones menores automáticas y log de PHP **fuera** de la carpeta pública.
- Usuario de base de datos dedicado con privilegios solo sobre su base.
- Caché de página (plugin o servidor) y caché de objetos (Redis).
- WP-Cron real: `*/5 * * * * wp cron event run --due-now --path=<WP_PATH>`.
- Bloquear el acceso público a `wp-content/debug.log` si se usa la ruta por defecto.

## 8. Hallazgos del entorno local

- La MariaDB 10.4 de XAMPP no arrancaba (aserción de InnoDB en E/S asíncrona). Se inició con `--innodb-use-native-aio=0`, sin modificar `my.ini`. Para iniciarla desde el panel de XAMPP, agrega `innodb_use_native_aio = 0` en `[mysqld]`.
- La tabla del sistema `mysql.db` estaba dañada e impedía arrancar MySQL. El 17/11/2026 se respaldó y se reparó con `aria_chk -r`. Se restauró el permiso de `pma` sobre `phpmyadmin`; Joomla y Magento usan `root`, así que no se vieron afectados. El WordPress local sigue usando `root`; en producción debe usarse un usuario dedicado.
