<?php

namespace App\Services;

use App\Repositories\AppointmentRepository;
use App\Validators\AppointmentValidator;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;


/**
 * Servicio para gestionar las citas.
 *
 * Este servicio proporciona métodos para registrar, obtener, actualizar y eliminar citas.
 */
class AppointmentService
{
    public function __construct(
        private AppointmentRepository $appointmentRepository
    ) {}

    /**
     * Registra una nueva cita.
     *
     * @param array $data Datos de la cita.
     *
     * @return int ID de la cita creada.
     */
    public function create(array $data): int
    {
        AppointmentValidator::validate($data);

        return $this->appointmentRepository->create($data);
    }

    /**
     * Obtiene todas las citas.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->appointmentRepository->findAll();
    }

    /**
     * Obtiene una cita por su ID.
     *
     * @param int $id ID de la cita.
     *
     * @return array
     */
    public function findById(int $id): array
    {
        $appointment = $this->appointmentRepository
            ->findById($id);

        if ($appointment === null) {
            throw new NotFoundException(
                'Cita no encontrada.'
            );
        }

        return $appointment;
    }

    /**
     * Actualiza una cita existente.
     *
     * @param int $id ID de la cita.
     * @param array $data Datos de la cita.
     *
     * @return array Cita actualizada.
     */
    public function update(int $id, array $data): array
    {
        AppointmentValidator::validate($data);

        $appointment = $this->appointmentRepository
            ->findById($id);

        if ($appointment === null) {
            throw new NotFoundException(
                'Cita no encontrada.'
            );
        }

        $this->appointmentRepository->update(
            $id,
            $data
        );

        return $this->findById($id);
    }

    /**
     * Cancela una cita existente.
     *
     * @param int $id ID de la cita.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $appointment = $this->appointmentRepository
            ->findById($id);

        if ($appointment === null) {
            throw new NotFoundException(
                'Cita no encontrada.'
            );
        }

        if ($appointment['estado'] === 'cancelada') {
            throw new ConflictException(
                'La cita ya se encuentra cancelada.'
            );
        }

        $this->appointmentRepository->delete($id);
    }
}
