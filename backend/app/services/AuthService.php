<?php

namespace App\Services;

use App\Helpers\PasswordHelper;
use App\Repositories\UserRepository;
use App\Repositories\ClientRepository;
use App\Validators\UserValidator;

use PDO;
use Throwable;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Autenticación
 * Archivo  : AuthService.php
 * Versión  : 1.0.0
 *
 * Descripción:
 * Contiene la lógica de negocio relacionada con la
 * autenticación y registro de usuarios.
 *
 * Responsabilidades:
 * - Registrar usuarios.
 * - Coordinar validaciones.
 * - Coordinar repositorios.
 * - Gestionar transacciones.
 *
 * Esta clase NO genera respuestas HTTP.
 * ---------------------------------------------------------
 */

class AuthService
{
    /**
     * Conexión a la base de datos.
     *
     * @var PDO
     */
    private PDO $connection;

    /**
     * Repositorio de usuarios.
     *
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * Repositorio de clientes.
     *
     * @var ClientRepository
     */
    private ClientRepository $clientRepository;

    /**
     * Constructor.
     *
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;

        $this->userRepository = new UserRepository($connection);
        $this->clientRepository = new ClientRepository($connection);
    }

    /**
     * Registra un nuevo usuario
     * 
     * @param array $data Datos del formulario.
     * 
     * @return array Resultado del proceso.
     */
    public function register(array $data): array
    {
        // 1. Validar datos
        $validation = UserValidator::validateRegister($data);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validation['errors']
            ];
        }

        // 2. Verificar si el correo ya está registrado
        $user = $this->userRepository->findByEmail($data['email']);

        if ($user !== null) {
            return [
                'success' => false,
                'status' => 409,
                'message' => 'El correo electrónico ya está registrado.'
            ];
        }

        // 3. Encriptar la contraseña
        $hashedPassword = PasswordHelper::hash($data['password']);

        $userData = [
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password_hash' => $hashedPassword,
            'id_rol' => 2, // Asignar rol de cliente
            'activo' => true
        ];

        try {
            // 4. Iniciar transacción
            $this->connection->beginTransaction();

            // 5. Crear usuario
            $userId = $this->userRepository->create($userData);

            // 6. Crear cliente asociado al usuario
            $this->clientRepository->create($userId);

            // 7. Confirmar transacción
            $this->connection->commit();

            return [
                'success' => true,
                'status' => 201,
                'message' => 'Usuario registrado correctamente.',
                'data' => [
                    'id_usuario' => $userId
                ]
            ];

        } catch (Throwable $e) {
            // 8. Si algo falla, revertimos todos los cambios
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            return [
                'success' => false,
                'status' => 500,
                'message' => 'Ocurrió un error al registrar el usuario.'
            ];
        }
    }

    /**
     * Autentica un usuario mediante correo y contraseña.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return array Resultado del proceso de autenticación.
     */
    public function login(array $data): array
    {
        $validation = UserValidator::validateLogin($data);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validation['errors']
            ];
        }

        $user = $this->userRepository->findByEmail($data['email']);

        if ($user === null) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Credenciales inválidas.'
            ];
        }

        $isValid = PasswordHelper::verify(
            $data['password'],
            $user['password_hash']
        );

        if (!$isValid) {
            return [
                'success' => false,
                'status' => 401,
                'message' => 'Credenciales inválidas.'
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'id_usuario' => $user['id_usuario'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'id_rol' => $user['id_rol']
            ]
        ];
    }
}
