<?php

namespace App\Services;

use DateTimeImmutable;
use App\Constants\Roles;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClientRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ServiceRepository;
use App\Validators\AppointmentValidator;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;


/**
 * Servicio para gestionar las citas.
 *
 * Este servicio proporciona métodos para registrar, obtener, actualizar y eliminar citas.
 *
 * Reglas de acceso:
 * - Un administrador puede operar sobre cualquier cita.
 * - Un cliente solo puede ver y modificar sus propias citas.
 *
 * Cuando un cliente intenta acceder a una cita ajena se responde
 * "no encontrada" en lugar de "prohibido", para no revelar qué
 * identificadores existen en la base de datos.
 */
class AppointmentService
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private ClientRepository $clientRepository,
        private ServiceRepository $serviceRepository,
        private ProfessionalRepository $professionalRepository
    ) {}

    /**
     * Registra una nueva cita.
     *
     * @param array $data Datos de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return int ID de la cita creada.
     */
    public function create(array $data, array $authUser): int
    {
        // Un cliente solo puede reservar para sí mismo:
        // el id_cliente se toma del token, nunca del cuerpo de la petición.
        if (!$this->isAdmin($authUser)) {
            $data['id_cliente'] = $this->resolveClientId($authUser);
        }

        AppointmentValidator::validate($data);

        $this->assertBookable($data);

        return $this->appointmentRepository->create($data);
    }

    /**
     * Obtiene las citas visibles para el usuario autenticado.
     *
     * @param array $authUser Usuario autenticado.
     *
     * @return array
     */
    public function findAll(array $authUser): array
    {
        if ($this->isAdmin($authUser)) {
            return $this->appointmentRepository->findAll();
        }

        return $this->appointmentRepository->findAllByClient(
            $this->resolveClientId($authUser)
        );
    }

    /**
     * Obtiene una cita por su ID.
     *
     * @param int $id ID de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return array
     */
    public function findById(int $id, array $authUser): array
    {
        $appointment = $this->getAppointmentOrFail($id);

        $this->assertOwnership($appointment, $authUser);

        return $appointment;
    }

    /**
     * Actualiza una cita existente.
     *
     * @param int $id ID de la cita.
     * @param array $data Datos de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return array Cita actualizada.
     */
    public function update(
        int $id,
        array $data,
        array $authUser
    ): array {
        $appointment = $this->getAppointmentOrFail($id);

        $this->assertOwnership($appointment, $authUser);

        // Un cliente no puede reasignar su cita a otro cliente.
        if (!$this->isAdmin($authUser)) {
            $data['id_cliente'] = (int) $appointment['id_cliente'];
        }

        AppointmentValidator::validate($data);

        // Al reprogramar, la propia cita no cuenta como conflicto.
        $this->assertBookable($data, $id);

        $this->appointmentRepository->update(
            $id,
            $data
        );

        return $this->getAppointmentOrFail($id);
    }

    /**
     * Cancela una cita existente.
     *
     * @param int $id ID de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function delete(int $id, array $authUser): void
    {
        $appointment = $this->getAppointmentOrFail($id);

        $this->assertOwnership($appointment, $authUser);

        if ($appointment['estado'] === 'cancelada') {
            throw new ConflictException(
                'La cita ya se encuentra cancelada.'
            );
        }

        $this->appointmentRepository->delete($id);
    }

    /**
     * Verifica que la cita se pueda agendar.
     *
     * Comprueba que el servicio y el profesional existan y estén
     * activos, que el horario no esté en el pasado y que el
     * profesional no tenga otra cita cruzada en ese intervalo.
     *
     * @param array $data Datos ya validados en formato.
     * @param int|null $excludeId Cita a excluir del cruce (al reprogramar).
     *
     * @return void
     *
     * @throws ValidationException Datos que el cliente puede corregir.
     * @throws ConflictException El horario ya está ocupado.
     */
    private function assertBookable(
        array $data,
        ?int $excludeId = null
    ): void {
        $service = $this->serviceRepository->findById(
            (int) $data['id_servicio']
        );

        if ($service === null || !(bool) $service['activo']) {
            throw new ValidationException([
                'id_servicio' => 'El servicio seleccionado no está disponible.'
            ]);
        }

        $professional = $this->professionalRepository->findById(
            (int) $data['id_profesional']
        );

        if ($professional === null || !(bool) $professional['activo']) {
            throw new ValidationException([
                'id_profesional' => 'El profesional seleccionado no está disponible.'
            ]);
        }

        $status = $data['estado'] ?? 'pendiente';

        // Una cita cancelada o completada no ocupa agenda ni
        // necesita estar en el futuro: permite cerrar el historial.
        if (in_array($status, ['cancelada', 'completada'], true)) {
            return;
        }

        $start = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $data['fecha'] . ' ' . $data['hora']
        );

        if ($start < new DateTimeImmutable()) {
            throw new ValidationException([
                'fecha' => 'No es posible agendar una cita en un horario que ya pasó.'
            ]);
        }

        $end = $start->modify(
            '+' . (int) $service['duracion'] . ' minutes'
        );

        // Si el servicio cruza la medianoche se compara hasta el
        // final del día: la agenda se organiza por fecha.
        $endTime = $end->format('Y-m-d') === $start->format('Y-m-d')
            ? $end->format('H:i:s')
            : '23:59:59';

        $hasOverlap = $this->appointmentRepository->hasOverlap(
            (int) $data['id_profesional'],
            $start->format('Y-m-d'),
            $start->format('H:i:s'),
            $endTime,
            $excludeId
        );

        if ($hasOverlap) {
            throw new ConflictException(
                'El profesional ya tiene una cita agendada en ese horario.'
            );
        }
    }

    /**
     * Obtiene una cita o lanza una excepción si no existe.
     *
     * @param int $id ID de la cita.
     *
     * @return array
     */
    private function getAppointmentOrFail(int $id): array
    {
        $appointment = $this->appointmentRepository->findById($id);

        if ($appointment === null) {
            throw new NotFoundException(
                'Cita no encontrada.'
            );
        }

        return $appointment;
    }

    /**
     * Indica si el usuario autenticado es administrador.
     *
     * @param array $authUser Usuario autenticado.
     *
     * @return bool
     */
    private function isAdmin(array $authUser): bool
    {
        return (int) ($authUser['id_rol'] ?? 0) === Roles::ADMIN;
    }

    /**
     * Obtiene el ID de cliente asociado al usuario autenticado.
     *
     * @param array $authUser Usuario autenticado.
     *
     * @return int
     *
     * @throws ForbiddenException
     */
    private function resolveClientId(array $authUser): int
    {
        $client = $this->clientRepository->findByUserId(
            (int) ($authUser['id_usuario'] ?? 0)
        );

        if ($client === null) {
            throw new ForbiddenException(
                'El usuario autenticado no tiene un perfil de cliente asociado.'
            );
        }

        return (int) $client['id_cliente'];
    }

    /**
     * Verifica que el usuario autenticado pueda operar sobre la cita.
     *
     * @param array $appointment Cita consultada.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     *
     * @throws NotFoundException
     */
    private function assertOwnership(
        array $appointment,
        array $authUser
    ): void {
        if ($this->isAdmin($authUser)) {
            return;
        }

        if (
            (int) $appointment['id_cliente']
            !== $this->resolveClientId($authUser)
        ) {
            throw new NotFoundException(
                'Cita no encontrada.'
            );
        }
    }
}
