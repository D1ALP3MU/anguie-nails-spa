<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use App\Validators\ServiceValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\ConflictException;
use Throwable;

#use PDO;

class ServiceService
{

    public function __construct(
        private ServiceRepository $repository
    ) {}

    /**
     * Obtiene todos los servicios activos.
     *
     * @return array
     */
    public function getServices(): array
    {

        return $this->repository->findAll();
    }

    /**
     * Obtiene un servicio por su ID.
     *
     * @param int $id
     *
     * @return array
     */
    public function getServiceById(int $id): array
    {
        $service = $this->repository->findById($id);

        if ($service === null) {
            throw new NotFoundException(
                'Servicio no encontrado.'
            );
        }

        return $service;
    }

    /**
     * Crea un nuevo servicio.
     *
     * @param array $data Datos del servicio.
     *
     * @return int ID del servicio creado.
     *
     * @throws ValidationException
     */
    public function create(array $data): int
    {

        ServiceValidator::validate($data);

        $serviceData = $this->prepareServiceData($data);

        return $this->repository->create($serviceData);
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param int $id ID del servicio.
     * @param array $data Datos del servicio.
     *
     * @return void
     */
    public function update(
        int $id,
        array $data
    ): void {
        $this->findExistingService($id);

        ServiceValidator::validate($data);

        $serviceData = $this->prepareServiceData($data);

        $this->repository->update(
            $id,
            $serviceData
        );
    }

    /**
     * Desactiva un servicio.
     *
     * @param int $id ID del servicio.
     *
     * @return void
     */
    public function delete(int $id): void
    {
        $service = $this->findExistingService($id);

        if (!$service['activo']) {
            throw new ConflictException(
                'El servicio ya se encuentra desactivado.'
            );
        }

        $this->repository->delete($id);
    }

    /**
     * Normaliza y prepara los datos de un servicio antes de persistirlos.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return array Datos normalizados.
     */
    private function prepareServiceData(array $data): array
    {
        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        return [
            'nombre' => $nombre,
            'descripcion' => $descripcion === ''
                ? null
                : $descripcion,
            'duracion' => (int) $data['duracion'],
            'precio' => $data['precio']
        ];
    }

    /**
     * Obtiene un servicio existente por su ID.
     *
     * @param int $id
     *
     * @return array|null
     */
    private function findExistingService(int $id): ?array
    {
        $service = $this->repository->findById($id);

        if ($service === null) {
            throw new NotFoundException(
                'Servicio no encontrado.'
            );
        }

        return $service;
    }

    /**
     * Ejecuta una operación del repositorio capturando posibles excepciones.
     *
     * @param callable $operation Operación a ejecutar.
     *
     * @return mixed
     *
     * @throws Throwable
     */
    private function executeRepositoryOperation(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
