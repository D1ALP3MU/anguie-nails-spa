<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\AppointmentService;
use App\Responses\Response;


/**
 * Controlador para gestionar las citas.
 *
 * Este controlador proporciona métodos para manejar las solicitudes relacionadas con las citas.
 *
 * Todas las acciones requieren un usuario autenticado, que se recibe
 * como parámetro y se delega al servicio para aplicar las reglas de acceso.
 */
class AppointmentController
{
    public function __construct(
        private AppointmentService $service
    ) {}

    /**
     * Registra una nueva cita.
     *
     * @param Request $request Petición entrante.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function store(Request $request, array $authUser): void
    {
        $id = $this->service->create(
            $request->body(),
            $authUser
        );

        Response::created([
            'id_cita' => $id
        ]);
    }

    /**
     * Obtiene las citas visibles para el usuario autenticado.
     *
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function index(array $authUser): void
    {
        $appointments = $this->service->findAll($authUser);

        Response::success($appointments);
    }

    /**
     * Obtiene una cita por su ID.
     *
     * @param int $id ID de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function show(int $id, array $authUser): void
    {
        $appointment = $this->service->findById(
            $id,
            $authUser
        );

        Response::success($appointment);
    }

    /**
     * Actualiza una cita existente.
     *
     * @param int $id ID de la cita.
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
        $appointment = $this->service->update(
            $id,
            $request->body(),
            $authUser
        );

        Response::success($appointment);
    }

    /**
     * Cancela una cita existente.
     *
     * @param int $id ID de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function delete(int $id, array $authUser): void
    {
        $this->service->delete($id, $authUser);

        Response::success([
            'message' => 'Cita cancelada correctamente.'
        ]);
    }
}
