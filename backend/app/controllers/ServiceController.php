<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\ServiceService;
use App\Responses\Response;

class ServiceController
{
    public function __construct(
        private ServiceService $service
    ) {}

    /**
     * Obtiene todos los servicios activos.
     *
     * @return void
     */
    public function index(): void
    {
        $services = $this->service->findAll();

        Response::success($services);
    }

    /**
     * Obtiene un servicio por su ID.
     *
     * @param int $id
     *
     * @return void
     */
    public function show(int $id): void
    {
        $service = $this->service->findById($id);

        Response::success($service);
    }

    /**
     * Crea un nuevo servicio.
     *
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function store(Request $request): void
    {
        $id = $this->service->create($request->body());

        Response::created([
            'id_servicio' => $id
        ]);
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param int $id ID del servicio.
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function update(int $id, Request $request): void
    {
        $service = $this->service->update(
            $id,
            $request->body()
        );

        Response::success($service);
    }

    /**
     * Desactiva un servicio.
     *
     * @param int $id ID del servicio.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $this->service->delete($id);

        Response::success([
            'message' => 'Servicio desactivado correctamente.'
        ]);
    }
}
