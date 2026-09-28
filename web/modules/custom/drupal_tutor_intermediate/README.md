# Drupal Tutor Intermediate Module

Módulo del **Sprint 2** que amplía los conceptos básicos con: formulario de configuración (`ConfigFormBase`), control de acceso personalizado (`_custom_access` + servicio), EntityQuery avanzada, Batch API, y hooks de instalación (`hook_install`/`hook_uninstall`).

---

## Tabla de contenidos

1. [Arquitectura general](#arquitectura-general)
2. [Formulario de configuración](#formulario-de-configuracion)
3. [Control de acceso personalizado (VIP)](#control-de-acceso-personalizado-vip)
4. [EntityQuery avanzada](#entityquery-avanzada)
5. [Batch API](#batch-api)
6. [Hooks de instalación](#hooks-de-instalacion)
7. [Diagramas de flujo](#diagramas-de-flujo)
8. [Decisiones de diseño](#decisiones-de-diseno)
9. [Cómo probar](#como-probar)

---

## Arquitectura general

```
drupal_tutor_intermediate/
├── drupal_tutor_intermediate.info.yml        # Metadatos + dependencia a tutor_basic
├── drupal_tutor_intermediate.routing.yml     # 3 rutas (config, VIP, batch)
├── drupal_tutor_intermediate.services.yml    # 2 servicios (access + processor)
├── drupal_tutor_intermediate.module          # hook_install / hook_uninstall
└── src/
    ├── Access/VipAccessCheck.php             # _custom_access service
    ├── Controller/VipController.php          # Controller simple
    ├── Form/
    │   ├── TutorSettingsForm.php             # ConfigFormBase
    │   └── BatchUpdateForm.php               # Form + BatchBuilder
    └── Services/NodeProcessor.php            # Logica Batch + EntityQuery
```

### Dependencias (`.info.yml`)

```yaml
dependencies:
  - drupal_tutor_basic    # Requiere el modulo base (servicios, entidades)
```

---

## Formulario de configuración

### Archivo: `src/Form/TutorSettingsForm.php`

```php
class TutorSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['drupal_tutor_intermediate.settings'];
  }

  public function getFormId() {
    return 'drupal_tutor_intermediate_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drupal_tutor_intermediate.settings');

    $form['batch_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit node (Batch)'),
      '#description' => $this->t('Cuantos nodos se procesaran por cada peticion AJAX'),
      '#default_value' => $config->get('batch_limit') ?? 50,
      '#min' => 10,
      '#max' => 500,
    ];

    $form['vip_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain VIP'),
      '#description' => $this->t('Solo los correos con este dominio podran acceder a la ruta vip'),
      '#default_value' => $config->get('vip_domain') ?? '@mipropio.com',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('drupal_tutor_intermediate.settings')
      ->set('batch_limit', $form_state->getValue('batch_limit'))
      ->set('vip_domain', $form_state->getValue('vip_domain'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
```

### ¿Por qué `ConfigFormBase`?

```mermaid
classDiagram
    class FormBase {
        +buildForm()
        +validateForm()
        +submitForm()
        +getFormId()
    }
    class ConfigFormBase {
        +config() : Config
        +configFactory() : ConfigFactory
        +getEditableConfigNames() : string[]
    }
    class TutorSettingsForm {
        +getEditableConfigNames()
        +buildForm()
        +submitForm()
    }
    FormBase --|> ConfigFormBase
    ConfigFormBase --|> TutorSettingsForm
```

**Ventajas:**

| Feature | FormBase | ConfigFormBase |
|---------|----------|----------------|
| `$this->config('name')` | Manual | Helper |
| `$this->configFactory()` | Manual | Helper |
| Guarda config automático | No | En `parent::submitForm()` |
| `getEditableConfigNames()` | No existe | Obligatorio |
| Permisos admin | Manual | Ruta usa `administer site configuration` |

### Flujo de guardado

```mermaid
sequenceDiagram
    actor Admin
    participant Route
    participant Form
    participant ConfigFactory
    participant ConfigStorage
    participant Database

    Admin->>Route: POST /admin/config/tutor/opciones
    Route->>Form: TutorSettingsForm.buildForm
    Form->>ConfigFactory: config(drupal_tutor_intermediate.settings)
    ConfigFactory->>ConfigStorage: load
    ConfigStorage-->>Form: Config object
    Form-->>Admin: HTML form
    Admin->>Route: Submit batch_limit=25 vip_domain=@empresa.com
    Route->>Form: validateForm -> submitForm
    Form->>ConfigFactory: getEditable(drupal_tutor_intermediate.settings)
    ConfigFactory->>ConfigStorage: create editable object
    Form->>EditableConfig: set().set().save
    EditableConfig->>ConfigStorage: write
    ConfigStorage->>Database: UPDATE config SET data=... WHERE name=...
    Database-->>Admin: Redirect + status message
```

### Configuración creada (`hook_install`)

```php
function drupal_tutor_intermediate_install() {
  \Drupal::service('config.factory')->getEditable('drupal_tutor_intermediate.settings')
    ->set('batch_limit', 50)
    ->set('vip_domain', '@mipropio.com')
    ->save(TRUE);
}
```

**Archivo generado:**
```yaml
batch_limit: 50
vip_domain: '@mipropio.com'
```

---

## Control de acceso personalizado (VIP)

### Archivo: `src/Access/VipAccessCheck.php`

```php
class VipAccessCheck {
  protected $currentUser;
  protected $configFactory;

  public function __construct(AccountInterface $currentUser, ConfigFactoryInterface $configFactory) {
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
  }

  public function access(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden()->addCacheableDependency($account);
    }

    $email = $account->getEmail();
    $config = $this->configFactory->get('drupal_tutor_intermediate.settings');
    $required_domain = $config->get('vip_domain');

    if (empty($required_domain)) {
      return AccessResult::forbidden()->addCacheableDependency($config);
    }

    if (str_ends_with($email, $required_domain)) {
      return AccessResult::allowed()->addCacheableDependency($config);
    }

    return AccessResult::forbidden('No tienes acceso a esta zona')->addCacheableDependency($config);
  }
}
```

### Registro como servicio (`services.yml`)

```yaml
services:
  drupal_tutor_intermediate.custom_access:
    class: Drupal\drupal_tutor_intermediate\Access\VipAccessCheck
    arguments: ['@current_user', '@config.factory']
```

### Ruta con `_custom_access` (`routing.yml`)

```yaml
drupal_tutor_intermediate.vip_area:
  path: '/tutor/intermedio/zona-vip'
  defaults:
    _controller: 'Drupal\drupal_tutor_intermediate\Controller\VipController::content'
    _title: 'Zona VIP'
  requirements:
    _custom_access: 'drupal_tutor_intermediate.custom_access:access'
```

### Servicio vs Clase estática

```mermaid
flowchart TD
    A[Request tutor intermedio zona-vip] --> B{Router}
    B --> C{_custom_access type}
    C --> D[Clase metodo sin DI]
    C --> E[Servicio metodo con DI]
    E --> F[VipAccessCheck construct]
    F --> G[access AccountInterface]
```

| Sintaxis | Inyección | Uso típico |
|----------|-----------|------------|
| `'Clase::metodo'` | No | Access checks simples |
| `'servicio:metodo'` | Si | Access checks con config, BD |

### `AccessResult` y Cacheabilidad

```php
// CORRECTO
return AccessResult::allowed()->addCacheableDependency($config);

// INCORRECTO
return AccessResult::allowed();
```

**Cache tags:**
- `$account` → `user:<uid>` + `user.roles:authenticated`
- `$config` → `config:drupal_tutor_intermediate.settings`

### Flujo completo VIP Access

```mermaid
sequenceDiagram
    actor User
    participant Router
    participant AccessManager
    participant Container
    participant VipAccessCheck
    participant ConfigFactory
    participant Controller

    User->>Router: GET tutor intermedio zona-vip
    Router->>AccessManager: checkAccess route account
    AccessManager->>Container: get custom_access service
    Container->>VipAccessCheck: new VipAccessCheck current_user config_factory
    AccessManager->>VipAccessCheck: access account
    VipAccessCheck->>ConfigFactory: get settings
    ConfigFactory-->>VipAccessCheck: Config object
    VipAccessCheck->>VipAccessCheck: str_ends_with email domain
    alt Anonymous
        VipAccessCheck-->>AccessManager: Forbidden cache user
    else Email matches
        VipAccessCheck-->>AccessManager: Allowed cache config
    else Email no match
        VipAccessCheck-->>AccessManager: Forbidden cache config
    end
    AccessManager-->>Router: AccessResult
    alt Allowed
        Router->>Controller: VipController content
        Controller-->>User: Zona VIP acceso permitido
    else Forbidden
        Router-->>User: 403 Access Denied
    end
```

---

## EntityQuery avanzada

### Archivo: `src/Services/NodeProcessor.php`

```php
public function getNodesToProcess(): array {
  $storage = $this->entityTypeManager->getStorage('node');

  $query = $storage->getQuery()
    ->condition('type', 'article')
    ->condition('status', 1)
    ->condition('title', '%[[]ACTUALIZADO[]]%', 'NOT LIKE')
    ->accessCheck(FALSE);
  return $query->execute();
}
```

### Análisis de la query

```mermaid
flowchart TD
    A[getStorage node] --> B[getQuery]
    B --> C[condition type article]
    C --> D[condition status 1]
    D --> E[condition title NOT LIKE]
    E --> F[accessCheck FALSE]
    F --> G[execute]
    G --> H[Array NIDs]
```

**Escape de corchetes en LIKE:**
- `[` y `]` son comodines en SQL LIKE
- Para buscar literal `[ACTUALIZADO]`: escapar como `[[]ACTUALIZADO[]]`
- Drupal/PDO traduce a: `title NOT LIKE '%[ACTUALIZADO]%'`

**`accessCheck(FALSE)`** — Saltar node_access. Proceso batch es admin.

### SQL generado aproximado

```sql
SELECT nid
FROM node_field_data
WHERE type = 'article'
  AND status = 1
  AND title NOT LIKE '%[ACTUALIZADO]%' ESCAPE '\'
```

---

## Batch API

### Archivo: `src/Form/BatchUpdateForm.php`

```php
public function submitForm(array &$form, FormStateInterface $form_state) {
  $nids = $this->nodeProcessor->getNodesToProcess();
  $limit = $this->config('drupal_tutor_intermediate.settings')->get('batch_limit') ?? 50;

  $chunks = array_chunk($nids, $limit);

  $batch = (new BatchBuilder())
    ->setTitle($this->t('Actualizando Articulos generados por Devel'))
    ->setFinishCallback([NodeProcessor::class, 'finishBatch'])
    ->setInitMessage($this->t('Iniciando...'))
    ->setProgressMessage($this->t('Procesando lote @current de @total.'));

  foreach ($chunks as $chunk) {
    foreach ($chunk as $nid) {
      $batch->addOperation([NodeProcessor::class, 'processBatchItem'], [$nid]);
    }
  }

  $form_state->set('batch', $batch->toArray());
}
```

### Archivo: `src/Services/NodeProcessor.php` — Callbacks

```php
public static function processBatchItem($nid, $context) {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $node = $storage->load($nid);

  if ($node) {
    $old_title = $node->getTitle();
    $node->setTitle('[ACTUALIZADO] ' . $old_title);
    $node->save();

    $context['message'] = 'Actualizando nodo ID: ' . $nid;
    $context['results'][] = $nid;
  }
}

public static function finishBatch($success, $results, $operations) {
  $messenger = \Drupal::messenger();
  if ($success) {
    $count = count($results);
    $messenger->addStatus('Exito! se actualizaron ' . $count . ' resultados.');
  } else {
    $messenger->addError('Ha ocurrido un error actualizando los resultados.');
  }
}
```

### Arquitectura Batch API

```mermaid
flowchart TD
    A[BatchUpdateForm_submitForm] --> B[getNodesToProcess]
    B --> C[array_chunk NIDs por batch_limit]
    C --> D[BatchBuilder]
    D --> E[addOperation por NID]
    E --> F[form_state set batch]
    F --> G[Form submit completo]
    G --> H[Drupal detecta batch]
    H --> I[Redirige a batch page]
    I --> J[Batch processing AJAX]
    J --> K[processBatchItem NID 1]
    K --> L[save node]
    L --> M[context results nid]
    M --> K
    K --> N[Proximo chunk via AJAX]
    N --> K
    K --> O[finishBatch]
    O --> P[Messenger status]
    P --> Q[Redirect final]
```

### `BatchBuilder` vs `batch_set()` legacy

| Método | Drupal 8/9 | Drupal 10+ | Form submit |
|--------|------------|------------|-------------|
| `batch_set($batch)` | Si | Deprecated | No |
| `form_state.set('batch')` | No | Recomendado | Si |
| `BatchBuilder` (fluent) | Si | Si | Si |

### Procesamiento por chunks

```php
$nids = [1,2,3,4,5,6,7,8,9,10];
$limit = 3;
$chunks = array_chunk($nids, 3);
// [[1,2,3], [4,5,6], [7,8,9], [10]]
// 10 operaciones en 4 requests AJAX
```

---

## Hooks de instalación

### Archivo: `drupal_tutor_intermediate.module`

```php
function drupal_tutor_intermediate_install() {
  \Drupal::service('config.factory')->getEditable('drupal_tutor_intermediate.settings')
    ->set('batch_limit', 50)
    ->set('vip_domain', '@mipropio.com')
    ->save(TRUE);
}

function drupal_tutor_intermediate_uninstall() {
  \Drupal::service('config.storage')->delete('drupal_tutor_intermediate.settings');
}
```

### Flujo de instalación

```mermaid
sequenceDiagram
    actor Admin
    participant Drush
    participant ModuleHandler
    participant ConfigFactory
    participant ConfigStorage
    participant Database

    Admin->>Drush: drush en drupal_tutor_intermediate
    Drush->>ModuleHandler: install(['drupal_tutor_intermediate'])
    ModuleHandler->>ModuleHandler: module_load_install
    ModuleHandler->>ConfigFactory: getEditable(settings)
    ConfigFactory->>ConfigStorage: create editable
    ModuleHandler->>EditableConfig: set().set().save(TRUE)
    EditableConfig->>ConfigStorage: write
    ConfigStorage->>Database: INSERT INTO config
    Database-->>Admin: Module enabled + config created
```

### `save(TRUE)` — Skip schema validation

```php
// TRUE = confía en instalación nueva, no valida schema
->save(TRUE);

// FALSE (default) = valida contra config/schema/*.yml
->save(FALSE);
```

---

## Diagramas de flujo

### 1. Arquitectura general del módulo

```mermaid
graph TB
    subgraph Config[Configuracion]
        CF[TutorSettingsForm] --> CS[settings]
        CS --> BF[BatchUpdateForm]
        CS --> VA[VipAccessCheck]
    end

    subgraph VIP[Zona VIP]
        R1[Ruta zona vip] --> VA
        VA --> VC[VipController]
    end

    subgraph Batch[Actualizacion masiva]
        R2[Ruta actualizar nodos] --> BF
        BF --> NP[NodeProcessor]
        NP --> EQ[EntityQuery]
        EQ --> NP
        NP --> Node[Node save]
        NP --> Messenger
    end

    subgraph Install[Instalacion]
        HI[hook_install] --> CS
        HU[hook_uninstall] --> CS
    end
```

### 2. Flujo Batch API detallado

```mermaid
sequenceDiagram
    actor Admin
    participant Form
    participant BatchBuilder
    participant FormState
    participant BatchSystem
    participant Processor
    participant Database
    participant Messenger

    Admin->>Form: POST tutor intermedio actualizar nodos
    Form->>Processor: getNodesToProcess
    Processor->>Database: EntityQuery
    Database-->>Processor: nid1 nid2 ...
    Form->>Form: array_chunk nids batch_limit
    Form->>BatchBuilder: new BatchBuilder addOperation
    Form->>FormState: set batch batch toArray
    FormState-->>BatchSystem: Batch detectado
    BatchSystem->>Admin: Redirect batch page op start
    Admin->>BatchSystem: AJAX batch page op do
    loop Por cada operacion
        BatchSystem->>Processor: processBatchItem nid context
        Processor->>Database: load nid setTitle save
        Processor->>Context: results nid
    end
    BatchSystem->>Processor: finishBatch success results ops
    Processor->>Messenger: addStatus Exito X resultados
    BatchSystem-->>Admin: Redirect final
```

### 3. Access Check con cache tags

```mermaid
flowchart TD
    A[Access Check] --> B{Anonymous}
    B --> C[Forbidden cache account]
    B --> D[Get email]
    D --> E[Load config]
    E --> F{vip_domain vacio}
    F --> G[Forbidden cache config]
    F --> H{str_ends_with}
    H --> I[Allowed cache config]
    H --> J[Forbidden cache config]

    C --> K[Cache tags user uid]
    G --> L[Cache tags config settings]
    I --> L
    J --> L

    K --> M[Render cache entry]
    L --> M
    M --> N[Proxima request cache hit]
```

### 4. EntityQuery con escape LIKE

```mermaid
flowchart LR
    A[Titulo ACTUALIZADO Mi articulo] --> B[condition title NOT LIKE]
    B --> C[Patron porciento corchete ACTUALIZADO corchete porciento]
    C --> D[Drupal escapa corchetes]
    D --> E[SQL title NOT LIKE porciento ACTUALIZADO porciento]
    E --> F{Coincide}
    F --> G[Excluido]
    F --> H[Incluido]
```

### 5. hook_install con save(TRUE)

```mermaid
flowchart TD
    A[drush en modulo] --> B{Config existe}
    B --> C[hook_install]
    C --> D[save TRUE]
    D --> E[INSERT config sin validar schema]
    B --> F[No ejecuta hook_install]
    F --> G[Config existente respetada]
```

---

## Decisiones de diseño

### 1. `_custom_access` con servicio inyectado

| Opción | Pros | Contras |
|--------|------|---------|
| `_permission` | Simple | No permite logica dinamica |
| `_role` | Simple | Muy grueso |
| `_access: 'TRUE'` | Abierto | Sin control |
| `_custom_access: 'Clase::metodo'` | Sin container | Sin DI |
| **`_custom_access: 'servicio:metodo'`** | **DI completo** | **Ligeramente verbose** |

### 2. `ConfigFormBase` vs `FormBase` + config manual

**ConfigFormBase gana:** helpers, validación schema, permisos admin por defecto.

### 3. Batch callbacks `static` vs inyectados

```php
// Static (estándar Drupal Batch)
public static function processBatchItem($nid, $context) {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  ...
}
```

**Por qué static:** Batch API serializa operaciones; en cada request AJAX se re-instancia. `static` no requiere serializar el objeto.

### 4. `array_chunk` para lotes (1 operación por NID)

- Progreso granular (barra avanza por NID)
- `context['message']` muestra NID actual
- Fallo en 1 NID no afecta a otros

---

## Cómo probar

```bash
fin exec drush cr
fin exec drush en drupal_tutor_intermediate -y

# 1. Configuración
# Navegador: /admin/config/tutor/opciones

# 2. Zona VIP
# Usuario con email @ejemplo.com -> /tutor/intermedio/zona-vip -> ACCESO
# Usuario con @otro.com -> 403

# 3. Batch Update
# drush genc 20 --types=article
# Navegador: /tutor/intermedio/actualizar-nodos

# 4. Logs
# /admin/reports/dblog -> Type: drupal_tutor_intermediate
```

---

## Referencias técnicas

- [ConfigFormBase](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Form%21ConfigFormBase.php/class/ConfigFormBase)
- [Access System](https://www.drupal.org/docs/drupal-apis/access-api)
- [Batch API](https://www.drupal.org/docs/drupal-apis/batch-api)
- [EntityQuery](https://www.drupal.org/docs/drupal-apis/entity-api/entity-query)
- [hook_install](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Extension%21module.api.php/function/hook_install)
- [Cacheability Metadata](https://www.drupal.org/docs/drupal-apis/cache-api/cacheable-dependencies)