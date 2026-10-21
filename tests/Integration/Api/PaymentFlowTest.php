<?php

namespace Tests\Integration\Api;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Exceptions\AuthException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Helpers\JwtHelper;
use Tests\Integration\IntegrationTestCase;

/**
 * Cobros de extremo a extremo, con la tabla de rutas real.
 *
 * Lo que se comprueba aquí y no en la prueba unitaria es la
 * restricción uq_pagos_cita contra MySQL: que impida el segundo
 * cobro y que anular libere la cita.
 */
class PaymentFlowTest extends IntegrationTestCase
{
    private Router $router;

    private int $clientId;
    private int $otherClientId;
    private int $clientUserId;
    private int $serviceId;
    private int $professionalId;
    private int $appointmentId;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['JWT_SECRET'] = str_repeat('clave-de-pruebas-', 4);
        $_ENV['JWT_EXPIRE'] = '3600';

        unset($_SERVER['HTTP_AUTHORIZATION']);

        $container = new Container();

        $container->bind(PDO::class, $this->db);

        $this->router = new Router(
            $container,
            require dirname(__DIR__, 3) . '/backend/app/routes/api.php'
        );

        $this->clientId = $this->createClient('ana@example.com', 'Ana');
        $this->otherClientId = $this->createClient('bea@example.com', 'Bea');

        $this->clientUserId = (int) $this->db
            ->query('SELECT id_usuario FROM clientes WHERE id_cliente = ' . $this->clientId)
            ->fetchColumn();

        $this->serviceId = $this->createService(60);
        $this->professionalId = $this->createProfessional();

