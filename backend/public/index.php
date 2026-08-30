<?php

use App\Exceptions\ExceptionHandler;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Para desarrollo, mostrar errores en pantalla. Para producción, desactivar.
ini_set('display_errors', 1);
// Para desarrollo, mostrar todos los errores. Para producción, ajustar según sea necesario.
//ini_set('display_errors', 0);

error_reporting(E_ALL);

require_once __DIR__ . '/../app/config/env.php';

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    
    require_once __DIR__ . '/../app/routes/api.php';
} catch (Throwable $e) {

    ExceptionHandler::handle($e);
}
