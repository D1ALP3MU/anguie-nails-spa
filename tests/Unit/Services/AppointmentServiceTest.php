<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClientRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ServiceRepository;
use App\Services\AppointmentService;

/**
 * Reglas de acceso y de agenda de las citas.
 *
 * Los repositorios se sustituyen por dobles: aquí solo se
 * comprueba la decisión del servicio, no el SQL.
 */
class AppointmentServiceTest extends TestCase
{
    private AppointmentRepository $appointments;
    private ClientRepository $clients;
    private ServiceRepository $services;
    private ProfessionalRepository $professionals;
    private AppointmentService $service;

    /** Cliente autenticado: usuario 10 -> cliente 5. */
    private const CLIENT_USER = [
        'id_usuario' => 10,
        'id_rol' => Roles::CLIENT
    ];

    private const ADMIN_USER = [
        'id_usuario' => 1,
        'id_rol' => Roles::ADMIN
    ];

    protected function setUp(): void
    {
        $this->appointments = $this->createMock(AppointmentRepository::class);
        $this->clients = $this->createMock(ClientRepository::class);
        $this->services = $this->createMock(ServiceRepository::class);
        $this->professionals = $this->createMock(ProfessionalRepository::class);

        $this->service = new AppointmentService(
            $this->appointments,
            $this->clients,
            $this->services,
            $this->professionals
        );

        // Por defecto: el usuario 10 es el cliente 5.
        $this->clients
            ->method('findByUserId')
            ->willReturnCallback(
                fn (int $userId) => $userId === 10
                    ? ['id_cliente' => 5, 'id_usuario' => 10]
                    : null
            );

        // Por defecto: servicio y profesional válidos y activos.
        $this->services
            ->method('findById')
            ->willReturnCallback(
                fn (int $id) => $id === 2
                    ? ['id_servicio' => 2, 'duracion' => 60, 'activo' => 1]
                    : null
            );

        $this->professionals
            ->method('findById')
            ->willReturnCallback(
                fn (int $id) => $id === 1
                    ? ['id_profesional' => 1, 'activo' => 1]
                    : null
            );
    }

