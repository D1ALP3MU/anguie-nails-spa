<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * Validador para las citas.
 *
 * Este validador proporciona métodos para validar los datos de una cita antes de ser procesados o almacenados en la base de datos.
 */
class AppointmentValidator
{
    private const ALLOWED_STATUS = [
        'pendiente',
        'confirmada',
        'cancelada',
        'completada'
    ];

    private const MAX_NOTES_LENGTH = 1000;

    /**
     * Valida los datos de una cita.
     *
     * @param array $data
     *
     * @return void
     */
    public static function validate(array $data): void
    {
        $errors = [];

        self::validateClientId($data, $errors);
        self::validateServiceId($data, $errors);
        self::validateProfessionalId($data, $errors);
        self::validateDate($data, $errors);
        self::validateTime($data, $errors);
        self::validateStatus($data, $errors);
        self::validateNotes($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    private static function validateClientId(
        array $data,
        array &$errors
    ): void {
        if (
            !isset($data['id_cliente'])
            || $data['id_cliente'] === ''
        ) {
            $errors['id_cliente'] =
                'El cliente es obligatorio.';
            return;
        }

        if (
            filter_var(
                $data['id_cliente'],
                FILTER_VALIDATE_INT
            ) === false
            || (int) $data['id_cliente'] <= 0
        ) {
            $errors['id_cliente'] =
                'El ID del cliente debe ser un número entero mayor a cero.';
        }
    }

    private static function validateServiceId(
        array $data,
        array &$errors
    ): void {
        if (
            !isset($data['id_servicio'])
            || $data['id_servicio'] === ''
        ) {
            $errors['id_servicio'] =
                'El servicio es obligatorio.';
            return;
        }

        if (
            filter_var(
                $data['id_servicio'],
                FILTER_VALIDATE_INT
            ) === false
            || (int) $data['id_servicio'] <= 0
        ) {
            $errors['id_servicio'] =
                'El ID del servicio debe ser un número entero mayor a cero.';
        }
    }

    private static function validateProfessionalId(
        array $data,
        array &$errors
    ): void {
        if (
            !isset($data['id_profesional'])
            || $data['id_profesional'] === ''
        ) {
            $errors['id_profesional'] =
                'El profesional es obligatorio.';
            return;
        }

        if (
            filter_var(
                $data['id_profesional'],
                FILTER_VALIDATE_INT
            ) === false
            || (int) $data['id_profesional'] <= 0
        ) {
            $errors['id_profesional'] =
                'El ID del profesional debe ser un número entero mayor a cero.';
        }
    }

    private static function validateDate(
        array $data,
        array &$errors
    ): void {
        $date = trim($data['fecha'] ?? '');

        if ($date === '') {
            $errors['fecha'] =
                'La fecha es obligatoria.';
            return;
        }

        $dateObject = \DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

        if (
            $dateObject === false
            || $dateObject->format('Y-m-d') !== $date
        ) {
            $errors['fecha'] =
                'La fecha debe tener el formato YYYY-MM-DD.';
        }
    }

    private static function validateTime(
        array $data,
        array &$errors
    ): void {
        $time = trim($data['hora'] ?? '');

        if ($time === '') {
            $errors['hora'] =
                'La hora es obligatoria.';
            return;
        }

        $timeObject = \DateTime::createFromFormat(
            'H:i',
            $time
        );

        if (
            $timeObject === false
            || $timeObject->format('H:i') !== $time
        ) {
            $errors['hora'] =
                'La hora debe tener el formato HH:MM.';
        }
    }

    private static function validateStatus(
        array $data,
        array &$errors
    ): void {
        $status = $data['estado'] ?? 'pendiente';

        if (!in_array($status, self::ALLOWED_STATUS, true)) {
            $errors['estado'] =
                'El estado de la cita no es válido.';
        }
    }

    private static function validateNotes(
        array $data,
        array &$errors
    ): void {
        $notes = trim($data['notas'] ?? '');

        if (
            $notes !== ''
            && mb_strlen($notes) > self::MAX_NOTES_LENGTH
        ) {
            $errors['notas'] =
                'Las notas no pueden superar los '
                . self::MAX_NOTES_LENGTH
                . ' caracteres.';
        }
    }
}