        $this->appointmentId = $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    private function authenticateAs(int $userId, int $role): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . JwtHelper::generate([
            'id_usuario' => $userId,
            'id_rol' => $role
        ]);
    }

    private function asClient(): void
    {
        $this->authenticateAs($this->clientUserId, Roles::CLIENT);
    }

    private function asAdmin(): void
    {
        $this->authenticateAs(
            $this->createUser('admin@example.com', Roles::ADMIN, 'Admin'),
            Roles::ADMIN
        );
    }

    /**
     * Despacha una petición.
     *
     * La cadena de consulta se separa de la ruta igual que hace
     * Request::fromGlobals(): el enrutador empareja solo la ruta.
     */
    private function send(string $method, string $path, array $body = []): array
    {
        [$route, $queryString] = array_pad(explode('?', $path, 2), 2, '');

        parse_str($queryString, $query);

        ob_start();

        try {
            $this->router->dispatch(
                new Request($method, $route, $body, $query)
            );
        } finally {
            $output = ob_get_clean();
        }

        return json_decode($output, true) ?? [];
    }

    private function charge(array $overrides = []): array
    {
        return $this->send(
            'POST',
            "/api/appointments/{$this->appointmentId}/payment",
            array_merge(['monto' => 50000, 'metodo' => 'efectivo'], $overrides)
        );
    }

    private function statusOf(int $paymentId): string
    {
        return (string) $this->db
            ->query("SELECT estado FROM pagos WHERE id_pago = {$paymentId}")
            ->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Permisos
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function cobrar_exige_sesion(): void
    {
        $this->expectException(AuthException::class);

        $this->charge();
    }

    #[Test]
    public function una_clienta_no_puede_cobrar(): void
    {
        $this->asClient();

        $this->expectException(ForbiddenException::class);

        $this->charge();
    }

    #[Test]
    public function una_clienta_consulta_el_cobro_de_su_cita(): void
    {
        $this->asAdmin();
        $this->charge();

        $this->asClient();

        $response = $this->send(
            'GET',
            "/api/appointments/{$this->appointmentId}/payment"
        );

        $this->assertSame(50000.0, (float) $response['data']['monto']);
    }

    #[Test]
    public function una_clienta_no_ve_el_cobro_de_una_cita_ajena(): void
    {
        $ajena = $this->createAppointment(
            $this->otherClientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '15:00'
        );

        $this->asClient();

        $this->expectException(NotFoundException::class);

        $this->send('GET', "/api/appointments/{$ajena}/payment");
    }

    #[Test]
    public function una_cita_sin_cobrar_devuelve_nulo_y_no_un_error(): void
    {
        // Es el estado normal de una cita recién agendada.
        $this->asClient();

        $response = $this->send(
            'GET',
            "/api/appointments/{$this->appointmentId}/payment"
        );

        $this->assertTrue($response['success']);
        $this->assertNull($response['data']);
    }

    /*
    |--------------------------------------------------------------------------
    | Un cobro vigente por cita
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_cobro_queda_registrado(): void
    {
        $this->asAdmin();

        $response = $this->charge(['referencia_pago' => 'REF-001']);

        $this->assertTrue($response['success']);
        $this->assertSame(1, $this->countRows('pagos'));
        $this->assertSame('pagado', $this->statusOf($response['data']['id_pago']));
    }

    #[Test]
    public function no_se_cobra_dos_veces_la_misma_cita(): void
    {
        $this->asAdmin();
        $this->charge();

        $this->expectException(ConflictException::class);

        $this->charge();
    }

    #[Test]
    public function anular_deja_la_cita_cobrable_otra_vez(): void
    {
        // Un UNIQUE corriente la habría dejado bloqueada: de ahí la
        // columna generada.
        $this->asAdmin();

        $primero = $this->charge()['data']['id_pago'];

        $this->send('DELETE', "/api/payments/{$primero}");

        $segundo = $this->charge(['metodo' => 'tarjeta']);

        $this->assertTrue($segundo['success']);
        $this->assertSame('reembolsado', $this->statusOf($primero));
        $this->assertSame(2, $this->countRows('pagos'));
    }

    #[Test]
    public function el_historial_de_cobros_anulados_se_conserva(): void
    {
        $this->asAdmin();

        $primero = $this->charge()['data']['id_pago'];
        $this->send('DELETE', "/api/payments/{$primero}");
        $this->charge();

        // Dos filas en la tabla, pero solo una vigente.
        $this->assertSame(2, $this->countRows('pagos'));
        $this->assertSame(
            1,
            (int) $this->db
                ->query('SELECT COUNT(*) FROM pagos WHERE cobro_vigente IS NOT NULL')
                ->fetchColumn()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function un_cobro_pendiente_se_confirma(): void
    {
        $this->asAdmin();

        $id = $this->charge([
            'metodo' => 'transferencia',
            'estado' => 'pendiente'
        ])['data']['id_pago'];

        $this->assertSame('pendiente', $this->statusOf($id));

        $this->send('PUT', "/api/payments/{$id}");

        $this->assertSame('pagado', $this->statusOf($id));
    }

    #[Test]
    public function anular_un_pendiente_lo_rechaza(): void
    {
        $this->asAdmin();

        $id = $this->charge(['estado' => 'pendiente'])['data']['id_pago'];

        $this->send('DELETE', "/api/payments/{$id}");

        $this->assertSame('rechazado', $this->statusOf($id));
    }

    #[Test]
    public function no_se_anula_dos_veces(): void
    {
        $this->asAdmin();

        $id = $this->charge()['data']['id_pago'];

        $this->send('DELETE', "/api/payments/{$id}");

        $this->expectException(ConflictException::class);

        $this->send('DELETE', "/api/payments/{$id}");
    }

    /*
    |--------------------------------------------------------------------------
    | Relación con la cita
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function no_se_cancela_una_cita_con_cobro_vigente(): void
    {
        $this->asAdmin();
        $this->charge();

        $this->expectException(ConflictException::class);

        $this->send('DELETE', "/api/appointments/{$this->appointmentId}");
    }

    #[Test]
    public function tras_anular_el_cobro_la_cita_se_puede_cancelar(): void
    {
        $this->asAdmin();

        $id = $this->charge()['data']['id_pago'];

        $this->send('DELETE', "/api/payments/{$id}");
        $this->send('DELETE', "/api/appointments/{$this->appointmentId}");

        $this->assertSame(
            'cancelada',
            (string) $this->db
                ->query("SELECT estado FROM citas WHERE id_cita = {$this->appointmentId}")
                ->fetchColumn()
        );
    }

    #[Test]
    public function no_se_cobra_una_cita_cancelada(): void
    {
        $this->asAdmin();

        $this->send('DELETE', "/api/appointments/{$this->appointmentId}");

        $this->expectException(ConflictException::class);

        $this->charge();
    }

    #[Test]
    public function el_cobro_valida_sus_datos(): void
    {
        $this->asAdmin();

        $this->expectException(ValidationException::class);

        $this->charge(['monto' => -100]);
    }

    /*
    |--------------------------------------------------------------------------
    | Listado del salón
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_listado_esta_reservado_al_administrador(): void
    {
        $this->asClient();

        $this->expectException(ForbiddenException::class);

        $this->send('GET', '/api/payments');
    }

    #[Test]
    public function el_listado_trae_los_datos_de_la_cita(): void
    {
        $this->asAdmin();
        $this->charge();

        $fila = $this->send('GET', '/api/payments')['data'][0];

        $this->assertSame('Ana', $fila['cliente']);
        $this->assertSame('Manicura', $fila['servicio']);
        $this->assertSame(50000.0, (float) $fila['precio_cita']);
    }

    #[Test]
    public function el_listado_filtra_por_forma_de_pago(): void
    {
        $this->asAdmin();
        $this->charge(['metodo' => 'tarjeta']);

        $this->assertCount(
            1,
            $this->send('GET', '/api/payments?metodo=tarjeta')['data']
        );

        $this->assertCount(
            0,
            $this->send('GET', '/api/payments?metodo=efectivo')['data']
        );
    }

    #[Test]
    public function el_listado_filtra_por_rango_de_fechas_de_la_cita(): void
    {
        $this->asAdmin();
        $this->charge();

        $this->assertCount(
            1,
            $this->send(
                'GET',
                '/api/payments?desde=' . $this->futureDate()
                    . '&hasta=' . $this->futureDate()
            )['data']
        );

        $this->assertCount(
            0,
            $this->send('GET', '/api/payments?desde=' . $this->futureDate('+90 days'))['data']
        );
    }

    #[Test]
    public function un_filtro_mal_escrito_es_un_error(): void
    {
        $this->asAdmin();

        $this->expectException(ValidationException::class);

        $this->send('GET', '/api/payments?desde=01/01/2027');
    }
}