    /**
     * Datos de una cita válida, con fecha futura.
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'id_servicio' => 2,
            'id_profesional' => 1,
            'fecha' => date('Y-m-d', strtotime('+30 days')),
            'hora' => '10:00'
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Pertenencia
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function un_cliente_solo_puede_reservar_para_si_mismo(): void
    {
        // Intenta reservar a nombre del cliente 99.
        $payload = $this->validPayload(['id_cliente' => 99]);

        $this->appointments
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                // El id_cliente enviado se descarta y se usa el del token.
                fn (array $data) => $data['id_cliente'] === 5
            ))
            ->willReturn(77);

        $this->assertSame(
            77,
            $this->service->create($payload, self::CLIENT_USER)
        );
    }

    #[Test]
    public function un_administrador_puede_reservar_a_nombre_de_otro(): void
    {
        $payload = $this->validPayload(['id_cliente' => 99]);

        $this->appointments
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $data) => (int) $data['id_cliente'] === 99
            ))
            ->willReturn(78);

        $this->assertSame(
            78,
            $this->service->create($payload, self::ADMIN_USER)
        );
    }

    #[Test]
    public function un_cliente_sin_perfil_no_puede_reservar(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->service->create(
            $this->validPayload(),
            ['id_usuario' => 999, 'id_rol' => Roles::CLIENT]
        );
    }

    #[Test]
    public function el_listado_de_un_cliente_se_filtra_por_su_id(): void
    {
        $this->appointments
            ->expects($this->once())
            ->method('findAllByClient')
            ->with(5)
            ->willReturn([['id_cita' => 1]]);

        $this->appointments
            ->expects($this->never())
            ->method('findAll');

        $this->service->findAll(self::CLIENT_USER);
    }

    #[Test]
    public function el_administrador_ve_todas_las_citas(): void
    {
        $this->appointments
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $this->appointments
            ->expects($this->never())
            ->method('findAllByClient');

        $this->service->findAll(self::ADMIN_USER);
    }

    #[Test]
    public function una_cita_ajena_se_reporta_como_inexistente(): void
    {
        // Responder 403 revelaría que el identificador existe.
        $this->appointments
            ->method('findById')
            ->willReturn(['id_cita' => 1, 'id_cliente' => 99, 'estado' => 'pendiente']);

        $this->expectException(NotFoundException::class);

        $this->service->findById(1, self::CLIENT_USER);
    }

    #[Test]
    public function un_cliente_no_puede_cancelar_una_cita_ajena(): void
    {
        $this->appointments
            ->method('findById')
            ->willReturn(['id_cita' => 1, 'id_cliente' => 99, 'estado' => 'pendiente']);

        $this->appointments
            ->expects($this->never())
            ->method('delete');

        $this->expectException(NotFoundException::class);

        $this->service->delete(1, self::CLIENT_USER);
    }

    #[Test]
    public function un_cliente_puede_cancelar_su_propia_cita(): void
    {
        $this->appointments
            ->method('findById')
            ->willReturn(['id_cita' => 1, 'id_cliente' => 5, 'estado' => 'pendiente']);

        $this->appointments
            ->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->service->delete(1, self::CLIENT_USER);
    }

    #[Test]
    public function no_se_cancela_dos_veces_la_misma_cita(): void
    {
        $this->appointments
            ->method('findById')
            ->willReturn(['id_cita' => 1, 'id_cliente' => 5, 'estado' => 'cancelada']);

        $this->expectException(ConflictException::class);

        $this->service->delete(1, self::CLIENT_USER);
    }

    #[Test]
    public function al_reprogramar_un_cliente_no_puede_reasignar_la_cita(): void
    {
        $this->appointments
            ->method('findById')
            ->willReturn(['id_cita' => 1, 'id_cliente' => 5, 'estado' => 'pendiente']);

        $this->appointments
            ->expects($this->once())
            ->method('update')
            ->with(1, $this->callback(
                fn (array $data) => (int) $data['id_cliente'] === 5
            ));

        $this->service->update(
            1,
            $this->validPayload([
                'id_cliente' => 99,
                'estado' => 'pendiente'
            ]),
            self::CLIENT_USER
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reglas de agenda
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function no_se_agenda_sobre_un_horario_ocupado(): void
    {
        $this->appointments
            ->method('hasOverlap')
            ->willReturn(true);

        $this->appointments
            ->expects($this->never())
            ->method('create');

        $this->expectException(ConflictException::class);

        $this->service->create(
            $this->validPayload(),
            self::CLIENT_USER
        );
    }

    #[Test]
    public function el_cruce_se_calcula_con_la_duracion_del_servicio(): void
    {
        $date = date('Y-m-d', strtotime('+30 days'));

        // Servicio de 60 minutos a las 10:00 -> intervalo [10:00, 11:00).
        $this->appointments
            ->expects($this->once())
            ->method('hasOverlap')
            ->with(1, $date, '10:00:00', '11:00:00', null)
            ->willReturn(false);

        $this->appointments->method('create')->willReturn(1);

        $this->service->create(
            $this->validPayload(['fecha' => $date]),
            self::CLIENT_USER
        );
    }

    #[Test]
    public function al_reprogramar_la_cita_no_choca_consigo_misma(): void
    {
        $this->appointments
            ->method('findById')
            ->willReturn(['id_cita' => 42, 'id_cliente' => 5, 'estado' => 'pendiente']);

        // Se excluye la propia cita del cálculo de cruce.
        $this->appointments
            ->expects($this->once())
            ->method('hasOverlap')
            ->with(1, $this->anything(), $this->anything(), $this->anything(), 42)
            ->willReturn(false);

        $this->service->update(
            42,
            $this->validPayload(['estado' => 'pendiente']),
            self::CLIENT_USER
        );
    }

    #[Test]
    public function no_se_agenda_en_el_pasado(): void
    {
        $this->expectException(ValidationException::class);

        try {

            $this->service->create(
                $this->validPayload([
                    'fecha' => date('Y-m-d', strtotime('-1 day'))
                ]),
                self::CLIENT_USER
            );

        } catch (ValidationException $e) {

            $this->assertArrayHasKey('fecha', $e->getErrors());

            throw $e;
        }
    }

    #[Test]
    public function un_servicio_inexistente_es_un_error_de_validacion(): void
    {
        // Antes reventaba en la clave foránea y devolvía 500.
        $this->expectException(ValidationException::class);

        try {

            $this->service->create(
                $this->validPayload(['id_servicio' => 99999]),
                self::CLIENT_USER
            );

        } catch (ValidationException $e) {

            $this->assertArrayHasKey('id_servicio', $e->getErrors());

            throw $e;
        }
    }

    #[Test]
    public function un_profesional_inexistente_es_un_error_de_validacion(): void
    {
        $this->expectException(ValidationException::class);

        try {

            $this->service->create(
                $this->validPayload(['id_profesional' => 99999]),
                self::CLIENT_USER
            );

        } catch (ValidationException $e) {

            $this->assertArrayHasKey('id_profesional', $e->getErrors());

            throw $e;
        }
    }

    #[Test]
    public function una_cita_completada_puede_registrarse_con_fecha_pasada(): void
    {
        // Permite que el administrador cierre el historial.
        $this->appointments
            ->method('findById')
            ->willReturn(['id_cita' => 1, 'id_cliente' => 5, 'estado' => 'pendiente']);

        $this->appointments
            ->expects($this->never())
            ->method('hasOverlap');

        $this->appointments
            ->expects($this->once())
            ->method('update');

        $this->service->update(
            1,
            $this->validPayload([
                'id_cliente' => 5,
                'fecha' => '2020-01-01',
                'estado' => 'completada'
            ]),
            self::ADMIN_USER
        );
    }
}
