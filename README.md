# Anguie Nails

Aplicación web para la gestión de un spa de manicure y pedicure, desarrollada como proyecto Full Stack utilizando JavaScript Vanilla en el frontend y PHP con arquitectura en capas en el backend.

El proyecto está siendo construido siguiendo principios de Clean Architecture, separación de responsabilidades y buenas prácticas de desarrollo para facilitar su mantenimiento y escalabilidad.

---

# Características

Actualmente el proyecto incluye:

- Landing Page
- Catálogo dinámico de servicios
- Sistema de reservas
- Modal reutilizable
- Validaciones de formularios
- SPA (Single Page Application)
- Router propio
- Arquitectura modular
- Backend PHP REST API
- Conexión con MySQL
- Arquitectura Repository-Service-Controller
- Variables de entorno (.env)

Próximamente:

- Login
- Registro de usuarios
- Gestión de clientes
- Catálogo de productos
- Carrito de compras
- Checkout
- Panel administrativo
- Gestión de profesionales
- Gestión de citas
- Pagos

---

# Tecnologías

## Frontend

- HTML5
- CSS3
- JavaScript ES6+
- SPA Vanilla JS

## Backend

- PHP 8
- Apache
- MySQL
- PDO

## Herramientas

- Git
- GitHub
- XAMPP
- MySQL Workbench
- VS Code

---

# Arquitectura del proyecto

```
anguie-nails/

backend/
app/
config/
controllers/
repositories/
services/
routes/
public/

frontend/
assets/
css/
js/
modules/
pages/

database/

docs/
```

---

# Arquitectura Backend

El backend sigue una arquitectura por capas.

```
Request

↓

Route

↓

Controller

↓

Service

↓

Repository

↓

Database
```

Cada capa tiene una responsabilidad única.

---

# Arquitectura Frontend

El frontend está construido como una SPA.

```
Router

↓

Page

↓

Components

↓

Controller

↓

Services

↓

API
```

---

# Base de datos

El proyecto utiliza MySQL.

Actualmente contiene módulos para:

- Usuarios
- Roles
- Clientes
- Profesionales
- Servicios
- Citas
- Productos
- Pagos
- Carrito (próximamente)

---

# Instalación

## 1. Clonar repositorio

```bash
git clone https://github.com/TU-USUARIO/anguie-nails.git
```

---

## 2. Abrir el proyecto

Puede abrirse con Visual Studio Code.

---

## 3. Instalar XAMPP

Iniciar:

- Apache
- MySQL

---

## 4. Crear la base de datos

Ejecutar el script ubicado en:

```
database/
```

desde MySQL Workbench.

---

## 5. Configurar variables de entorno

Crear un archivo:

```
.env
```

Ejemplo:

```env
DB_HOST=localhost
DB_NAME=spa_db
DB_USER=root
DB_PASSWORD=tu_password
```

---

## 6. Ejecutar frontend

Abrir:

```
frontend/index.html
```

con Live Server.

---

## 7. Ejecutar backend

Mover el proyecto a:

```
xampp/htdocs/
```

y acceder mediante:

```
http://localhost/anguie-nails/backend/api/services
```

---

# API

Actualmente disponibles:

```
GET /api/services
```

Próximamente:

```
POST /api/bookings

GET /api/products

POST /api/cart

POST /api/login

POST /api/register
```

---

# Buenas prácticas implementadas

- Arquitectura modular
- Componentes reutilizables
- Variables de entorno
- Repository Pattern
- Service Layer
- Controladores independientes
- Validaciones Frontend
- Validaciones Backend
- Código desacoplado
- SPA Vanilla JS

---

# Estado del proyecto

En desarrollo activo.

Actualmente se está implementando:

- Integración completa Frontend ↔ Backend
- Persistencia en MySQL
- Catálogo de productos
- Carrito de compras
- Checkout
- Autenticación

---

# Autor

Diego Pérez

Tecnólogo en Análisis y Desarrollo de Software (SENA)

Desarrollador Full Stack Junior.
