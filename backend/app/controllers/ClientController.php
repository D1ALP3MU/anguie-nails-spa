<?php

namespace App\Controllers;

use App\Services\ClientService;
use App\Responses\Response;

class ClientController
{
    public function __construct(
        private ClientService $service
    ) {}

    /**
     * Registra un nuevo cliente.
     *
     * @return void
     */
    public function store(): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $id = $this->service->register($data);

        Response::created([
            'id_cliente' => $id
        ]);
    }

    /**
     * Obtiene todos los clientes.
     *
     * @return void
     */
    public function index(): void
    {
        $clients = $this->service->findAll();

        Response::json([
            'success' => true,
            'data' => $clients
        ], 200);
    }
}
