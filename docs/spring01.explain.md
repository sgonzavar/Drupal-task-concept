Los Servicios Core del Sprint 1
entity_type.manager (EntityTypeManagerInterface)
Es el servicio más utilizado en Drupal. Actúa como un "directorio central" para interactuar con cualquier entidad del sistema (Nodos, Usuarios, Taxonomías, Comentarios).

¿Qué hace? Te proporciona el Storage (almacenamiento) para crear, cargar, actualizar o borrar entidades, y te da acceso al Query para realizar búsquedas en la base de datos sin escribir SQL directo.

Ejemplo en nuestro código: Lo usamos para consultar los últimos artículos creados por Devel y para guardar la nueva "Página" desde el formulario.

current_user (AccountProxyInterface)
Representa al usuario que está ejecutando la petición HTTP en ese exacto momento.

¿Qué hace? Permite obtener el ID del usuario (id()), su correo, sus roles (getRoles()) o comprobar si tiene permisos específicos (hasPermission()). Reemplaza el uso de la variable global $user que existía en Drupal 7.

logger.factory (LoggerChannelFactoryInterface)
Es el canal de comunicación con el sistema de registros (Watchdog) de Drupal.

¿Qué hace? Permite registrar errores, advertencias o información útil (info, error, notice) que luego los administradores pueden revisar en la interfaz (admin/reports/dblog).

¿Cómo funcionan los arguments en el archivo services.yml?
En Drupal (que utiliza el Contenedor de Inyección de Dependencias de Symfony), el archivo services.yml es donde le das las instrucciones al sistema sobre cómo construir tu clase PHP.

Cuando escribes esto: arguments: ['@entity_type.manager', '@current_user']

Estás dictando dos reglas estrictas:public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
// ...
}

¿Qué otras cosas se pueden pasar en arguments?
El archivo de servicios es muy flexible. No solo puedes inyectar otros servicios; puedes pasar distintos tipos de valores para hacer tus clases más dinámicas:

Otros servicios personalizados: Si creas un servicio @drupal_tutor.calculadora, puedes pasarlo a otro servicio tuyo simplemente poniéndolo en la lista.

Servicios Opcionales (?@): Si un servicio podría no existir (por ejemplo, si depende de un módulo que puede estar apagado), le agregas el signo de interrogación: ['?@mi_modulo.opcional']. Si no existe, Drupal inyectará NULL en tu constructor.

Parámetros globales (%): Puedes pasar variables de configuración fijas definidas en el propio YAML o en settings.php usando porcentajes.



El símbolo @: Le indica al contenedor: "No me pases un texto, búscame un servicio registrado con este nombre máquina, instáncialo si no existe, y tráelo".

El orden es ley: El orden de los elementos en el array arguments debe coincidir exactamente con el orden de las variables en el método __construct() de tu clase PHP.

Si en el YAML pones primero @entity_type.manager y luego @current_user, tu PHP obligatoriamente debe recibirlos en ese mismo orden: arguments: ['@database', '%system.default_tz%']

Valores primitivos (Strings, Booleanos, Arrays): Puedes pasar datos quemados directamente, lo cual es útil para reutilizar una misma clase PHP con diferentes configuraciones.
arguments: ['@logger.factory', 'mi_canal_personalizado', TRUE]
