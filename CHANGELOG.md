# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/).

El detalle commit a commit está en `git log`; aquí se resume lo que cambia de comportamiento.

---

## [Sin publicar]

### Eliminado

- El modal de reserva y la validación de fechas en el navegador. La reserva tiene página propia, y el backend es quien decide qué horas se pueden reservar.
- Las tablas `productos`, `carrito_items`, `pedidos` y `pedido_items`. Estaban en el esquema desde el inicio sin que ningún código las usara: correspondían a una tienda en línea que no se va a construir. Migración en `database/migrations/002_elimina_tienda.sql`.

### Añadido

- Horario del salón, configurable en el `.env` (`SALON_DIAS`, `SALON_APERTURA`, `SALON_CIERRE`, `SALON_INTERVALO`, `SALON_ANTELACION_MAX_DIAS`). Sin las variables rige lunes a sábado, de 9:00 a 19:00, cada 30 minutos y hasta 8 semanas vista. Antes se podía reservar a cualquier hora de cualquier día, incluidas las tres de la madrugada.
- `GET /api/schedule`, público, con el horario, y `GET /api/availability`, con las horas libres de una profesional un día para un servicio. Se calculan con la misma regla de solapes con la que se acepta la reserva, así que lo que se ofrece es lo que se acepta.
- Página de reserva propia (`#/reservar/{id}`) en lugar del modal: se elige profesional, día en una tira semanal y hora entre las libres, con un resumen fijo del servicio, la fecha y el precio. Antes la hora era un campo libre y el choque se descubría al enviar; el modal ni siquiera decía qué servicio se estaba reservando. Si otra persona ocupa la hora a la vez, avisa y vuelve a pedir las horas.
- «Mis citas» con la próxima cita destacada —fecha, cuánto falta, profesional y precio—, las demás próximas debajo y el historial plegado. Antes eran tarjetas de texto plano con la fecha en formato ISO.
- La agenda abre en el día de hoy, agrupa las citas por día, marca la hora actual y pliega lo ya cerrado. Cada cita muestra una sola acción, la siguiente que toca —confirmar, completar o cobrar— y el resto va en un menú «⋯». Antes abría con todo el histórico, empezando por agosto.
- Menú «⋯» reutilizable, manejable con teclado. Lo usan la agenda y todas las tablas del panel, que pasan a mostrarse como tarjetas en móvil.
- Buscador de clientas por nombre, correo, teléfono y dirección, sin importar tildes ni mayúsculas.
- Botón para mostrar la contraseña en los siete campos de contraseña, accesible por teclado y lector de pantalla.
- Pie de página con la marca, el horario —el mismo del `.env`—, la dirección con enlace a Google Maps, WhatsApp e Instagram. En el panel, un pie mínimo. En la portada y en «Nuestro equipo», una franja para reservar.
- Alta y baja de administradores desde el panel (`/api/admins`). Antes la única vía era insertar la fila por SQL. El alta crea solo el usuario, sin perfil en `clientes`, con una contraseña temporal que la persona cambia desde «Mi cuenta». Dos reglas lo sostienen: no se desactiva al último administrador ni se desactiva nadie a sí mismo. El primero sigue creándose por SQL al instalar.
- Página «Mi cuenta» con cambio de contraseña (`PUT /api/profile/password`). No existía forma de cambiarla: la única salida era editarla en la base de datos. Hay que escribir la actual, y el usuario se resuelve desde el token, así que nadie puede cambiar la ajena. Los tokens ya emitidos siguen valiendo hasta que expiren, porque la sesión es sin estado.
- Historial de la clienta: ficha con sus datos, sus cifras —citas, total pagado, lo que queda por cobrar y la última visita— y todas sus citas de la más reciente a la más antigua. Se llega desde el listado de clientes y tiene dirección propia, así que se puede abrir en otra pestaña.
- `GET /api/appointments` admite el filtro `id_cliente`. Solo surte efecto para un administrador: el filtro por pertenencia se aplica después y sobrescribe lo que venga en la consulta.
- El enrutador del frontend admite tramos entre llaves, como la tabla de rutas del backend. Los segmentos se comparan uno a uno en lugar de con una expresión regular, para que un punto en la dirección no actúe como comodín.
- `composer.json` declara la versión de PHP admitida, que no tenía ninguna restricción: nada impedía instalar el proyecto en una versión incompatible.
- `docs/actualizacion-php.md`, con el motivo y el procedimiento para actualizar PHP a raíz de PHP2Shell (CVE-2026-109618 y CVE-2026-109619).
- Informe de facturación: lo cobrado, lo pendiente de verificar, lo anulado y —sobre todo— lo que falta por cobrar, con desglose por forma de pago, profesional y servicio. Las sumas las hace la base, no el navegador.
- La agenda muestra el estado de cobro de cada cita y permite cobrar, confirmar y anular. El listado de citas trae su cobro vigente, así que no hace falta una petición por fila.
- Registro de cobros. El sistema no procesa pagos: el dinero se mueve fuera y aquí queda la constancia, con forma de pago y referencia. Una cita tiene como mucho un cobro vigente; anularlo la deja cobrable otra vez y el cobro anulado permanece como historial.
- `citas.precio`, congelado al reservar. El precio vivía solo en `servicios`, así que subir una tarifa revaluaba hacia atrás las citas pasadas.
- Enlace a «Nuestro equipo» en el menú. La ruta `#/professionals` ya existía y era pública, pero solo se llegaba a ella escribiendo la URL. La etiqueta repite el encabezado de su página para que no se confunda con «Equipo», que lleva al panel de gestión.

