<?php

namespace App\Services;

use App\Constants\Roles;
use App\Helpers\PasswordHelper;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Administradores
 * Archivo  : AdminService.php
 *
 * Descripción:
 * Alta y baja de las cuentas de administración.
 *
 * Un administrador es solo una fila en `usuarios`: a diferencia
 * del alta de clientes no se crea perfil en `clientes`, porque no
 * reserva citas. Por eso esto no comparte camino con
 * ClientService::register, que ata las dos tablas en una
 * transacción y fija el rol de cliente.
 *
 * El primer administrador sigue creándose por SQL: un endpoint
 * público capaz de crear administradores sería un agujero.
 *
 * Esta clase NO contiene consultas SQL ni genera respuestas HTTP.
 * ---------------------------------------------------------
 */
class AdminService
{
    public function __construct(
        private UserRepository $repository
    ) {}

    /**
     * Obtiene los administradores activos.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->repository->findAllActiveByRole(Roles::ADMIN);
    }

    /**
     * Da de alta a un administrador.
     *
     * La contraseña es temporal: quien la recibe la cambia desde
     * «Mi cuenta». No hace falta transacción, es un solo INSERT.
     *
     * @param array $data Datos del administrador.
     *
     * @return int ID del usuario creado.
     *
     * @throws ConflictException Si el correo ya está registrado.
     */
    public function create(array $data): int
    {
        UserValidator::validateAdmin($data);

        $email = trim($data['email']);

        if ($this->repository->findByEmail($email) !== null) {
            throw new ConflictException(
                'El correo electrónico ya se encuentra registrado.'
            );
        }

        return $this->repository->create([
            'nombre' => trim($data['nombre']),
            'email' => $email,
            'password_hash' => PasswordHelper::hash($data['password']),
            'id_rol' => Roles::ADMIN
        ]);
    }

    /**
     * Da de baja a un administrador.
     *
     * @param int $id ID del usuario.
     * @param array $authUser Usuario autenticado.
     *
     * @return void
     *
     * @throws NotFoundException
     * @throws ConflictException
     */
    public function delete(int $id, array $authUser): void
    {
        $user = $this->repository->findById($id);

        if (
            $user === null
            || (int) $user['id_rol'] !== Roles::ADMIN
        ) {
            throw new NotFoundException(
                'Administrador no encontrado.'
            );
        }

        if (!(bool) $user['activo']) {
            throw new ConflictException(
                'El administrador ya se encuentra desactivado.'
            );
        }

        /*
         * El orden importa: si solo queda una administradora y es
         * ella quien lo pide, los dos casos se cumplen a la vez.
         * Primero el del último, porque el otro mensaje remite a
         * «otra administradora» que entonces no existe.
         *
         * Sin administradores activos nadie entra al panel, y
         * recuperarlo exige entrar a la base de datos por SQL.
         */
        if ($this->repository->countActiveByRole(Roles::ADMIN) <= 1) {
            throw new ConflictException(
                'No es posible desactivar al último administrador: '
                . 'el salón se quedaría sin acceso al panel.'
            );
        }

        // Darse de baja a una misma cierra la sesión propia sin
        // que quede claro por qué. Se dice en lugar de hacerlo.
        if ($id === (int) ($authUser['id_usuario'] ?? 0)) {
            throw new ConflictException(
                'No puedes desactivar tu propia cuenta. '
                . 'Pídeselo a otra administradora.'
            );
        }

        $this->repository->deactivate($id);
    }
}
