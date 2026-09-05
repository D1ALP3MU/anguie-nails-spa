<?php

namespace App\Repositories;

use PDO;
use PDOException;
use App\Exceptions\ConflictException;

/**
 * Repositorio para manejar las operaciones de la entidad "Cita" en la base de datos.
 *
 * Este repositorio proporciona métodos para realizar operaciones CRUD (Crear, Leer, Actualizar, Eliminar) en la tabla de citas.
 *
 */

class AppointmentRepository
{
    /**
     * Código de MySQL para una entrada duplicada.
     */
    private const MYSQL_DUPLICATE_ENTRY = 1062;

    /**
     * Índice que reserva el horario de un profesional.
     */
    private const SLOT_INDEX = 'uq_citas_agenda';

    /**
     * Proyección común a todas las consultas de lectura.
     */
    private const BASE_SELECT = "
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
    ";

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
        $sql = self::BASE_SELECT . "
            ORDER BY c.fecha ASC, c.hora ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las citas de un cliente.
     *
     * @param int $clientId ID del cliente.
     *
     * @return array
     */
    public function findAllByClient(int $clientId): array
    {
        $sql = self::BASE_SELECT . "
            WHERE c.id_cliente = :id_cliente
            ORDER BY c.fecha ASC, c.hora ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_cliente' => $clientId
        ]);

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
        $sql = self::BASE_SELECT . "
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
     * Indica si el profesional ya tiene una cita que se cruza
     * con el intervalo solicitado.
     *
     * Dos citas se cruzan cuando una empieza antes de que
     * la otra termine, considerando la duración de cada servicio.
     * Las citas canceladas liberan el horario.
     *
     * @param int $professionalId ID del profesional.
     * @param string $date Fecha en formato Y-m-d.
     * @param string $start Hora de inicio en formato H:i:s.
     * @param string $end Hora de fin en formato H:i:s.
     * @param int|null $excludeAppointmentId Cita a excluir (al actualizar).
     *
     * @return bool
     */
    public function hasOverlap(
        int $professionalId,
        string $date,
        string $start,
        string $end,
        ?int $excludeAppointmentId = null
    ): bool {
        $parameters = [
            'id_profesional' => $professionalId,
            'fecha'          => $date,
            'inicio'         => $start,
            'fin'            => $end
        ];

        $exclusion = '';

        if ($excludeAppointmentId !== null) {
            $exclusion = ' AND c.id_cita <> :excluir';

            $parameters['excluir'] = $excludeAppointmentId;
        }

        $sql = "
            SELECT COUNT(*)
            FROM citas c
            INNER JOIN servicios s
                ON s.id_servicio = c.id_servicio
            WHERE c.id_profesional = :id_profesional
                AND c.fecha = :fecha
                AND c.estado <> 'cancelada'
                AND c.hora < :fin
                AND ADDTIME(
                        c.hora,
                        SEC_TO_TIME(s.duracion * 60)
                    ) > :inicio
                $exclusion
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($parameters);

        return (int) $stmt->fetchColumn() > 0;
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

        $this->executeGuardingSlot($stmt, [
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

        $this->executeGuardingSlot($stmt, [
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
     * Ejecuta una escritura traduciendo la violación del índice
     * uq_citas_agenda a un conflicto de dominio.
     *
     * AppointmentService ya comprueba el cruce de horarios antes de
     * llegar aquí; el índice es la red de seguridad para dos peticiones
     * simultáneas que pasen esa comprobación a la vez. Sin esta
     * traducción, esa carrera devolvería un 500 en lugar de un 409.
     *
     * @param \PDOStatement $stmt Sentencia preparada.
     * @param array $parameters Parámetros de la sentencia.
     *
     * @return void
     *
     * @throws ConflictException
     */
    private function executeGuardingSlot(
        \PDOStatement $stmt,
        array $parameters
    ): void {
        try {

            $stmt->execute($parameters);

        } catch (PDOException $e) {

            if ($this->isSlotCollision($e)) {
                throw new ConflictException(
                    'El profesional ya tiene una cita agendada en ese horario.'
                );
            }

            throw $e;
        }
    }

    /**
     * Distingue el choque de horario de cualquier otra violación
     * de integridad.
     *
     * El SQLSTATE 23000 cubre también las claves foráneas, así que
     * mirarlo a secas haría que un id_servicio inexistente se
     * reportara como "horario ocupado". Se exige además el código
     * de entrada duplicada de MySQL y el nombre del índice.
     *
     * @param PDOException $e Excepción lanzada por PDO.
     *
     * @return bool
     */
    private function isSlotCollision(PDOException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;

        // 1062: Duplicate entry. 1452 sería una clave foránea.
        if ($driverCode !== self::MYSQL_DUPLICATE_ENTRY) {
            return false;
        }

        return str_contains(
            $e->getMessage(),
            self::SLOT_INDEX
        );
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
