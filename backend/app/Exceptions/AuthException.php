<?php

namespace App\Exceptions;

/**
 * Fallo de autenticación: token ausente, malformado o expirado.
 *
 * Extiende UnauthorizedException para que ExceptionHandler la
 * traduzca a 401 sin necesitar un caso propio: ambas describen
 * lo mismo desde el punto de vista de quien llama.
 */
class AuthException extends UnauthorizedException
{
}
