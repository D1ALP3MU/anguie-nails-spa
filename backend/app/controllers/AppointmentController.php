<?php

namespace App\Controllers;

use App\Services\AppointmentService;
use App\Responses\Response;


/**
 * Controlador para gestionar las citas.
 *
 * Este controlador proporciona métodos para manejar las solicitudes relacionadas con las citas.
 */
class AppointmentController
{
    public function __construct(
        private AppointmentService $service
    ) {}

    /**
     * Registra una nueva cita.
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
            'id_cita' => $id
        ]);
    }

    /**
     * Obtiene todas las citas.
     *
     * @return void
     */
    public function index(): void
    {
        $appointments = $this->service->findAll();

        Response::success($appointments);
    }

    /**
     * Obtiene una cita por su ID.
     *
     * @param int $id ID de la cita.
     *
     * @return void
     */
    public function show(int $id): void
    {
        $appointment = $this->service->findById($id);

        Response::success($appointment);
    }

    /**
     * Actualiza una cita existente.
     *
     * @param int $id ID de la cita.
     *
     * @return void
     */
    public function update(int $id): void
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $appointment = $this->service->update(
            $id,
            $data
        );

        Response::success($appointment);
    }

    /**
     * Cancela una cita existente.
     *
     * @param int $id ID de la cita.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $this->service->delete($id);

        Response::success([
            'message' => 'Cita cancelada correctamente.'
        ]);
    }
}
