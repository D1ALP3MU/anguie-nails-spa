<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use App\Validators\ServiceValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ConflictException;

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
    public function findAll(): array
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
    public function findById(int $id): array
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
     * @return array Servicio actualizado.
     */
    public function update(
        int $id,
        array $data
    ): array {
        $this->findExistingService($id);

        ServiceValidator::validate($data);

        $serviceData = $this->prepareServiceData($data);

        $this->repository->update(
            $id,
            $serviceData
        );

        return $this->findById($id);
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
     * Normaliza y prepara los datos antes de persistirlos.
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
     * @return array
     */
    private function findExistingService(int $id): array
    {
        $service = $this->repository->findById($id);

        if ($service === null) {
            throw new NotFoundException(
                'Servicio no encontrado.'
            );
        }

        return $service;
    }
}
