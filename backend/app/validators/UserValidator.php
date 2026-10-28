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
 * Valida los datos enviados para el inicio de sesión y para el
 * cambio de contraseña.
 *
 * El registro se valida en ClientValidator, que cubre además
 * el teléfono y la dirección del perfil de cliente.
 *
 * Responsabilidades:
 * - Validar correo electrónico.
 * - Validar que se haya enviado una contraseña.
 * - Validar la forma de una contraseña nueva.
 *
 * No realiza consultas a la base de datos: comprobar que la
 * contraseña actual sea la correcta es cosa del servicio.
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
     * Valida los datos para dar de alta a un administrador.
     *
     * No pide teléfono ni dirección: un administrador no es un
     * cliente y no tiene perfil en la tabla `clientes`.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return void
     */
    public static function validateAdmin(array $data): void
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
     * Valida la contraseña en el alta.
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
            $errors['password'] = 'La contraseña es obligatoria.';
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
     * Valida los datos enviados para cambiar la contraseña.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return void
     */
    public static function validatePasswordChange(array $data): void
    {
        $errors = [];

        if (($data['password_actual'] ?? '') === '') {
            $errors['password_actual'] =
                'La contraseña actual es obligatoria.';
        }

        self::validateNewPassword($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Valida la contraseña nueva y su confirmación.
     *
     * Se comprueba de lo general a lo particular y se corta en el
     * primer fallo: decir «debe tener 8 caracteres» y «no coincide
     * con la confirmación» a la vez no ayuda a nadie.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateNewPassword(
        array $data,
        array &$errors
    ): void {
        $nueva = $data['password_nueva'] ?? '';

        if ($nueva === '') {
            $errors['password_nueva'] =
                'La contraseña nueva es obligatoria.';
            return;
        }

        if (mb_strlen($nueva) < self::MIN_PASSWORD_LENGTH) {
            $errors['password_nueva'] =
                'La contraseña debe tener al menos '
                . self::MIN_PASSWORD_LENGTH
                . ' caracteres.';
            return;
        }

        if (mb_strlen($nueva) > self::MAX_PASSWORD_LENGTH) {
            $errors['password_nueva'] =
                'La contraseña no puede superar los '
                . self::MAX_PASSWORD_LENGTH
                . ' caracteres.';
            return;
        }

        if ($nueva === ($data['password_actual'] ?? '')) {
            $errors['password_nueva'] =
                'La contraseña nueva debe ser distinta de la actual.';
            return;
        }

        if ($nueva !== ($data['password_confirmacion'] ?? '')) {
            $errors['password_confirmacion'] =
                'La confirmación no coincide con la contraseña nueva.';
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
