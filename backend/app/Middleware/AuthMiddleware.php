<?php

namespace App\Middleware;

use App\Helpers\JwtHelper;
use App\Responses\Response;
use App\Exceptions\AuthException;

class AuthMiddleware
{

    /**
     * Verifica que la petición tenga un token JWT válido.
     * 
     * @return array Información del usuario autenticado.
     */
    public static function handle(): array
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authorization)) {
            Response::error(
                'No se envió el token de autenticación.',
                401
            );
        }

        if (!str_starts_with($authorization, 'Bearer ')) {
            Response::error(
                'Formato de token inválido.',
                401
            );
        }

        $token = substr($authorization, 7);

        try {

            return JwtHelper::validate($token);

        } catch (AuthException $e) {

            Response::error(
                $e->getMessage(),
                401
            );

        }
        
        return [];
    }
}