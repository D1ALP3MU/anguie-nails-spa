<?php

namespace App\Services;

use App\Repositories\ProfessionalRepository;
use App\Exceptions\NotFoundException;

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
}
