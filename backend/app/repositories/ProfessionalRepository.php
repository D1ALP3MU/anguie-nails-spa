<?php

namespace App\Repositories;

use PDO;

class ProfessionalRepository
{
    public function __construct(
        private PDO $db
    ) {}

    /**
     * Obtiene todos los profesionales activos.
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                id_profesional,
                nombre,
                especialidad,
                telefono
            FROM profesionales
            WHERE activo = 1
            ORDER BY nombre ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un profesional por su ID.
     *
     * @param int $id ID del profesional.
     *
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id_profesional,
                nombre,
                especialidad,
                telefono,
                activo
            FROM profesionales
            WHERE id_profesional = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $professional = $stmt->fetch(PDO::FETCH_ASSOC);

        return $professional ?: null;
    }
    /**
     * Obtiene un profesional por su ID,
     * independientemente de su estado.
     *
     * @param int $id ID del profesional.
     *
     * @return array|null
     */
    public function findByIdIncludingInactive(int $id): ?array
    {
        $sql = "
            SELECT
                id_profesional,
                nombre,
                especialidad,
                telefono,
                activo
            FROM profesionales
            WHERE id_profesional = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $professional = $stmt->fetch(PDO::FETCH_ASSOC);

        return $professional ?: null;
    }

    /**
     * Crea un nuevo profesional.
     *
     * @param array $data Datos del profesional.
     *
     * @return int ID del profesional creado.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO profesionales (
                nombre,
                especialidad,
                telefono
            )
            VALUES (
                :nombre,
                :especialidad,
                :telefono
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'nombre'       => $data['nombre'],
            'especialidad' => $data['especialidad'] ?? null,
            'telefono'     => $data['telefono'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un profesional existente.
     *
     * @param int $id ID del profesional.
     * @param array $data Datos a actualizar.
     *
     * @return void
     */
    public function update(int $id, array $data): void
    {
        $sql = "
            UPDATE profesionales
            SET
                nombre = :nombre,
                especialidad = :especialidad,
                telefono = :telefono
            WHERE id_profesional = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id'           => $id,
            'nombre'       => $data['nombre'],
            'especialidad' => $data['especialidad'] ?? null,
            'telefono'     => $data['telefono'] ?? null
        ]);
    }

    /**
     * Desactiva un profesional mediante eliminación lógica.
     *
     * No se borra la fila: las citas pasadas la referencian y el
     * historial debe seguir siendo legible.
     *
     * @param int $id ID del profesional.
     *
     * @return void
     */
    public function deactivate(int $id): void
    {
        $sql = "
            UPDATE profesionales
            SET activo = 0
            WHERE id_profesional = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);
    }

    /**
     * Cuenta las citas vigentes que tiene un profesional de hoy
     * en adelante.
     *
     * Sirve para impedir que se dé de baja a alguien que todavía
     * tiene clientas esperándola.
     *
     * @param int $id ID del profesional.
     *
     * @return int
     */
    public function countUpcomingAppointments(int $id): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM citas
            WHERE id_profesional = :id
                AND estado IN ('pendiente', 'confirmada')
                AND fecha >= CURDATE()
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        return (int) $stmt->fetchColumn();
    }
}