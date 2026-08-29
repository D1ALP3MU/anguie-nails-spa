<?php

namespace App\Controllers;

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
     * @return void
     */
    public function store(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $id = $this->service->create($data);

        Response::created([
            'id_servicio' => $id
        ]);
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param int $id ID del servicio.
     *
     * @return void
     */
    public function update(int $id): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $service = $this->service->update(
            $id,
            $data
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
