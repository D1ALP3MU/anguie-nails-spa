<?php

namespace App\Validators;
use App\Exceptions\ValidationException;

class ServiceValidator
{
    /**
     * Longitud mínima permitida para el nombre del servicio.
     */
    private const MIN_NAME_LENGTH = 3;

    /**
     * Longitud máxima permitida para el nombre del servicio.
     */
    private const MAX_NAME_LENGTH = 100;

    /**
     * Longitud máxima permitida para la descripción.
     */
    private const MAX_DESCRIPTION_LENGTH = 500;

    /**
     * Duración máxima permitida para un servicio (en minutos).
     */
    private const MAX_DURATION = 240;

    /**
     * Precio máximo permitido para un servicio.
     */
    private const MAX_PRICE = 1000000;

    /**
     * Valida los datos de un servicio.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return array Resultado de la validación.
     */
    public static function validate(array $data): void
    {
        $errors = [];
        
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        // Validar nombre del servicio
        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre del servicio es obligatorio.';
        } elseif (
            mb_strlen($nombre) < self::MIN_NAME_LENGTH
        ) {
            $errors['nombre'] =
                'El nombre del servicio debe tener al menos '
                . self::MIN_NAME_LENGTH .
                ' caracteres.';
        } elseif (
            mb_strlen($nombre) > self::MAX_NAME_LENGTH
        ) {
            $errors['nombre'] =
                'El nombre del servicio no puede superar los '
                . self::MAX_NAME_LENGTH .
                ' caracteres.';
        }

        // Validar descripción del servicio (opcional)
        if (
            !empty($descripcion)
            && mb_strlen($descripcion) > self::MAX_DESCRIPTION_LENGTH
        ) {
            $errors['descripcion'] =
                'La descripción no puede superar los '
                . self::MAX_DESCRIPTION_LENGTH .
                ' caracteres.';
        }

        // Validar duración del servicio
        if (!isset($data['duracion']) || $data['duracion'] === '') {
            $errors['duracion'] = 'La duración del servicio es obligatoria.';
        } elseif (
            filter_var(
                $data['duracion'],
                FILTER_VALIDATE_INT
            ) === false
        ) {
            $errors['duracion'] = 'La duración del servicio debe ser un número entero.';
        } elseif ($data['duracion'] <= 0) {
            $errors['duracion'] = 'La duración del servicio debe ser mayor a cero.';
        } elseif ($data['duracion'] > self::MAX_DURATION) {
            $errors['duracion'] =
                'La duración del servicio no puede superar los '
                . self::MAX_DURATION .
                ' minutos.';
        }

        // Validar el precio del servicio
        if (!isset($data['precio']) || $data['precio'] === '') {
            $errors['precio'] = 'El precio del servicio es obligatorio.';
        } elseif (!is_numeric($data['precio'])) {

            $errors['precio'] = 'El precio del servicio debe ser un valor numérico.';
        } elseif ($data['precio'] <= 0) {

            $errors['precio'] = 'El precio del servicio debe ser mayor a cero.';
        } elseif ($data['precio'] > self::MAX_PRICE) {

            $errors['precio'] =
                'El precio del servicio no puede superar $'
                . number_format(self::MAX_PRICE, 0, ',', '.')
                . '.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
