<?php

/**
 * Arranque de la suite de pruebas.
 *
 * Carga el .env si existe, pero sin abortar si falta: las pruebas
 * unitarias no necesitan base de datos, y las de integración se
 * omiten solas cuando no hay conexión.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$envPath = __DIR__ . '/../.env';

if (is_file($envPath)) {

    foreach (parse_ini_file($envPath) ?: [] as $key => $value) {
        $_ENV[$key] ??= $value;
    }
}
