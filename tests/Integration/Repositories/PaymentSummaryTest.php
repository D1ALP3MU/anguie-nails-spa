<?php

namespace Tests\Integration\Repositories;

use PHPUnit\Framework\Attributes\Test;
use App\Repositories\PaymentRepository;
use Tests\Integration\IntegrationTestCase;

/**
 * Resumen de facturación contra MySQL real.
 *
 * Es donde una agregación puede engañar sin que se note: sumar lo
 * anulado, contar dos veces por culpa de un JOIN, o dar por cobrada
 * una cita que tiene el cobro reembolsado.
 */
class PaymentSummaryTest extends IntegrationTestCase
{
    private PaymentRepository $repository;

    private int $clientId;
    private int $serviceId;
    private int $laura;
    private int $marta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PaymentRepository($this->db);

        $this->clientId = $this->createClient();
        $this->serviceId = $this->createService(60);
        $this->laura = $this->createProfessional('Laura');
        $this->marta = $this->createProfessional('Marta');
    }

    /**
     * Crea una cita con su cobro, si se indica.
     */
    private function cita(
        string $fecha,
        string $hora,
        int $profesional,
        float $precio,
        ?array $cobro = null,
        string $estadoCita = 'pendiente'
    ): int {
        $id = $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $profesional,
            $fecha,
            $hora,
            $estadoCita,
            $precio
        );

        if ($cobro !== null) {
            $this->db->prepare(
                'INSERT INTO pagos (id_cita, monto, metodo, estado)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $id,
                $cobro['monto'],
                $cobro['metodo'] ?? 'efectivo',
                $cobro['estado'] ?? 'pagado'
            ]);
        }

        return $id;
    }

    /*
    |--------------------------------------------------------------------------
    | Totales
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function una_agenda_vacia_da_ceros_y_no_nulos(): void
    {
        // Un informe en blanco debe poder formatearse sin comprobar
        // nulos en cada casilla.
        $resumen = $this->repository->summary([]);

        $this->assertSame(0.0, (float) $resumen['totales']['facturado']);
        $this->assertSame(0, (int) $resumen['totales']['cobros']);
        $this->assertSame(0.0, (float) $resumen['sin_cobrar']['total']);
        $this->assertSame([], $resumen['por_metodo']);
        $this->assertSame([], $resumen['por_servicio']);
    }

    #[Test]
    public function separa_lo_facturado_de_lo_pendiente_y_lo_anulado(): void
    {
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, ['monto' => 50000]);
        $this->cita('2027-03-02', '09:00', $this->laura, 30000, ['monto' => 30000, 'estado' => 'pendiente']);
        $this->cita('2027-03-03', '09:00', $this->laura, 20000, ['monto' => 20000, 'estado' => 'reembolsado']);
        $this->cita('2027-03-04', '09:00', $this->laura, 10000, ['monto' => 10000, 'estado' => 'rechazado']);

        $t = $this->repository->summary([])['totales'];

        $this->assertSame(50000.0, (float) $t['facturado']);
        $this->assertSame(30000.0, (float) $t['pendiente']);
        $this->assertSame(30000.0, (float) $t['anulado']);
        $this->assertSame(1, (int) $t['cobros']);
    }

    #[Test]
    public function lo_anulado_no_entra_en_lo_facturado(): void
    {
        // El error clásico de un informe: sumar todo lo que hay en
        // la tabla de pagos.
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, ['monto' => 50000, 'estado' => 'reembolsado']);

        $this->assertSame(
            0.0,
            (float) $this->repository->summary([])['totales']['facturado']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Agrupaciones
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function agrupa_por_forma_de_pago(): void
    {
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, ['monto' => 50000, 'metodo' => 'efectivo']);
        $this->cita('2027-03-02', '09:00', $this->laura, 30000, ['monto' => 30000, 'metodo' => 'efectivo']);
        $this->cita('2027-03-03', '09:00', $this->laura, 90000, ['monto' => 90000, 'metodo' => 'tarjeta']);

        $porMetodo = $this->repository->summary([])['por_metodo'];

        // Ordenado por total descendente.
        $this->assertSame('tarjeta', $porMetodo[0]['etiqueta']);
        $this->assertSame(90000.0, (float) $porMetodo[0]['total']);
        $this->assertSame('efectivo', $porMetodo[1]['etiqueta']);
        $this->assertSame(80000.0, (float) $porMetodo[1]['total']);
        $this->assertSame(2, (int) $porMetodo[1]['cobros']);
    }

    #[Test]
    public function agrupa_por_profesional(): void
    {
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, ['monto' => 50000]);
        $this->cita('2027-03-02', '09:00', $this->marta, 70000, ['monto' => 70000]);
        $this->cita('2027-03-03', '09:00', $this->marta, 10000, ['monto' => 10000]);

        $porProfesional = $this->repository->summary([])['por_profesional'];

        $this->assertCount(2, $porProfesional);
        $this->assertSame('Marta', $porProfesional[0]['etiqueta']);
        $this->assertSame(80000.0, (float) $porProfesional[0]['total']);
    }

    #[Test]
    public function agrupa_por_servicio(): void
    {
        $manicura = $this->serviceId;
        $nailArt = $this->createService(120, 'Nail Art');

        $this->cita('2027-03-01', '09:00', $this->laura, 50000, ['monto' => 50000]);
        $this->cita('2027-03-02', '09:00', $this->laura, 30000, ['monto' => 30000]);

        $este = $this->createAppointment(
            $this->clientId,
            $nailArt,
            $this->laura,
            '2027-03-03',
            '09:00',
            'pendiente',
            90000
        );

        $this->db->prepare(
            'INSERT INTO pagos (id_cita, monto, metodo, estado)
             VALUES (?, ?, ?, ?)'
        )->execute([$este, 90000, 'efectivo', 'pagado']);

        $porServicio = $this->repository->summary([])['por_servicio'];

        $this->assertCount(2, $porServicio);
        $this->assertSame('Nail Art', $porServicio[0]['etiqueta']);
        $this->assertSame(90000.0, (float) $porServicio[0]['total']);
        $this->assertSame('Manicura', $porServicio[1]['etiqueta']);
        $this->assertSame(80000.0, (float) $porServicio[1]['total']);
        $this->assertSame(2, (int) $porServicio[1]['cobros']);
    }

    #[Test]
    public function las_agrupaciones_ignoran_lo_no_cobrado(): void
    {
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, ['monto' => 50000, 'estado' => 'pendiente']);

        $resumen = $this->repository->summary([]);

        $this->assertSame([], $resumen['por_metodo']);
        $this->assertSame([], $resumen['por_profesional']);
        $this->assertSame([], $resumen['por_servicio']);
    }

    /*
    |--------------------------------------------------------------------------
    | Citas sin cobrar
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function suma_lo_que_falta_por_cobrar(): void
    {
        // Solo es posible porque la cita congela su precio.
        $this->cita('2027-03-01', '09:00', $this->laura, 50000);
        $this->cita('2027-03-02', '09:00', $this->laura, 30000);
        $this->cita('2027-03-03', '09:00', $this->laura, 90000, ['monto' => 90000]);

        $sinCobrar = $this->repository->summary([])['sin_cobrar'];

        $this->assertSame(2, (int) $sinCobrar['citas']);
        $this->assertSame(80000.0, (float) $sinCobrar['total']);
    }

    #[Test]
    public function una_cita_cancelada_no_cuenta_como_deuda(): void
    {
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, null, 'cancelada');

        $this->assertSame(
            0,
            (int) $this->repository->summary([])['sin_cobrar']['citas']
        );
    }

    #[Test]
    public function una_cita_con_cobro_anulado_vuelve_a_deber(): void
    {
        // El cobro sigue en la tabla, pero ya no cuenta.
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, [
            'monto' => 50000,
            'estado' => 'reembolsado'
        ]);

        $sinCobrar = $this->repository->summary([])['sin_cobrar'];

        $this->assertSame(1, (int) $sinCobrar['citas']);
        $this->assertSame(50000.0, (float) $sinCobrar['total']);
    }

    #[Test]
    public function una_cita_con_cobro_pendiente_no_se_cuenta_dos_veces(): void
    {
        // Pendiente ya tiene cobro vigente: no está "sin cobrar",
        // aunque todavía no sea facturación.
        $this->cita('2027-03-01', '09:00', $this->laura, 50000, [
            'monto' => 50000,
            'estado' => 'pendiente'
        ]);

        $resumen = $this->repository->summary([]);

        $this->assertSame(0, (int) $resumen['sin_cobrar']['citas']);
        $this->assertSame(50000.0, (float) $resumen['totales']['pendiente']);
    }

    /*
    |--------------------------------------------------------------------------
    | Rango
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_rango_acota_todas_las_secciones(): void
    {
        $this->cita('2027-03-15', '09:00', $this->laura, 50000, ['monto' => 50000]);
        $this->cita('2027-04-15', '09:00', $this->marta, 90000, ['monto' => 90000]);
        $this->cita('2027-04-16', '09:00', $this->marta, 30000);

        $marzo = $this->repository->summary([
            'desde' => '2027-03-01',
            'hasta' => '2027-03-31'
        ]);

        $this->assertSame(50000.0, (float) $marzo['totales']['facturado']);
        $this->assertCount(1, $marzo['por_profesional']);
        $this->assertSame('Laura', $marzo['por_profesional'][0]['etiqueta']);
        $this->assertSame(0, (int) $marzo['sin_cobrar']['citas']);

        $abril = $this->repository->summary([
            'desde' => '2027-04-01',
            'hasta' => '2027-04-30'
        ]);

        $this->assertSame(90000.0, (float) $abril['totales']['facturado']);
        $this->assertSame(30000.0, (float) $abril['sin_cobrar']['total']);
    }

    #[Test]
    public function el_rango_usa_la_fecha_de_la_cita(): void
    {
        // El cobro se registra hoy, pero la cita es de marzo: la
        // facturación pertenece al periodo del trabajo.
        $this->cita('2027-03-15', '09:00', $this->laura, 50000, ['monto' => 50000]);

        $this->assertSame(
            50000.0,
            (float) $this->repository->summary([
                'desde' => '2027-03-01',
                'hasta' => '2027-03-31'
            ])['totales']['facturado']
        );
    }
}
