# Anguie Nails 1.0.0

**5 de septiembre de 2026**

Primera versión completa del sistema de reservas. El flujo funciona de punta a punta: una clienta se registra, consulta el catálogo, reserva con la profesional que prefiere y gestiona sus citas; el salón administra servicios, equipo y clientas desde su panel.

---

## Qué incluye

### Para la clienta

- **Registro e inicio de sesión** con sesión por token.
- **Catálogo de servicios** con duración y precio, consultable sin cuenta.
- **Equipo del salón** con especialidad de cada profesional, también público.
- **Reserva de citas** eligiendo servicio, profesional, fecha y hora.
- **Mis citas**, donde consulta y cancela las suyas.

### Para el salón

- **Servicios**: alta, edición y baja del catálogo.
- **Equipo**: alta, edición y baja de profesionales.
- **Clientas**: listado, edición y baja.
- **Agenda completa** de todas las citas.

---

## Lo que garantiza el sistema

**Una profesional no puede tener dos citas a la vez.** El cruce se calcula con la duración de cada servicio, no solo con la hora de inicio: una cita de 60 minutos a las 10:00 bloquea cualquier solicitud entre las 10:00 y las 11:00. Una cita contigua se acepta y una cancelada libera el horario. La regla está protegida además con una restricción en la base de datos, para el caso de dos reservas simultáneas.

**Cada quien ve solo lo suyo.** Una clienta accede a sus citas y a su perfil, nunca a los de otra. El identificador de clienta se resuelve desde el token de sesión, así que enviarlo en la petición no sirve de nada.

**No se pierde historial.** Las bajas son lógicas: servicios, clientas, profesionales y citas dejan de aparecer, pero sus datos permanecen para que el historial siga siendo legible.

**No se deja a nadie esperando.** No se puede dar de baja a una profesional con citas pendientes: primero hay que reprogramarlas o cancelarlas.

---

## Requisitos

- PHP 8.2 o superior
- MySQL 8 o superior — el esquema usa una columna generada
- Composer

---

## Instalación

```bash
git clone https://github.com/D1ALP3MU/anguie-nails-spa.git
cd anguie-nails-spa
composer install
cp .env.example .env
```

Editar `.env` con las credenciales de la base de datos y **una clave JWT propia de 32 bytes como mínimo**:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Crear la base de datos y levantar la API:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p spa_db < database/seeds/roles.sql
php -S localhost:8001 -t backend/public
```

El frontend se sirve con cualquier servidor estático desde `frontend/`. Instrucciones completas en el [README](../../README.md).

Para crear el primer administrador hay que insertarlo a mano, porque el registro público siempre crea cuentas de clienta:

```sql
INSERT INTO usuarios (nombre, email, password_hash, id_rol)
VALUES ('Admin', 'admin@ejemplo.com', '<hash>', 1);
```

El hash se genera con `php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT);"`.

---

## API

23 endpoints: **6 públicos**, **8 para usuarios autenticados** y **9 restringidos al administrador**.

Cada ruta declara sus permisos junto a su definición en `backend/app/routes/api.php`, de modo que la política de acceso completa se lee de un vistazo. Referencia detallada en [`docs/api.md`](../api.md).

---

## Calidad

**253 pruebas automatizadas**: 158 unitarias que corren en menos de un segundo sin base de datos, y 95 de integración contra MySQL real.

```bash
composer test
```

Las de integración crean y destruyen su propia base en cada ejecución, y nunca tocan la de desarrollo. Si MySQL no está disponible se omiten en lugar de fallar.

---

## Limitaciones conocidas

Este release cubre el ciclo de reservas. Queda fuera:

- **CORS abierto.** La API acepta peticiones de cualquier origen. Antes de exponerla a internet hay que restringirlo a los dominios propios.
- **Sesión sin renovación.** El token vence según `JWT_EXPIRE` y no hay refresco: al expirar, la interfaz cierra la sesión y pide iniciarla de nuevo. Tampoco hay revocación.
- **El token vive en `sessionStorage`**, accesible desde JavaScript. Se mitiga escapando toda salida que provenga del usuario, pero la protección real sería una cookie `HttpOnly`. La decisión está registrada en [`docs/decisions.md`](../decisions.md) para revisarla antes de un despliegue público.
- **Sin paginación.** Los listados devuelven todos los registros. Suficiente para el volumen actual del salón.
- **Sin pagos ni notificaciones.** El esquema contempla las tablas, pero no hay código que las use.
- **Alta de administradores solo por SQL.**

---

## Documentación

- [README](../../README.md) — instalación y puesta en marcha
- [Arquitectura](../architecture.md) — capas, flujo de una petición y núcleo
- [API](../api.md) — referencia de endpoints
- [Decisiones](../decisions.md) — ocho registros con su motivo
- [Changelog](../../CHANGELOG.md) — historial de cambios
