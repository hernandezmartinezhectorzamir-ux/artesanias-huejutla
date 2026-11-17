# Flujo de trabajo con Git y GitHub

## Ramas

| Rama | Propósito | Se despliega en |
|---|---|---|
| `main` | Código en producción. Protegida: solo se actualiza por *pull request* aprobado y con CI en verde. | Producción, con aprobación manual |
| `develop` | Integración de funcionalidades terminadas. | Entorno de pruebas (automático) |
| `feature/<tema>` | Una funcionalidad: `feature/mesa-ayuda-sla`, `feature/filtros-catalogo`. Sale de `develop` y regresa a `develop`. | — |
| `hotfix/<tema>` | Corrección urgente. Sale de `main` y se fusiona en `main` y en `develop`. | — |
| `release/<versión>` (opcional) | Estabilización antes de publicar. | — |

```
main     ●────────────────●─────────────●  (v1.0.0)     (v1.0.1)
          \              /             / \
develop    ●──●────●────●──●──────────●   \
               \  /      \ /               hotfix/precio-envio
        feature/x        feature/y
```

## Mensajes de commit: Conventional Commits

```
<tipo>(<ámbito>): <descripción en imperativo>
```

| Tipo | Uso |
|---|---|
| `feat` | Nueva funcionalidad: `feat(mesa-ayuda): agrega SLA por prioridad` |
| `fix` | Corrección: `fix(tema): corrige menú móvil recortado` |
| `docs` | Documentación |
| `style` | Formato sin cambio funcional |
| `refactor` | Reestructura sin cambiar comportamiento |
| `test` | Pruebas |
| `ci` | GitHub Actions |
| `chore` | Mantenimiento y dependencias |
| `seo` | Cambios de SEO o contenido optimizado |

Ámbitos sugeridos: `tema`, `plugin`, `mesa-ayuda`, `sem`, `ga4`, `monitoreo`, `woocommerce`, `ci`, `docs`.

## Pull requests

1. Actualiza tu rama desde `develop`.
2. Ejecuta `composer test` y `vendor/bin/phpcs` localmente.
3. Abre el PR hacia `develop` describiendo **qué** cambia, **por qué** y **cómo probarlo**.
4. El CI debe pasar y al menos una persona debe revisar.
5. Para publicar: PR de `develop` a `main` y aprobación del despliegue en el Environment *produccion*.

## Protección recomendada en GitHub

- *Settings → Branches → main:* exigir PR, al menos 1 aprobación, *status checks* (CI) y prohibir *force push*.
- *Settings → Environments → produccion:* revisores obligatorios y despliegue solo desde `main`.
- *Settings → Secrets and variables → Actions:* secretos por entorno (ver README, sección 9).

## Inicializar el repositorio

```bash
git init -b main
git add .
git commit -m "chore: estructura inicial de Artesanías Huejutla (tema, plugin, CI/CD y documentación)"
git branch develop
git remote add origin https://github.com/<organizacion>/artesanias-huejutla.git
git push -u origin main develop
```

**Nunca** subas `wp-config.php`, `.env`, respaldos `.sql`, llaves ni contraseñas. El `.gitignore` y el CI lo impiden.
