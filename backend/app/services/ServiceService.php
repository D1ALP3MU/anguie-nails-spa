<?php

namespace App\Services;

use App\Repositories\ServiceRepository;

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
        $services = $this->repository->findAll();

        return [
            'success' => true,
            'status' => 200,
            'data' => $services
        ];
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
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Servicio no encontrado.'
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'data' => $service
        ];
    }
}
