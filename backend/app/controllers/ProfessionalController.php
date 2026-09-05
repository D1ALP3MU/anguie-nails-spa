<?php

namespace App\Controllers;

use App\Core\Request;
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
    /**
     * Registra un nuevo profesional.
     *
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function store(Request $request): void
    {
        $id = $this->service->create($request->body());

        Response::created([
            'id_profesional' => $id
        ]);
    }

    /**
     * Actualiza un profesional existente.
     *
     * @param int $id ID del profesional.
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function update(int $id, Request $request): void
    {
        $professional = $this->service->update(
            $id,
            $request->body()
        );

        Response::success($professional);
    }

    /**
     * Da de baja a un profesional.
     *
     * @param int $id ID del profesional.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $this->service->delete($id);

        Response::success([
            'message' => 'Profesional dado de baja correctamente.'
        ]);
    }
}