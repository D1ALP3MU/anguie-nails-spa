<?php

use App\Exceptions\ExceptionHandler;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);

error_reporting(E_ALL);

require_once __DIR__ . '/../app/config/env.php';

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    
    require_once __DIR__ . '/../app/routes/api.php';
} catch (Throwable $e) {

    ExceptionHandler::handle($e);
}
