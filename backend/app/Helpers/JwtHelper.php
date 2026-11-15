<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;
use App\Exceptions\AuthException;

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
        $issuedAt = time();

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

    /**
     * Valida un token JWT y devuelve su contenido si es válido.
     * 
     * @param string $token Token JWT a validar.
     * 
     * @return array Contenido del token si es válido.
     */
    public static function validate(string $token): array
    {
        try {

            $decoded = JWT::decode(
                $token,
                new Key(
                    self::getSecret(),
                    'HS256'
                )
            );

            return (array) $decoded;
        } catch (Throwable $e) {

            // Cualquier fallo al decodificar significa lo mismo para
            // quien llama: el token no sirve. Se captura Throwable
            // porque la librería lanza además DomainException
            // (JSON malformado) e InvalidArgumentException, que de
            // otro modo escaparían y producirían un 500 en lugar de un 401.
            error_log(
                'Token JWT rechazado ('
                . get_class($e) . '): '
                . $e->getMessage()
            );

            throw new AuthException(
                'Token inválido o expirado.'
            );
        }
    }
}
