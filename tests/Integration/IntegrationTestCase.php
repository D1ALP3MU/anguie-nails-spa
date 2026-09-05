<?php

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use App\Constants\Roles;

/**
 * Base de las pruebas que sí tocan MySQL.
 *
 * Si no hay servidor disponible las pruebas se omiten en lugar de
 * fallar, para que la suite unitaria siga corriendo en cualquier
 * máquina sin configuración previa.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        $connection = TestDatabase::connection();

        if ($connection === null) {
            $this->markTestSkipped(
                'MySQL no disponible: ' . TestDatabase::unavailableReason()
            );
        }

        $this->db = $connection;

        TestDatabase::reset($this->db);
    }

    /*
    |--------------------------------------------------------------------------
    | Datos de apoyo
    |--------------------------------------------------------------------------
    */

    protected function createUser(
        string $email = 'ana@example.com',
        int $role = Roles::CLIENT,
        string $name = 'Ana'
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, id_rol)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            $name,
            $email,
            password_hash('Password123', PASSWORD_DEFAULT),
            $role
        ]);

        return (int) $this->db->lastInsertId();
    }

    protected function createClient(
        string $email = 'ana@example.com',
        string $name = 'Ana'
    ): int {
        $userId = $this->createUser($email, Roles::CLIENT, $name);

        $stmt = $this->db->prepare(
            'INSERT INTO clientes (id_usuario, telefono) VALUES (?, ?)'
        );

        $stmt->execute([$userId, '3001112233']);

        return (int) $this->db->lastInsertId();
    }

    protected function createService(
        int $duration = 60,
        string $name = 'Manicura'
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO servicios (nombre, duracion, precio) VALUES (?, ?, ?)'
        );

        $stmt->execute([$name, $duration, 50000]);

        return (int) $this->db->lastInsertId();
    }

    protected function createProfessional(string $name = 'Laura'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO profesionales (nombre, especialidad) VALUES (?, ?)'
        );

        $stmt->execute([$name, 'Manicura']);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return int ID de la cita creada.
     */
    protected function createAppointment(
        int $clientId,
        int $serviceId,
        int $professionalId,
        string $date,
        string $time,
        string $status = 'pendiente'
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO citas
                (id_cliente, id_servicio, id_profesional, fecha, hora, estado)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $clientId,
            $serviceId,
            $professionalId,
            $date,
            $time,
            $status
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cuenta las filas de una tabla.
     */
    protected function countRows(string $table): int
    {
        return (int) $this->db
            ->query("SELECT COUNT(*) FROM `{$table}`")
            ->fetchColumn();
    }

    /**
     * Fecha futura en formato Y-m-d.
     */
    protected function futureDate(string $modifier = '+30 days'): string
    {
        return date('Y-m-d', strtotime($modifier));
    }
}
