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
 * Valida los datos enviados para el registro e inicio
 * de sesión de usuarios.
 *
 * Responsabilidades:
 * - Validar nombre.
 * - Validar correo electrónico.
 * - Validar contraseña.
 *
 * No realiza consultas a la base de datos.
 * ---------------------------------------------------------
 */

class UserValidator
{
    private const MIN_NAME_LENGTH = 3;
    private const MAX_NAME_LENGTH = 100;

    private const MAX_EMAIL_LENGTH = 100;

    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 255;

    /**
     * Valida la información del formulario de registro.
     *
     * @param array $data Datos enviados por el usuario.
     *
     * @return void
     */
    public static function validateRegister(array $data): void
    {
        $errors = [];

        self::validateName($data, $errors);
        self::validateEmail($data, $errors);
        self::validatePassword($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

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
     * Valida el nombre.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateName(
        array $data,
        array &$errors
    ): void {
        $name = trim($data['nombre'] ?? '');

        if ($name === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($name) < self::MIN_NAME_LENGTH) {
            $errors['nombre'] =
                'El nombre debe tener al menos '
                . self::MIN_NAME_LENGTH
                . ' caracteres.';
        } elseif (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            $errors['nombre'] =
                'El nombre no puede superar los '
                . self::MAX_NAME_LENGTH
                . ' caracteres.';
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
     * Valida la contraseña durante el registro.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validatePassword(
        array $data,
        array &$errors
    ): void {
        $password = $data['password'] ?? '';

        if ($password === '') {
            $errors['password'] =
                'La contraseña es obligatoria.';
        } elseif (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] =
                'La contraseña debe tener al menos '
                . self::MIN_PASSWORD_LENGTH
                . ' caracteres.';
        } elseif (mb_strlen($password) > self::MAX_PASSWORD_LENGTH) {
            $errors['password'] =
                'La contraseña no puede superar los '
                . self::MAX_PASSWORD_LENGTH
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
