# Anguie Nails

Sistema web para la gestión integral de un salón de belleza especializado en manicure y pedicure.

Backend en PHP sin framework, con arquitectura por capas. Frontend en JavaScript vanilla, sin dependencias ni paso de compilación.

---

## Qué hace

### Para la clienta

Se registra, consulta el catálogo y el equipo, reserva con la profesional que prefiere y gestiona sus propias citas.

### Para el salón

Panel con seis pantallas: **agenda** del día con filtros y cambio de estado, **catálogo** de servicios, **equipo**, **clientas**, **facturación** y **administradores**.

### Reglas que el sistema hace cumplir

- **Una profesional no atiende dos citas a la vez.** El cruce se calcula con la duración de cada servicio, no solo con la hora de inicio, y una restricción en la base lo respalda ante reservas simultáneas.
- **Cada quien ve solo lo suyo.** El identificador de clienta se resuelve desde el token; enviarlo en la petición no sirve de nada.
- **No se pierde historial.** Todas las bajas son lógicas: servicios, clientas, profesionales, citas y cobros dejan de contar, pero sus datos permanecen.
- **Una cita tiene como mucho un cobro vigente.** Anularlo la deja cobrable otra vez y el cobro anulado queda como constancia.
- **No se deja a nadie esperando.** No se da de baja a una profesional con citas pendientes, ni se cancela una cita ya cobrada.

> El sistema **registra** pagos, no los procesa: el dinero se mueve fuera —efectivo, transferencia, datáfono— y aquí queda la constancia. No hay integración con ninguna pasarela.

---

## Tecnologías

### Backend

- PHP 8.2 o superior *(se desarrolla sobre 8.4.27)*
- MySQL 8 o superior (el esquema usa una columna generada)
- PDO con sentencias preparadas
- Composer y autoload PSR-4
- `firebase/php-jwt` para los tokens
- PHPUnit 11 para las pruebas

### Frontend

- HTML5, CSS3
- JavaScript ES6 con módulos nativos
- Enrutado por hash, sin framework ni bundler

---

## Requisitos

- PHP 8.2 o superior *(probado en 8.4.27)*
- MySQL 8 o superior
- Composer
- Git

> **Usa una versión parcheada de PHP.** Las vulnerabilidades PHP2Shell (CVE-2026-109618 y CVE-2026-109619, CVSS 9.2) permiten ejecución remota de código sin autenticación en PHP 5.4–8.5. Las primeras versiones corregidas son **8.2.35**, **8.3.36**, **8.4.27** y **8.5.12**. Procedimiento de actualización en [`docs/actualizacion-php.md`](docs/actualizacion-php.md).

---

## Instalación

### 1. Clonar e instalar dependencias

```bash
git clone <url-del-repositorio>
cd anguie-nails
composer install
```

### 2. Configurar el entorno

```bash
cp .env.example .env
```

Editar `.env` con las credenciales de la base de datos y una clave JWT propia.

> `APP_TIMEZONE` debe coincidir con la zona horaria de MySQL. De ese reloj dependen las reglas de la agenda, y la aplicación lo fija por su cuenta en lugar de confiar en el `php.ini` del servidor.

> `JWT_SECRET` debe tener **32 bytes como mínimo**: HS256 rechaza claves más cortas y el inicio de sesión dejaría de funcionar. Se puede generar una con:
>
> ```bash
> php -r "echo bin2hex(random_bytes(32));"
> ```

