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

    /**
     * Obtiene un cliente por su ID.
     *
     * @param int $id ID del cliente.
     *
     * @return void
     */
    public function show(int $id): void
    {
        $client = $this->service->findById($id);

        Response::success($client);
    }

    /**
     * Desactiva un cliente existente.
     *
     * @param int $id ID del cliente.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $this->service->delete($id);

        Response::success([
            'message' => 'Cliente eliminado correctamente.'
        ]);
    }

    /**
     * Actualiza un cliente existente.
     *
     * @param int $id ID del cliente.
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
            'message' => 'Cliente actualizado correctamente.'
        ]);
    }
}
