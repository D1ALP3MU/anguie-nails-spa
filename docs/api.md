# API

Base: `http://localhost:8001/api`

Todas las peticiones y respuestas usan JSON con codificación UTF-8.

---

## Formato de respuesta

Toda respuesta trae una clave `success`.

**Éxito**

```json
{
  "success": true,
  "data": { }
}
```

**Error**

```json
{
  "success": false,
  "message": "Descripción del problema."
}
```

**Error de validación** — añade el mapa de campos, para que el formulario pueda marcarlos todos de una vez:

```json
{
  "success": false,
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "email": "El correo electrónico no es válido.",
    "telefono": "El teléfono es obligatorio."
  }
}
```

---

## Códigos de estado

| Código | Cuándo |
| --- | --- |
| `200` | Consulta o modificación correcta |
| `201` | Recurso creado |
| `401` | Falta el token, está malformado o expiró |
| `403` | Autenticado, pero el rol no autoriza la acción |
| `404` | El recurso no existe **o no es visible para quien pregunta** |
| `405` | La ruta existe, pero no con ese método |
| `409` | Conflicto: correo repetido, horario ocupado, cancelar dos veces |
| `422` | Datos inválidos |
| `500` | Error interno |

> **Sobre el 404**: cuando un cliente consulta una cita o un perfil que no le pertenece, la API responde `404`, no `403`. Un `403` confirmaría que ese identificador existe y permitiría recorrer la base probando números.

---

## Autenticación

Las rutas protegidas esperan la cabecera:

```
Authorization: Bearer <token>
```

El token es un JWT firmado con HS256 que vence según `JWT_EXPIRE`. Contiene `id_usuario`, `nombre`, `email` e `id_rol`.

Roles: `1` administrador · `2` cliente · `3` empleado.

---

## Registro e inicio de sesión

### `POST /api/auth/register` · público

Crea la cuenta y su perfil de cliente en una sola transacción. Registrarse y ser cliente son lo mismo en este dominio: no existe un camino que cree una cuenta sin perfil.

```json
{
  "nombre": "Ana Pérez",
  "email": "ana@example.com",
  "password": "Password123",
  "telefono": "3001112233",
  "direccion": "Calle 1 #2-3"
}
```

`direccion` es opcional. La contraseña necesita 8 caracteres como mínimo. El rol se fija en el servidor.

**201**

```json
{ "success": true, "data": { "id_cliente": 14 } }
```

**409** si el correo ya está registrado.

---

### `POST /api/auth/login` · público

```json
{ "email": "ana@example.com", "password": "Password123" }
```

