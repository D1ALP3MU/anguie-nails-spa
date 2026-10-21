<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Validators\PaymentValidator;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Pagos
 * Archivo  : PaymentService.php
 *
 * Descripción:
 * Reglas de cobro de las citas.
 *
 * Una cita tiene como mucho un cobro vigente. Anularlo la deja
 * cobrable de nuevo, y el cobro anulado permanece como historial.
 *
 * La pertenencia se delega en AppointmentService: si la cita no es
 * visible para quien pregunta, su cobro tampoco.
 *
 * Esta clase NO contiene consultas SQL ni genera respuestas HTTP.
 * ---------------------------------------------------------
 */
class PaymentService
{
    public function __construct(
        private PaymentRepository $repository,
        private AppointmentService $appointmentService
    ) {}

    /**
     * Obtiene el cobro vigente de una cita.
     *
     * @param int $appointmentId ID de la cita.
     * @param array $authUser Usuario autenticado.
     *
     * @return array|null Null si la cita aún no se ha cobrado.
     */
    public function findByAppointment(
        int $appointmentId,
        array $authUser
    ): ?array {
        // Lanza "no encontrada" si la cita no existe o es ajena.
        $this->appointmentService->findById($appointmentId, $authUser);

        return $this->repository
            ->findActiveByAppointment($appointmentId);
    }

    /**
     * Obtiene los cobros que cumplen los filtros indicados.
     *
     * @param array $filters Filtros de la consulta.
     *
     * @return array
     */
    public function findAll(array $filters = []): array
    {
        return $this->repository->findAllFiltered(
            $this->normalizeFilters($filters)
        );
    }

    /**
     * Resume la facturación de un periodo.
     *
     * @param array $filters Filtros de la consulta.
     *
     * @return array
     */
    public function summary(array $filters = []): array
    {
        return $this->repository->summary(
            $this->normalizeFilters($filters)
        );
    }

    /**
     * Registra el cobro de una cita.
     *
     * @param int $appointmentId ID de la cita.
     * @param array $data Datos del cobro.
     * @param array $authUser Usuario autenticado.
     *
     * @return int ID del cobro creado.
     *
     * @throws ConflictException Si la cita ya está cobrada o cancelada.
     */
    public function create(
        int $appointmentId,
        array $data,
        array $authUser
    ): int {
        $appointment = $this->appointmentService
            ->findById($appointmentId, $authUser);

        // Cobrar una cita cancelada no tiene sentido: no hubo
        // servicio que pagar.
        if ($appointment['estado'] === 'cancelada') {
            throw new ConflictException(
                'No es posible cobrar una cita cancelada.'
            );
        }

        PaymentValidator::validate($data);

        if ($this->repository->findActiveByAppointment($appointmentId)) {
            throw new ConflictException(
                'La cita ya tiene un cobro registrado. Anúlalo antes de volver a cobrar.'
            );
        }

        $data['id_cita'] = $appointmentId;

        return $this->repository->create($data);
    }

    /**
     * Confirma un cobro pendiente.
     *
     * Es el caso de la transferencia anunciada y luego verificada.
     *
     * @param int $id ID del cobro.
     *
     * @return array Cobro actualizado.
     *
     * @throws ConflictException Si el cobro no está pendiente.
     */
    public function confirm(int $id): array
    {
        $payment = $this->getOrFail($id);

        if ($payment['estado'] !== 'pendiente') {
            throw new ConflictException(
                'Solo se puede confirmar un cobro pendiente.'
            );
        }

        $this->repository->updateStatus($id, 'pagado');

        return $this->getOrFail($id);
    }

    /**
     * Anula un cobro y deja la cita cobrable otra vez.
     *
     * El estado resultante distingue qué ocurrió: un cobro que ya
     * había entrado se devuelve, y uno que nunca llegó a entrar se
     * descarta. Esa diferencia importa al cuadrar caja.
     *
     * @param int $id ID del cobro.
     *
     * @return void
     *
     * @throws ConflictException Si ya estaba anulado.
     */
    public function void(int $id): void
    {
        $payment = $this->getOrFail($id);

        if (PaymentRepository::isVoided($payment)) {
            throw new ConflictException(
                'El cobro ya se encuentra anulado.'
            );
        }

        $this->repository->updateStatus(
            $id,
            $payment['estado'] === 'pagado'
                ? 'reembolsado'
                : 'rechazado'
        );
    }

    /**
     * Obtiene un cobro o lanza una excepción si no existe.
     *
     * @param int $id ID del cobro.
     *
     * @return array
     *
     * @throws NotFoundException
     */
    private function getOrFail(int $id): array
    {
        $payment = $this->repository->findById($id);

        if ($payment === null) {
            throw new NotFoundException(
                'Cobro no encontrado.'
            );
        }

        return $payment;
    }

    /**
     * Valida y normaliza los filtros del listado.
     *
     * Un filtro ausente o vacío se descarta; uno con formato inválido
     * es un error, para que una fecha mal escrita no devuelva todo.
     *
     * @param array $filters Filtros recibidos.
     *
     * @return array
     *
     * @throws ValidationException
     */
    private function normalizeFilters(array $filters): array
    {
        $criteria = [];
        $errors = [];

        foreach (['desde', 'hasta'] as $key) {

            $value = trim((string) ($filters[$key] ?? ''));

            if ($value === '') {
                continue;
            }

            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

            if ($date === false || $date->format('Y-m-d') !== $value) {
                $errors[$key] = 'La fecha debe tener el formato YYYY-MM-DD.';
                continue;
            }

            $criteria[$key] = $value;
        }

        if (
            isset($criteria['desde'], $criteria['hasta'])
            && $criteria['desde'] > $criteria['hasta']
        ) {
            $errors['desde'] = 'La fecha inicial no puede ser posterior a la final.';
        }

        $status = trim((string) ($filters['estado'] ?? ''));

        if ($status !== '') {

            $allowed = [...PaymentValidator::INITIAL_STATUSES, 'rechazado', 'reembolsado'];

            if (!in_array($status, $allowed, true)) {
                $errors['estado'] = 'El estado indicado no es válido.';
            } else {
                $criteria['estado'] = $status;
            }
        }

        $method = trim((string) ($filters['metodo'] ?? ''));

        if ($method !== '') {

            if (!in_array($method, PaymentValidator::METHODS, true)) {
                $errors['metodo'] = 'La forma de pago indicada no es válida.';
            } else {
                $criteria['metodo'] = $method;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $criteria;
    }
}