### 3. Crear la base de datos

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p spa_db < database/seeds/roles.sql
```

`schema.sql` crea la base ya actualizada. Sobre una base existente, aplicar en orden las migraciones que falten:

```bash
mysql -u root -p spa_db < database/migrations/001_citas_agenda_unica.sql
mysql -u root -p spa_db < database/migrations/002_elimina_tienda.sql
mysql -u root -p spa_db < database/migrations/003_pagos.sql
```

### 4. Crear el primer administrador

Solo **el primero**: a partir de ahí se dan de alta desde el panel, en «Administradores». Este paso se hace una vez por instalación.

Se inserta a mano porque el registro público siempre crea cuentas de clienta, y un endpoint público capaz de crear administradores sería un agujero:

```bash
php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT);"
```

```sql
INSERT INTO usuarios (nombre, email, password_hash, id_rol)
VALUES ('Admin', 'admin@ejemplo.com', '<el hash anterior>', 1);
```

### 5. Levantar el backend

```bash
php -S localhost:8001 -t backend/public
```

La API queda en `http://localhost:8001/api`.

> El puerto debe coincidir con `API_URL` en `frontend/js/config/env.js`.

### 6. Levantar el frontend

Servir la carpeta `frontend/` con cualquier servidor estático, por ejemplo la extensión Live Server de VS Code. No sirve abrir `index.html` con doble clic: los módulos ES6 requieren protocolo HTTP.

---

## Pruebas

```bash
composer test              # toda la suite
composer test:unit         # solo unitarias, sin base de datos
composer test:integration  # solo integración, requiere MySQL
```

Las pruebas de integración crean y destruyen la base `DB_NAME_TEST` en cada ejecución y **nunca tocan la base de desarrollo**. Si MySQL no está disponible se omiten en lugar de fallar, de modo que la suite unitaria corre en cualquier máquina sin configuración previa.

---

## Endpoints

Referencia completa con formatos de petición y respuesta en [`docs/api.md`](docs/api.md).

| Método | Endpoint | Acceso |
| --- | --- | --- |
| `GET` | `/api/services` | Público |
| `GET` | `/api/services/{id}` | Público |
| `POST` | `/api/services` | Administrador |
| `PUT` | `/api/services/{id}` | Administrador |
| `DELETE` | `/api/services/{id}` | Administrador |
| `GET` | `/api/professionals` | Público |
| `GET` | `/api/professionals/{id}` | Público |
| `POST` | `/api/professionals` | Administrador |
| `PUT` | `/api/professionals/{id}` | Administrador |
| `DELETE` | `/api/professionals/{id}` | Administrador |
| `POST` | `/api/auth/register` | Público |
| `POST` | `/api/auth/login` | Público |
| `GET` | `/api/profile` | Autenticado |
| `PUT` | `/api/profile/password` | Autenticado |
| `GET` | `/api/admins` | Administrador |
| `POST` | `/api/admins` | Administrador |
| `DELETE` | `/api/admins/{id}` | Administrador |
| `GET` | `/api/clients` | Administrador |
| `POST` | `/api/clients` | Administrador |
| `GET` | `/api/clients/{id}` | Administrador o el propio cliente |
| `PUT` | `/api/clients/{id}` | Administrador o el propio cliente |
| `DELETE` | `/api/clients/{id}` | Administrador |
| `GET` | `/api/appointments` | Autenticado, filtrado por dueño |
| `POST` | `/api/appointments` | Autenticado |
| `GET` | `/api/appointments/{id}` | Dueño o administrador |
| `PUT` | `/api/appointments/{id}` | Dueño o administrador |
| `DELETE` | `/api/appointments/{id}` | Dueño o administrador |
| `GET` | `/api/appointments/{id}/payment` | Dueño o administrador |
| `POST` | `/api/appointments/{id}/payment` | Administrador |
| `GET` | `/api/payments` | Administrador |
| `GET` | `/api/payments/summary` | Administrador |
| `PUT` | `/api/payments/{id}` | Administrador |
| `DELETE` | `/api/payments/{id}` | Administrador |

---

## Estructura del proyecto