### Corregido

- En un servidor Linux la API no arrancaba. Git guardaba ocho carpetas de `backend/app` en minúscula y `Database.php` como `database.php`, mientras que los namespaces van en mayúscula. En Windows no se nota; en Linux el autoload no encontraba 33 de las 48 clases.
- No se podía reservar para el mismo día. El navegador calculaba «hoy» en UTC, y en Colombia una fecha sin hora es la tarde del día anterior: la validación rechazaba el día en curso a cualquier hora.
- Al pulsar «atrás» con el modal de reserva abierto, el modal se quedaba encima de la página siguiente y no se podía cerrar ni con la X, ni con Escape, ni pulsando fuera. Los modales tampoco respondían a Escape ni recibían el foco al abrirse.
- Al iniciar sesión con una contraseña equivocada aparecía «Tu sesión no es válida o expiró». El cliente HTTP trataba cualquier 401 como sesión caducada; ahora solo cuando la petición llevaba sesión.
- En el alta de clientas, el navegador podía ofrecer o rellenar la contraseña guardada de la propia administradora: al campo le faltaba `autocomplete="new-password"`.
- «Reservar ahora», en la portada, llevaba a «Mis citas» en lugar del catálogo.
- Los botones se pintaban en Arial, porque los controles de formulario no heredan la tipografía; un enlace con la misma clase salía en DM Sans.
- El menú lateral desaparecía mientras cargaba cada página.
- En los importes y los títulos, el 0 de Cormorant se leía como una «o»: la fuente usa por defecto cifras de estilo antiguo.
- En móvil, el formulario de fechas de la agenda y de facturación ocupaba media pantalla con huecos entre los campos.
- `.env.example` no se podía analizar: usaba `#` para los comentarios y el analizador INI de PHP solo reconoce `;`. Quien siguiera el README y copiara la plantilla se encontraba con que la aplicación no arrancaba.

- La aplicación fija su propia zona horaria en lugar de heredar la del `php.ini`. Con la configuración que trae XAMPP, en horario europeo, PHP creía que ya era el día siguiente desde media tarde y rechazaba reservas legítimas del mismo día con «No es posible agendar una cita en un horario que ya pasó».
- `countUpcomingAppointments` recibe la fecha de corte en lugar de usar `CURDATE()`. El sistema tenía dos relojes —el de PHP y el de MySQL— que podían discrepar hasta en un día.

### Cambiado

- La API rechaza las reservas fuera del horario del salón o del plazo de reserva. Al reprogramar, la regla solo se aplica si la cita cambia de fecha, de hora o de servicio: una cita aceptada antes de existir el horario se puede seguir confirmando y completando.
- Colores con contraste AA medido: el rosa de marca pasa de `#D4637A` a `#B04560`, y se oscurecen el texto atenuado, los chips de estado y el borde de los campos. Las acciones destructivas usan un rojo propio, distinto del rosa de marca, con el que antes se confundían.
- Una escala de ocho tamaños de letra sustituye a los 31 valores sueltos, y todos los títulos pasan a Cormorant, también los del panel y los modales.
- Los botones tienen estados de hover, pulsado y desactivado, y hay foco visible con teclado en toda la interfaz. En los diálogos que destruyen algo, el foco inicial va a «Cancelar».
- Encabezado común en el panel, con el título a la izquierda y la acción a la derecha, y tablas compactas con las cifras alineadas a la derecha.
- «Clienta» en singular en el panel y en los mensajes de la API, y el equipo en femenino en todas partes. Antes la misma aplicación decía «Clienta» en la agenda y «Nuevo Cliente» en el listado.
- La navegación pasa de una barra superior a un menú lateral. En escritorio queda fija a la izquierda; por debajo de 900px se esconde y se abre como cajón desde una barra superior con botón de hamburguesa. El espacio vertical permite agrupar las rutas del panel bajo un rótulo «Administración» y mostrar siempre el nombre de la usuaria, que la barra superior ocultaba por debajo de 640px.