**200**

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "id_usuario": 7,
      "nombre": "Ana Pérez",
      "email": "ana@example.com",
      "id_rol": 2
    }
  }
}
```

**401** con el mensaje `Credenciales inválidas.` tanto si el correo no existe como si la contraseña es incorrecta. La respuesta es idéntica a propósito: distinguirlas permitiría averiguar qué correos están registrados.

---

### `GET /api/profile` · autenticado

Devuelve el contenido del token.

```json
{
  "success": true,
  "data": {
    "iat": 1757000000,
    "exp": 1757003600,
    "id_usuario": 7,
    "nombre": "Ana Pérez",
    "email": "ana@example.com",
    "id_rol": 2
  }
}
```

---

## Servicios

### `GET /api/services` · público

Solo los servicios activos.

```json
{
  "success": true,
  "data": [
    {
      "id_servicio": 2,
      "nombre": "Pedicure Spa",
      "descripcion": "Relajación y belleza para tus pies.",
      "duracion": 60,
      "precio": "70000.00"
    }
  ]
}
```

`duracion` va en minutos y es lo que usa la agenda para calcular cruces de horario.

### `GET /api/services/{id}` · público

Igual que el anterior, con un solo objeto y el campo `activo`. **404** si no existe.

### `POST /api/services` · administrador

```json
{
  "nombre": "Manicura Premium",
  "descripcion": "Cuidado profesional para tus manos.",
  "duracion": 60,
  "precio": 50000
}
```

**201** con `{ "id_servicio": 12 }`.

### `PUT /api/services/{id}` · administrador

Mismo cuerpo que la creación. Devuelve el servicio actualizado.

### `DELETE /api/services/{id}` · administrador

Baja lógica: marca `activo = 0` y deja de aparecer en el catálogo. **409** si ya estaba desactivado.

---

## Profesionales

### `GET /api/professionals` · público

```json
{
  "success": true,
  "data": [
    {
      "id_profesional": 1,
      "nombre": "Laura Gómez",
      "especialidad": "Manicura",
      "telefono": "3005554433"
    }
  ]
}
```

### `GET /api/professionals/{id}` · público

Un solo objeto, con `activo`. **404** si no existe.

---

## Clientes

El registro público usa `POST /api/auth/register`. Estas rutas son la gestión administrativa.

### `GET /api/clients` · administrador

Solo clientes activos, con los datos de usuario ya unidos.

```json
{
  "success": true,
  "data": [
    {
      "id_cliente": 14,
      "id_usuario": 20,
      "nombre": "Ana Pérez",
      "email": "ana@example.com",
      "telefono": "3001112233",
      "direccion": "Calle 1 #2-3",
      "created_at": "2026-09-05 10:00:00",
      "updated_at": "2026-09-05 10:00:00"
    }
  ]
}
```

### `POST /api/clients` · administrador

Alta desde el panel. Mismo cuerpo y mismo controlador que el registro público; solo cambia quién puede llamarla.

### `GET /api/clients/{id}` · administrador o el propio cliente

**404** si el cliente autenticado pide un perfil ajeno.

### `PUT /api/clients/{id}` · administrador o el propio cliente

```json
{
  "nombre": "Ana María Pérez",
  "email": "ana.maria@example.com",
  "telefono": "3009998877",
  "direccion": "Calle 4 #5-6"
}
```

No incluye contraseña. Actualiza `usuarios` y `clientes` en una transacción y devuelve el cliente actualizado. **409** si el correo ya pertenece a otra cuenta.

### `DELETE /api/clients/{id}` · administrador

Baja lógica: desactiva el usuario y el cliente desaparece del listado, pero la fila permanece porque las citas la referencian. **409** si ya estaba dado de baja.

---

## Citas

### `GET /api/appointments` · autenticado

Un cliente recibe **solo sus citas**; un administrador, todas. El filtro se aplica en el servidor a partir del token.

```json
{
  "success": true,
  "data": [
    {
      "id_cita": 15,
      "id_cliente": 14,
      "cliente": "Ana Pérez",
      "id_servicio": 2,
      "servicio": "Pedicure Spa",
      "id_profesional": 1,
      "profesional": "Laura Gómez",
      "fecha": "2027-06-10",
      "hora": "10:00:00",
      "estado": "pendiente",
      "notas": null,
      "created_at": "2026-09-05 10:00:00",
      "updated_at": "2026-09-05 10:00:00"
    }
  ]
}
```

Ordenadas por fecha y hora ascendente.

### `POST /api/appointments` · autenticado

```json
{
  "id_servicio": 2,
  "id_profesional": 1,
  "fecha": "2027-06-10",
  "hora": "10:00",
  "notas": "Prefiero tonos claros"
}
```

`fecha` en formato `YYYY-MM-DD` y `hora` en `HH:MM`. `notas` admite 1000 caracteres.

**`id_cliente` no se envía.** Para un cliente autenticado se toma del token, y si viene en el cuerpo se descarta: nadie puede reservar a nombre de otro. Un administrador sí debe enviarlo, para agendar por cuenta de un cliente.

**201** con `{ "id_cita": 15 }`.

Errores posibles:

| Código | Motivo |
| --- | --- |
| `422` | Formato inválido, fecha pasada, o servicio/profesional inexistente o inactivo |
| `409` | El profesional ya tiene una cita que se cruza con ese horario |
| `403` | El usuario autenticado no tiene perfil de cliente asociado |

El cruce se calcula con la duración del servicio, no solo con la hora de inicio: una cita de 60 minutos a las 10:00 bloquea cualquier solicitud entre las 10:00 y las 11:00. Una cita contigua sí se acepta —las 11:00 en punto quedan libres— y una cita cancelada libera su horario.

### `GET /api/appointments/{id}` · dueño o administrador

**404** si la cita no existe o pertenece a otro cliente.

### `PUT /api/appointments/{id}` · dueño o administrador

```json
{
  "id_servicio": 2,
  "id_profesional": 1,
  "fecha": "2027-06-10",
  "hora": "11:30",
  "estado": "confirmada",
  "notas": null
}
```

Estados válidos: `pendiente`, `confirmada`, `cancelada`, `completada`.

Al reprogramar, la propia cita se excluye del cálculo de cruce. Un cliente no puede reasignar la cita a otro cliente. Las citas en estado `cancelada` o `completada` se libran de la comprobación de fecha futura, para poder cerrar el historial.

### `DELETE /api/appointments/{id}` · dueño o administrador

Cancelación lógica: pasa a `estado = 'cancelada'` y libera el horario. **409** si ya estaba cancelada.

---

## Ejemplos

```bash
# Registro
curl -X POST http://localhost:8001/api/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"nombre":"Ana Pérez","email":"ana@example.com","password":"Password123","telefono":"3001112233"}'

# Inicio de sesión
TOKEN=$(curl -s -X POST http://localhost:8001/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"ana@example.com","password":"Password123"}' \
  | php -r 'echo json_decode(file_get_contents("php://stdin"),true)["data"]["token"];')

# Reservar
curl -X POST http://localhost:8001/api/appointments \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"id_servicio":2,"id_profesional":1,"fecha":"2027-06-10","hora":"10:00"}'

# Mis citas
curl http://localhost:8001/api/appointments -H "Authorization: Bearer $TOKEN"
```
