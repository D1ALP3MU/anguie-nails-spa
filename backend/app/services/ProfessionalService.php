<?php

namespace App\Services;

use App\Repositories\ProfessionalRepository;
use App\Validators\ProfessionalValidator;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Profesionales
 * Archivo  : ProfessionalService.php
 *
 * Descripción:
 * Lógica de negocio del equipo del salón.
 *
 * Esta clase NO contiene consultas SQL ni genera respuestas HTTP.
 * ---------------------------------------------------------
 */
class ProfessionalService
{
    public function __construct(
        private ProfessionalRepository $repository
    ) {}

    /**
     * Obtiene todos los profesionales activos.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Obtiene un profesional por su ID.
     *
     * @param int $id ID del profesional.
     *
     * @return array
     */
    public function findById(int $id): array
    {
        $professional = $this->repository->findById($id);

        if ($professional === null) {
            throw new NotFoundException(
                'Profesional no encontrado.'
            );
        }

        return $professional;
    }

    /**
     * Registra un nuevo profesional.
     *
     * @param array $data Datos del profesional.
     *
     * @return int ID del profesional creado.
     */
    public function create(array $data): int
    {
        ProfessionalValidator::validate($data);

        return $this->repository->create(
            $this->prepare($data)
        );
    }

    /**
     * Actualiza un profesional existente.
     *
     * @param int $id ID del profesional.
     * @param array $data Datos a actualizar.
     *
     * @return array Profesional actualizado.
     */
    public function update(int $id, array $data): array
    {
        $this->findExisting($id);

        ProfessionalValidator::validate($data);

        $this->repository->update(
            $id,
            $this->prepare($data)
        );

        return $this->findById($id);
    }

    /**
     * Da de baja a un profesional.
     *
     * @param int $id ID del profesional.
     *
     * @return void
     *
     * @throws ConflictException Si ya está inactivo o tiene agenda.
     */
    public function delete(int $id): void
    {
        $professional = $this->findExisting($id);

        if (!(bool) $professional['activo']) {
            throw new ConflictException(
                'El profesional ya se encuentra desactivado.'
            );
        }

        // Dar de baja a alguien con clientas esperándola dejaría
        // esas citas huérfanas. Primero hay que reprogramarlas
        // o cancelarlas.
        $pending = $this->repository->countUpcomingAppointments($id);

        if ($pending > 0) {
            throw new ConflictException(
                'No es posible dar de baja al profesional: tiene '
                . $pending
                . ($pending === 1 ? ' cita pendiente.' : ' citas pendientes.')
                . ' Reprográmalas o cancélalas primero.'
            );
        }

        $this->repository->deactivate($id);
    }

    /**
     * Normaliza los datos antes de persistirlos.
     *
     * Los campos opcionales vacíos se guardan como NULL en lugar
     * de como cadena vacía, para que la lectura sea uniforme.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return array
     */
    private function prepare(array $data): array
    {
        $specialty = trim($data['especialidad'] ?? '');
        $phone = trim($data['telefono'] ?? '');

        return [
            'nombre' => trim($data['nombre']),
            'especialidad' => $specialty === '' ? null : $specialty,
            'telefono' => $phone === '' ? null : $phone
        ];
    }

    /**
     * Obtiene un profesional sin filtrar por estado.
     *
     * Se usa antes de modificar o dar de baja: uno inactivo sigue
     * existiendo y debe poder distinguirse de uno inexistente.
     *
     * @param int $id ID del profesional.
     *
     * @return array
     *
     * @throws NotFoundException
     */
    private function findExisting(int $id): array
    {
        $professional = $this->repository
            ->findByIdIncludingInactive($id);

        if ($professional === null) {
            throw new NotFoundException(
                'Profesional no encontrado.'
            );
        }

        return $professional;
    }
}
