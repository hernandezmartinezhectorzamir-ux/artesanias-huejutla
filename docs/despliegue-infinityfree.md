# Publicar Artesanías Huejutla en InfinityFree

Guía para pasar el sitio de XAMPP a un hosting gratuito con URL pública y HTTPS.

## Por qué no Supabase para la base de datos

Supabase ofrece **PostgreSQL**. WordPress y WooCommerce solo funcionan con **MySQL o MariaDB**: no hay soporte oficial para PostgreSQL y los complementos que lo intentan no son compatibles con WooCommerce. InfinityFree **incluye bases de datos MySQL gratis** en la misma cuenta, así que el sitio usa esa. Supabase no hace falta para este proyecto.

## Qué se sube y cómo

| Parte | Cómo llega al hosting | Archivos |
|---|---|---|
| WordPress | Instalador automático del panel (Softaculous) | se instala en el servidor |
| WooCommerce, Rank Math, WP Super Cache,  | *Plugins → Añadir nuevo* en el escritorio de WordPress | se descargan en el servidor |
| Tema `artesanias-huejutla` y plugin `ah-gestion-ti` | FTP (FileZilla) la primera vez; después, GitHub Actions en cada cambio | ~100 |
| Medios (`wp-content/uploads`) | FTP | ~135 |
| Base de datos | `scripts/preparar-migracion.sh` + importar en phpMyAdmin | 1 archivo .sql (~1.2 MB) |

## Pasos

### 1. Crear la cuenta y el hosting

1. Regístrate en [infinityfree.com](https://www.infinityfree.com/) (*Sign up*) y confirma tu correo.
2. En el panel: **Create Account** → elige un **subdominio gratuito** (por ejemplo `artesanias-huejutla.infinityfreeapp.com`) → crea la cuenta de hosting.
3. Anota los datos de **FTP** (servidor, usuario, contraseña) y la **contraseña de la cuenta de hosting**.
4. En el panel de la cuenta: **Free SSL Certificates** → solicita el certificado para tu subdominio (puede tardar unos minutos).

### 2. Base de datos

1. Panel de control → **MySQL Databases** → crea una base (por ejemplo `artesanias`).
2. Anota **nombre de la base, usuario y servidor MySQL** que muestra el panel.

### 3. WordPress y plugins

1. Panel de control → **Softaculous** → WordPress → instala en la carpeta raíz del dominio (deja vacío el directorio).
2. Entra al escritorio de WordPress e instala desde *Plugins → Añadir nuevo*: WooCommerce, Rank Math SEO y WP Super Cache (no hace falta configurarlos: la base importada trae la configuración).

### 4. Tema, plugin propio y medios por FTP

1. Instala [FileZilla](https://filezilla-project.org/download.php?type=client) y conéctate con los datos FTP del paso 1.
2. Sube `wp-content/themes/artesanias-huejutla/` a `htdocs/wp-content/themes/`.
3. Sube `wp-content/plugins/ah-gestion-ti/` a `htdocs/wp-content/plugins/`.
4. Descomprime en tu equipo `uploads-<fecha>.zip` (paso 5) y sube la carpeta `uploads` a `htdocs/wp-content/`.

### 5. Base de datos

1. En el equipo local, con MySQL encendido:
   ```bash
   bash scripts/preparar-migracion.sh https://artesanias-huejutla.infinityfreeapp.com
   ```
   Genera `migracion/bd-<fecha>.sql` con las direcciones ya cambiadas y `migracion/uploads-<fecha>.zip`. La base local no se modifica.
2. Panel de control → **phpMyAdmin** de tu base → **Importar** → elige el `.sql`.
3. Edita `htdocs/wp-config.php` siguiendo `config/wp-config-infinityfree.example.php`: datos de la base, `$table_prefix = 'wp_'`, `WP_HOME`/`WP_SITEURL` con `https://` y las constantes de seguridad.
4. Entra a `https://<tu-subdominio>/wp-admin` con tu usuario local, ve a *Ajustes → Enlaces permanentes* y pulsa **Guardar** (regenera las URLs).

### 6. Despliegue automático desde GitHub

En el repositorio: **Settings → Secrets and variables → Actions**.

| Tipo | Nombre | Valor |
|---|---|---|
| Secret | `FTP_SERVER` | servidor FTP del panel |
| Secret | `FTP_USERNAME` | usuario FTP |
| Secret | `FTP_PASSWORD` | contraseña FTP |
| Secret | `FTP_DIR` | `/htdocs/` |
| Variable | `SITE_URL` | `https://<tu-subdominio>` |

Con eso, cada cambio que pase CI sube solo el tema y el plugin que cambiaron. Sin SSH no hay respaldo automático ni modo mantenimiento: haz un respaldo manual en phpMyAdmin (*Exportar*) antes de cambios grandes.

## Limitaciones del plan gratuito

- Sin SSH ni WP-CLI: las tareas se hacen desde el escritorio de WordPress y phpMyAdmin.
- El servidor puede responder a programas automáticos (no navegadores) con una verificación por JavaScript. Eso puede afectar a la prueba de humo de GitHub, al monitoreo externo y a las notificaciones automáticas de las pasarelas de pago; por eso la prueba de humo en modo FTP es informativa. Para pagos reales conviene un hosting de pago.
- Recursos y número de archivos limitados: el sitio completo tiene unos 12,800 archivos, por debajo de los límites habituales; revisa el uso en el panel.
- Correo: se recomienda SMTP externo (Brevo) con las constantes `AH_SMTP_*`.
