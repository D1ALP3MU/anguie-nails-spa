<?php

namespace Tests\Unit\Services;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Config\SalonSchedule;
use App\Exceptions\ValidationException;
use App\Repositories\AppointmentRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ServiceRepository;
use App\Services\AvailabilityService;

/**
 * Horas libres para reservar, con el horario real del salón
 * (lunes a sábado, 9:00 a 19:00, cada 30 minutos, 8 semanas).
 *
 * Las fechas se calculan desde hoy pero siempre caen en un día
 * conocido —un lunes de dentro de 8 a 14 días—, así que el resultado
 * no depende del día en que se ejecute la prueba.
 */
class AvailabilityServiceTest extends TestCase
{
    private AppointmentRepository $appointments;
    private ServiceRepository $services;
    private ProfessionalRepository $professionals;
    private AvailabilityService $service;

    /** Pedicura de 90 minutos. */
    private const SERVICE_ID = 3;
    private const PROFESSIONAL_ID = 1;

    protected function setUp(): void
    {
        $this->appointments = $this->createMock(AppointmentRepository::class);
        $this->services = $this->createMock(ServiceRepository::class);
        $this->professionals = $this->createMock(ProfessionalRepository::class);

        $this->services
            ->method('findById')
            ->willReturnCallback(fn (int $id) => match ($id) {
                self::SERVICE_ID => ['id_servicio' => $id, 'duracion' => 90, 'activo' => 1],
                9 => ['id_servicio' => 9, 'duracion' => 60, 'activo' => 0],
                default => null
            });

        $this->professionals
            ->method('findById')
            ->willReturnCallback(fn (int $id) => $id === self::PROFESSIONAL_ID
                ? ['id_profesional' => $id, 'activo' => 1]
                : null);

        $this->service = new AvailabilityService(
            new SalonSchedule([]),
            $this->appointments,
            $this->services,
            $this->professionals
        );
    }

    private static function nextMonday(): DateTimeImmutable
    {
        return (new DateTimeImmutable('today'))
            ->modify('next monday')
            ->modify('+7 days');
    }

    private function slots(DateTimeImmutable $day, array $overrides = []): array
    {
        return $this->service->freeSlots(array_merge([
            'id_profesional' => (string) self::PROFESSIONAL_ID,
            'id_servicio' => (string) self::SERVICE_ID,
            'fecha' => $day->format('Y-m-d')
        ], $overrides));
    }

    #[Test]
    public function un_dia_libre_ofrece_toda_la_jornada(): void
    {
        $this->appointments->method('findBusyIntervals')->willReturn([]);

        $result = $this->slots(self::nextMonday());

        $this->assertTrue($result['abierto']);
        $this->assertSame('09:00', $result['horas'][0]);
        // 90 minutos: la última que termina antes de las 19:00.
        $this->assertSame('17:30', end($result['horas']));
        $this->assertCount(18, $result['horas']);
    }

    #[Test]
    public function no_se_ofrecen_horas_que_se_crucen_con_otra_cita(): void
    {
        // Ocupada de 10:00 a 11:30. Un servicio de 90 minutos que
        // empiece entre las 9:00 y las 11:00 la pisaría.
        $this->appointments
            ->method('findBusyIntervals')
            ->willReturn([['inicio' => '10:00:00', 'fin' => '11:30:00']]);

        $horas = $this->slots(self::nextMonday())['horas'];

        foreach (['09:00', '09:30', '10:00', '10:30', '11:00'] as $pisa) {
            $this->assertNotContains($pisa, $horas);
        }

        // Justo al terminar la otra sí cabe.
        $this->assertSame('11:30', $horas[0]);
    }

    #[Test]
    public function el_dia_que_el_salon_no_abre_no_hay_horas(): void
    {
        $this->appointments->expects($this->never())->method('findBusyIntervals');

        $result = $this->slots(self::nextMonday()->modify('-1 day'));

        $this->assertFalse($result['abierto']);
        $this->assertSame([], $result['horas']);
    }

    #[Test]
    public function un_dia_pasado_no_tiene_horas(): void
    {
        $this->assertSame([], $this->slots(new DateTimeImmutable('-7 days'))['horas']);
    }

    #[Test]
    public function mas_alla_del_plazo_no_hay_horas(): void
    {
        $this->assertSame([], $this->slots(self::nextMonday()->modify('+8 weeks'))['horas']);
    }

    #[Test]
    public function un_servicio_retirado_no_se_puede_consultar(): void
    {
        $this->expectException(ValidationException::class);

        $this->slots(self::nextMonday(), ['id_servicio' => '9']);
    }

    #[Test]
    public function los_parametros_se_validan(): void
    {
        try {

            $this->service->freeSlots([
                'id_profesional' => 'abc',
                'id_servicio' => '0',
                'fecha' => '2026-04-31'
            ]);

            $this->fail('Se esperaba un error de validación.');

        } catch (ValidationException $e) {

            $this->assertEqualsCanonicalizing(
                ['id_profesional', 'id_servicio', 'fecha'],
                array_keys($e->getErrors())
            );
        }
    }
}
