<?php

namespace App\Controllers;

use App\Core\Request;
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
 * El registro de cuentas no vive aquí: crear una cuenta equivale
 * a crear un cliente, así que POST /api/auth/register se atiende
 * con ClientController::store() y comparte una única transacción.
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
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function login(Request $request): void
    {
        $result = $this->authService->login($request->body());

        Response::success($result);
    }

    /**
     * Devuelve los datos del usuario autenticado.
     *
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function profile(array $authUser): void
    {
        Response::success($authUser);
    }
}
