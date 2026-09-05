<?php

namespace Tests\Integration\Services;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\ConflictException;
use App\Repositories\ProfessionalRepository;
use App\Services\ProfessionalService;
use Tests\Integration\IntegrationTestCase;

/**
 * Equipo del salón contra MySQL real.
 *
 * Lo que se comprueba aquí y no en la prueba unitaria es el SQL
 * que decide si un profesional tiene agenda pendiente.
 */
class ProfessionalServiceTest extends IntegrationTestCase
{
    private ProfessionalService $service;
    private ProfessionalRepository $repository;

    private int $clientId;
    private int $serviceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ProfessionalRepository($this->db);

        $this->service = new ProfessionalService($this->repository);

        $this->clientId = $this->createClient();
        $this->serviceId = $this->createService(60);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Laura Gómez',
            'especialidad' => 'Manicura',
            'telefono' => '3005554433'
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Alta y edición
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_alta_persiste_los_datos(): void
    {
        $id = $this->service->create($this->payload());

        $guardado = $this->service->findById($id);

        $this->assertSame('Laura Gómez', $guardado['nombre']);
        $this->assertSame('Manicura', $guardado['especialidad']);
        $this->assertSame('3005554433', $guardado['telefono']);
    }

    #[Test]
    public function los_opcionales_vacios_quedan_nulos_en_la_base(): void
    {
        $id = $this->service->create($this->payload([
            'especialidad' => '',
            'telefono' => ''
        ]));

        $fila = $this->db
            ->query("SELECT especialidad, telefono FROM profesionales WHERE id_profesional = {$id}")
            ->fetch();

        $this->assertNull($fila['especialidad']);
        $this->assertNull($fila['telefono']);
    }

    #[Test]
    public function el_alta_queda_activa_por_defecto(): void
    {
        $id = $this->service->create($this->payload());

        $this->assertCount(1, $this->service->findAll());
        $this->assertSame(1, (int) $this->repository
            ->findByIdIncludingInactive($id)['activo']);
    }

    #[Test]
    public function la_edicion_persiste_los_cambios(): void
    {
        $id = $this->service->create($this->payload());

        $actualizado = $this->service->update($id, [
            'nombre' => 'Laura Gómez Ruiz',
            'especialidad' => 'Nail art',
            'telefono' => '3001112233'
        ]);

        $this->assertSame('Laura Gómez Ruiz', $actualizado['nombre']);
        $this->assertSame('Nail art', $actualizado['especialidad']);
    }

    /*
    |--------------------------------------------------------------------------
    | Baja y agenda pendiente
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function la_baja_no_borra_la_fila(): void
    {
        // Las citas pasadas la referencian: el historial debe
        // seguir siendo legible.
        $id = $this->service->create($this->payload());

        $this->service->delete($id);

        $this->assertSame(1, $this->countRows('profesionales'));
        $this->assertSame([], $this->service->findAll());
    }

    #[Test]
    #[DataProvider('agendaQueBloquea')]
    public function no_se_da_de_baja_con_agenda_vigente(
        string $estado,
        string $cuando
    ): void {
        $id = $this->service->create($this->payload());

        $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $id,
            $this->futureDate($cuando),
            '10:00',
            $estado
        );

        $this->expectException(ConflictException::class);

        $this->service->delete($id);
    }

    public static function agendaQueBloquea(): array
    {
        return [
            'pendiente mañana' => ['pendiente', '+1 day'],
            'confirmada mañana' => ['confirmada', '+1 day'],
            'pendiente en un mes' => ['pendiente', '+30 days'],
        ];
    }

    #[Test]
    #[DataProvider('agendaQueNoBloquea')]
    public function se_da_de_baja_pese_a_esas_citas(
        string $estado,
        string $cuando
    ): void {
        $id = $this->service->create($this->payload());

        $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $id,
            $this->futureDate($cuando),
            '10:00',
            $estado
        );

        $this->service->delete($id);

        $this->assertSame([], $this->service->findAll());
    }

    public static function agendaQueNoBloquea(): array
    {
        return [
            'cancelada futura' => ['cancelada', '+1 day'],
            'completada pasada' => ['completada', '-10 days'],
            'pendiente pasada' => ['pendiente', '-10 days'],
        ];
    }

    #[Test]
    public function la_cita_de_hoy_todavia_bloquea(): void
    {
        // El corte es CURDATE(), no "estrictamente futuro":
        // una cita de esta tarde sigue contando.
        $id = $this->service->create($this->payload());

        $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $id,
            date('Y-m-d'),
            '23:00'
        );

        $this->expectException(ConflictException::class);

        $this->service->delete($id);
    }

    #[Test]
    public function la_agenda_de_otro_profesional_no_bloquea(): void
    {
        $laura = $this->service->create($this->payload());
        $marta = $this->service->create($this->payload(['nombre' => 'Marta Ríos']));

        $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $marta,
            $this->futureDate(),
            '10:00'
        );

        $this->service->delete($laura);

        $this->assertCount(1, $this->service->findAll());
    }

    #[Test]
    public function el_mensaje_dice_cuantas_citas_bloquean(): void
    {
        $id = $this->service->create($this->payload());

        foreach (['10:00', '12:00'] as $hora) {
            $this->createAppointment(
                $this->clientId,
                $this->serviceId,
                $id,
                $this->futureDate(),
                $hora
            );
        }

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('2 citas pendientes');

        $this->service->delete($id);
    }
}
