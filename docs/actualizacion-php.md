# Actualizar PHP

## Por qué

El 21 de octubre de 2026 PHP publicó parches de emergencia para **PHP2Shell**: dos fallos de ejecución remota de código **sin autenticación**, puntuados **9.2 sobre 10**.

| CVE | Qué es |
| --- | --- |
| [CVE-2026-109618](https://github.com/advisories/GHSA-w8hw-w9fx-j79w) | Corrupción de memoria al agotarse la memoria durante el arranque de una petición |
| [CVE-2026-109619](https://github.com/advisories/GHSA-6wmf-4rv5-p3rp) | Estado global que sobrevive a la petición y contamina la siguiente |

Afectan a PHP **5.4 hasta 8.5**. Se registraron intentos de explotación el 17 de octubre, cuatro días antes de la divulgación pública. **Ningún cambio de configuración los mitiga por completo**: la única solución es actualizar.

---

## Versiones

### Corregidas

| Rama | Primera versión corregida |
| --- | --- |
| 8.5 | **8.5.12** |
| 8.4 | **8.4.27** |
| 8.3 | **8.3.36** |
| 8.2 | **8.2.35** |
| 8.1 y anteriores | Sin publicación oficial; dependen del empaquetador |

### Soporte

| Rama | Soporte de seguridad hasta |
| --- | --- |
| 8.2 | **31 dic 2026** |
| 8.3 | 31 dic 2027 |
| 8.4 | 31 dic 2028 *(soporte activo)* |
| 8.5 | 31 dic 2029 *(soporte activo)* |

---

## Qué versión elegir

**8.4.** Quedarse en la rama 8.2 obliga a repetir esta actualización en pocas semanas, cuando termine su soporte, por el mismo trabajo de instalación.

El proyecto es compatible. Se comprobó antes de recomendarlo:

- No usa ninguna función retirada (`each`, `create_function`, `ereg`, `utf8_encode`…).
- No tiene parámetros con tipo implícitamente nulo, que 8.4 marca como obsoletos. Los `mixed $x = null` de `Core\Request` no cuentan: `mixed` ya incluye `null`.
- No usa interpolación `"${var}"`, obsoleta desde 8.2.
- Lo más moderno que emplea son propiedades `readonly` (8.1) y promoción en constructor (8.0).
- `firebase/php-jwt` admite `^8.0`; PHPUnit 11.5 admite `>=8.2`.

`composer.json` declara `"php": "^8.2"`, que es la compatibilidad de lenguaje. **No incluye el suelo de seguridad a propósito**: una restricción como `>=8.2.35` mezcla compatibilidad con parcheo, envejece con cada CVE y rompería la instalación de quien tenga una versión anterior aún compatible. La versión parcheada se comprueba aquí y en el arranque, no en el resolutor de dependencias.

---

## Procedimiento

### 1. Antes de empezar

```bash
php -v                 # anotar la versión de partida
composer test          # debe dar 338 en verde
git status             # árbol limpio, sin cambios sin guardar
```

Si la suite no está verde antes de actualizar, no se sabrá si un fallo posterior viene de PHP o de otra cosa.

### 2. No exponer Apache mientras tanto

El Apache de XAMPP carga `php8apache2_4.dll` (mod_php), el escenario de mayor riesgo, y sirve el proyecto en `http://localhost/anguie-nails`.

Mientras la versión siga sin parchear, desarrollar con el servidor integrado, que escucha solo en loopback:

```bash
php -S localhost:8001 -t backend/public
php -S localhost:8000 -t frontend
```

### 3. Instalar PHP

Dos caminos. El primero es más simple; el segundo, más quirúrgico.

**a) XAMPP completo** — descargar la versión que traiga PHP 8.4 parcheado. Arrastra también Apache y MySQL, así que conviene respaldar `htdocs` y las bases antes.

**b) Reemplazar solo PHP** — descargar el ZIP de [windows.php.net](https://windows.php.net/download/), eligiendo:

- **Thread Safe (TS)** si se va a usar el Apache de XAMPP con mod_php.
- **Non Thread Safe (NTS)** si solo se usa `php -S` o PHP-FPM.

> La instalación actual es **TS** (`ZTS`), porque XAMPP usa mod_php.

```
1. Parar Apache y MySQL desde el panel de XAMPP.
2. Renombrar C:\xampp\php  →  C:\xampp\php-8.2.12-backup
3. Descomprimir el ZIP nuevo en C:\xampp\php
4. Copiar php.ini del respaldo al directorio nuevo.
5. Revisar en php.ini que las rutas extension_dir apunten al sitio correcto.
```

Conservar el respaldo hasta haber validado: volver atrás es renombrar dos carpetas.

### 4. Validar

```bash
php -v                           # confirmar 8.4.27 o superior
php -m                           # pdo_mysql, openssl, mbstring, curl presentes
composer check-platform-reqs     # las dependencias aceptan la versión
composer install                 # regenerar autoload
composer test                    # 338 en verde
```

Después, el recorrido manual: levantar los dos servidores, iniciar sesión como administrador y como clienta, reservar una cita, cobrarla y mirar el informe de facturación.

### 5. Dejar constancia

Actualizar en este repositorio:

- `README.md` → requisitos.
- `composer.json` → la restricción, si la versión mínima cambia.
- `CHANGELOG.md` → entrada del cambio de versión.

---

## Si algo falla

| Síntoma | Causa probable |
| --- | --- |
| Apache no arranca | Se instaló NTS donde hacía falta TS, o la ruta de `php8apache2_4.dll` cambió de nombre |
| `pdo_mysql` no carga | `extension_dir` del `php.ini` apunta al directorio antiguo |
| Tests que fallan de golpe | Mirar primero el mensaje: una obsolescencia nueva avisa, no rompe |
| Nada funciona | Renombrar las carpetas al revés y volver a 8.2.12 |

La suite de 338 pruebas es la red: en menos de un minuto dice si el salto rompió algo, en vez de descubrirlo en el navegador semanas después.

---

## Fuentes

- [CISA — Critical Vulnerabilities in PHP Require Immediate Patching](https://www.cisa.gov/news-events/alerts/2026/10/21/critical-vulnerabilities-php-require-immediate-patching)
- [watchTowr — PHP2Shell FAQ](https://watchtowr.com/intelligence/php-cve-2026-109618-faq/)
- [Help Net Security — PHP2Shell](https://www.helpnetsecurity.com/2026/10/21/php2shell-php-vulnerabilities-patched-cve-2026-109618-cve-2026-109619/)
- [PHP — Versiones soportadas](https://www.php.net/supported-versions.php)

---

## Realizado: 8.2.12 → 8.4.27 (24 oct 2026)

La actualización se hizo sin tocar una línea de código de la aplicación: los 338 tests pasaron a la primera y no apareció ningún aviso de obsolescencia.

Dos cosas que el procedimiento de arriba no anticipaba y conviene saber para la próxima:

**El `php.ini` de XAMPP referencia archivos que el ZIP oficial no trae.** Copiarlo tal cual dejó PHP **sin arrancar**, con un `Fatal error: Unable to start standard module`. El culpable era:

```ini
browscap = "C:\xampp\php\extras\browscap.ini"
```

XAMPP incluía ese archivo en `php\extras\`; el ZIP de php.net, no. Al faltar, PHP aborta por completo en vez de avisar. Se comentó la directiva: el proyecto no usa `get_browser()`, que es lo único para lo que sirve.

También hubo que crear `C:\xampp\php\logs\`, que `error_log` necesita y el ZIP tampoco trae.

**Cuidado al diagnosticar.** Lanzar el binario antiguo contra el `ext\` nuevo produce una cascada de «Unable to load dynamic library» que parece un problema grave y no lo es: son DLL de otra versión. Comprobar siempre con el binario que se quiere usar.
