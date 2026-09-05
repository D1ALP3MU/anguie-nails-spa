# Arquitectura

## Principio

Cada capa tiene una responsabilidad y solo conoce la inmediatamente inferior. Las dos reglas que sostienen el diseño:

- **Todo el SQL vive en los repositorios.** Ningún servicio, controlador ni validador escribe una consulta.
- **Solo los controladores y `ExceptionHandler` producen respuestas HTTP.** Un servicio nunca hace `echo` ni fija un código de estado: lanza una excepción de dominio.

---

## Flujo de una petición

```
Cliente HTTP
     │
     ▼
public/index.php ─────── carga .env, configura errores, arma el contenedor
     │
     ▼
Core\Router ──────────── empareja la ruta, aplica guardas, resuelve el controlador
     │                        │
     │                        └── Middleware\AuthMiddleware   → AuthException (401)
     │                            Middleware\RoleMiddleware   → ForbiddenException (403)
     ▼
Controller ───────────── lee el cuerpo, delega, formatea la respuesta
     │
     ▼
Validator ────────────── formato de los datos          → ValidationException (422)
     │
     ▼
Service ──────────────── reglas de negocio y permisos  → NotFound / Conflict / Forbidden
     │
     ▼
Repository ───────────── SQL con sentencias preparadas
     │
     ▼
   MySQL
```

Cualquier excepción sube hasta `index.php`, donde `ExceptionHandler` la traduce a JSON. Es el único punto donde un error se convierte en respuesta.

---

## Capas

| Capa | Responsabilidad | No hace |
| --- | --- | --- |
| **Controller** | Leer la petición, invocar al servicio, devolver JSON | Lógica de negocio, SQL |
| **Validator** | Comprobar el formato de los datos de entrada | Consultar la base de datos |
| **Service** | Reglas de negocio, permisos, transacciones | SQL, respuestas HTTP |
| **Repository** | Acceso a datos | Decisiones de negocio |

La validación se divide a propósito:

- **`Validator`** responde «¿tiene forma de fecha?». No toca la base de datos, así que es puro y trivial de probar.
- **`Service`** responde «¿ese servicio existe y el profesional está libre?». Necesita datos, y por eso vive un nivel más abajo.

---

## Núcleo

### `Core\Route`

Describe una ruta y **lo que exige para ejecutarse**, en la misma línea:

```php
Route::get('/api/services', ServiceController::class, 'index'),          // público
Route::get('/api/appointments', AppointmentController::class, 'index')->requireAuth(),
Route::get('/api/clients', ClientController::class, 'index')->allowRoles(Roles::ADMIN),
```

`allowRoles()` implica `requireAuth()`: declarar solo el rol nunca deja una ruta sin autenticar.

Los segmentos `{id}` son parámetros. Los tramos literales se escapan al construir la expresión regular, de modo que un punto en la ruta no actúe como comodín.

### `Core\Container`

Construye una clase leyendo los tipos de su constructor y resolviendo cada dependencia de forma recursiva. Lo único que se registra a mano es la conexión:

```php
$container->bind(PDO::class, (new Database())->connect());
$controller = $container->get(AppointmentController::class);
```

Eso arma por sí solo el controlador, su servicio y los cuatro repositorios que necesita. Las instancias se reutilizan durante la petición.

### `Core\Request`

Representa la petición entrante: método, ruta, cuerpo ya decodificado y cadena de consulta. Los controladores la reciben del enrutador en lugar de leer `php://input` por su cuenta.

Eso centraliza el análisis del cuerpo —un JSON malformado se rechaza en un solo sitio, con un mensaje que dice cuál es el problema— y hace verificable la escritura: una petición se puede construir con datos explícitos, cosa que con `php://input` era imposible desde las pruebas.

### `Core\Router`

Empareja la ruta, aplica sus guardas, resuelve el controlador y le pasa los argumentos:

- un parámetro de tipo `Request` recibe la petición;
- uno llamado `$authUser` recibe el usuario autenticado;
- un segmento `{id}` va al parámetro `$id`, convertido a entero si así está declarado.

```php
Route::put('/api/appointments/{id}', AppointmentController::class, 'update')->requireAuth()

public function update(int $id, Request $request, array $authUser): void
```

Si un controlador pide `$authUser` en una ruta que no exige autenticación, el enrutador falla de inmediato con un mensaje que lo explica. Es una red de seguridad deliberada: el diseño anterior permitía cometer ese error en silencio.

