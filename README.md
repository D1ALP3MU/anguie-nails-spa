# Anguie Nails

Sistema web para la gestión integral de un salón de belleza especializado en manicure y pedicure.

Backend en PHP sin framework, con arquitectura por capas. Frontend en JavaScript vanilla, sin dependencias ni paso de compilación.

---

## Objetivo

Administrar el proceso completo de reservas del salón:

- Registro e inicio de sesión de clientes
- Catálogo de servicios
- Agenda de citas con control de disponibilidad
- Gestión de profesionales
- Panel administrativo de clientes

---

## Tecnologías

### Backend

- PHP 8.2
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

- PHP 8.2 o superior
- MySQL 8 o superior
- Composer
- Git

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

`schema.sql` crea la base desde cero. Sobre una base anterior a la restricción de agenda, aplicar en su lugar:

```bash
mysql -u root -p spa_db < database/migrations/001_citas_agenda_unica.sql
```

### 4. Levantar el backend

```bash
php -S localhost:8001 -t backend/public
```

La API queda en `http://localhost:8001/api`.

> El puerto debe coincidir con `API_URL` en `frontend/js/config/env.js`.

### 5. Levantar el frontend

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
| `POST` | `/api/auth/register` | Público |
| `POST` | `/api/auth/login` | Público |
| `GET` | `/api/profile` | Autenticado |
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
│   ├── schema.sql          Esquema completo
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
- [`CHANGELOG.md`](CHANGELOG.md) — historial de cambios

---

## Estado del proyecto

En desarrollo.

### Implementado

- Autenticación con JWT y autorización por rol
- Registro de clientes en una sola transacción
- Catálogo de servicios con CRUD administrativo
- Agenda de citas con detección de cruce de horarios
- Panel administrativo de clientes
- Enrutado declarativo con guardas por ruta
- Suite de 210 pruebas automatizadas

### Pendiente

- Gestión de profesionales desde el panel
- Historial y reportes
- Pagos
- Notificaciones por correo

---

## Autor

Diego Pérez

Tecnólogo en Análisis y Desarrollo de Software (SENA)

Desarrollador Full Stack Junior.
