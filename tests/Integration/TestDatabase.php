<?php

namespace Tests\Integration;

use PDO;
use PDOException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : TestDatabase.php
 *
 * Descripción:
 * Prepara una base de datos desechable para las pruebas de
 * integración, a partir del mismo database/schema.sql que usa
 * la aplicación.
 *
 * Nunca toca la base de desarrollo: usa DB_NAME_TEST, que por
 * defecto es spa_db_test.
 * ---------------------------------------------------------
 */
class TestDatabase
{
    private static ?PDO $connection = null;

    private static ?string $unavailableReason = null;

    private static bool $prepared = false;

    /**
     * Nombre de la base de datos de pruebas.
     */
    public static function name(): string
    {
        return $_ENV['DB_NAME_TEST'] ?? 'spa_db_test';
    }

    /**
     * Devuelve la conexión a la base de pruebas, creándola y
     * cargando el esquema la primera vez.
     *
     * @return PDO|null Null si MySQL no está disponible.
     */
    public static function connection(): ?PDO
    {
        if (self::$prepared) {
            return self::$connection;
        }

        self::$prepared = true;

        try {

            self::$connection = self::prepare();

        } catch (PDOException $e) {

            self::$unavailableReason = $e->getMessage();

            self::$connection = null;
        }

        return self::$connection;
    }

    /**
     * Motivo por el que la base no está disponible, para el mensaje
     * de omisión de las pruebas.
     */
    public static function unavailableReason(): string
    {
        return self::$unavailableReason ?? 'motivo desconocido';
    }

    /**
     * Crea la base, carga el esquema y siembra los roles.
     *
     * @throws PDOException Si no se puede conectar con MySQL.
     */
    private static function prepare(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $user = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';

        $name = self::name();

        $server = new PDO(
            "mysql:host={$host}",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]
        );

        $server->exec("DROP DATABASE IF EXISTS `{$name}`");

        $server->exec(
            "CREATE DATABASE `{$name}`
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_unicode_ci"
        );

        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        foreach (self::schemaStatements() as $statement) {
            $pdo->exec($statement);
        }

        return $pdo;
    }

    /**
     * Lee database/schema.sql y devuelve solo las sentencias de
     * creación de tablas e índices.
     *
     * Se descartan DROP DATABASE, CREATE DATABASE y USE: la base de
     * pruebas ya está creada y seleccionada, y ejecutarlas apuntaría
     * a spa_db, que es la de desarrollo.
     *
     * @return array<string>
     */
    private static function schemaStatements(): array
    {
        $path = dirname(__DIR__, 2) . '/database/schema.sql';

        $sql = file_get_contents($path);

        // Quita los comentarios de línea para no confundir el troceado.
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);

        $statements = [];

        foreach (explode(';', $sql) as $statement) {

            $statement = trim($statement);

            if ($statement === '') {
                continue;
            }

            if (preg_match('/^(DROP\s+DATABASE|CREATE\s+DATABASE|USE)\b/i', $statement)) {
                continue;
            }

            $statements[] = $statement;
        }

        return $statements;
    }

    /**
     * Vacía todas las tablas y vuelve a sembrar los roles.
     *
     * Se usa entre pruebas en lugar de una transacción envolvente
     * porque algunos servicios abren su propia transacción y MySQL
     * no admite anidarlas.
     */
    public static function reset(PDO $pdo): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $tables = $pdo
            ->query('SHOW TABLES')
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        self::seedRoles($pdo);
    }

    /**
     * Siembra los roles con los identificadores que espera
     * App\Constants\Roles.
     */
    private static function seedRoles(PDO $pdo): void
    {
        $pdo->exec(
            "INSERT INTO roles (id_rol, nombre) VALUES
                (1, 'Administrador'),
                (2, 'Cliente'),
                (3, 'Empleado')"
        );
    }
}
