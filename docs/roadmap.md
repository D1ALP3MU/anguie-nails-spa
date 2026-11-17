# Hoja de ruta

Lo que falta, en orden de prioridad. Lo ya hecho está en [`CHANGELOG.md`](../CHANGELOG.md).

**Actualizada:** 2026-11-16

---

## 1. Antes de publicar en internet

Nada de esto hace falta para trabajar en local, pero **todo** hace falta antes de exponer la aplicación. Varias piezas dependen de la primera decisión.

- **Decidir la topología.** Un único dominio, con el frontend en la raíz y la API bajo `/api`, elimina de raíz el problema de CORS y simplifica todo lo demás. Dos dominios separados obligan a configurar CORS con cuidado.
- **HTTPS.** Es lo verdaderamente crítico: sin él, el token de sesión y las contraseñas viajan en claro.
- **`API_URL` configurable.** Hoy está fijada a `http://localhost:8001/api` en `frontend/js/config/env.js`.
- **CORS restringido** al origen propio, en lugar de aceptar cualquiera. Si se elige un único dominio, deja de hacer falta.
- **Un usuario de MySQL propio**, con permisos solo sobre `spa_db`, en lugar de `root`.
- **`APP_ENV=production`**, para que los errores no se muestren en pantalla.
- **`composer install --no-dev`** en el servidor: `vendor/` no va en Git.
- **Copias de seguridad** de la base de datos, programadas.

Ver «Limitaciones conocidas» en el [README](../README.md#limitaciones-conocidas).

---

## 2. Pendiente de una decisión o de un material

Bloqueado por algo que no es código.

| Qué | Qué falta |
|---|---|
| **Política de tratamiento de datos personales** | El texto de la política (Ley 1581 de 2012). La aplicación recoge nombre, correo, teléfono y dirección. Con el texto: una página propia, el enlace en el pie y una casilla de aceptación en el registro. |
| **Portada** | Fotos reales del salón y de trabajos hechos, y un texto que diga qué distingue al salón. Hoy es un título, una frase y un botón. |
| **Género en «Administradores»** | Elegir criterio. La interfaz dice «Nuevo administrador»; «Mi cuenta» y la API dicen «administradora». Con clientas y profesionales ya se unificó en femenino. |

---

## 3. Funcionalidad

### Notificaciones por correo

A la clienta, al reservar, al confirmarse y al cancelarse su cita.

- **Proveedor:** Brevo, por SMTP, en su plan gratuito. Resend exige un dominio verificado incluso para empezar.
- **Dominio propio:** sin él, el correo sale desde un remitente genérico del proveedor y una parte acabará en spam. Es el mismo dominio que hace falta para el despliegue.
- **Diseño:** un `Mailer` como interfaz —registrada en el contenedor con `bind()`, porque una interfaz no se construye por reflexión— y un `NotificationService`. En las pruebas, un `Mailer` falso que guarda los envíos en memoria.
- **Regla:** que el correo falle no puede impedir la reserva. El envío va dentro de un `try`/`catch` que registra el fallo y sigue.

### Recordatorio el día antes

Lo que más reduce las inasistencias, pero no lo dispara ninguna petición: necesita una tarea programada (cron) que se ejecute cada día. Depende de que el hosting elegido la permita.

### Forzar el cambio de la contraseña temporal

Hoy una administradora creada desde el panel puede seguir usando para siempre la contraseña que le dieron. Hace falta una columna `debe_cambiar_password` y una guarda que, mientras esté activa, solo deje acceder a «Mi cuenta».

---

## 4. Más adelante

Ideas evaluadas y aplazadas a propósito. No son urgentes, pero conviene no olvidar por qué se aplazaron.

- **Agenda en cuadrícula por profesional** (la opción B de la propuesta de la agenda): las citas como bloques a su hora, por columnas. Es la mejor vista para ver huecos libres. Requiere devolver la duración del servicio en la agenda y una vista móvil aparte.
- **«La primera profesional libre»** al reservar, para la clienta a la que le da igual quién la atienda.
- **Horario por profesional, pausas y festivos.** Hoy el horario es el del salón para todas, sin pausa al mediodía, y no conoce los festivos de Colombia: un festivo entre semana aparece como día de apertura.
- **Paginación** de los listados. Hoy la API devuelve todos los registros y el buscador de clientas filtra en el navegador, lo que es suficiente para el volumen de un salón. Habrá que revisarlo si se llega a varios miles de clientas.
- **Sesión en cookie `HttpOnly`**, con renovación y revocación del token. Hoy el token vive en `sessionStorage` y caduca sin refresco (decisión 007).
