<?php

namespace App\Repositories;

use PDO;
use RuntimeException;

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
    /**
     * Conexión a la base de datos.
     *
     * @var PDO
     */
    private PDO $connection;

    /**
     * Constructor del repositorio.
     *
     * @param PDO $connection Conexión activa a MySQL.
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Crea un cliente asociado a un usuario.
     *
     * @param int $userId ID del usuario.
     *
     * @return bool
     *
     * @throws RuntimeException
     */
    public function create(int $userId): bool
    {
        $sql = "
            INSERT INTO clientes (id_usuario)
            VALUES (:id_usuario)
        ";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ':id_usuario',
            $userId,
            PDO::PARAM_INT
        );

        if (!$statement->execute()) {
            throw new RuntimeException(
                'No fue posible crear el cliente.'
            );
        }

        return true;
    }
}