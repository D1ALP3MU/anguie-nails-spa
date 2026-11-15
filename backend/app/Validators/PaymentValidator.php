<?php

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Pagos
 * Archivo  : PaymentValidator.php
 *
 * Descripción:
 * Valida los datos de un cobro.
 *
 * El sistema registra pagos, no los procesa: el dinero se mueve
 * fuera (efectivo en el mostrador, transferencia, datáfono) y aquí
 * queda la constancia. Por eso `referencia_pago` es texto libre y
 * no hay nada que verificar contra una pasarela.
 *
 * No realiza consultas a la base de datos.
 * ---------------------------------------------------------
 */
class PaymentValidator
{
    /**
     * Formas de pago que acepta el salón. Replica el ENUM de la tabla.
     */
    public const METHODS = [
        'efectivo',
        'tarjeta',
        'transferencia'
    ];

    /**
     * Estados con los que puede nacer un cobro.
     *
     * Los otros dos —rechazado y reembolsado— solo se alcanzan
     * anulando, nunca al registrar.
     */
    public const INITIAL_STATUSES = [
        'pendiente',
        'pagado'
    ];

    private const MAX_AMOUNT = 1000000;

    private const MAX_REFERENCE_LENGTH = 255;

    /**
     * Valida los datos de un cobro nuevo.
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

        self::validateAmount($data, $errors);
        self::validateMethod($data, $errors);
        self::validateStatus($data, $errors);
        self::validateReference($data, $errors);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Valida el monto.
     *
     * No se compara con el precio de la cita a propósito: un cobro
     * puede llevar descuento, propina o redondeo.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateAmount(
        array $data,
        array &$errors
    ): void {
        if (!isset($data['monto']) || $data['monto'] === '') {
            $errors['monto'] = 'El monto es obligatorio.';
            return;
        }

        if (!is_numeric($data['monto'])) {
            $errors['monto'] = 'El monto debe ser un valor numérico.';
            return;
        }

        if ((float) $data['monto'] <= 0) {
            $errors['monto'] = 'El monto debe ser mayor a cero.';
            return;
        }

        if ((float) $data['monto'] > self::MAX_AMOUNT) {
            $errors['monto'] =
                'El monto no puede superar $'
                . number_format(self::MAX_AMOUNT, 0, ',', '.')
                . '.';
        }
    }

    /**
     * Valida la forma de pago.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateMethod(
        array $data,
        array &$errors
    ): void {
        $method = trim($data['metodo'] ?? '');

        if ($method === '') {
            $errors['metodo'] = 'La forma de pago es obligatoria.';
            return;
        }

        if (!in_array($method, self::METHODS, true)) {
            $errors['metodo'] = 'La forma de pago indicada no es válida.';
        }
    }

    /**
     * Valida el estado inicial, que es opcional.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateStatus(
        array $data,
        array &$errors
    ): void {
        $status = trim($data['estado'] ?? '');

        if ($status === '') {
            return;
        }

        if (!in_array($status, self::INITIAL_STATUSES, true)) {
            $errors['estado'] =
                'Un cobro solo puede registrarse como pendiente o pagado.';
        }
    }

    /**
     * Valida la referencia, que es opcional.
     *
     * @param array $data
     * @param array &$errors
     *
     * @return void
     */
    private static function validateReference(
        array $data,
        array &$errors
    ): void {
        $reference = trim($data['referencia_pago'] ?? '');

        if (
            $reference !== ''
            && mb_strlen($reference) > self::MAX_REFERENCE_LENGTH
        ) {
            $errors['referencia_pago'] =
                'La referencia no puede superar los '
                . self::MAX_REFERENCE_LENGTH
                . ' caracteres.';
        }
    }
}
