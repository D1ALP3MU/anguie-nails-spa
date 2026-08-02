<?php

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\ServiceController;
use App\Repositories\ServiceRepository;
use App\Services\ServiceService;
use App\Responses\Response;

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url(
    $_SERVER['REQUEST_URI'], 
    PHP_URL_PATH
);

$database = new Database();

$pdo = $database->connect();

switch ("$method $path") {
    /*
    |--------------------------------------------------------------------------
    | Servicios
    |--------------------------------------------------------------------------
    */
    case 'GET /':
        
        $repository = new ServiceRepository($pdo);

        $service = new ServiceService($repository);

        $controller = new ServiceController($service);

        $controller->index();

        break;

    /*
    |--------------------------------------------------------------------------
    | Autenticación
    |--------------------------------------------------------------------------
    */
    case 'POST /api/auth/register':

        $controller = new AuthController($pdo);

        $controller->register();

        break;

    case 'POST /api/auth/login':
        
        $controller = new AuthController($pdo);

        $controller->login();

        break;

    default:
        Response::error(
            'Ruta no encontrada',
            404
        );

}