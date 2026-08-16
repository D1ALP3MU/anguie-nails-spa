<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

class ClientValidator
{
    private const MIN_NAME_LENGTH = 3;
    private const MAX_NAME_LENGTH = 100;

    private const MAX_EMAIL_LENGTH = 100;

    private const MIN_PASSWORD_LENGTH = 8;
    private const MAX_PASSWORD_LENGTH = 255;

    private const MAX_PHONE_LENGTH = 20;

    private const MAX_ADDRESS_LENGTH = 255;

    /**
     * Valida los datos del cliente.
     *
     * @param array $data
     *
     * @return void
     */
    public static function validate(array $data): void
    {
        $errors = [];

        self::validateName($data, $errors);
        self::validateEmail($data, $errors);
        self::validatePassword($data, $errors);
        self::validatePhone($data, $errors);
        self::validateAddress($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private static function validateName(array $data, array &$errors): void
    {
        $name = trim($data['nombre'] ?? '');

        if ($name === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($name) < self::MIN_NAME_LENGTH) {
            $errors['nombre'] =
                'El nombre debe tener al menos '
                . self::MIN_NAME_LENGTH .
                ' caracteres.';
        } elseif (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            $errors['nombre'] =
                'El nombre no puede superar los '
                . self::MAX_NAME_LENGTH .
                ' caracteres.';
        }
    }

    private static function validateEmail(array $data, array &$errors): void
    {
        $email = trim($data['email'] ?? '');

        if ($email === '') {
            $errors['email'] = 'El correo electrónico es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo electrónico no es válido.';
        } elseif (mb_strlen($email) > self::MAX_EMAIL_LENGTH) {
            $errors['email'] =
                'El correo electrónico no puede superar los '
                . self::MAX_EMAIL_LENGTH .
                ' caracteres.';
        }
    }

    private static function validatePassword(array $data, array &$errors): void
    {
        $password = $data['password'] ?? '';

        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria.';
        } elseif (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] =
                'La contraseña debe tener al menos '
                . self::MIN_PASSWORD_LENGTH .
                ' caracteres.';
        } elseif (mb_strlen($password) > self::MAX_PASSWORD_LENGTH) {
            $errors['password'] =
                'La contraseña no puede superar los '
                . self::MAX_PASSWORD_LENGTH .
                ' caracteres.';
        }
    }

    private static function validatePhone(array $data, array &$errors): void
    {
        $phone = trim($data['telefono'] ?? '');

        if ($phone === '') {
            $errors['telefono'] = 'El teléfono es obligatorio.';
        } elseif (mb_strlen($phone) > self::MAX_PHONE_LENGTH) {
            $errors['telefono'] =
                'El teléfono no puede superar los '
                . self::MAX_PHONE_LENGTH .
                ' caracteres.';
        }
    }

    private static function validateAddress(array $data, array &$errors): void
    {
        $address = trim($data['direccion'] ?? '');

        if (
            $address !== ''
            && mb_strlen($address) > self::MAX_ADDRESS_LENGTH
        ) {
            $errors['direccion'] =
                'La dirección no puede superar los '
                . self::MAX_ADDRESS_LENGTH .
                ' caracteres.';
        }
    }
}