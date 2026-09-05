<?php

namespace Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Repositories\ClientRepository;
use App\Repositories\UserRepository;
use App\Services\ClientService;

/**
 * Reglas de acceso al perfil de cliente.
 */
class ClientServiceTest extends TestCase
{
    private PDO $db;
    private UserRepository $users;
    private ClientRepository $clients;
    private ClientService $service;

    /** El usuario 10 es dueño del cliente 5. */
    private const OWNER = [
        'id_usuario' => 10,
        'id_rol' => Roles::CLIENT
    ];

    private const OTHER_CLIENT = [
        'id_usuario' => 20,
        'id_rol' => Roles::CLIENT
    ];

    private const ADMIN = [
        'id_usuario' => 1,
        'id_rol' => Roles::ADMIN
    ];

    private const CLIENT_ROW = [
        'id_cliente' => 5,
        'id_usuario' => 10,
        'nombre' => 'Ana',
        'email' => 'ana@example.com',
        'telefono' => '3001112233',
        'direccion' => null
    ];

    protected function setUp(): void
    {
        $this->db = $this->createMock(PDO::class);
        $this->users = $this->createMock(UserRepository::class);
        $this->clients = $this->createMock(ClientRepository::class);

        $this->service = new ClientService(
            $this->db,
            $this->users,
            $this->clients
        );
    }

    #[Test]
    public function un_cliente_puede_consultar_su_propio_perfil(): void
    {
        $this->clients
            ->method('findById')
            ->willReturn(self::CLIENT_ROW);

        $this->assertSame(
            5,
            $this->service->findById(5, self::OWNER)['id_cliente']
        );
    }

    #[Test]
    public function un_perfil_ajeno_se_reporta_como_inexistente(): void
    {
        $this->clients
            ->method('findById')
            ->willReturn(self::CLIENT_ROW);

        $this->expectException(NotFoundException::class);

        $this->service->findById(5, self::OTHER_CLIENT);
    }

    #[Test]
    public function el_administrador_puede_consultar_cualquier_perfil(): void
    {
        $this->clients
            ->method('findById')
            ->willReturn(self::CLIENT_ROW);

        $this->assertSame(
            5,
            $this->service->findById(5, self::ADMIN)['id_cliente']
        );
    }

    #[Test]
    public function un_perfil_inexistente_lanza_no_encontrado(): void
    {
        $this->clients
            ->method('findById')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->findById(404, self::ADMIN);
    }

    #[Test]
    public function la_pertenencia_se_verifica_antes_de_tocar_la_base(): void
    {
        $this->clients
            ->method('findById')
            ->willReturn(self::CLIENT_ROW);

        // Ni transacción ni escritura para un perfil ajeno.
        $this->db->expects($this->never())->method('beginTransaction');
        $this->users->expects($this->never())->method('update');
        $this->clients->expects($this->never())->method('update');

        $this->expectException(NotFoundException::class);

        $this->service->update(
            5,
            [
                'nombre' => 'Secuestrado',
                'email' => 'hack@example.com',
                'telefono' => '3000000000'
            ],
            self::OTHER_CLIENT
        );
    }

    #[Test]
    public function no_se_registra_un_correo_ya_usado(): void
    {
        $this->users
            ->method('findByEmail')
            ->willReturn(['id_usuario' => 99]);

        $this->db->expects($this->never())->method('beginTransaction');

        $this->expectException(ConflictException::class);

        $this->service->register([
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'Password123',
            'telefono' => '3001112233'
        ]);
    }

    #[Test]
    public function no_se_desactiva_dos_veces_el_mismo_cliente(): void
    {
        $this->clients
            ->method('findByIdIncludingInactive')
            ->willReturn(['id_cliente' => 5, 'id_usuario' => 10, 'activo' => 0]);

        $this->expectException(ConflictException::class);

        $this->service->delete(5);
    }
}
