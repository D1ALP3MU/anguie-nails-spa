<?php

namespace App\Controllers;

use App\Core\Request;
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
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function store(Request $request): void
    {
        $id = $this->service->register($request->body());

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

        Response::success($clients);
    }

    /**
     * Obtiene un cliente por su ID.
     *
     * @param int $id ID del cliente.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function show(int $id, array $authUser): void
    {
        $client = $this->service->findById($id, $authUser);

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
     * @param Request $request Petición entrante.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function update(
        int $id,
        Request $request,
        array $authUser
    ): void {
        $client = $this->service->update(
            $id,
            $request->body(),
            $authUser
        );

        Response::success($client);
    }
}
