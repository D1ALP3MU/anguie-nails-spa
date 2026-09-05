<?php

namespace Tests\Integration\Repositories;

use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\ConflictException;
use App\Repositories\AppointmentRepository;
use Tests\Integration\IntegrationTestCase;

/**
 * Comportamiento de la agenda contra MySQL real.
 *
 * Aquí se comprueba lo que un doble no puede: el SQL del cruce de
 * horarios y la restricción única que impide la doble reserva.
 */
class AppointmentRepositoryTest extends IntegrationTestCase
{
    private AppointmentRepository $repository;

    private int $clientId;
    private int $otherClientId;
    private int $serviceId;
    private int $professionalId;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new AppointmentRepository($this->db);

        $this->clientId = $this->createClient('ana@example.com', 'Ana');
        $this->otherClientId = $this->createClient('bea@example.com', 'Bea');

        // Servicio de 60 minutos.
        $this->serviceId = $this->createService(60);

        $this->professionalId = $this->createProfessional();

        $this->date = $this->futureDate();
    }

    private function book(
        string $time,
        string $status = 'pendiente',
        ?int $serviceId = null
    ): int {
        return $this->createAppointment(
            $this->clientId,
            $serviceId ?? $this->serviceId,
            $this->professionalId,
            $this->date,
            $time,
            $status
        );
    }

    private function overlaps(
        string $start,
        string $end,
        ?int $exclude = null
    ): bool {
        return $this->repository->hasOverlap(
            $this->professionalId,
            $this->date,
            $start,
            $end,
            $exclude
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cruce de horarios
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function una_agenda_vacia_no_tiene_cruces(): void
    {
        $this->assertFalse($this->overlaps('10:00:00', '11:00:00'));
    }

    #[Test]
    #[DataProvider('intervalosQueCruzan')]
    public function detecta_el_cruce(
        string $start,
        string $end,
        string $caso
    ): void {
        // Cita existente de 10:00 a 11:00.
        $this->book('10:00');

        $this->assertTrue(
            $this->overlaps($start, $end),
            "Debería detectar cruce: {$caso}"
        );
    }

    public static function intervalosQueCruzan(): array
    {
        return [
            'mismo horario exacto' => ['10:00:00', '11:00:00', 'idéntico'],
            'empieza dentro' => ['10:30:00', '11:30:00', 'arranca a mitad'],
            'termina dentro' => ['09:30:00', '10:30:00', 'termina a mitad'],
            'contiene a la existente' => ['09:00:00', '12:00:00', 'la envuelve'],
            'contenido en la existente' => ['10:15:00', '10:45:00', 'va por dentro'],
        ];
    }

    #[Test]
    #[DataProvider('intervalosQueNoCruzan')]
    public function no_reporta_cruce_donde_no_lo_hay(
        string $start,
        string $end,
        string $caso
    ): void {
        $this->book('10:00');

        $this->assertFalse(
            $this->overlaps($start, $end),
            "No debería detectar cruce: {$caso}"
        );
    }

    public static function intervalosQueNoCruzan(): array
    {
        return [
            'justo antes' => ['09:00:00', '10:00:00', 'termina cuando empieza'],
            'justo despues' => ['11:00:00', '12:00:00', 'empieza cuando termina'],
            'mucho antes' => ['07:00:00', '08:00:00', 'sin relación'],
            'mucho despues' => ['16:00:00', '17:00:00', 'sin relación'],
        ];
    }

    #[Test]
    public function el_cruce_usa_la_duracion_del_servicio_existente(): void
    {
        // Servicio largo: 10:00 + 120 min ocupa hasta las 12:00.
        $largo = $this->createService(120, 'Nail Art');

        $this->book('10:00', 'pendiente', $largo);

        // Las 11:30 quedarían libres si solo se mirara la hora de inicio.
        $this->assertTrue($this->overlaps('11:30:00', '12:30:00'));
    }

    #[Test]
    public function una_cita_cancelada_libera_el_horario(): void
    {
        $this->book('10:00', 'cancelada');

        $this->assertFalse($this->overlaps('10:00:00', '11:00:00'));
    }

    #[Test]
    public function una_cita_completada_sigue_ocupando_el_horario(): void
    {
        $this->book('10:00', 'completada');

        $this->assertTrue($this->overlaps('10:00:00', '11:00:00'));
    }

    #[Test]
    public function otro_profesional_no_genera_cruce(): void
    {
        $otro = $this->createProfessional('Marta');

        $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $otro,
            $this->date,
            '10:00'
        );

        $this->assertFalse($this->overlaps('10:00:00', '11:00:00'));
    }

    #[Test]
    public function otra_fecha_no_genera_cruce(): void
    {
        $this->book('10:00');

        $this->assertFalse(
            $this->repository->hasOverlap(
                $this->professionalId,
                $this->futureDate('+31 days'),
                '10:00:00',
                '11:00:00',
                null
            )
        );
    }

    #[Test]
    public function la_cita_excluida_no_choca_consigo_misma(): void
    {
        // Es lo que permite reprogramar sin cambiar de horario.
        $id = $this->book('10:00');

        $this->assertTrue($this->overlaps('10:00:00', '11:00:00'));
        $this->assertFalse($this->overlaps('10:00:00', '11:00:00', $id));
    }

    /*
    |--------------------------------------------------------------------------
    | Restricción única en la base de datos
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function la_base_impide_la_doble_reserva(): void
    {
        // Red de seguridad ante dos peticiones simultáneas que
        // superen la comprobación de cruce a la vez.
        $this->repository->create([
            'id_cliente' => $this->clientId,
            'id_servicio' => $this->serviceId,
            'id_profesional' => $this->professionalId,
            'fecha' => $this->date,
            'hora' => '10:00'
        ]);

        $this->expectException(ConflictException::class);

        $this->repository->create([
            'id_cliente' => $this->otherClientId,
            'id_servicio' => $this->serviceId,
            'id_profesional' => $this->professionalId,
            'fecha' => $this->date,
            'hora' => '10:00'
        ]);
    }

    #[Test]
    public function tras_cancelar_el_horario_vuelve_a_estar_libre(): void
    {
        // Un UNIQUE corriente lo bloquearía para siempre, porque la
        // fila cancelada permanece: de ahí la columna generada.
        $id = $this->repository->create([
            'id_cliente' => $this->clientId,
            'id_servicio' => $this->serviceId,
            'id_profesional' => $this->professionalId,
            'fecha' => $this->date,
            'hora' => '10:00'
        ]);

        $this->repository->delete($id);

        $nuevo = $this->repository->create([
            'id_cliente' => $this->otherClientId,
            'id_servicio' => $this->serviceId,
            'id_profesional' => $this->professionalId,
            'fecha' => $this->date,
            'hora' => '10:00'
        ]);

        $this->assertGreaterThan(0, $nuevo);
        $this->assertNotSame($id, $nuevo);
    }

    #[Test]
    public function pueden_coexistir_varias_citas_canceladas_en_el_mismo_horario(): void
    {
        $this->book('10:00', 'cancelada');
        $this->book('10:00', 'cancelada');
        $this->book('10:00', 'cancelada');

        $this->assertSame(3, $this->countRows('citas'));
    }

    #[Test]
    public function otros_errores_de_integridad_no_se_disfrazan_de_conflicto(): void
    {
        // Un id_servicio inexistente debe seguir siendo un fallo de
        // clave foránea, no un "horario ocupado".
        $this->expectException(PDOException::class);

        $this->repository->create([
            'id_cliente' => $this->clientId,
            'id_servicio' => 999999,
            'id_profesional' => $this->professionalId,
            'fecha' => $this->date,
            'hora' => '10:00'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas de lectura
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_listado_por_cliente_solo_trae_sus_citas(): void
    {
        $this->book('10:00');

        $this->createAppointment(
            $this->otherClientId,
            $this->serviceId,
            $this->professionalId,
            $this->date,
            '12:00'
        );

        $propias = $this->repository->findAllByClient($this->clientId);

        $this->assertCount(1, $propias);
        $this->assertSame($this->clientId, (int) $propias[0]['id_cliente']);
        $this->assertCount(2, $this->repository->findAll());
    }

    #[Test]
    public function el_listado_resuelve_los_nombres_relacionados(): void
    {
        $this->book('10:00');

        $cita = $this->repository->findAll()[0];

        $this->assertSame('Ana', $cita['cliente']);
        $this->assertSame('Manicura', $cita['servicio']);
        $this->assertSame('Laura', $cita['profesional']);
    }

    #[Test]
    public function el_listado_va_ordenado_por_fecha_y_hora(): void
    {
        $this->book('16:00');
        $this->book('08:00');
        $this->book('12:00');

        $horas = array_column($this->repository->findAll(), 'hora');

        $this->assertSame(
            ['08:00:00', '12:00:00', '16:00:00'],
            $horas
        );
    }

    #[Test]
    public function una_cita_inexistente_devuelve_null(): void
    {
        $this->assertNull($this->repository->findById(999999));
    }
}
