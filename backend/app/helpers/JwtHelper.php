<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHelper
{

    /**
     * Obtiene la clave secreta utilizada para firmar los JWT.
     *
     * @return string
     */
    private static function getSecret(): string
    {
        return $_ENV['JWT_SECRET'];
    }

    /**
     * Obtiene el tiempo de expiración del token.
     *
     * @return int
     */
    private static function getExpiration(): int
    {
        return (int) $_ENV['JWT_EXPIRE'];
    }

    /**
     * Genera un token JWT.
     *
     * @param array $payload Información que contendrá el token.
     *
     * @return string
     */
    public static function generate(array $payload): string
    {
        $issuedAt = time(); // Guardar el tiempo actual en que se genera el token

        $expiresAt = $issuedAt + self::getExpiration();

        $tokenPayload = [

            'iat' => $issuedAt,

            'exp' => $expiresAt,

            ...$payload

        ];

        return JWT::encode(
            $tokenPayload,
            self::getSecret(),
            'HS256'
        );
    }
}
