<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Usuarios
 * Archivo  : UserValidator.php
 * Versión  : 1.0.0
 *
 * Descripción:
 * Valida los datos enviados para el inicio de sesión.
 *
 * El registro se valida en ClientValidator, que cubre además
 * el teléfono y la dirección del perfil de cliente.
 *
 * Responsabilidades:
 * - Validar correo electrónico.
 * - Validar que se haya enviado una contraseña.
 *
 * No realiza consultas a la base de datos.
 * ---------------------------------------------------------
 */

class UserValidator
{

    private const MAX_EMAIL_LENGTH = 100;


    /**
     * Valida los datos enviados para el inicio de sesión.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return void
     */
    public static function validateLogin(array $data): void
    {
        $errors = [];

        self::validateEmail($data, $errors);
        self::validateLoginPassword($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Valida el correo electrónico.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateEmail(
        array $data,
        array &$errors
    ): void {
        $email = trim($data['email'] ?? '');

        if ($email === '') {
            $errors['email'] =
                'El correo electrónico es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] =
                'El correo electrónico no es válido.';
        } elseif (mb_strlen($email) > self::MAX_EMAIL_LENGTH) {
            $errors['email'] =
                'El correo electrónico no puede superar los '
                . self::MAX_EMAIL_LENGTH
                . ' caracteres.';
        }
    }

    /**
     * Valida la contraseña durante el inicio de sesión.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateLoginPassword(
        array $data,
        array &$errors
    ): void {
        $password = $data['password'] ?? '';

        if ($password === '') {
            $errors['password'] =
                'La contraseña es obligatoria.';
        }
    }
}
