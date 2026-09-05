<?php

use App\Config\Database;
use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Exceptions\ExceptionHandler;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/config/env.php';

require_once __DIR__ . '/../../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Reporte de errores
|--------------------------------------------------------------------------
| Los errores solo se muestran en pantalla en entornos de desarrollo.
| Si APP_ENV no está definido se asume producción, de modo que un
| despliegue mal configurado falle del lado seguro.
*/
$isDevelopment = in_array(
    $_ENV['APP_ENV'] ?? 'production',
    ['local', 'development'],
    true
);

ini_set('display_errors', $isDevelopment ? '1' : '0');

ini_set('log_errors', '1');

error_reporting($isDevelopment ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

/*
|--------------------------------------------------------------------------
| Arranque
|--------------------------------------------------------------------------
| El contenedor construye controladores, servicios y repositorios a
| partir de los tipos de sus constructores. Lo único que hay que
| registrar a mano es la conexión.
*/
try {

    $container = new Container();

    $container->bind(
        PDO::class,
        (new Database())->connect()
    );

    $router = new Router(
        $container,
        require __DIR__ . '/../app/routes/api.php'
    );

    $router->dispatch(
        Request::fromGlobals()
    );

} catch (Throwable $e) {

    ExceptionHandler::handle($e);
}
