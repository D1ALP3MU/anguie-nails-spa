# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

El proyecto todavía no publica versiones etiquetadas. El detalle commit a commit está en `git log`; aquí se resume lo que cambia de comportamiento.

---

## [Sin publicar]

### Seguridad

- Los endpoints de clientes y citas exigen autenticación. Antes `GET /api/clients` devolvía nombre, correo, teléfono y dirección de todos los clientes sin credenciales, y el CRUD de citas completo estaba abierto.
- Las citas se filtran por dueño. Un cliente solo ve y modifica las suyas; el `id_cliente` se resuelve desde el token y se descarta el que venga en el cuerpo de la petición, de modo que nadie puede reservar a nombre de otro.
- El catálogo de servicios y el listado de profesionales pasan a ser públicos. Antes exigían token, mientras los datos sensibles no lo hacían.
- Un fallo de conexión a la base ya no devuelve al cliente el mensaje de PDO, que incluía host, usuario y en ocasiones la contraseña. Se registra en el log del servidor.
- `display_errors` depende de `APP_ENV`. Si la variable no está definida se asume producción, para que un despliegue mal configurado falle del lado seguro.
- Toda salida que provenga del usuario se escapa antes de interpolarla en HTML. El caso expuesto eran las notas de una cita: 1000 caracteres de texto libre que se mostraban sin filtrar.
- Un recurso ajeno se reporta como inexistente (404) en lugar de prohibido (403), para no revelar qué identificadores existen.

### Añadido

- Detección de cruce de horarios en la agenda, calculada con la duración de cada servicio. Una cita de 60 minutos a las 10:00 bloquea cualquier solicitud entre las 10:00 y las 11:00; una contigua se acepta y una cancelada libera el horario.
- Índice `uq_citas_agenda` como red de seguridad ante peticiones simultáneas, con una columna generada que excluye las citas canceladas. Migración en `database/migrations/001_citas_agenda_unica.sql`.
- Validación de que el servicio y el profesional existan y estén activos, y de que el horario no esté en el pasado.
- Guardas de ruta en el frontend: `auth`, `roles` y `guestOnly` declaradas junto a cada ruta, con retorno a la ruta pretendida tras iniciar sesión.
- Interceptor de 401 en el cliente HTTP: limpia la sesión y redirige a login. Antes, con el token vencido, la interfaz seguía mostrando al usuario como conectado mientras todo fallaba.
- Enrutador declarativo con contenedor de dependencias. La protección de las 21 rutas se lee de un vistazo en `backend/app/routes/api.php`.
- Respuesta `405` cuando la ruta existe pero no con ese método.
- Suite de 210 pruebas: 137 unitarias sin base de datos y 73 de integración contra MySQL real. `composer test`.
- Objeto `Request` inyectado por el enrutador. Los controladores dejan de leer `php://input`, el análisis del cuerpo se centraliza y un JSON malformado se rechaza con un mensaje claro en lugar de reportarse como campos obligatorios.
- `.env.example` y documentación de arquitectura, API y decisiones técnicas.

### Cambiado

- El registro de cuentas tiene un único camino. `POST /api/auth/register` crea el usuario y su perfil de cliente en una transacción; `POST /api/clients` queda restringido al administrador.
- La reserva de citas requiere sesión iniciada. El formulario ya no pide nombre, correo ni teléfono: los toma de la cuenta.
- Los middlewares lanzan excepciones de dominio en lugar de escribir la respuesta directamente. `ExceptionHandler` es el único punto donde un error se convierte en respuesta.
- El usuario en sesión se escribe a través de `setUser`, que emite un evento. La barra de navegación se repinta sola al iniciar o cerrar sesión, sin depender de que cambie el hash.
- La semilla de roles fija los identificadores de forma explícita, para que no se desincronicen de `App\Constants\Roles`.

### Corregido

- Un token malformado devolvía 500 en lugar de 401. La librería JWT lanza `DomainException` ante un JSON corrupto, y esa excepción no estaba capturada.
- Se dejaba de crear una cuenta desechable con contraseña aleatoria por cada reserva. Ese flujo además fallaba con 409 en cuanto el correo ya existía, así que un cliente registrado no podía reservar.
- El bloque `catch` del enrutador del frontend referenciaba una variable declarada dentro del `try`, así que cualquier fallo de carga dejaba el indicador de carga colgado en lugar de mostrar el error.
- La cabecera `Authorization` se busca también en `REDIRECT_HTTP_AUTHORIZATION` y en `getallheaders()`. Bajo Apache con mod_php se perdía en silencio y toda la autenticación devolvía 401 con un token válido.
- Una violación de clave foránea se reportaba como «horario ocupado». El `SQLSTATE 23000` cubre cualquier fallo de integridad; ahora se distingue por el código de MySQL y el nombre del índice.
- Los tramos literales de una ruta se escapan al construir su expresión regular: un punto en la ruta actuaba como comodín.
- `GET /api/services` no devolvía la descripción del servicio, así que el catálogo mostraba un texto por defecto en todas las tarjetas.
- El registro devolvía 200 en lugar de 201.
- `AuthService` declaraba dos propiedades tipadas que nunca se inicializaban y que habrían lanzado un error al primer acceso.

### Eliminado

- Diez módulos del frontend inalcanzables desde el punto de entrada, entre ellos un cliente HTTP con una interpolación rota, datos de prueba ya reemplazados por la API y una persistencia en `localStorage` superada por el backend.
