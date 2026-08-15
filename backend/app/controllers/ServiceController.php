<?php

namespace App\Controllers;

use App\Services\ServiceService;
use App\Responses\Response;

class ServiceController
{

    public function __construct(
        private ServiceService $service
    ) {}

    public function index(): void
    {

        $services = $this->service->getServices();

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
        $service = $this->service->getServiceById($id);

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

        $this->service->update($id, $data);

        Response::success([
            'message' => 'Servicio actualizado correctamente.'
        ]);
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
