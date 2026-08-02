# Anguie Nails

Sistema web para la gestión integral de un salón de belleza especializado en manicure y pedicure.

El proyecto está siendo desarrollado con una arquitectura por capas utilizando PHP, JavaScript y MySQL, siguiendo principios de organización, mantenibilidad y escalabilidad.

---

## Objetivo

Desarrollar una plataforma que permita administrar el proceso completo de reservas del salón, incluyendo:

- Registro de clientes
- Inicio de sesión
- Catálogo de servicios
- Agenda de citas
- Administración de profesionales
- Panel administrativo
- Gestión de usuarios
- Reportes

---

## Arquitectura

El backend implementa una arquitectura por capas:

```
Cliente
    │
HTTP
    │
Router
    │
Controller
    │
Service
    │
Repository
    │
MySQL
```

Cada capa tiene una única responsabilidad.

---

## Tecnologías utilizadas

### Backend

- PHP 8.2
- Composer
- MySQL
- PDO

### Frontend

- HTML5
- CSS3
- JavaScript ES6

### Herramientas

- Visual Studio Code
- Postman
- Git
- GitHub
- XAMPP

---

## Estructura del proyecto

```
anguie-nails/

backend/
│
├── app/
│   ├── config/
│   ├── controllers/
│   ├── helpers/
│   ├── repositories/
│   ├── responses/
│   ├── routes/
│   ├── services/
│   └── validators/
│
├── database/
│
└── public/

frontend/
│
├── assets/
├── css/
├── js/
└── pages/

vendor/

composer.json
README.md
```

---

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL
- Git

---

## Instalación

### Clonar el repositorio

```bash
git clone <url-del-repositorio>
```

Entrar al proyecto

```bash
cd anguie-nails
```

Instalar dependencias

```bash
composer install
```

---

## Configuración

Crear el archivo:

```
backend/app/config/env.php
```

Con la configuración de la base de datos.

---

## Base de datos

1. Crear la base de datos.

2. Ejecutar el script ubicado en:

```
database/schema/
```

3. Ejecutar los datos iniciales:

```
database/seeds/roles.sql
```

---

## Ejecutar el backend

```bash
php -S localhost:8000 -t backend/public
```

Backend disponible en

```
http://localhost:8000
```

---

## Ejecutar el frontend

Puede utilizarse Live Server o cualquier servidor estático.

---

## Endpoints implementados

### Servicios

| Método | Endpoint | Estado       |
| ------ | -------- | ------------ |
| GET    | /        | Implementado |

### Autenticación

| Método | Endpoint           | Estado       |
| ------ | ------------------ | ------------ |
| POST   | /api/auth/register | Implementado |

---

## Estado del proyecto

Actualmente se encuentra en desarrollo.

### Funcionalidades implementadas

- Arquitectura por capas
- Router básico
- Autoload PSR-4
- Registro de usuarios
- Validaciones
- Transacciones
- Catálogo de servicios

### Próximas funcionalidades

- Login
- JWT / Sesiones
- Reserva de citas
- Panel administrativo
- Gestión de profesionales
- Historial de reservas

---

## Buenas prácticas aplicadas

- PSR-4
- Composer
- Separación de responsabilidades
- Validaciones en backend
- Respuestas JSON uniformes
- Transacciones en base de datos
- Repositorios
- Servicios
- Controladores

---

# Autor

Diego Pérez

Tecnólogo en Análisis y Desarrollo de Software (SENA)

Desarrollador Full Stack Junior.
