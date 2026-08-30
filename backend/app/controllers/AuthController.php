<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Responses\Response;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Autenticación
 * Archivo  : AuthController.php
 * Versión  : 1.0.0
 *
 * Descripción:
 * Controlador encargado de gestionar las peticiones HTTP
 * relacionadas con la autenticación.
 *
 * Responsabilidades:
 * - Recibir peticiones HTTP.
 * - Invocar el servicio de autenticación.
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
     * @param AuthService $authService Servicio de autenticación.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
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

        Response::success($result);
    }
}
