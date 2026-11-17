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

Eso arma por sí solo el controlador, su servicio, los cinco repositorios que este usa y el horario del salón. Las instancias se reutilizan durante la petición.

Un parámetro de tipo básico con valor por defecto recibe ese valor. Es lo que permite que `Config\SalonSchedule` se construya sin registrarla: su constructor admite la configuración y, si no se le pasa, la lee de `$_ENV`. Las pruebas que no tratan del horario sí la registran, con `SalonSchedule::alwaysOpen()` (ver [Pruebas](#pruebas)).

Una interfaz no se puede construir por reflexión: si algún día se inyecta una, habrá que registrar su implementación con `bind()`, como el PDO.

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

### Horario del salón

`Config\SalonSchedule` lee del `.env` qué días abre el salón, de qué hora a qué hora, cada cuánto se ofrecen horas y con cuánta antelación se puede reservar. Una configuración mal escrita lanza una excepción al construirse: es preferible un error claro a aceptar citas a deshoras sin que nadie lo note.

Tres piezas lo usan, y **las tres aplican la misma regla**:

- `AvailabilityService::freeSlots()` calcula las horas libres que ofrece la página de reserva: las del horario en que cabe el servicio entero, que no están en el pasado y que no se cruzan con otra cita de la profesional. Los intervalos ocupados salen de `AppointmentRepository::findBusyIntervals()`, con los mismos criterios que `hasOverlap()`.
- `AppointmentService::assertBookable()` rechaza la reserva que no cumple esa misma regla, por si alguien pide a mano a la API lo que el formulario no ofrece.
- `GET /api/schedule` entrega el horario al frontend, que con él marca los días cerrados y lo muestra en el pie de página.

Así **lo que se ofrece es lo que se acepta**. La única diferencia posible es que dos clientas elijan la misma hora a la vez, y esa la resuelve la reserva con un `409`.

Al reprogramar, el horario solo se exige si la cita cambia de fecha, de hora o de servicio. Una cita aceptada antes de que existiera el horario —o antes de cambiarlo— se puede seguir confirmando y completando sin moverla. Decisión 013.

---

## El reloj

Las reglas de la agenda dependen de la hora: si una cita está en el pasado, si una profesional tiene citas próximas, qué es «hoy» al filtrar.

**Hay un solo reloj, y es el de PHP.** La aplicación fija su zona horaria desde `APP_TIMEZONE` en `public/index.php`, en lugar de heredar la del `php.ini` del servidor, porque de ese valor depende la corrección y no puede cambiar según dónde se instale.

Ninguna consulta usa `CURDATE()` ni `NOW()`. Las fechas de corte llegan como parámetro desde el servicio. Antes, `countUpcomingAppointments` tomaba la suya de MySQL: eran dos relojes que podían discrepar hasta en un día si las zonas no coincidían.

`APP_TIMEZONE` debe coincidir con la zona de MySQL de todos modos, porque ambos leen las mismas fechas almacenadas sin huso.

Las pruebas fijan la misma zona en `tests/bootstrap.php`: sin eso pasarían o fallarían según la máquina que las ejecute.

El frontend calcula «hoy» con la hora local del navegador (`utils/date.js`) para lo que es de presentación: qué día abre la agenda, dónde va la línea de la hora actual, qué día marca la tira de la reserva. Nunca con `toISOString()`, que convierte a UTC y en Colombia da el día siguiente desde las siete de la tarde. **Lo que decide si una cita es válida es siempre el backend**: el navegador no valida fechas.

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
"/booking":        { page: BookingPage, auth: true },
"/admin/clients":  { page: ClientsPage, roles: [ROLES.ADMIN] },
"/login":          { page: LoginPage, guestOnly: true },
```

Son guardas **de interfaz, no de seguridad**: evitan mostrar una pantalla inútil, pero la API vuelve a comprobar autenticación y rol en cada petición.

Un tramo entre llaves es un parámetro, igual que en la tabla de rutas del backend, y llega a la página como propiedad del objeto que recibe:

```js
"/admin/clients/{id}/historial": { page: ClientHistoryPage, roles: [ROLES.ADMIN] },

export async function ClientHistoryPage({ id })
```

Las rutas literales se resuelven por clave y solo si ninguna coincide se recorren las que tienen parámetros, de modo que un patrón nunca secuestra una dirección fija. Los tramos se comparan uno a uno en vez de construirse una expresión regular: es lo que evita que un punto en la dirección actúe como comodín, que fue un fallo real en `Core\Route`.

Cada página registra sus listeners al montarse y los retira con `registerCleanup()` al navegar, porque el marcado se repinta entero en cada ruta.

### Comportamientos globales

Hay tres comportamientos que **no** siguen esa regla: el modal (`components/ui/Modal.js`), el menú «⋯» (`ActionMenu.js`) y el botón de mostrar contraseña (`PasswordInput.js`). Sus manejadores se registran **una sola vez**, en `main.js`, y nunca se retiran.

El motivo es el modal: se inserta en `<body>`, fuera de `#app`, así que sobrevive al cambio de ruta. Cuando su cierre dependía de los listeners de la página que lo abrió, pulsar «atrás» con el modal abierto lo dejaba encima de la página nueva y sin forma de cerrarlo: la limpieza de rutas ya había retirado esos listeners. El menú y el botón de contraseña siguen el mismo modelo porque aparecen en muchas páginas y no tienen nada propio de ninguna.

Los tres se apoyan en atributos `data-*` y en delegación sobre `document`: el controlador de cada página sigue atendiendo sus propias acciones —por ejemplo, la opción «Editar» de un menú es un botón con el mismo `data-edit-client` de siempre—, y el componente solo se ocupa de abrir, cerrar y mover el foco. Decisión 014.

### Datos del salón

Los datos de contacto que muestra el pie —dirección, WhatsApp, Instagram— están en `config/salon.js`, un único sitio. El horario no: se pide a `GET /api/schedule` al arrancar (`state/salonSchedule.js`), sin retrasar la primera página, y el pie se completa en cuanto llega. Si la API no responde, el pie se pinta sin ese bloque.

### Sesión y estado

`api/session.js` es el único punto de acceso al almacenamiento de la sesión. Vive en la capa de API, y no en el módulo de autenticación, para que `http.js` pueda limpiarla ante un 401 sin importar `auth.service.js`, que a su vez importa `http.js`.

`http.js` intercepta los 401 **de las peticiones que llevaban sesión**: borra la sesión, recuerda la ruta pretendida y redirige a login. Sin eso, la interfaz seguiría mostrando al usuario como conectado mientras todas las peticiones fallan. Un 401 sin sesión —al iniciarla con una contraseña equivocada— es un error normal y muestra el mensaje de la API; antes se anunciaba como «sesión caducada».

Los errores que lanza `http.js` llevan el código HTTP en `error.status`, para distinguir, por ejemplo, una hora que alguien acaba de ocupar (`409`) de un dato mal escrito (`422`).

Las escrituras del usuario en sesión pasan por `state/actions.js::setUser`, que emite `userChanged` por el bus de `core/events.js`. La barra de navegación se suscribe y se repinta sola, sin depender de que cambie el hash.

### Escapado

Todo dato que provenga del usuario se pasa por `utils/html.js::escapeHtml` antes de interpolarlo. El caso más expuesto son las notas de una cita: texto libre de 1000 caracteres que se muestra en la página de citas. Otro es el buscador de clientas, que repite lo escrito en el aviso de «ninguna coincide».

---

## Pruebas

```
tests/Unit/          sin base de datos, milisegundos
tests/Integration/   MySQL real, base desechable
```

Las unitarias sustituyen los repositorios por dobles y comprueban decisiones: permisos, reglas, validaciones.

Las de integración comprueban lo que un doble no puede: el SQL del cruce de horarios, la restricción única de la agenda y que las transacciones deshagan de verdad. Usan `DB_NAME_TEST`, una base que se crea y destruye en cada ejecución a partir de `database/schema.sql`, y se omiten solas si no hay MySQL.

**Ninguna prueba depende del día en que se ejecuta.** Las que no tratan del horario del salón reciben `SalonSchedule::alwaysOpen()`, que acepta cualquier día y hora: con el horario real, una prueba que reserva «dentro de 30 días» fallaría cuando esa fecha cayera en domingo. Las que sí tratan del horario usan fechas fijas de día conocido, o «un lunes de dentro de 8 a 14 días», que siempre es futuro, siempre es día de apertura y siempre cae dentro del plazo.

### Mayúsculas en las rutas

Windows no distingue mayúsculas en los nombres de archivo; Linux sí. El autoload PSR-4 busca `App\Controllers\X` en `backend/app/Controllers/X.php` con esa grafía exacta, así que una carpeta guardada en Git como `controllers` funciona en desarrollo y rompe la API en producción. Pasó: hasta noviembre de 2026, 33 de las 48 clases no habrían cargado en un servidor Linux.

Al crear una carpeta o un archivo de clase, su nombre debe coincidir exactamente con el namespace y la clase. Para renombrar solo las mayúsculas en Windows hay que hacerlo en dos pasos, porque Git no ve la diferencia: `git mv carpeta carpeta_tmp` y luego `git mv carpeta_tmp Carpeta`.
