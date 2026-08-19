<?php

namespace App\Services;

use PDO;
use Throwable;
use App\Constants\Roles;
use App\Repositories\UserRepository;
use App\Repositories\ClientRepository;
use App\Validators\ClientValidator;
use App\Exceptions\ConflictException;

/**
 * Servicio para manejar la lógica de negocio relacionada con los clientes.
 * 
 * Responsabilidades:
 * - Registrar nuevos clientes.
 * 
 * Esta clase NO contiene lógica de acceso a datos.
 * ---------------------------------------------------------
 */

class ClientService
{
    public function __construct(
        private PDO $db,
        private UserRepository $userRepository,
        private ClientRepository $clientRepository
    ) {}

    /**
     * Registra un nuevo cliente.
     *
     * @param array $data
     *
     * @return int
     */
    public function register(array $data): int
    {
        ClientValidator::validate($data);

        if ($this->userRepository->findByEmail($data['email']) !== null) {
            throw new ConflictException(
                'El correo electrónico ya se encuentra registrado.'
            );
        }

        $this->db->beginTransaction();

        try {

            $userData = $this->prepareUserData($data);

            $userId = $this->userRepository->create($userData);

            $clientData = $this->prepareClientData(
                $userId,
                $data
            );

            $clientId = $this->clientRepository->create(
                $clientData
            );

            $this->db->commit();

            return $clientId;
        } catch (Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Prepara los datos del usuario.
     *
     * @param array $data
     *
     * @return array
     */
    private function prepareUserData(array $data): array
    {
        return [
            'nombre' => trim($data['nombre']),
            'email' => trim($data['email']),
            'password_hash' => password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            ),
            'id_rol' => Roles::CLIENT
        ];
    }

    /**
     * Prepara los datos del cliente.
     *
     * @param int $userId
     * @param array $data
     *
     * @return array
     */
    private function prepareClientData(
        int $userId,
        array $data
    ): array {

        $direccion = trim($data['direccion'] ?? '');
        return [
            'id_usuario' => $userId,
            'telefono' => trim($data['telefono']),
            'direccion' => $direccion === ''
                ? null
                : $direccion
        ];
    }

    /**
     * Obtiene todos los clientes activos.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->clientRepository->findAll();
    }
}
