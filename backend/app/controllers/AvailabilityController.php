<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\AvailabilityService;
use App\Responses\Response;

class AvailabilityController
{
    public function __construct(
        private AvailabilityService $service
    ) {}

    /**
     * Horario del salón: días de apertura, horas y plazo de reserva.
     *
     * @return void
     */
    public function schedule(): void
    {
        Response::success($this->service->schedule());
    }

    /**
     * Horas libres de una profesional para un servicio en un día.
     *
     * @param Request $request Petición con id_profesional, id_servicio
     *                         y fecha en la cadena de consulta.
     *
     * @return void
     */
    public function index(Request $request): void
    {
        $slots = $this->service->freeSlots([
            'id_profesional' => $request->query('id_profesional'),
            'id_servicio' => $request->query('id_servicio'),
            'fecha' => $request->query('fecha')
        ]);

        Response::success($slots);
    }
}
