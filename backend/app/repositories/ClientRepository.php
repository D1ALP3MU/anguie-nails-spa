<?php

namespace App\Repositories;

use PDO;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Clientes
 * Archivo  : ClientRepository.php
 * Versión  : 1.0.0
 *
 * Descripción:
 * Gestiona el acceso a la información de los clientes.
 *
 * Responsabilidades:
 * - Crear clientes.
 *
 * Esta clase NO contiene lógica de negocio.
 * ---------------------------------------------------------
 */

class ClientRepository
{
    public function __construct(
        private PDO $db
    ) {}

    /**
     * Crea un nuevo cliente.
     *
     * @param array $data Datos del cliente.
     *
     * @return int ID del cliente creado.
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO clientes (
                id_usuario,
                telefono,
                direccion
            )
            VALUES (
                :id_usuario,
                :telefono,
                :direccion
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_usuario' => $data['id_usuario'],
            'telefono'   => $data['telefono'],
            'direccion'  => $data['direccion'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Obtiene todos los clientes activos.
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "
            SELECT
                c.id_cliente,
                c.id_usuario,
                u.nombre,
                u.email,
                c.telefono,
                c.direccion,
                c.created_at,
                c.updated_at
            FROM clientes c
            INNER JOIN usuarios u
                ON u.id_usuario = c.id_usuario
            WHERE u.activo = 1
            ORDER BY c.id_cliente DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
