<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\AdminService;
use App\Responses\Response;

/**
 * Controlador de las cuentas de administración.
 *
 * Todas las acciones exigen rol de administrador, declarado en la
 * tabla de rutas. La baja recibe además el usuario autenticado,
 * porque el servicio necesita saber quién la pide para no dejar
 * que alguien se desactive a sí mismo.
 */
class AdminController
{
    public function __construct(
        private AdminService $service
    ) {}

    /**
     * Obtiene los administradores activos.
     *
     * @return void
     */
    public function index(): void
    {
        Response::success($this->service->findAll());
    }

    /**
     * Da de alta a un administrador.
     *
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function store(Request $request): void
    {
        $id = $this->service->create($request->body());

        Response::created([
            'id_usuario' => $id
        ]);
    }

    /**
     * Da de baja a un administrador.
     *
     * @param int $id ID del usuario.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function delete(int $id, array $authUser): void
    {
        $this->service->delete($id, $authUser);

        Response::success([
            'message' => 'Administrador desactivado correctamente.'
        ]);
    }
}
