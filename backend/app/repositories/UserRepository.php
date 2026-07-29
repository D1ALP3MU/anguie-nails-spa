<?php

namespace App\Repositories;

use PDO;
use RuntimeException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Usuarios
 * Archivo  : UserRepository.php
 * Versión  : 1.0.0
 *
 * Descripción:
 * Gestiona el acceso a la información de los usuarios en la
 * base de datos.
 *
 * Responsabilidades:
 * - Consultar usuarios.
 * - Crear usuarios.
 * - Buscar usuarios por diferentes criterios.
 *
 * Esta clase NO contiene lógica de negocio.
 * ---------------------------------------------------------
 */

class UserRepository
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
     * Busca un usuario por su correo electrónico.
     *
     * @param string $email Correo del usuario.
     *
     * @return array|null Retorna el usuario si existe o null.
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "
            SELECT *
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(
            ':email',
            $email,
            PDO::PARAM_STR
        );

        $statement->execute();

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Crea un nuevo usuario.
     *
     * @param array $userData Información del usuario.
     *
     * @return int ID del usuario creado.
     *
     * @throws RuntimeException Si ocurre un error al crear el usuario.
     */
    public function create(array $userData): int
    {
        $sql = "
            INSERT INTO usuarios (
                nombre,
                email,
                password_hash,
                id_rol,
                activo
            )
            VALUES (
                :nombre,
                :email,
                :password_hash,
                :id_rol,
                :activo
            )
        ";

        $statement = $this->connection->prepare($sql);

        $statement->bindValue(':nombre', $userData['nombre']);
        $statement->bindValue(':email', $userData['email']);
        $statement->bindValue(':password_hash', $userData['password_hash']);
        $statement->bindValue(':id_rol', $userData['id_rol'], PDO::PARAM_INT);
        $statement->bindValue(':activo', $userData['activo'], PDO::PARAM_BOOL);

        if (!$statement->execute()) {
            throw new RuntimeException('No fue posible crear el usuario.');
        }

        return (int) $this->connection->lastInsertId();
    }
}
