<?php

namespace App\Controllers;

use App\Core\Request;
use App\Services\PaymentService;
use App\Responses\Response;

/**
 * Controlador de cobros.
 *
 * Esta clase NO contiene lógica de negocio.
 */
class PaymentController
{
    public function __construct(
        private PaymentService $service
    ) {}

    /**
     * Lista los cobros del salón.
     *
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function index(Request $request): void
    {
        $payments = $this->service->findAll([
            'desde' => $request->query('desde'),
            'hasta' => $request->query('hasta'),
            'estado' => $request->query('estado'),
            'metodo' => $request->query('metodo')
        ]);

        Response::success($payments);
    }

    /**
     * Resumen de facturación del periodo.
     *
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    public function summary(Request $request): void
    {
        Response::success(
            $this->service->summary([
                'desde' => $request->query('desde'),
                'hasta' => $request->query('hasta')
            ])
        );
    }

    /**
     * Obtiene el cobro vigente de una cita.
     *
     * Devuelve null cuando la cita todavía no se ha cobrado: no es
     * un error, es el estado normal de una cita recién agendada.
     *
     * @param int $id ID de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function show(int $id, array $authUser): void
    {
        Response::success(
            $this->service->findByAppointment($id, $authUser)
        );
    }

    /**
     * Registra el cobro de una cita.
     *
     * @param int $id ID de la cita.
     * @param Request $request Petición entrante.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     */
    public function store(
        int $id,
        Request $request,
        array $authUser
    ): void {
        $paymentId = $this->service->create(
            $id,
            $request->body(),
            $authUser
        );

        Response::created([
            'id_pago' => $paymentId
        ]);
    }

    /**
     * Confirma un cobro pendiente.
     *
     * @param int $id ID del cobro.
     *
     * @return void
     */
    public function confirm(int $id): void
    {
        Response::success(
            $this->service->confirm($id)
        );
    }

    /**
     * Anula un cobro.
     *
     * @param int $id ID del cobro.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $this->service->void($id);

        Response::success([
            'message' => 'Cobro anulado correctamente.'
        ]);
    }
}
