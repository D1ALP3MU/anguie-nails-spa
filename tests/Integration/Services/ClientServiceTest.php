<?php

namespace Tests\Integration\Services;

use RuntimeException;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Exceptions\ConflictException;
use App\Repositories\ClientRepository;
use App\Repositories\UserRepository;
use App\Services\ClientService;
use Tests\Integration\IntegrationTestCase;

/**
 * Alta y edición de clientes contra MySQL real.
 *
 * Lo que se comprueba aquí y no en la prueba unitaria es la
 * transacción: que 'usuarios' y 'clientes' se escriban juntos o
 * no se escriba ninguno.
 */
class ClientServiceTest extends IntegrationTestCase
{
    private ClientService $service;
    private UserRepository $users;
    private ClientRepository $clients;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new UserRepository($this->db);
        $this->clients = new ClientRepository($this->db);

        $this->service = new ClientService(
            $this->db,
            $this->users,
            $this->clients
        );
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'Password123',
            'telefono' => '3001112233',
            'direccion' => 'Calle 1'
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Alta
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_registro_crea_usuario_y_cliente(): void
    {
        // Es el camino unificado: registrarse crea ambas filas.
        $clientId = $this->service->register($this->payload());

        $this->assertGreaterThan(0, $clientId);
        $this->assertSame(1, $this->countRows('usuarios'));
        $this->assertSame(1, $this->countRows('clientes'));
    }

    #[Test]
    public function el_registro_asigna_el_rol_cliente(): void
    {
        $this->service->register($this->payload());

        $user = $this->users->findByEmail('ana@example.com');

        $this->assertSame(Roles::CLIENT, (int) $user['id_rol']);
    }

    #[Test]
    public function la_contrasena_se_guarda_cifrada(): void
    {
        $this->service->register($this->payload());

        $user = $this->users->findByEmail('ana@example.com');

        $this->assertNotSame('Password123', $user['password_hash']);
        $this->assertTrue(
            password_verify('Password123', $user['password_hash'])
        );
    }

    #[Test]
    public function la_direccion_vacia_se_guarda_como_nula(): void
    {
        $clientId = $this->service->register(
            $this->payload(['direccion' => '   '])
        );

        $this->assertNull(
            $this->clients->findById($clientId)['direccion']
        );
    }

    #[Test]
    public function un_correo_repetido_no_deja_rastro(): void
    {
        $this->service->register($this->payload());

        try {

            $this->service->register(
                $this->payload(['nombre' => 'Otra Ana'])
            );

            $this->fail('Se esperaba un conflicto por correo repetido.');

        } catch (ConflictException) {
            // Esperado.
        }

        $this->assertSame(1, $this->countRows('usuarios'));
        $this->assertSame(1, $this->countRows('clientes'));
    }

    #[Test]
    public function si_falla_la_creacion_del_cliente_no_queda_el_usuario(): void
    {
        // El punto de la transacción: sin ella quedaría un usuario
        // huérfano, incapaz de reservar, que es justo el estado que
        // producía el registro antiguo.
        $failing = $this->createMock(ClientRepository::class);

        $failing->method('create')
            ->willThrowException(new RuntimeException('fallo simulado'));

        $service = new ClientService(
            $this->db,
            $this->users,
            $failing
        );

        try {

            $service->register($this->payload());

            $this->fail('Se esperaba que la excepción se propagara.');

        } catch (RuntimeException) {
            // Esperado.
        }

        $this->assertSame(0, $this->countRows('usuarios'));
        $this->assertSame(0, $this->countRows('clientes'));
        $this->assertFalse($this->db->inTransaction());
    }

    /*
    |--------------------------------------------------------------------------
    | Consulta y edición
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_listado_une_los_datos_de_usuario_y_cliente(): void
    {
        $this->service->register($this->payload());

        $listado = $this->service->findAll();

        $this->assertCount(1, $listado);
        $this->assertSame('Ana Pérez', $listado[0]['nombre']);
        $this->assertSame('ana@example.com', $listado[0]['email']);
        $this->assertSame('3001112233', $listado[0]['telefono']);
    }

    #[Test]
    public function la_edicion_actualiza_ambas_tablas(): void
    {
        $clientId = $this->service->register($this->payload());

        $user = $this->users->findByEmail('ana@example.com');

        $actualizado = $this->service->update(
            $clientId,
            [
                'nombre' => 'Ana María',
                'email' => 'ana.maria@example.com',
                'telefono' => '3009998877',
                'direccion' => 'Calle 2'
            ],
            ['id_usuario' => (int) $user['id_usuario'], 'id_rol' => Roles::CLIENT]
        );

        $this->assertSame('Ana María', $actualizado['nombre']);
        $this->assertSame('ana.maria@example.com', $actualizado['email']);
        $this->assertSame('3009998877', $actualizado['telefono']);
    }

    #[Test]
    public function no_se_puede_tomar_el_correo_de_otro_cliente(): void
    {
        $this->service->register($this->payload());

        $segundo = $this->service->register(
            $this->payload([
                'nombre' => 'Bea',
                'email' => 'bea@example.com'
            ])
        );

        $bea = $this->clients->findById($segundo);

        $this->expectException(ConflictException::class);

        $this->service->update(
            $segundo,
            [
                'nombre' => 'Bea',
                'email' => 'ana@example.com',
                'telefono' => '3001112233'
            ],
            ['id_usuario' => (int) $bea['id_usuario'], 'id_rol' => Roles::CLIENT]
        );
    }

    #[Test]
    public function conservar_el_propio_correo_al_editar_es_valido(): void
    {
        $clientId = $this->service->register($this->payload());

        $user = $this->users->findByEmail('ana@example.com');

        $actualizado = $this->service->update(
            $clientId,
            [
                'nombre' => 'Ana Pérez',
                'email' => 'ana@example.com',
                'telefono' => '3005554433'
            ],
            ['id_usuario' => (int) $user['id_usuario'], 'id_rol' => Roles::CLIENT]
        );

        $this->assertSame('3005554433', $actualizado['telefono']);
    }

    /*
    |--------------------------------------------------------------------------
    | Baja lógica
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function la_baja_desactiva_al_usuario_sin_borrar_la_fila(): void
    {
        $clientId = $this->service->register($this->payload());

        $this->service->delete($clientId);

        // La fila sigue existiendo: las citas la referencian.
        $this->assertSame(1, $this->countRows('usuarios'));
        $this->assertSame(1, $this->countRows('clientes'));

        $activo = $this->db
            ->query('SELECT activo FROM usuarios LIMIT 1')
            ->fetchColumn();

        $this->assertSame(0, (int) $activo);
    }

    #[Test]
    public function un_cliente_dado_de_baja_desaparece_del_listado(): void
    {
        $clientId = $this->service->register($this->payload());

        $this->service->delete($clientId);

        $this->assertSame([], $this->service->findAll());
    }

    #[Test]
    public function no_se_da_de_baja_dos_veces(): void
    {
        $clientId = $this->service->register($this->payload());

        $this->service->delete($clientId);

        $this->expectException(ConflictException::class);

        $this->service->delete($clientId);
    }
}
