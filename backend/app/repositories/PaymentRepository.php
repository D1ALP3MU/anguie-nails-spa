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
     * Resume la facturación de un periodo.
     *
     * Se agrupa en la base y no en el cliente: un informe no tiene
     * por qué descargar cobro por cobro para sumarlos.
     *
     * El rango se aplica sobre la fecha de la cita, igual que en el
     * listado: lo que interesa es cuánto produjo el trabajo de ese
     * periodo, no cuándo entró el dinero.
     *
     * @param array $filters Claves admitidas: desde, hasta.
     *
     * @return array
     */
    public function summary(array $filters): array
    {
        [$range, $parameters] = $this->buildRange($filters, 'c.fecha');

        return [
            'totales' => $this->totals($range, $parameters),
            'por_metodo' => $this->groupBy(
                'pg.metodo AS etiqueta',
                'pg.metodo',
                $range,
                $parameters
            ),
            'por_profesional' => $this->groupBy(
                'p.nombre AS etiqueta',
                'p.id_profesional',
                $range,
                $parameters,
                'INNER JOIN profesionales p ON p.id_profesional = c.id_profesional'
            ),
            'por_servicio' => $this->groupBy(
                's.nombre AS etiqueta',
                's.id_servicio',
                $range,
                $parameters,
                'INNER JOIN servicios s ON s.id_servicio = c.id_servicio'
            ),
            'sin_cobrar' => $this->uncharged($range, $parameters)
        ];
    }

    /**
     * Construye la condición de rango y sus parámetros.
     *
     * @param array $filters Filtros recibidos.
     * @param string $column Columna de fecha a comparar.
     *
     * @return array{0: string, 1: array}
     */
    private function buildRange(array $filters, string $column): array
    {
        $conditions = [];
        $parameters = [];

        if (isset($filters['desde'])) {
            $conditions[] = "{$column} >= :desde";
            $parameters['desde'] = $filters['desde'];
        }

        if (isset($filters['hasta'])) {
            $conditions[] = "{$column} <= :hasta";
            $parameters['hasta'] = $filters['hasta'];
        }

        return [
            $conditions === [] ? '' : ' AND ' . implode(' AND ', $conditions),
            $parameters
        ];
    }

    /**
     * Totales por estado del cobro.
     *
     * @param string $range Condición de rango.
     * @param array $parameters Parámetros del rango.
     *
     * @return array
     */
    private function totals(string $range, array $parameters): array
    {
        $sql = "
            SELECT
                COALESCE(SUM(CASE WHEN pg.estado = 'pagado' THEN pg.monto END), 0) AS facturado,
                COALESCE(SUM(CASE WHEN pg.estado = 'pendiente' THEN pg.monto END), 0) AS pendiente,
                COALESCE(SUM(CASE WHEN pg.estado IN ('rechazado', 'reembolsado') THEN pg.monto END), 0) AS anulado,
                COUNT(CASE WHEN pg.estado = 'pagado' THEN 1 END) AS cobros
            FROM pagos pg
            INNER JOIN citas c
                ON c.id_cita = pg.id_cita
            WHERE 1 = 1 {$range}
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parameters);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Agrupa lo facturado por la dimensión indicada.
     *
     * Solo cuenta lo cobrado de verdad: lo pendiente todavía no es
     * facturación, y lo anulado dejó de serlo.
     *
     * @param string $label Expresión que da la etiqueta del grupo.
     * @param string $groupBy Columna de agrupación.
     * @param string $range Condición de rango.
     * @param array $parameters Parámetros del rango.
     * @param string $join Unión adicional, si hace falta.
     *
     * @return array
     */
    private function groupBy(
        string $label,
        string $groupBy,
        string $range,
        array $parameters,
        string $join = ''
    ): array {
        $sql = "
            SELECT
                {$label},
                SUM(pg.monto) AS total,
                COUNT(*) AS cobros
            FROM pagos pg
            INNER JOIN citas c
                ON c.id_cita = pg.id_cita
            {$join}
            WHERE pg.estado = 'pagado' {$range}
            GROUP BY {$groupBy}
            ORDER BY total DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parameters);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Citas que todavía no se han cobrado.
     *
     * Es la respuesta a «¿cuánto me deben?», y solo se puede dar
     * porque la cita congela su precio al reservarse.
     *
     * Las canceladas quedan fuera: no hubo servicio que cobrar.
     *
     * @param string $range Condición de rango.
     * @param array $parameters Parámetros del rango.
     *
     * @return array
     */
    private function uncharged(string $range, array $parameters): array
    {
        $sql = "
            SELECT
                COUNT(*) AS citas,
                COALESCE(SUM(c.precio), 0) AS total
            FROM citas c
            LEFT JOIN pagos pg
                ON pg.id_cita = c.id_cita
                AND pg.cobro_vigente IS NOT NULL
            WHERE pg.id_pago IS NULL
                AND c.estado <> 'cancelada'
                {$range}
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parameters);

        return $stmt->fetch(PDO::FETCH_ASSOC);
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
