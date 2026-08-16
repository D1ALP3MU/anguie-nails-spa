<?php

namespace App\Repositories;

use PDO;

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
     * @param PDO $db Conexión activa a MySQL.
     */
    public function __construct(PDO $db)
    {
        $this->connection = $db;
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
            SELECT
                id_usuario,
                nombre,
                email,
                password_hash,
                id_rol,
                activo
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Crea un nuevo usuario.
     *
     * @param array $userData Información del usuario.
     *
     * @return int ID del usuario creado.
     *
     */
    public function create(array $userData): int
    {
        $sql = "
            INSERT INTO usuarios (
                nombre,
                email,
                password_hash,
                id_rol
            )
            VALUES (
                :nombre,
                :email,
                :password_hash,
                :id_rol
            )
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':nombre' => $userData['nombre'],
            ':email' => $userData['email'],
            ':password_hash' => $userData['password_hash'],
            ':id_rol' => $userData['id_rol']
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
