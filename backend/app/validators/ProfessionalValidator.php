<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Profesionales
 * Archivo  : ProfessionalValidator.php
 *
 * Descripción:
 * Valida los datos de un profesional del salón.
 *
 * Responsabilidades:
 * - Validar nombre.
 * - Validar especialidad.
 * - Validar teléfono.
 *
 * No realiza consultas a la base de datos.
 * ---------------------------------------------------------
 */
class ProfessionalValidator
{
    private const MIN_NAME_LENGTH = 3;
    private const MAX_NAME_LENGTH = 100;

    private const MAX_SPECIALTY_LENGTH = 100;

    private const MAX_PHONE_LENGTH = 20;

    /**
     * Valida los datos de un profesional.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return void
     *
     * @throws ValidationException
     */
    public static function validate(array $data): void
    {
        $errors = [];

        self::validateName($data, $errors);
        self::validateSpecialty($data, $errors);
        self::validatePhone($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Valida el nombre del profesional.
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
            $errors['nombre'] = 'El nombre del profesional es obligatorio.';
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
     * Valida la especialidad, que es opcional.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateSpecialty(
        array $data,
        array &$errors
    ): void {
        $specialty = trim($data['especialidad'] ?? '');

        if (
            $specialty !== ''
            && mb_strlen($specialty) > self::MAX_SPECIALTY_LENGTH
        ) {
            $errors['especialidad'] =
                'La especialidad no puede superar los '
                . self::MAX_SPECIALTY_LENGTH
                . ' caracteres.';
        }
    }

    /**
     * Valida el teléfono, que es opcional.
     *
     * A diferencia del cliente, aquí no es obligatorio: el salón
     * puede registrar a una profesional antes de tener su contacto.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validatePhone(
        array $data,
        array &$errors
    ): void {
        $phone = trim($data['telefono'] ?? '');

        if (
            $phone !== ''
            && mb_strlen($phone) > self::MAX_PHONE_LENGTH
        ) {
            $errors['telefono'] =
                'El teléfono no puede superar los '
                . self::MAX_PHONE_LENGTH
                . ' caracteres.';
        }
    }
}
