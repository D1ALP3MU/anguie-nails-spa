<?php

namespace App\Middleware;

use App\Helpers\JwtHelper;
use App\Exceptions\AuthException;

/**
 * Verifica que la petición traiga un token JWT válido.
 *
 * No genera respuestas HTTP: lanza excepciones de dominio y deja
 * que ExceptionHandler decida el formato y el código. Así existe
 * un único lugar donde se traduce un error a una respuesta.
 */
class AuthMiddleware
{
    /**
     * @return array Información del usuario autenticado.
     *
     * @throws AuthException
     */
    public static function handle(): array
    {
        $authorization = self::readAuthorizationHeader();

        if ($authorization === '') {
            throw new AuthException(
                'No se envió el token de autenticación.'
            );
        }

        if (!str_starts_with($authorization, 'Bearer ')) {
            throw new AuthException(
                'Formato de token inválido.'
            );
        }

        return JwtHelper::validate(
            substr($authorization, 7)
        );
    }

    /**
     * Lee la cabecera Authorization.
     *
     * Apache con mod_php no expone la cabecera en $_SERVER salvo
     * que se propague explícitamente, así que se consulta también
     * la variante reescrita y getallheaders().
     *
     * @return string
     */
    private static function readAuthorizationHeader(): string
    {
        $candidates = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? '',
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''
        ];

        foreach ($candidates as $value) {
            if ($value !== '') {
                return $value;
            }
        }

        if (function_exists('getallheaders')) {

            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    return $value;
                }
            }
        }

        return '';
    }
}
