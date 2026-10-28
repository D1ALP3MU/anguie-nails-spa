<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use App\Services\AdminService;

/**
 * Alta y baja de administradores.
 *
 * Lo que de verdad se protege aquí son las dos formas de dejar el
 * salón sin acceso al panel: desactivar al último administrador y
 * desactivarse una misma. Recuperarse de cualquiera de las dos
 * exige entrar a la base de datos por SQL.
 */
class AdminServiceTest extends TestCase
{
    private UserRepository $users;
    private AdminService $service;

    private const YO = [
        'id_usuario' => 7,
        'nombre' => 'Anguie',
        'email' => 'anguie@example.com',
        'id_rol' => Roles::ADMIN
    ];

    protected function setUp(): void
    {
        $this->users = $this->createMock(UserRepository::class);

        $this->service = new AdminService($this->users);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Marcela Ruiz',
            'email' => 'marcela@example.com',
            'password' => 'Password123'
        ], $overrides);
    }

    private static function adminRow(array $overrides = []): array
    {
        return array_merge([
            'id_usuario' => 9,
            'nombre' => 'Marcela',
            'email' => 'marcela@example.com',
            'password_hash' => 'irrelevante',
            'id_rol' => Roles::ADMIN,
            'activo' => 1
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Alta
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_alta_crea_un_usuario_con_rol_administrador(): void
    {
        $this->users->method('findByEmail')->willReturn(null);

        $this->users
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $d) => $d['id_rol'] === Roles::ADMIN
            ))
            ->willReturn(9);

        $this->assertSame(9, $this->service->create($this->payload()));
    }

    #[Test]
    public function el_alta_guarda_la_contrasena_cifrada(): void
    {
        $this->users->method('findByEmail')->willReturn(null);

        $guardado = null;

        $this->users
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (array $d) use (&$guardado) {
                $guardado = $d['password_hash'];
                return true;
            }))
            ->willReturn(9);

        $this->service->create($this->payload());

        $this->assertNotSame('Password123', $guardado);
        $this->assertTrue(password_verify('Password123', $guardado));
    }

    #[Test]
    public function el_alta_guarda_nombre_y_correo_sin_espacios(): void
    {
        $this->users->method('findByEmail')->willReturn(null);

        $this->users
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $d) => $d['nombre'] === 'Marcela Ruiz'
                    && $d['email'] === 'marcela@example.com'
            ))
            ->willReturn(9);

        $this->service->create($this->payload([
            'nombre' => '  Marcela Ruiz  ',
            'email' => '  marcela@example.com  '
        ]));
    }

    #[Test]
    public function el_correo_repetido_se_rechaza(): void
    {
        $this->users->method('findByEmail')->willReturn(self::adminRow());

        $this->users->expects($this->never())->method('create');

        $this->expectException(ConflictException::class);

        $this->service->create($this->payload());
    }

    #[Test]
    public function el_alta_valida_los_datos(): void
    {
        $this->users->expects($this->never())->method('create');

        $this->expectException(ValidationException::class);

        $this->service->create($this->payload(['password' => 'corta']));
    }

    /*
    |--------------------------------------------------------------------------
    | Baja
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function la_baja_desactiva_al_administrador(): void
    {
        $this->users->method('findById')->willReturn(self::adminRow());
        $this->users->method('countActiveByRole')->willReturn(2);

        $this->users
            ->expects($this->once())
            ->method('deactivate')
            ->with(9);

        $this->service->delete(9, self::YO);
    }

    #[Test]
    public function no_se_desactiva_al_ultimo_administrador(): void
    {
        // Sin administradores activos nadie entra al panel.
        $this->users->method('findById')->willReturn(self::adminRow());
        $this->users->method('countActiveByRole')->willReturn(1);

        $this->users->expects($this->never())->method('deactivate');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('último administrador');

        $this->service->delete(9, self::YO);
    }

    #[Test]
    public function nadie_se_desactiva_a_si_mismo(): void
    {
        $this->users
            ->method('findById')
            ->willReturn(self::adminRow(['id_usuario' => 7]));

        $this->users->method('countActiveByRole')->willReturn(5);

        $this->users->expects($this->never())->method('deactivate');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('tu propia cuenta');

        $this->service->delete(7, self::YO);
    }

    #[Test]
    public function la_unica_administradora_que_se_borra_oye_el_motivo_util(): void
    {
        /*
         * Los dos casos se cumplen a la vez. Debe ganar el del
         * último administrador: el otro mensaje la remitiría a
         * «otra administradora» que no existe.
         */
        $this->users
            ->method('findById')
            ->willReturn(self::adminRow(['id_usuario' => 7]));

        $this->users->method('countActiveByRole')->willReturn(1);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('último administrador');

        $this->service->delete(7, self::YO);
    }

    #[Test]
    public function una_clienta_no_se_da_de_baja_por_esta_via(): void
    {
        /*
         * La baja de clientes vive en ClientService, que además
         * comprueba sus propias reglas. Pasar un id de clienta por
         * aquí no debe saltárselas.
         */
        $this->users
            ->method('findById')
            ->willReturn(self::adminRow(['id_rol' => Roles::CLIENT]));

        $this->users->expects($this->never())->method('deactivate');

        $this->expectException(NotFoundException::class);

        $this->service->delete(9, self::YO);
    }

    #[Test]
    public function un_administrador_inexistente_es_no_encontrado(): void
    {
        $this->users->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->delete(404, self::YO);
    }

    #[Test]
    public function no_se_desactiva_dos_veces(): void
    {
        $this->users
            ->method('findById')
            ->willReturn(self::adminRow(['activo' => 0]));

        $this->users->expects($this->never())->method('deactivate');

        $this->expectException(ConflictException::class);

        $this->service->delete(9, self::YO);
    }
}
