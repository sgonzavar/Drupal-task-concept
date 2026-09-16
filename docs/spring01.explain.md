# Sprint 1: módulo `drupal_tutor_basic`

## Objetivo

El Sprint 1 introduce las piezas fundamentales para desarrollar un módulo en Drupal sin concentrar toda la lógica en controllers o formularios. El resultado es el módulo `drupal_tutor_basic`, que implementa cinco ejercicios alrededor de rutas, entidades, servicios, inyección de dependencias, ParamConverter, render arrays, Form API y logs.

El módulo está habilitado en la configuración exportada del sitio y su definición se encuentra en:

```text
web/modules/custom/drupal_tutor_basic/
```

## Funcionalidades realizadas

| # | Ruta | Clase principal | Resultado |
| ---: | --- | --- | --- |
| 1 | `/tutor/basic/articles` | `BasicController::listArticles()` | Lista los 10 artículos publicados más recientes. |
| 2 | `/tutor/basic/article/{node}/reading-time` | `BasicController::Read_time()` | Carga un nodo y calcula su tiempo estimado de lectura. |
| 3 | `/tutor/basic/site-statistics` | `BasicController::getSiteStatistics()` | Cuenta usuarios y páginas del sitio. |
| 4 | `/tutor/basic/contact-form` | `SimpleContactForm` | Valida un contacto y registra el evento en el log. |
| 5 | `/tutor/basic/create-page` | `FastPageCreateForm` | Crea y publica un nodo tipo página. |

### 1. Listado de artículos recientes

El método `ContentManagerService::getLatesrArticles()` obtiene el storage de nodos y construye una `EntityQuery` con estas condiciones:

- tipo de contenido `article`;
- estado publicado (`status = 1`);
- orden descendente por fecha de creación;
- límite configurable, usado con un valor de 10 desde el controller;
- comprobación de acceso activada con `accessCheck(TRUE)`.

El controller carga las entidades encontradas, convierte cada artículo en un enlace con `toLink()` y entrega los elementos al tema `item_list`. De esta forma Drupal se encarga del HTML de la lista y puede mostrar el mensaje `No articles found` cuando no existen resultados.

### 2. Tiempo de lectura con ParamConverter

La ruta recibe `{node}` y declara el parámetro como `entity:node`. El ParamConverter de Drupal transforma automáticamente el ID de la URL en un objeto `NodeInterface`, por lo que el controller no necesita cargar manualmente la entidad.

El cuerpo del nodo se envía a `ContentManagerService::calculateReadingTime()`, que:

1. elimina las etiquetas HTML con `strip_tags()`;
2. cuenta las palabras con `str_word_count()`;
3. divide el total entre 200 palabras por minuto;
4. redondea hacia arriba con `ceil()`.

La fórmula aplicada es:

```text
minutos = ceil(total de palabras / 200)
```

La implementación actual acepta cualquier nodo que tenga un campo `body`, aunque el nombre de la ruta se refiera a un artículo.

### 3. Estadísticas del sitio

`ContentManagerService::gestSiteStats()` ejecuta dos consultas de conteo:

- todos los usuarios registrados;
- todos los nodos de tipo `page`.

Ambas usan `accessCheck(FALSE)` porque se trata de una estadística administrativa global, no de una lista de entidades visibles para el usuario actual. La ruta está limitada al rol `administrator` y el controller presenta ambos totales.

### 4. Formulario de contacto

`SimpleContactForm` extiende `FormBase` e implementa las tres etapas normales de Form API:

- `buildForm()` crea los campos obligatorios `name`, `email` y `message`, además del botón de envío;
- `validateForm()` comprueba el correo con `FILTER_VALIDATE_EMAIL` y asocia el error al campo correspondiente;
- `submitForm()` registra el nombre y el correo en el canal `drupal_tutor_basic` y muestra un mensaje de confirmación mediante Messenger.

El registro se puede revisar en **Administración > Informes > Mensajes recientes del registro** (`/admin/reports/dblog`). En la implementación actual, el texto del mensaje se valida como obligatorio, pero no se almacena ni se incluye en el log.

### 5. Creación rápida de páginas

`FastPageCreateForm` también extiende `FormBase`, pero incorpora el manejo de entidades:

- solicita un título y un cuerpo obligatorios;
- valida que el título tenga al menos cinco caracteres;
- crea un nodo con bundle `page` mediante el storage de nodos;
- guarda el cuerpo con el formato `basic_html`;
- publica inmediatamente el contenido con `status = 1`;
- muestra el título y el ID del nodo creado;
- redirige a la ruta canónica de la nueva página.

La ruta exige el permiso `create page content` y el rol `administrator`.

## Servicio personalizado

El servicio se registra en `drupal_tutor_basic.services.yml`:

```yaml
services:
  drupal_tutor_basic.content_manager:
    class: Drupal\drupal_tutor_basic\Services\ContentManagerService
    arguments: ['@entity_type.manager', '@current_user']
```

`ContentManagerService` reúne la lógica reutilizable que no pertenece a la capa de presentación:

| Método actual | Responsabilidad |
| --- | --- |
| `getLatesrArticles(int $limit = 5)` | Consultar y cargar los artículos publicados más recientes. |
| `calculateReadingTime(string $text)` | Estimar los minutos de lectura de un texto. |
| `gestSiteStats()` | Contar usuarios y páginas. |

