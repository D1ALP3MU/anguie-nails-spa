<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Responses\Response;

use PDO;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Autenticación
 * Archivo  : PasswordHelper.php
 * Versión  : 1.0.0
 * 
 * Descripción:
 * Controlador encargado de gestionar las peticiones HTTP
 * relacionadas con la autenticación.
 * 
 * Responsabilidades:
 * - Recibir peticiones HTTP.
 * - Invocar los servicios correspondientes.
 * - Retornar respuestas JSON.
 * 
 * Esta clase NO contiene lógica de negocio.
 * ---------------------------------------------------------
 */

class AuthController
{
    /**
     * Servicio de autenticación.
     * 
     * @var AuthService
     */
    private AuthService $authService;

    /**
     * Constructor del controlador.
     * 
     * @param PDO $connection Conexión activa a MySQL.
     */
    public function __construct(PDO $connection)
    {
        $this->authService = new AuthService($connection);
    }

    /**
     * Registra un nuevo usuario.
     * 
     * @return void
     */
    public function register(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $result = $this->authService->register($data);

        Response::json(
            $result,
            $result['status']
        );
    }

    /**
     * Inicia sesión de un usuario.
     *
     * @return void
     */
    public function login(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $result = $this->authService->login($data);

        Response::json(
            $result,
            $result['status']
        );
    }
}
