# Drupal Task Concepts

Proyecto de práctica para aprender desarrollo con Drupal mediante ejemplos pequeños de módulos personalizados: rutas, controllers, render arrays, formularios, bloques, servicios, inyección de dependencias y eventos.

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

## Ejemplos propios

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

Módulo activo en la configuración y todavía en desarrollo. Contiene un ejercicio de servicio personalizado con inyección del administrador de entidades y del usuario actual. La idea es cubrir:

- consulta de los últimos artículos publicados;
- cálculo del tiempo de lectura de un texto, usando 200 palabras por minuto;
- estadísticas básicas de usuarios y páginas del sitio;
- controllers para exponer estos resultados mediante rutas.

Los controllers de este segundo módulo aún no están incluidos, por lo que sus rutas representan trabajo pendiente.

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
│       └── drupal_tutor_basic/  # Ejercicios de servicios en progreso
├── composer.json                # Dependencias declaradas
└── composer.lock                # Versiones instaladas y reproducibles
```