Distingue además **405** de **404** cuando la ruta existe pero con otro método.

---

## Seguridad

### Autenticación

Token JWT firmado con HS256, enviado en `Authorization: Bearer <token>`. El token lleva `id_usuario`, `nombre`, `email` e `id_rol`, y **nunca** el hash de la contraseña.

`AuthMiddleware` busca la cabecera en `HTTP_AUTHORIZATION`, en `REDIRECT_HTTP_AUTHORIZATION` y en `getallheaders()`, porque Apache con mod_php no la expone en `$_SERVER` salvo que se propague explícitamente.

### Autorización

Dos niveles complementarios:

1. **Por rol**, declarado en la tabla de rutas y aplicado por `RoleMiddleware`.
2. **Por pertenencia**, dentro del servicio. `AppointmentService` resuelve el `id_cliente` a partir del `id_usuario` del token y filtra con él; el `id_cliente` que venga en el cuerpo de la petición se descarta, de modo que nadie puede reservar a nombre de otro.

Cuando un cliente pide un recurso ajeno se responde **404, no 403**: un 403 confirmaría que ese identificador existe.

### Contraseñas

`password_hash` con `PASSWORD_DEFAULT`. El inicio de sesión devuelve el mismo mensaje genérico si el correo no existe o si la contraseña es incorrecta, para que no se puedan enumerar cuentas registradas.

---

## Agenda

La regla central del negocio —un profesional no puede atender dos citas a la vez— se aplica en dos niveles:

**En la aplicación**, `AppointmentService::assertBookable()` calcula el cruce real usando la duración de cada servicio: una cita de 60 minutos a las 10:00 ocupa hasta las 11:00 y bloquea cualquier solicitud que se solape con ese intervalo.

**En la base de datos**, el índice `uq_citas_agenda` es la red de seguridad ante dos peticiones simultáneas. Como las citas se cancelan de forma lógica, un `UNIQUE` corriente dejaría el horario bloqueado para siempre; se usa una columna generada que vale `NULL` cuando la cita está cancelada, y MySQL admite `NULL` repetidos en un índice único.

---

## Frontend

Misma separación por responsabilidad, un directorio por funcionalidad:

```
modules/<funcionalidad>/
├── <nombre>.page.js        compone el HTML de la página
├── <nombre>.controller.js  escucha eventos del DOM
├── <nombre>.api.js         llama a la API y mapea la respuesta
└── components/             fragmentos de HTML reutilizables
```

### Enrutado

Por hash, con guardas declaradas junto a cada ruta:

```js
"/booking": { page: BookingPage, auth: true },
"/clients": { page: ClientsPage, roles: [ROLES.ADMIN] },
"/login":   { page: LoginPage, guestOnly: true },
```

Son guardas **de interfaz, no de seguridad**: evitan mostrar una pantalla inútil, pero la API vuelve a comprobar autenticación y rol en cada petición.

### Sesión y estado

`api/session.js` es el único punto de acceso al almacenamiento de la sesión. Vive en la capa de API, y no en el módulo de autenticación, para que `http.js` pueda limpiarla ante un 401 sin importar `auth.service.js`, que a su vez importa `http.js`.

`http.js` intercepta los 401: borra la sesión, recuerda la ruta pretendida y redirige a login. Sin eso, la interfaz seguiría mostrando al usuario como conectado mientras todas las peticiones fallan.

Las escrituras del usuario en sesión pasan por `state/actions.js::setUser`, que emite `userChanged` por el bus de `core/events.js`. La barra de navegación se suscribe y se repinta sola, sin depender de que cambie el hash.

### Escapado

Todo dato que provenga del usuario se pasa por `utils/html.js::escapeHtml` antes de interpolarlo. El caso más expuesto son las notas de una cita: texto libre de 1000 caracteres que se muestra en la página de citas.

---

## Pruebas

```
tests/Unit/          sin base de datos, milisegundos
tests/Integration/   MySQL real, base desechable
```

Las unitarias sustituyen los repositorios por dobles y comprueban decisiones: permisos, reglas, validaciones.

Las de integración comprueban lo que un doble no puede: el SQL del cruce de horarios, la restricción única de la agenda y que las transacciones deshagan de verdad. Usan `DB_NAME_TEST`, una base que se crea y destruye en cada ejecución a partir de `database/schema.sql`, y se omiten solas si no hay MySQL.