El controller recibe este servicio a través de su constructor. Su método estático `create()` obtiene la instancia desde el contenedor con el identificador `drupal_tutor_basic.content_manager`.

## Servicios core utilizados

### `entity_type.manager`

Implementa `EntityTypeManagerInterface` y funciona como punto de entrada a las entidades de Drupal. Permite solicitar el storage de un tipo de entidad para consultar, crear, cargar, actualizar o eliminar registros sin escribir SQL directo.

En este sprint se usa para:

- consultar y cargar artículos;
- contar usuarios y páginas;
- crear y guardar un nuevo nodo tipo página.

Se inyecta en `ContentManagerService` y directamente en `FastPageCreateForm`.

### `current_user`

Representa al usuario que ejecuta la petición mediante `AccountProxyInterface`. Permite consultar su ID, nombre, roles y permisos.

Se inyecta en `ContentManagerService` como parte del ejercicio de dependencias. La propiedad queda disponible para reglas relacionadas con el usuario actual, aunque los tres métodos implementados todavía no la utilizan.

### `logger.factory`

Implementa `LoggerChannelFactoryInterface` y permite crear canales para el sistema de logs de Drupal. `SimpleContactForm` solicita la fábrica en su constructor y crea el canal `drupal_tutor_basic`:

```php
$this->logger = $loggerFactory->get('drupal_tutor_basic');
```

Durante el envío del formulario se registra un evento de nivel `info`, visible en el informe de logs cuando el módulo Database Logging (`dblog`) está habilitado.

## Cómo funciona `arguments` en `services.yml`

Drupal utiliza el contenedor de inyección de dependencias de Symfony. La propiedad `arguments` indica qué valores debe entregar al constructor cuando crea un servicio.

```yaml
arguments: ['@entity_type.manager', '@current_user']
```

Esto corresponde, en el mismo orden, al constructor:

```php
public function __construct(
  EntityTypeManagerInterface $entityTypeManager,
  AccountProxyInterface $currentUser,
) {
  $this->entityTypeManager = $entityTypeManager;
  $this->currentUser = $currentUser;
}
```

Reglas importantes:

- `@servicio`: solicita al contenedor una instancia de otro servicio registrado.
- `@?servicio`: referencia opcional; si el servicio no existe se inyecta `NULL`.
- `%parametro%`: obtiene un parámetro definido en el contenedor.
- Los valores primitivos se pueden pasar directamente, por ejemplo strings, booleanos o arrays.
- El orden de `arguments` debe coincidir con el orden de los parámetros del constructor.

Ejemplos:

```yaml
# Servicio y parámetro del contenedor.
arguments: ['@database', '%system.default_tz%']

# Servicio, string y booleano.
arguments: ['@logger.factory', 'mi_canal', true]
```

## Inyección en controllers y formularios

Los controllers que extienden `ControllerBase` y los formularios que extienden `FormBase` pueden implementar `create(ContainerInterface $container)`. Drupal llama esa fábrica para resolver las dependencias y después invoca el constructor.

En este módulo se aplican tres casos:

| Clase | Dependencia inyectada |
| --- | --- |
| `BasicController` | `drupal_tutor_basic.content_manager` |
| `SimpleContactForm` | `logger.factory` |
| `FastPageCreateForm` | `entity_type.manager` |

Este patrón mantiene las clases comprobables y evita llamadas globales como `\Drupal::service()` dentro de la lógica de negocio.

## Archivos del Sprint 1

```text
drupal_tutor_basic/
├── drupal_tutor_basic.info.yml
├── drupal_tutor_basic.routing.yml
├── drupal_tutor_basic.services.yml
└── src/
    ├── Controller/
    │   └── BasicController.php
    ├── Form/
    │   ├── FastPageCreateForm.php
    │   └── SimpleContactForm.php
    └── Services/
        └── ContentManagerService.php
```

## Cómo probarlo

Si el módulo no está habilitado o se modificaron rutas y servicios:

```bash
fin exec drush en drupal_tutor_basic -y
fin exec drush cr
```

Después se pueden recorrer las cinco rutas indicadas al inicio. Para el listado y las estadísticas conviene tener contenido `article` y `page`; puede crearse manualmente o generarse con Devel Generate.

## Estado actual y detalles a tener en cuenta

La documentación anterior describe el comportamiento que existe hoy en el repositorio. También hay algunos detalles útiles para una futura ronda de limpieza:

- los nombres `getLatesrArticles()` y `gestSiteStats()` contienen errores tipográficos, pero se documentan así porque son los nombres reales usados por el controller;
- el comentario de `calculateReadingTime()` menciona 100 palabras por minuto, mientras que la operación implementada usa 200;
- `current_user` está correctamente inyectado, pero aún no participa en la lógica;
- `FastPageCreateForm::buildForm()` conserva un `dump($form)` de depuración;
- en la ruta del formulario de contacto, `requirements` está indentado dentro de `defaults`; por eso el permiso `access content` no se aplica actualmente. Debe moverse al mismo nivel que `defaults` para proteger la ruta como se pretendía.