```
anguie-nails/
│
├── backend/
│   ├── app/
│   │   ├── Constants/      Roles del sistema
│   │   ├── Core/           Contenedor, enrutador y definición de rutas
│   │   ├── Exceptions/     Excepciones de dominio y su traducción a HTTP
│   │   ├── Middleware/     Autenticación y autorización
│   │   ├── config/         Carga del entorno y conexión PDO
│   │   ├── controllers/    Entrada y salida HTTP
│   │   ├── helpers/        JWT y contraseñas
│   │   ├── repositories/   Acceso a datos: todo el SQL vive aquí
│   │   ├── responses/      Formato uniforme de respuesta
│   │   ├── routes/         Tabla de rutas
│   │   ├── services/       Lógica de negocio
│   │   └── validators/     Validación de formato
│   └── public/             Punto de entrada
│
├── database/
│   ├── schema.sql          Esquema completo y actualizado
│   ├── migrations/         Cambios sobre bases existentes
│   └── seeds/              Datos iniciales
│
├── frontend/
│   ├── assets/
│   ├── css/                base · layout · components · pages
│   └── js/
│       ├── api/            Cliente HTTP y sesión
│       ├── components/     Componentes de interfaz reutilizables
│       ├── constants/      Espejo de constantes del backend
│       ├── core/           Bus de eventos y limpieza de listeners
│       ├── modules/        Un módulo por funcionalidad
│       │   ├── admin/       agenda · clients · professionals
│       │   │                services · reports
│       │   ├── auth/        registro e inicio de sesión
│       │   ├── booking/     citas de la clienta
│       │   ├── home/
│       │   ├── professionals/
│       │   └── services/     catálogo público
│       ├── router/         Enrutado por hash con guardas
│       ├── state/          Estado compartido
│       └── utils/          Utilidades sin estado
│
├── tests/
│   ├── Unit/               Sin base de datos
│   └── Integration/        Con MySQL real
│
└── docs/                   Arquitectura, API y decisiones
```

---

## Documentación

- [`docs/architecture.md`](docs/architecture.md) — capas, flujo de una petición y piezas del núcleo
- [`docs/api.md`](docs/api.md) — referencia de endpoints
- [`docs/decisions.md`](docs/decisions.md) — registro de decisiones técnicas
- [`docs/actualizacion-php.md`](docs/actualizacion-php.md) — por qué y cómo actualizar la versión de PHP
- [`CHANGELOG.md`](CHANGELOG.md) — historial de cambios
- [`docs/releases/`](docs/releases) — notas de cada versión publicada

---

## Estado del proyecto

En desarrollo.

### Implementado

- Autenticación con JWT y autorización por rol
- Registro de clientes en una sola transacción
- Catálogo de servicios con CRUD administrativo
- Agenda de citas con detección de cruce de horarios
- Registro de cobros, con un cobro vigente por cita
- Informe de facturación con desglose por forma de pago, profesional y servicio
- Panel administrativo completo: agenda, catálogo, equipo, clientas y facturación
- Historial por clienta, con sus cifras y todas sus citas
- Cambio de contraseña desde «Mi cuenta»
- Alta y baja de administradores desde el panel
- Enrutado declarativo con guardas por ruta
- Suite de 380 pruebas automatizadas

### Pendiente

- Notificaciones por correo
- Forzar el cambio de la contraseña temporal en el primer inicio de sesión

### Limitaciones conocidas

Antes de exponer la API a internet hay que revisar:

| | |
| --- | --- |
| **CORS abierto** | La API acepta peticiones de cualquier origen |
| **Sesión sin renovación** | El token vence según `JWT_EXPIRE` y no hay refresco ni revocación |
| **Token en `sessionStorage`** | Accesible desde JavaScript. Se mitiga escapando toda salida, pero la protección real sería una cookie `HttpOnly`. Registrado en [`docs/decisions.md`](docs/decisions.md) para revisarlo |
| **Sin paginación** | Los listados devuelven todos los registros. Suficiente para el volumen del salón |

---

## Autor

Diego Pérez

Tecnólogo en Análisis y Desarrollo de Software (SENA)

Desarrollador Full Stack Junior.
