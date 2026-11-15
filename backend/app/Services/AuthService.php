<?php

namespace App\Services;

use App\Helpers\PasswordHelper;
use App\Helpers\JwtHelper;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;


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
 * - Cambiar la contraseña del usuario autenticado.
 *
 * El alta de cuentas es responsabilidad de ClientService, que crea
 * el usuario y su perfil de cliente en una sola transacción.
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
     * @param UserRepository $userRepository Repositorio de usuarios.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
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

    /**
     * Cambia la contraseña del usuario autenticado.
     *
     * El usuario se resuelve desde el token, nunca desde el cuerpo
     * de la petición: así nadie puede cambiar la contraseña ajena.
     *
     * Exigir la contraseña actual es lo que impide que un token
     * robado se convierta en una cuenta secuestrada.
     *
     * @param array $authUser Usuario autenticado.
     * @param array $data Datos enviados por el cliente.
     *
     * @return void
     *
     * @throws ValidationException
     * @throws UnauthorizedException
     */
    public function changePassword(array $authUser, array $data): void
    {
        UserValidator::validatePasswordChange($data);

        $userId = (int) ($authUser['id_usuario'] ?? 0);

        $user = $this->userRepository->findById($userId);

        // El token es válido pero la cuenta ya no: queda desactivada
        // entre que se firmó el token y se usa.
        if ($user === null || !(bool) $user['activo']) {
            throw new UnauthorizedException(
                'La sesión ya no es válida.'
            );
        }

        $isValid = PasswordHelper::verify(
            $data['password_actual'],
            $user['password_hash']
        );

        if (!$isValid) {
            /*
             * 422 y no 401 a propósito: el cliente HTTP del frontend
             * cierra la sesión ante cualquier 401, así que
             * equivocarse al teclear la contraseña actual echaría
             * fuera a quien solo quería cambiarla.
             */
            throw new ValidationException([
                'password_actual' => 'La contraseña actual no es correcta.'
            ]);
        }

        $this->userRepository->updatePassword(
            $userId,
            PasswordHelper::hash($data['password_nueva'])
        );
    }
}
