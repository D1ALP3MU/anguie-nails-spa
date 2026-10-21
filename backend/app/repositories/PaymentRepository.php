<?php

namespace App\Repositories;

use PDO;
use PDOException;
use App\Exceptions\ConflictException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Pagos
 * Archivo  : PaymentRepository.php
 *
 * Descripción:
 * Acceso a los cobros de las citas.
 *
 * Esta clase NO contiene lógica de negocio.
 * ---------------------------------------------------------
 */
class PaymentRepository
{
    /**
     * Código de MySQL para una entrada duplicada.
     */
    private const MYSQL_DUPLICATE_ENTRY = 1062;

    /**
     * Índice que reserva el cobro vigente de una cita.
     */
    private const PAYMENT_INDEX = 'uq_pagos_cita';

    /**
     * Estados en los que un cobro deja de contar.
     */
    private const VOIDED = ['rechazado', 'reembolsado'];

    /**
     * Proyección común, con los datos de la cita que cobra.
     */
    private const BASE_SELECT = "
        SELECT
            pg.id_pago,
            pg.id_cita,
            pg.monto,
            pg.metodo,
            pg.estado,
            pg.referencia_pago,
            pg.created_at,
            c.id_cliente,
            u.nombre AS cliente,
            c.precio AS precio_cita,
            c.fecha,
            c.hora,
            s.nombre AS servicio
        FROM pagos pg
        INNER JOIN citas c
            ON c.id_cita = pg.id_cita
        INNER JOIN clientes cl
            ON cl.id_cliente = c.id_cliente
        INNER JOIN usuarios u
            ON u.id_usuario = cl.id_usuario
        INNER JOIN servicios s
            ON s.id_servicio = c.id_servicio
    ";

    public function __construct(
        private PDO $db
    ) {}

    /**
     * Obtiene el cobro vigente de una cita.
     *
     * Los anulados quedan fuera: siguen en la tabla como historial,
     * pero la cita vuelve a estar cobrable.
     *
     * @param int $appointmentId ID de la cita.
     *
     * @return array|null
     */
    public function findActiveByAppointment(int $appointmentId): ?array
    {
        $sql = self::BASE_SELECT . "
            WHERE pg.id_cita = :id_cita
                AND pg.cobro_vigente IS NOT NULL
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_cita' => $appointmentId
        ]);

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $payment ?: null;
    }

    /**
     * Obtiene un cobro por su ID.
     *
     * @param int $id ID del cobro.
     *
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = self::BASE_SELECT . "
            WHERE pg.id_pago = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $payment ?: null;
    }

    /**
     * Obtiene los cobros que cumplen los filtros indicados.
     *
     * @param array $filters Claves admitidas: id_cliente, estado,
     *                       metodo, desde, hasta.
     *
     * @return array
     */
    public function findAllFiltered(array $filters): array
    {
        $conditions = [];
        $parameters = [];

        $columns = [
            'id_cliente' => 'c.id_cliente',
            'estado' => 'pg.estado',
            'metodo' => 'pg.metodo'
        ];

        foreach ($columns as $key => $column) {

            if (isset($filters[$key])) {
                $conditions[] = "{$column} = :{$key}";
                $parameters[$key] = $filters[$key];
            }
        }

        // El rango se aplica sobre la fecha de la cita, no sobre la
        // del cobro: lo que el salón quiere saber es cuánto facturó
        // por el trabajo de un periodo.
        if (isset($filters['desde'])) {
            $conditions[] = 'c.fecha >= :desde';
            $parameters['desde'] = $filters['desde'];
        }

        if (isset($filters['hasta'])) {
            $conditions[] = 'c.fecha <= :hasta';
            $parameters['hasta'] = $filters['hasta'];
        }

        $where = $conditions === []
            ? ''
            : ' WHERE ' . implode(' AND ', $conditions);

        $sql = self::BASE_SELECT . $where . '
            ORDER BY c.fecha DESC, c.hora DESC
        ';

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parameters);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un cobro.
     *
     * @param array $data Datos del cobro.
     *
     * @return int ID del cobro creado.
     *
     * @throws ConflictException Si la cita ya tiene un cobro vigente.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO pagos (
                id_cita,
                monto,
                metodo,
                estado,
                referencia_pago
            )
            VALUES (
                :id_cita,
                :monto,
                :metodo,
                :estado,
                :referencia_pago
            )
        ";

        $stmt = $this->db->prepare($sql);

        try {

            $stmt->execute([
                'id_cita'         => $data['id_cita'],
                'monto'           => $data['monto'],
                'metodo'          => $data['metodo'],
                'estado'          => $data['estado'] ?? 'pagado',
                'referencia_pago' => $data['referencia_pago'] ?? null
            ]);

        } catch (PDOException $e) {

            // PaymentService ya comprueba que no haya cobro vigente;
            // el índice cubre dos peticiones simultáneas que pasen esa
            // comprobación a la vez. Se exige el código de entrada
            // duplicada y el nombre del índice, porque el SQLSTATE
            // 23000 cubre también las claves foráneas.
            if (
                ($e->errorInfo[1] ?? null) === self::MYSQL_DUPLICATE_ENTRY
                && str_contains($e->getMessage(), self::PAYMENT_INDEX)
            ) {
                throw new ConflictException(
                    'La cita ya tiene un cobro registrado.'
                );
            }

            throw $e;
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cambia el estado de un cobro.
     *
     * @param int $id ID del cobro.
     * @param string $status Estado nuevo.
     *
     * @return void
     */
    public function updateStatus(int $id, string $status): void
    {
        $sql = "
            UPDATE pagos
            SET estado = :estado
            WHERE id_pago = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id'     => $id,
            'estado' => $status
        ]);
    }

    /**
     * Indica si el cobro ya fue anulado.
     *
     * @param array $payment Fila del cobro.
     *
     * @return bool
     */
    public static function isVoided(array $payment): bool
    {
        return in_array($payment['estado'], self::VOIDED, true);
    }
}
