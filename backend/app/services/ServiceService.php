<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use App\Validators\ServiceValidator;
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

    /**
     * Crea un nuevo servicio.
     *
     * @param array $data Datos del servicio.
     *
     * @return array
     */
    public function create(array $data): array
    {

        $validation = ServiceValidator::validateCreate($data);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validation['errors']
            ];
        }

        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        $serviceData = [
            'nombre' => $nombre,
            'descripcion' => empty($descripcion)
                ? null
                : $descripcion,
            'duracion' => (int) $data['duracion'],
            'precio' => $data['precio']
        ];

        try {

            $serviceId = $this->repository->create($serviceData);

            return [
                'success' => true,
                'status' => 201,
                'message' => 'Servicio creado correctamente.',
                'data' => [
                    'id_servicio' => $serviceId
                ]
            ];
        } catch (Throwable $e) {

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Ocurrió un error al crear el servicio.'
            ];
        }
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param int $id ID del servicio.
     * @param array $data Datos del servicio.
     *
     * @return array
     */
    public function update(
        int $id,
        array $data
    ): array {
        $service = $this->repository->findById($id);

        if ($service === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Servicio no encontrado.'
            ];
        }

        $validation = ServiceValidator::validateCreate($data);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validation['errors']
            ];
        }

        $nombre = trim($data['nombre'] ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        $serviceData = [
            'nombre' => $nombre,
            'descripcion' => empty($descripcion)
                ? null
                : $descripcion,
            'duracion' => (int) $data['duracion'],
            'precio' => $data['precio']
        ];

        try {

            $this->repository->update(
                $id,
                $serviceData
            );

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Servicio actualizado correctamente.'
            ];
        } catch (Throwable $e) {

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Ocurrió un error al actualizar el servicio.'
            ];
        }
    }

    /**
     * Desactiva un servicio.
     *
     * @param int $id ID del servicio.
     *
     * @return array
     */
    public function delete(int $id): array
    {
        $service = $this->repository->findById($id);

        if ($service === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Servicio no encontrado.'
            ];
        }

        if (!$service['activo']) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'El servicio ya se encuentra desactivado.'
            ];
        }

        try {

            $this->repository->delete($id);

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Servicio desactivado correctamente.'
            ];
        } catch (Throwable $e) {

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Ocurrió un error al desactivar el servicio.'
            ];
        }
    }
}
