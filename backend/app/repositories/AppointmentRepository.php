<?php

namespace App\Repositories;

use PDO;

/**
 * Repositorio para manejar las operaciones de la entidad "Cita" en la base de datos.
 *
 * Este repositorio proporciona métodos para realizar operaciones CRUD (Crear, Leer, Actualizar, Eliminar) en la tabla de citas.
 *
 */

class AppointmentRepository
{
    public function __construct(
        private PDO $db
    ) {}

    /**
     * Obtiene todas las citas.
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                c.id_cita,
                c.id_cliente,
                u.nombre AS cliente,
                c.id_servicio,
                s.nombre AS servicio,
                c.id_profesional,
                p.nombre AS profesional,
                c.fecha,
                c.hora,
                c.estado,
                c.notas,
                c.created_at,
                c.updated_at
            FROM citas c
            INNER JOIN clientes cl
                ON cl.id_cliente = c.id_cliente
            INNER JOIN usuarios u
                ON u.id_usuario = cl.id_usuario
            INNER JOIN servicios s
                ON s.id_servicio = c.id_servicio
            INNER JOIN profesionales p
                ON p.id_profesional = c.id_profesional
            ORDER BY c.fecha ASC, c.hora ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una cita por su ID.
     *
     * @param int $id ID de la cita.
     *
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                c.id_cita,
                c.id_cliente,
                u.nombre AS cliente,
                c.id_servicio,
                s.nombre AS servicio,
                c.id_profesional,
                p.nombre AS profesional,
                c.fecha,
                c.hora,
                c.estado,
                c.notas,
                c.created_at,
                c.updated_at
            FROM citas c
            INNER JOIN clientes cl
                ON cl.id_cliente = c.id_cliente
            INNER JOIN usuarios u
                ON u.id_usuario = cl.id_usuario
            INNER JOIN servicios s
                ON s.id_servicio = c.id_servicio
            INNER JOIN profesionales p
                ON p.id_profesional = c.id_profesional
            WHERE c.id_cita = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $appointment ?: null;
    }

    /**
     * Crea una nueva cita.
     *
     * @param array $data Datos de la cita.
     *
     * @return int ID de la cita creada.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO citas (
                id_cliente,
                id_servicio,
                id_profesional,
                fecha,
                hora,
                estado,
                notas
            )
            VALUES (
                :id_cliente,
                :id_servicio,
                :id_profesional,
                :fecha,
                :hora,
                :estado,
                :notas
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_cliente'     => $data['id_cliente'],
            'id_servicio'    => $data['id_servicio'],
            'id_profesional' => $data['id_profesional'],
            'fecha'          => $data['fecha'],
            'hora'           => $data['hora'],
            'estado'         => $data['estado'] ?? 'pendiente',
            'notas'          => $data['notas'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza una cita existente.
     *
     * @param int $id ID de la cita.
     * @param array $data Datos a actualizar.
     *
     * @return void
     */
    public function update(
        int $id,
        array $data
    ): void {
        $sql = "
            UPDATE citas
            SET
                id_cliente = :id_cliente,
                id_servicio = :id_servicio,
                id_profesional = :id_profesional,
                fecha = :fecha,
                hora = :hora,
                estado = :estado,
                notas = :notas
            WHERE id_cita = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id'            => $id,
            'id_cliente'    => $data['id_cliente'],
            'id_servicio'   => $data['id_servicio'],
            'id_profesional' => $data['id_profesional'],
            'fecha'         => $data['fecha'],
            'hora'          => $data['hora'],
            'estado'        => $data['estado'],
            'notas'          => $data['notas'] ?? null
        ]);
    }

    /**
     * Cancela una cita mediante eliminación lógica.
     *
     * @param int $id ID de la cita.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $sql = "
            UPDATE citas
            SET estado = 'cancelada'
            WHERE id_cita = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);
    }
}
