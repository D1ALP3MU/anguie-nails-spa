<?php

namespace App\Repositories;

use PDO;

class ServiceRepository
{

    public function __construct(
        private PDO $db
    ) {}

    public function findAll(): array
    {

        $query = "
            SELECT
                id_servicio,
                nombre,
                duracion,
                precio
            FROM servicios
            WHERE activo = 1        
        ";

        $statement = $this->db->prepare($query);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un servicio por su ID.
     *
     * @param int $id
     *
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                id_servicio,
                nombre,
                descripcion,
                duracion,
                precio,
                activo
            FROM servicios
            WHERE id_servicio = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);

        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        return $service ?: null;
    }

    /**
     * Crea un nuevo servicio.
     *
     * @param array $data Datos del servicio.
     *
     * @return int ID del servicio creado.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO servicios (
                nombre,
                descripcion,
                duracion,
                precio
            )
            VALUES (
                :nombre,
                :descripcion,
                :duracion,
                :precio
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'nombre'   => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'duracion' => $data['duracion'],
            'precio'   => $data['precio']
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param int $id ID del servicio.
     * @param array $data Datos del servicio.
     *
     * @return void
     */
    public function update(
        int $id,
        array $data
    ): void {
        $sql = "
            UPDATE servicios
            SET
                nombre = :nombre,
                descripcion = :descripcion,
                duracion = :duracion,
                precio = :precio
            WHERE id_servicio = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
            'duracion' => $data['duracion'],
            'precio' => $data['precio']
        ]);
    }

    /**
     * Desactiva un servicio.
     *
     * @param int $id ID del servicio.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $sql = "
            UPDATE servicios
            SET activo = 0
            WHERE id_servicio = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id' => $id
        ]);
    }
}
