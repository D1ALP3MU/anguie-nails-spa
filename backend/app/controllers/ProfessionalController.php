<?php

namespace App\Controllers;

use App\Services\ProfessionalService;
use App\Responses\Response;

class ProfessionalController
{
    public function __construct(
        private ProfessionalService $service
    ) {}

    /**
     * Obtiene todos los profesionales activos.
     *
     * @return void
     */
    public function index(): void
    {
        $professionals = $this->service->findAll();

        Response::success($professionals);
    }

    /**
     * Obtiene un profesional por su ID.
     *
     * @param int $id ID del profesional.
     *
     * @return void
     */
    public function show(int $id): void
    {
        $professional = $this->service->findById($id);

        Response::success($professional);
    }
}
