# Drupal Task Concepts

Proyecto de práctica para aprender desarrollo con Drupal mediante ejemplos progresivos de módulos personalizados: rutas, controllers, render arrays, Form API, entidades, servicios, inyección de dependencias, ParamConverter, control de acceso, configuración, Batch API, Queue API, Cache API, Event Subscribers y comunicación HTTP.

El sitio usa Drupal 10, Composer y Docksal. Su configuración se versiona en `config/sync`, por lo que una instalación nueva puede reconstruirse con los módulos y ajustes actuales.

## Stack

- Drupal `10.6.16`
- PHP y MariaDB administrados por el stack LAMP de Docksal
- Composer
- Drush `12.5.3`
- Olivero como tema público y Claro como tema de administración

## Módulos contrib instalados

Las versiones corresponden a `composer.lock`.

| Módulo | Versión | Estado en la configuración |
| --- | ---: | --- |
| Admin Toolbar | 3.6.3 | Activado |
| Better Exposed Filters | 7.1.3 | Activado |
| Chaos Tool Suite (CTools) | 4.1.1 | Activado |
| Devel | 5.4.0 | Activado, junto con Devel Generate |
| Examples for Developers | 4.0.6 | Instalado; sus submódulos no están activados |
| Gin | 4.1.3 | Instalado, no activado |
| Gin Toolbar | 2.1.0 | Instalado, no activado |
| Paragraphs | 1.23.0 | Activado |
| Pathauto | 1.15.0 | Activado |
| Webform | 6.3.0 | Activado |

También están configurados tipos de contenido para artículos y páginas, tipos de media, un formulario de contacto de Webform y las vistas incluidas en la instalación estándar.

## Módulos personalizados

### `drupal_practice`

Módulo activo que reúne ejercicios básicos de la API de Drupal:

- **Controller y parámetros de ruta:** responde con un saludo personalizado.
  - `/drupal-practice/hello` (usa default "Drupal")
  - `/drupal-practice/hello/{name}`
- **Render arrays:** crea un contenedor, encabezado, lista temática y enlace sin escribir HTML directamente.
  - `/drupal-practice/render-array`
- **Form API:** formulario con nombre, correo, tecnología favorita y validación de edad mínima (≥18).
  - `/drupal-practice/form`
- **Plugin de bloque:** bloque "Drupal Practice Block", disponible para ubicar desde la administración de bloques (categoría "Custom").
- **Event subscriber:** escucha `ConfigEvents::SAVE` y registra en el log el nombre de la configuración guardada (solo configs `drupal_practice.*`).
- **Menú:** agrega un enlace al formulario en el menú principal.

### `drupal_tutor_basic`

Módulo del Sprint 1, activo en la configuración exportada. Practica servicios personalizados, inyección de dependencias, EntityQuery, ParamConverter, render arrays y Form API.

| Ruta | Acceso | Funcionalidad |
| --- | --- | --- |
| `/tutor/basic/articles` | Permiso `access content` | Lista los 10 artículos publicados más recientes mediante un servicio personalizado (`ContentManagerService::getLatestArticles`). |
| `/tutor/basic/article/{node}/reading-time` | Permiso `access content` | Convierte `{node}` en una entidad (ParamConverter), muestra su contenido filtrado (XSS safe) y calcula el tiempo de lectura a 200 palabras por minuto. |
| `/tutor/basic/site-statistics` | Rol `administrator` | Muestra el total de usuarios y de nodos tipo página. |
| `/tutor/basic/contact-form` | Permiso `access content` | Valida nombre, correo (formato válido) y mensaje; registra el contacto en el log de Drupal (`dblog`). |
| `/tutor/basic/create-page` | Rol `administrator` y permiso `create page content` | Crea y publica una página usando formato de texto fallback, muestra un mensaje de éxito y redirige al nodo. |

El servicio `drupal_tutor_basic.content_manager` concentra las consultas y el cálculo de lectura. Recibe `entity_type.manager` y `current_user` desde el contenedor de servicios. Los formularios también muestran inyección directa de `logger.factory` y `entity_type.manager`.

La explicación completa del Sprint 1 está en [`docs/spring01.explain.md`](docs/spring01.explain.md).

### `drupal_tutor_intermediate`

Módulo del Sprint 2 presente en el repositorio. Amplía los ejercicios con:

- Formulario de configuración para guardar el tamaño de lote (`batch_limit`) y el dominio VIP (`vip_domain`).
- Acceso personalizado a una zona VIP según el dominio del correo del usuario (`_custom_access` con servicio inyectado).
- EntityQuery avanzada para encontrar artículos publicados aún no procesados (sin `[ACTUALIZADO]` en el título).
- Batch API para anteponer `[ACTUALIZADO]` al título de los artículos en lotes configurables.
- Mensajes de progreso y resumen del procesamiento masivo.
- `hook_install()` / `hook_uninstall()` para crear/limpiar configuración por defecto al instalar/desinstalar.

Sus rutas son:

- `/admin/config/tutor/opciones` — Formulario de configuración
- `/tutor/intermedio/zona-vip` — Área VIP (acceso por dominio de email)
- `/tutor/intermedio/actualizar-nodos` — Actualizador masivo (Batch API)

> El módulo está completo y funcional. En una instalación reconstruida desde la configuración: `fin exec drush en drupal_tutor_intermediate -y`.

