# 🚀 Ruta de Entrenamiento: Drupal

Este documento contiene los retos prácticos de los Sprints 1 y 2. El objetivo es dominar la inyección de dependencias, formularios, consultas a entidades, y lógica asíncrona usando módulos custom y contenido generado por Devel.

---

## 🟢 Sprint 1: Nivel Básico
**Módulo:** `drupal_tutor_basic`
**Objetivos:** Dominar Inyección de dependencias, FormBase, ParamConverter y EntityQuery simple.

- [ ] **1. El Controlador del Usuario Inyectado**
  - **Ruta:** `/tutor/mi-cuenta`
  - **Reto:** Muestra el ID, nombre y correo del usuario actual.
  - **Restricción:** Inyecta el servicio `@current_user` en el constructor del controlador; prohibido usar `\Drupal::currentUser()`.

- [ ] **2. El Inspector de Usuarios (ParamConverter)**
  - **Ruta:** `/tutor/inspeccionar-usuario/{user}`
  - **Reto:** Imprime el mensaje: *"El usuario [nombre] fue registrado hace [X] segundos"*.
  - **Pista:** Usa `@datetime.time` y réstalo de `$user->getCreatedTime()`.

- [ ] **3. Calculadora con FormBase**
  - **Ruta:** `/tutor/calculadora`
  - **Reto:** Crea un formulario con "Número A", "Número B" y un `<select>` de operación (Sumar, Restar).
  - **Validación:** Si A o B no son numéricos, lanza un error en el campo vía `validateForm`.
  - **Ejecución:** Muestra el resultado con `$this->messenger()->addStatus()` en el `submitForm`.

- [ ] **4. EntityQuery de Limpieza**
  - **Reto:** Añade el método `getOldestPages()` a tu `ContentManagerService` para retornar los **3 nodos tipo "Página" más antiguos**.
  - **Consumo:** Llama a este método desde un controlador y pinta los títulos en una lista HTML.

- [ ] **5. Creación rápida de Usuarios con Form API**
  - **Reto:** Formulario (`FormBase`) con "Nombre de usuario" y "Correo".
  - **Acción:** En el `submitForm`, inyecta `EntityTypeManager` para crear el usuario, asignarle una contraseña quemada (ej. "Tutor123") y activarlo (`status => 1`).

---

## 🟡 Sprint 2: Nivel Intermedio
**Módulo:** `drupal_tutor_intermediate`
**Objetivos:** Configuración persistente, lógica asíncrona (Batch), consultas complejas y seguridad delegada.

- [ ] **6. El Interruptor de Mantenimiento (ConfigFormBase)**
  - **Ruta:** `/admin/config/tutor/mantenimiento`
  - **Reto:** Checkbox ("Activar alerta") y Textarea ("Mensaje").
  - **Acción:** Al guardar, persiste los valores en la tabla `config` bajo `drupal_tutor_intermediate.maintenance`.

- [ ] **7. Seguridad por Antigüedad (Custom Access)**
  - **Ruta:** `/tutor/solo-veteranos`
  - **Reto:** Protege la ruta usando `_custom_access`.
  - **Regla:** Solo permite la entrada (`AccessResult::allowed()`) si el ID del usuario logueado es **menor a 10**. De lo contrario, deniega.

- [ ] **8. EntityQuery Avanzado: "Los No Publicados del Admin"**
  - **Reto:** Crea un método en `NodeProcessor` que retorne los IDs de todos los artículos **despublicados** (`status = 0`) cuyo autor sea el usuario **ID 1**.

- [ ] **9. Batch API: Despublicación Masiva**
  - **Reto:** Modifica el formulario de Batch del Sprint 2 para buscar todos los artículos de Devel y **despublicarlos masivamente** (`$node->setUnpublished()`).
  - **UX:** La barra de progreso debe decir *"Despublicando el artículo: [Título]"*.

- [ ] **10. El Integrador (Config + Query + Controller)**
  - **Ruta:** `/tutor/alerta-sistema`
  - **Reto:**
    1. Lee el checkbox del Ejercicio 6 usando `config.factory`.
    2. Si está apagado: Muestra "Sistema operando con normalidad".
    3. Si está encendido: Muestra el "Mensaje" guardado Y cuenta cuántos artículos hay en total usando tu servicio del Sprint 1.
