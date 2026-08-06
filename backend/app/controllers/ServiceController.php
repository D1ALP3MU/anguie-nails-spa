<?php

namespace App\Controllers;

use App\Services\ServiceService;
use App\Responses\Response;

class ServiceController {

    public function __construct(
        private ServiceService $service
    ){}

    public function index(): void {

        $result = $this->service->getServices();

        Response::json(
            $result,
            $result['status']
        );
        
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
        $result = $this->service->getServiceById($id);

        Response::json(
            $result,
            $result['status']
        );
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

        $result = $this->service->create($data);

        Response::json(
            $result,
            $result['status']
        );
    }
}