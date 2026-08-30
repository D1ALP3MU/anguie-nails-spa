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
}
