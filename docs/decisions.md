# Registro de decisiones

Cada entrada deja constancia de una decisión técnica y, sobre todo, del motivo. La idea es no volver a discutir lo ya resuelto, y poder revisarlo cuando el motivo deje de ser válido.

---

## 001 · Arquitectura por capas en el backend

**Fecha:** 2026-07-26
**Estado:** Aceptada

Se adopta una arquitectura por capas:

```
Controller → Validator → Service → Repository
```

**Motivo:** separar responsabilidades y facilitar el mantenimiento.

---

## 002 · El registro de una cuenta crea también el perfil de cliente

**Fecha:** 2026-09-02
**Estado:** Aceptada

`POST /api/auth/register` y `POST /api/clients` comparten controlador y servicio. Existe un único camino de alta, que escribe en `usuarios` y en `clientes` dentro de una transacción.

**Motivo:** había dos rutas de registro divergentes. `/auth/register` creaba solo la fila de `usuarios`, y como `citas.id_cliente` apunta a `clientes`, cualquiera que se registrara por ahí quedaba con una cuenta incapaz de reservar. Registrarse y ser cliente son lo mismo en este dominio, así que tener dos caminos solo podía producir estados inconsistentes.

**Consecuencia:** `POST /api/clients` queda restringido al administrador, como alta desde el panel. `AuthService::register` y `UserValidator::validateRegister` se eliminaron.

---

## 003 · Un recurso ajeno se reporta como inexistente

**Fecha:** 2026-09-02
**Estado:** Aceptada

Cuando un cliente autenticado consulta una cita o un perfil que no le pertenece, la API responde **404**, no 403.

**Motivo:** un 403 confirma que ese identificador existe. Con él se puede recorrer la base probando números y averiguar cuántos clientes hay o cuándo se reservó una cita. El 404 no distingue entre «no existe» y «no es tuyo».

**Consecuencia:** el 403 queda reservado para el caso en que el usuario está autenticado pero su rol no autoriza la acción, o no tiene perfil de cliente asociado. Depurar es algo menos directo, y por eso los mensajes de esos casos son explícitos.

---

## 004 · La autorización se declara en la tabla de rutas

**Fecha:** 2026-09-05
**Estado:** Aceptada

Cada ruta declara sus guardas junto a su definición:

```php
Route::get('/api/clients', ClientController::class, 'index')->allowRoles(Roles::ADMIN),
```

Un `Container` mínimo construye controladores y servicios leyendo los tipos de sus constructores.

**Motivo:** el enrutador anterior era un `switch` de 349 líneas donde cada caso instanciaba su cadena de dependencias y aplicaba los middlewares a mano. Ese diseño hacía fácil olvidar una protección, y es exactamente como los endpoints de clientes y citas quedaron accesibles sin autenticación. Con la tabla declarativa, la protección de las 21 rutas se lee de un vistazo.

**Consecuencia:** `allowRoles()` implica `requireAuth()`, de modo que declarar solo el rol nunca deje una ruta sin autenticar. Si un controlador pide `$authUser` en una ruta pública, el enrutador falla al despachar con un mensaje que lo explica.

---

## 005 · Los middlewares lanzan excepciones, no respuestas

**Fecha:** 2026-09-05
**Estado:** Aceptada

`AuthMiddleware` y `RoleMiddleware` lanzan `AuthException` y `ForbiddenException` en lugar de escribir la respuesta y terminar la ejecución.

**Motivo:** convivían dos mecanismos de manejo de errores: un `ExceptionHandler` central y middlewares que llamaban a `Response::error()` con `exit`. Con dos caminos, el formato de las respuestas de error puede divergir sin que nadie lo note.

**Consecuencia:** `ExceptionHandler` es el único punto donde un error se convierte en respuesta. `AuthException` extiende `UnauthorizedException` para reutilizar su traducción a 401.

---

## 006 · El horario ocupado se protege en la aplicación y en la base

**Fecha:** 2026-09-05
**Estado:** Aceptada

La regla «un profesional no atiende dos citas a la vez» se aplica en dos niveles: `AppointmentService` calcula el cruce real usando la duración del servicio, y el índice `uq_citas_agenda` lo respalda en la base de datos.

**Motivo:** la comprobación en la aplicación es la única que puede considerar la duración —una cita de 90 minutos pisa a la siguiente aunque empiece a otra hora—, pero no protege de dos peticiones simultáneas que la superen a la vez. El índice cubre esa carrera; la aplicación cubre la regla completa.

**Consecuencia:** como las citas se cancelan de forma lógica, un `UNIQUE` corriente dejaría el horario bloqueado para siempre. Se usa una columna generada que vale `NULL` cuando la cita está cancelada, aprovechando que MySQL admite `NULL` repetidos en un índice único.

Al traducir la violación del índice a un conflicto de dominio hay que mirar el código de MySQL `1062` y el nombre del índice, no el `SQLSTATE` `23000`: ese código cubre también las claves foráneas, y usarlo a secas hacía que un `id_servicio` inexistente se reportara como «horario ocupado».

---

## 007 · El token se guarda en sessionStorage

**Fecha:** 2026-09-05
**Estado:** Aceptada, con reservas

El JWT se guarda en `sessionStorage` y se envía en la cabecera `Authorization`.

**Motivo:** es lo más simple para un frontend sin backend propio que pueda fijar cookies `HttpOnly`, y la sesión muere al cerrar la pestaña.

**Reserva:** al ser accesible desde JavaScript, un XSS podría robarlo. Se mitiga escapando toda salida que provenga del usuario, pero la mitigación real sería una cookie `HttpOnly` con protección CSRF. Revisar esta decisión antes de un despliegue en producción.

**Consecuencia:** no hay revocación ni refresco de token. Al vencer, la API responde 401 y el cliente HTTP limpia la sesión y redirige a login.

---

## 008 · El enrutador inyecta la petición

**Fecha:** 2026-09-05
**Estado:** Aceptada

Los controladores reciben un objeto `Core\Request` en lugar de leer `file_get_contents('php://input')` por su cuenta.

**Motivo:** `php://input` es un flujo del entorno que no se puede sustituir desde el proceso de pruebas. Mientras el cuerpo se leyera ahí, ninguna escritura era verificable de extremo a extremo: las pruebas de la API solo podían cubrir `GET`, y todo `POST`, `PUT` y `DELETE` quedaba comprobado a nivel de servicio pero nunca a través de la tabla de rutas.

**Consecuencia:** el análisis del cuerpo ocurre en un solo sitio. Un JSON malformado se rechaza con `422` y un mensaje que nombra el problema, en lugar de decodificarse a `null` y reportarse como una lista de campos obligatorios.

`Router::dispatch()` pasa a recibir la petición completa en vez de método y ruta sueltos. La inyección se resuelve por tipo, no por nombre, para que no dependa de cómo se llame el parámetro.