### `weather_sync`

Módulo de sincronización avanzada de clima que demuestra arquitectura enterprise: **Cliente HTTP + Manager de negocio + Queue API + Cache API + Event Subscriber + Block con AJAX**.

**Arquitectura:**

| Componente | Clase | Responsabilidad |
| --- | --- | --- |
| Cliente HTTP | `WeatherApiClient` | Peticiones GET/POST/PUT/DELETE a API REST externa, logging de errores, timeout 10s |
| Manager | `WeatherSyncManager` | Orquesta: cache (15 min), fallback a BD, crea/actualiza nodos `weather_news`, taxonomía `weather_type`, dispara evento `WeatherUpdatedEvent` |
| Queue Worker | `hook_cron()` + Queue API | Cada 5 horas encola ciudades desde `/cities` endpoint para procesamiento en background |
| Block AJAX | `WeatherInteractiveBlock` + `WeatherDashboardForm` | Selector de ciudad (taxonomy `weather_city`) → carga clima vía AJAX → renderiza nodo `weather_news` en teaser |
| Event Subscriber | `WeatherNotificationSubscriber` | Escucha `WeatherUpdatedEvent` para notificaciones/logs (pendiente implementar clase) |

**Dependencias declaradas:** `node`, `taxonomy` (crea vocabularios `weather_city` y `weather_type` al vuelo).

**Endpoints de la API externa (ficticios):**

- `GET /cities` — Lista de ciudades a sincronizar
- `GET /weather/{city}` — Clima actual de una ciudad

**Configuración requerida (no versionada, se crea dinámicamente):**

- Content type: `weather_news` (campos: `field_temperature`, `field_weather_type`, `field_city`)
- Vocabulario: `weather_city` (términos = ciudades disponibles)
- Vocabulario: `weather_type` (condiciones climáticas creadas al vuelo)

**Nota:** El módulo tiene algunos *bugs menores* (referencia a `WeatherInteractiveForm` inexistente en el block, `WeatherNotificationSubscriber` no implementado, typos en `$base_ulr`/`filed_weather_type`). Sirve como base para practicar debugging y completado.

---

## Puesta en marcha

### Requisitos

- [Docksal](https://docs.docksal.io/getting-started/setup/) instalado
- Docker Desktop en ejecución
- Git

### Instalación

```bash
git clone git@github.com:sgonzavar/Drupal-task-concept.git task-concepts
cd task-concepts
fin init
```

`fin init` levanta el stack, instala las dependencias de Composer y reconstruye Drupal usando la configuración de `config/sync`. Al terminar muestra las credenciales del administrador.

El sitio queda disponible normalmente en:

```text
http://task-concepts.docksal.site
```

El dominio depende del nombre de la carpeta del proyecto. Puede consultarse con `fin status`.

> `fin init` reinicializa el entorno y reinstala el sitio. Para arrancar una instalación existente usa `fin start`.

## Comandos útiles

```bash
# Arrancar o detener el entorno
fin start
fin stop

# Limpiar caché
fin exec drush cr

# Obtener un enlace de acceso como administrador
fin exec drush uli

# Ver el estado de Drupal
fin exec drush status

# Habilitar el módulo del Sprint 2
fin exec drush en drupal_tutor_intermediate -y

# Habilitar weather_sync (requiere tipos de entidad y vocabularios)
fin exec drush en weather_sync -y

# Exportar cambios hechos desde la interfaz
fin exec drush cex -y

# Importar la configuración versionada
fin exec drush cim -y
fin exec drush cr
```

## Flujo de configuración

La configuración de Drupal vive en `config/sync`.

1. Después de realizar cambios de estructura o configuración desde la interfaz, ejecuta `fin exec drush cex -y`.
2. Revisa y versiona los archivos YAML generados.
3. Después de traer cambios del repositorio, ejecuta `fin exec drush cim -y` y `fin exec drush cr`.

Los nodos, usuarios, archivos y envíos de Webform son contenido; no forman parte de la exportación de configuración.

## Estructura relevante

```text
.
├── .docksal/                    # Entorno local y comandos de inicialización
├── config/sync/                 # Configuración exportada de Drupal
├── web/                         # Document root
│   └── modules/custom/
│       ├── drupal_practice/     # Ejemplos básicos completos (Sprint 0)
│       ├── drupal_tutor_basic/  # Sprint 1: servicios, controllers y formularios
│       ├── drupal_tutor_intermediate/ # Sprint 2: config, acceso, Batch API
│       └── weather_sync/        # Sprint 3/Enterprise: HTTP Client, Queue, Cache, Events, AJAX
├── docs/
│   └── spring01.explain.md       # Explicación técnica del Sprint 1
├── exercs.md                     # Retos propuestos para practicar
├── composer.json                # Dependencias declaradas
└── composer.lock                # Versiones instaladas y reproducibles
```

## Retos propuestos

Ver [`exercs.md`](exercs.md) para ejercicios adicionales:

- Completar `weather_sync`: implementar `WeatherNotificationSubscriber`, QueueWorker, fixes de typos.
- Añadir tests (PHPUnit/Kernel) a `drupal_tutor_basic` y `drupal_tutor_intermediate`.
- Implementar `hook_help()` en cada módulo.
- Migrar `drupal_practice` a `Drupal\Core\Block\Attribute\Block` (ya hecho) y `#[Route]` attributes.