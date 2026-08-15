<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use App\Validators\ServiceValidator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
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

        $errors = ServiceValidator::validate($data);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $serviceData = $this->prepareServiceData($data);

        return $this->repository->create($serviceData);
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
        $service = $this->findExistingService($id);

        if ($service === null) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Servicio no encontrado.'
            ];
        }

        $validation = ServiceValidator::validate($data);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validation['errors']
            ];
        }

        $serviceData = $this->prepareServiceData($data);

        try {

            $this->executeRepositoryOperation(
                fn() => $this->repository->update(
                    $id,
                    $serviceData
                )
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
        $service = $this->findExistingService($id);

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

            $this->executeRepositoryOperation(
                fn() => $this->repository->delete($id)
            );

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
        return $this->repository->findById($id);
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
