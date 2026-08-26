<?php

use App\Config\Database;
use App\Controllers\AuthController;
use App\Controllers\ServiceController;
use App\Repositories\ServiceRepository;
use App\Services\ServiceService;
use App\Responses\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\ClientController;
use App\Repositories\ClientRepository;
use App\Repositories\UserRepository;
use App\Services\ClientService;

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$database = new Database();

$pdo = $database->connect();

/*
|--------------------------------------------------------------------------
| Rutas dinámicas
|--------------------------------------------------------------------------
*/
if (
    preg_match('#^/api/services/(\d+)$#', $path, $matches)
) {

    $repository = new ServiceRepository($pdo);

    $service = new ServiceService($repository);

    $controller = new ServiceController($service);

    switch ($method) {

        case 'GET':
            $controller->show(
                (int) $matches[1]
            );
            return;

        case 'PUT':
            $controller->update(
                (int) $matches[1]
            );
            return;

        case 'DELETE':
            $controller->delete(
                (int) $matches[1]
            );
            return;
    }
}

if (
    preg_match('#^/api/clients/(\d+)$#', $path, $matches)
) {

    $userRepository = new UserRepository($pdo);

    $clientRepository = new ClientRepository($pdo);

    $service = new ClientService(
        $pdo,
        $userRepository,
        $clientRepository
    );

    $controller = new ClientController($service);

    switch ($method) {

        case 'GET':
            $controller->show(
                (int) $matches[1]
            );
            return;

        case 'PUT':
            $controller->update(
                (int) $matches[1]
            );
            return;

        case 'DELETE':
            $controller->delete(
                (int) $matches[1]
            );
            return;
    }
}

switch ("$method $path") {
    /*
    |--------------------------------------------------------------------------
    | Servicios
    |--------------------------------------------------------------------------
    */
    case 'GET /api/services':

        $repository = new ServiceRepository($pdo);

        $service = new ServiceService($repository);

        $controller = new ServiceController($service);

        $controller->index();

        break;

    case 'POST /api/services':

        $repository = new ServiceRepository($pdo);

        $service = new ServiceService($repository);

        $controller = new ServiceController($service);

        $controller->store();

        break;

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */
    case 'GET /api/clients':

        $userRepository = new UserRepository($pdo);

        $clientRepository = new ClientRepository($pdo);

        $service = new ClientService(
            $pdo,
            $userRepository,
            $clientRepository
        );

        $controller = new ClientController($service);

        $controller->index();

        break;

    case 'POST /api/clients':

        $userRepository = new UserRepository($pdo);

        $clientRepository = new ClientRepository($pdo);

        $service = new ClientService(
            $pdo,
            $userRepository,
            $clientRepository
        );

        $controller = new ClientController($service);

        $controller->store();

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

    case 'GET /api/profile':
        $user = AuthMiddleware::handle();

        RoleMiddleware::handle(
            $user,
            [1]
        );

        Response::json([
            'success' => true,
            'user' => $user
        ]);

        break;

    default:
        Response::error(
            'Ruta no encontrada',
            404
        );
}