---

## [1.0.0] — 2026-09-05

Primera versión completa del sistema de reservas. Notas de la versión en
[`docs/releases/release-1.0.0.md`](docs/releases/release-1.0.0.md).

### Seguridad

- Los endpoints de clientes y citas exigen autenticación. Antes `GET /api/clients` devolvía nombre, correo, teléfono y dirección de todos los clientes sin credenciales, y el CRUD de citas completo estaba abierto.
- Las citas se filtran por dueño. Un cliente solo ve y modifica las suyas; el `id_cliente` se resuelve desde el token y se descarta el que venga en el cuerpo de la petición, de modo que nadie puede reservar a nombre de otro.
- El catálogo de servicios y el listado de profesionales pasan a ser públicos. Antes exigían token, mientras los datos sensibles no lo hacían.
- Un fallo de conexión a la base ya no devuelve al cliente el mensaje de PDO, que incluía host, usuario y en ocasiones la contraseña. Se registra en el log del servidor.
- `display_errors` depende de `APP_ENV`. Si la variable no está definida se asume producción, para que un despliegue mal configurado falle del lado seguro.
- Toda salida que provenga del usuario se escapa antes de interpolarla en HTML. El caso expuesto eran las notas de una cita: 1000 caracteres de texto libre que se mostraban sin filtrar.
- Un recurso ajeno se reporta como inexistente (404) en lugar de prohibido (403), para no revelar qué identificadores existen.

### Añadido

- Vista de agenda para el salón, con filtros por rango de fechas, profesional y estado, y acciones para confirmar, completar o cancelar. Un administrador veía todas las citas, pero en la página titulada «Mis citas», pensada para una clienta.
- `GET /api/appointments` admite filtros por cadena de consulta. Filtrar en la base y no en el cliente mantiene acotada la agenda, que crece sin techo.
- Panel de administración del catálogo. La API tenía el CRUD de servicios completo, pero no había pantalla: cambiar un precio exigía una herramienta externa. Con esto los tres módulos administrables tienen su panel.
- CRUD completo de profesionales, con panel de administración. Era el único módulo a medias: solo lectura y sin forma de dar de alta a nadie salvo con SQL a mano. No se puede dar de baja a quien tiene citas pendientes, porque dejaría a clientas esperando.
- Se elimina el rol Empleado, que no llegó a usarse. La constante no coincidía con el identificador de la base y ningún código la referenciaba.
- Detección de cruce de horarios en la agenda, calculada con la duración de cada servicio. Una cita de 60 minutos a las 10:00 bloquea cualquier solicitud entre las 10:00 y las 11:00; una contigua se acepta y una cancelada libera el horario.
- Índice `uq_citas_agenda` como red de seguridad ante peticiones simultáneas, con una columna generada que excluye las citas canceladas. Migración en `database/migrations/001_citas_agenda_unica.sql`.
- Validación de que el servicio y el profesional existan y estén activos, y de que el horario no esté en el pasado.
- Guardas de ruta en el frontend: `auth`, `roles` y `guestOnly` declaradas junto a cada ruta, con retorno a la ruta pretendida tras iniciar sesión.
- Interceptor de 401 en el cliente HTTP: limpia la sesión y redirige a login. Antes, con el token vencido, la interfaz seguía mostrando al usuario como conectado mientras todo fallaba.
- Enrutador declarativo con contenedor de dependencias. La protección de las 21 rutas se lee de un vistazo en `backend/app/routes/api.php`.
- Respuesta `405` cuando la ruta existe pero no con ese método.
- Suite de 268 pruebas: 164 unitarias sin base de datos y 104 de integración contra MySQL real. `composer test`.
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
