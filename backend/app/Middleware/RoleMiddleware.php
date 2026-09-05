<?php

namespace App\Middleware;

use App\Exceptions\AuthException;
use App\Exceptions\ForbiddenException;

/**
 * Verifica que el usuario tenga alguno de los roles permitidos.
 *
 * No genera respuestas HTTP: lanza excepciones de dominio y deja
 * que ExceptionHandler decida el formato y el código.
 */
class RoleMiddleware
{
    /**
     * @param array $user Información del usuario autenticado.
     * @param array $allowedRoles Roles que pueden acceder.
     *
     * @return void
     *
     * @throws AuthException Si el token no trae el rol.
     * @throws ForbiddenException Si el rol no está autorizado.
     */
    public static function handle(
        array $user,
        array $allowedRoles
    ): void {
        if (!isset($user['id_rol'])) {
            throw new AuthException(
                'No fue posible identificar el rol del usuario.'
            );
        }

        if (!in_array((int) $user['id_rol'], $allowedRoles, true)) {
            throw new ForbiddenException(
                'No tienes permisos para realizar esta acción.'
            );
        }
    }
}
