<?php

namespace App\Helpers;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Autenticación
 * Archivo  : PasswordHelper.php
 * Versión  : 1.0.0
 *
 * Descripción:
 * Centraliza las operaciones relacionadas con el manejo
 * seguro de contraseñas.
 *
 * Responsabilidades:
 * - Encriptar contraseñas.
 * - Verificar contraseñas.
 * ---------------------------------------------------------
 */
class PasswordHelper
{
    /**
     * Genera un hash seguro para una contraseña.
     *
     * @param string $password Contraseña en texto plano.
     *
     * @return string
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifica si una contraseña coincide con un hash almacenado.
     *
     * @param string $password Contraseña ingresada por el usuario.
     * @param string $hash Hash almacenado en la base de datos.
     *
     * @return bool True si la contraseña es válida, false en caso contrario.
     */
    public static function verify(
        string $password,
        string $hash
    ): bool {
        return password_verify(
            $password,
            $hash
        );
    }
}
