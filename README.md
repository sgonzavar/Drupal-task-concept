# Drupal Task Concepts

Proyecto de práctica para aprender desarrollo con Drupal mediante ejemplos progresivos de módulos personalizados: rutas, controllers, render arrays, Form API, entidades, servicios, inyección de dependencias, ParamConverter, control de acceso, configuración y Batch API.

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
  - `/drupal-practice/hello`
  - `/drupal-practice/hello/{name}`
- **Render arrays:** crea un contenedor, encabezado, lista temática y enlace sin escribir HTML directamente.
  - `/drupal-practice/render-array`
- **Form API:** formulario con nombre, correo, tecnología favorita y validación de edad mínima.
  - `/drupal-practice/form`
- **Plugin de bloque:** bloque `Drupal Practice Block`, disponible para ubicar desde la administración de bloques.
- **Event subscriber:** escucha `ConfigEvents::SAVE` y registra en el log el nombre de la configuración guardada.
- **Menú:** agrega un enlace al formulario en el menú principal.

### `drupal_tutor_basic`

Módulo del Sprint 1, activo en la configuración exportada. Practica servicios personalizados, inyección de dependencias, EntityQuery, ParamConverter, render arrays y Form API.

| Ruta | Acceso | Funcionalidad |
| --- | --- | --- |
| `/tutor/basic/articles` | Permiso `access content` | Lista los 10 artículos publicados más recientes mediante un servicio personalizado. |
| `/tutor/basic/article/{node}/reading-time` | Permiso `access content` | Convierte `{node}` en una entidad, muestra su contenido y calcula el tiempo de lectura a 200 palabras por minuto. |
| `/tutor/basic/site-statistics` | Rol `administrator` | Muestra el total de usuarios y de nodos tipo página. |
| `/tutor/basic/contact-form` | Formulario público en su implementación actual | Valida nombre, correo y mensaje; registra el contacto en el log de Drupal. |
| `/tutor/basic/create-page` | Rol `administrator` y permiso `create page content` | Crea y publica una página, muestra un mensaje de éxito y redirige al nodo. |

El servicio `drupal_tutor_basic.content_manager` concentra las consultas y el cálculo de lectura. Recibe `entity_type.manager` y `current_user` desde el contenedor de servicios. Los formularios también muestran inyección directa de `logger.factory` y `entity_type.manager`.

La explicación completa del Sprint 1 está en [`docs/spring01.explain.md`](docs/spring01.explain.md).

### `drupal_tutor_intermediate`

Módulo del Sprint 2 presente en el repositorio. Amplía los ejercicios con:

- formulario de configuración para guardar el tamaño de lote y el dominio VIP;
- acceso personalizado a una zona VIP según el dominio del correo del usuario;
- EntityQuery para encontrar artículos publicados aún no procesados;
- Batch API para anteponer `[ACTUALIZADO]` al título de los artículos;
- mensajes de progreso y resumen del procesamiento masivo.

Sus rutas son:

- `/admin/config/tutor/opciones`
- `/tutor/intermedio/zona-vip`
- `/tutor/intermedio/actualizar-nodos`

> El código de `drupal_tutor_intermediate` está versionado, pero el módulo todavía no aparece habilitado en `config/sync/core.extension.yml`. En una instalación reconstruida desde la configuración hay que habilitarlo antes de usar sus rutas: `fin exec drush en drupal_tutor_intermediate -y`.

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
│       ├── drupal_practice/     # Ejemplos básicos completos
│       ├── drupal_tutor_basic/  # Sprint 1: servicios, controllers y formularios
│       └── drupal_tutor_intermediate/ # Sprint 2: configuración, acceso y Batch API
├── docs/
│   └── spring01.explain.md       # Explicación técnica del Sprint 1
├── exercs.md                     # Retos propuestos para practicar
├── composer.json                # Dependencias declaradas
└── composer.lock                # Versiones instaladas y reproducibles
```
