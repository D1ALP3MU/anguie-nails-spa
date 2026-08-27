<?php

namespace App\Services;

use PDO;
use App\Helpers\PasswordHelper;
use App\Helpers\JwtHelper;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;
use App\Exceptions\UnauthorizedException;


/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Autenticación
 * Archivo  : AuthService.php
 * Versión  : 1.0.0
 *
 * Descripción:
 * Contiene la lógica de negocio relacionada con la
 * autenticación de usuarios.
 *
 * Responsabilidades:
 * - Autenticar usuarios.
 * - Coordinar validaciones.
 * - Verificar credenciales.
 * - Generar tokens JWT.
 *
 * Esta clase NO genera respuestas HTTP.
 * ---------------------------------------------------------
 */

class AuthService
{
    /**
     * Repositorio de usuarios.
     *
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * Constructor del servicio.
     *
     * @param PDO $connection Conexión activa a MySQL.
     */
    public function __construct(PDO $connection)
    {
        $this->userRepository = new UserRepository($connection);
    }

    /**
     * Autentica un usuario mediante correo y contraseña.
     *
     * @param array $data Datos enviados por el cliente.
     *
     * @return array Datos necesarios para la respuesta.
     *
     * @throws UnauthorizedException
     */
    public function login(array $data): array
    {
        UserValidator::validateLogin($data);

        $email = trim($data['email']);

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new UnauthorizedException(
                'Credenciales inválidas.'
            );
        }

        if (!(bool) $user['activo']) {
            throw new UnauthorizedException(
                'El usuario se encuentra desactivado.'
            );
        }

        $isValid = PasswordHelper::verify(
            $data['password'],
            $user['password_hash']
        );

        if (!$isValid) {
            throw new UnauthorizedException(
                'Credenciales inválidas.'
            );
        }

        $token = JwtHelper::generate([
            'id_usuario' => $user['id_usuario'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'id_rol' => $user['id_rol']
        ]);

        return [
            'token' => $token,
            'user' => [
                'id_usuario' => $user['id_usuario'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'id_rol' => $user['id_rol']
            ]
        ];
    }
}