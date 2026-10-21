<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Constants\Roles;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\PaymentRepository;
use App\Services\AppointmentService;
use App\Services\PaymentService;

/**
 * Reglas de cobro.
 *
 * La pertenencia no se prueba aquí: PaymentService la delega en
 * AppointmentService, que ya tiene sus propias pruebas. Lo que se
 * fija aquí es que la delegue de verdad.
 */
class PaymentServiceTest extends TestCase
{
    private PaymentRepository $repository;
    private AppointmentService $appointments;
    private PaymentService $service;

    private const ADMIN = [
        'id_usuario' => 1,
        'id_rol' => Roles::ADMIN
    ];

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PaymentRepository::class);
        $this->appointments = $this->createMock(AppointmentService::class);

        $this->service = new PaymentService(
            $this->repository,
            $this->appointments
        );

        // Por defecto, la cita existe y está pendiente.
        $this->appointments
            ->method('findById')
            ->willReturn([
                'id_cita' => 1,
                'estado' => 'pendiente',
                'precio' => 50000
            ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'monto' => 50000,
            'metodo' => 'efectivo'
        ], $overrides);
    }

    private function payment(array $overrides = []): array
    {
        return array_merge([
            'id_pago' => 7,
            'id_cita' => 1,
            'monto' => 50000,
            'metodo' => 'efectivo',
            'estado' => 'pagado'
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Registrar
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function registra_el_cobro_de_una_cita(): void
    {
        $this->repository
            ->method('findActiveByAppointment')
            ->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $d) => (int) $d['id_cita'] === 1
            ))
            ->willReturn(7);

        $this->assertSame(
            7,
            $this->service->create(1, $this->payload(), self::ADMIN)
        );
    }

    #[Test]
    public function el_id_de_la_cita_sale_de_la_ruta_no_del_cuerpo(): void
    {
        // Enviar otro id_cita en el cuerpo no debe mover el cobro
        // a una cita distinta de la que se está cobrando.
        $this->repository->method('findActiveByAppointment')->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $d) => (int) $d['id_cita'] === 1
            ))
            ->willReturn(7);

        $this->service->create(
            1,
            $this->payload(['id_cita' => 99]),
            self::ADMIN
        );
    }

    #[Test]
    public function no_se_cobra_dos_veces_la_misma_cita(): void
    {
        $this->repository
            ->method('findActiveByAppointment')
            ->willReturn($this->payment());

        $this->repository->expects($this->never())->method('create');

        $this->expectException(ConflictException::class);

        $this->service->create(1, $this->payload(), self::ADMIN);
    }

    #[Test]
    public function no_se_cobra_una_cita_cancelada(): void
    {
        $appointments = $this->createMock(AppointmentService::class);

        $appointments->method('findById')->willReturn([
            'id_cita' => 1,
            'estado' => 'cancelada',
            'precio' => 50000
        ]);

        $service = new PaymentService($this->repository, $appointments);

        $this->repository->expects($this->never())->method('create');

        $this->expectException(ConflictException::class);

        $service->create(1, $this->payload(), self::ADMIN);
    }

    #[Test]
    public function una_cita_ajena_impide_cobrar(): void
    {
        // AppointmentService responde "no encontrada" y la excepción
        // sube tal cual: el cobro no llega a intentarse.
        $appointments = $this->createMock(AppointmentService::class);

        $appointments->method('findById')
            ->willThrowException(new NotFoundException('Cita no encontrada.'));

        $service = new PaymentService($this->repository, $appointments);

        $this->repository->expects($this->never())->method('create');

        $this->expectException(NotFoundException::class);

        $service->create(1, $this->payload(), self::ADMIN);
    }

    #[Test]
    #[DataProvider('cobrosInvalidos')]
    public function valida_los_datos_del_cobro(array $overrides): void
    {
        $this->repository->method('findActiveByAppointment')->willReturn(null);

        $this->repository->expects($this->never())->method('create');

        $this->expectException(ValidationException::class);

        $this->service->create(1, $this->payload($overrides), self::ADMIN);
    }

    public static function cobrosInvalidos(): array
    {
        return [
            'sin monto' => [['monto' => '']],
            'monto cero' => [['monto' => 0]],
            'monto negativo' => [['monto' => -100]],
            'monto no numérico' => [['monto' => 'mucho']],
            'sin método' => [['metodo' => '']],
            'método inventado' => [['metodo' => 'cripto']],
            'estado no inicial' => [['estado' => 'reembolsado']],
        ];
    }

    #[Test]
    public function el_monto_no_tiene_que_coincidir_con_el_precio(): void
    {
        // Descuentos, propinas y redondeos son normales en un salón.
        $this->repository->method('findActiveByAppointment')->willReturn(null);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn(7);

        $this->service->create(
            1,
            $this->payload(['monto' => 45000]),
            self::ADMIN
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Confirmar
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function confirma_un_cobro_pendiente(): void
    {
        $this->repository
            ->method('findById')
            ->willReturn($this->payment(['estado' => 'pendiente']));

        $this->repository
            ->expects($this->once())
            ->method('updateStatus')
            ->with(7, 'pagado');

        $this->service->confirm(7);
    }

    #[Test]
    #[DataProvider('estadosNoConfirmables')]
    public function solo_se_confirma_lo_pendiente(string $estado): void
    {
        $this->repository
            ->method('findById')
            ->willReturn($this->payment(['estado' => $estado]));

        $this->repository->expects($this->never())->method('updateStatus');

        $this->expectException(ConflictException::class);

        $this->service->confirm(7);
    }

    public static function estadosNoConfirmables(): array
    {
        return [['pagado'], ['rechazado'], ['reembolsado']];
    }

    /*
    |--------------------------------------------------------------------------
    | Anular
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function anular_un_cobro_pagado_lo_reembolsa(): void
    {
        // El dinero entró y sale: queda constancia de la devolución.
        $this->repository
            ->method('findById')
            ->willReturn($this->payment(['estado' => 'pagado']));

        $this->repository
            ->expects($this->once())
            ->method('updateStatus')
            ->with(7, 'reembolsado');

        $this->service->void(7);
    }

    #[Test]
    public function anular_un_cobro_pendiente_lo_rechaza(): void
    {
        // Nunca llegó a entrar: no hay nada que devolver.
        $this->repository
            ->method('findById')
            ->willReturn($this->payment(['estado' => 'pendiente']));

        $this->repository
            ->expects($this->once())
            ->method('updateStatus')
            ->with(7, 'rechazado');

        $this->service->void(7);
    }

    #[Test]
    #[DataProvider('estadosYaAnulados')]
    public function no_se_anula_dos_veces(string $estado): void
    {
        $this->repository
            ->method('findById')
            ->willReturn($this->payment(['estado' => $estado]));

        $this->repository->expects($this->never())->method('updateStatus');

        $this->expectException(ConflictException::class);

        $this->service->void(7);
    }

    public static function estadosYaAnulados(): array
    {
        return [['rechazado'], ['reembolsado']];
    }

    #[Test]
    public function un_cobro_inexistente_lanza_no_encontrado(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->void(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Filtros del listado
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function los_filtros_vacios_se_descartan(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAllFiltered')
            ->with([])
            ->willReturn([]);

        $this->service->findAll([
            'desde' => '',
            'hasta' => null,
            'estado' => '  ',
            'metodo' => ''
        ]);
    }

    #[Test]
    public function los_filtros_validos_llegan_al_repositorio(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAllFiltered')
            ->with([
                'desde' => '2027-01-01',
                'hasta' => '2027-01-31',
                'estado' => 'pagado',
                'metodo' => 'efectivo'
            ])
            ->willReturn([]);

        $this->service->findAll([
            'desde' => '2027-01-01',
            'hasta' => '2027-01-31',
            'estado' => 'pagado',
            'metodo' => 'efectivo'
        ]);
    }

    #[Test]
    #[DataProvider('filtrosInvalidos')]
    public function un_filtro_mal_escrito_es_un_error(
        array $filters,
        string $campo
    ): void {
        $this->repository->expects($this->never())->method('findAllFiltered');

        try {

            $this->service->findAll($filters);

        } catch (ValidationException $e) {

            $this->assertArrayHasKey($campo, $e->getErrors());

            return;
        }

        $this->fail("Se esperaba un error en '{$campo}'.");
    }

    public static function filtrosInvalidos(): array
    {
        return [
            'fecha con formato local' => [['desde' => '01/01/2027'], 'desde'],
            'rango invertido' => [
                ['desde' => '2027-02-01', 'hasta' => '2027-01-01'],
                'desde'
            ],
            'estado inventado' => [['estado' => 'inventado'], 'estado'],
            'método inventado' => [['metodo' => 'cripto'], 'metodo'],
        ];
    }
}
