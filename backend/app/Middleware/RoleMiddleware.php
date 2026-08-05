<?php

namespace App\Middleware;

use App\Responses\Response;

class RoleMiddleware
{

    /**
     * Verifica que el usuario tenga alguno de los roles permitidos.
     *
     * @param array $user Información del usuario autenticado.
     * @param array $allowedRoles Roles que pueden acceder.
     *
     * @return void
     */
    public static function handle(
        array $user,
        array $allowedRoles
    ): void
    {
        if (!isset($user['id_rol'])) {
            Response::error(
                'No fue posible identificar el rol del usuario.',
                401
            );

            return;
        }

        if (!in_array($user['id_rol'], $allowedRoles, true)) {
            Response::error(
                'No tienes permisos para realizar esta acción.',
                403
            );

            return;
        }
    }

}