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
use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Helpers\JwtHelper;
use Tests\Integration\IntegrationTestCase;

/**
 * Recorrido completo con la tabla de rutas real.
 *
 * Ejercita en una sola pasada la tabla de rutas, las guardas, el
 * contenedor, los servicios, los repositorios y el SQL contra
 * MySQL. Lo único que queda fuera es la capa HTTP.
 *
 * Cubre lectura y escritura: desde que el enrutador inyecta un
 * objeto Request, el cuerpo de la petición se puede construir con
 * datos explícitos en lugar de leerse de php://input.
 */
class ApiFlowTest extends IntegrationTestCase
{
    private Router $router;

    private int $clientId;
    private int $otherClientId;
    private int $clientUserId;
    private int $serviceId;
    private int $professionalId;

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
        $adminId = $this->createUser('admin@example.com', Roles::ADMIN, 'Admin');

        $this->authenticateAs($adminId, Roles::ADMIN);
    }

    /**
     * Despacha una petición y devuelve la respuesta JSON decodificada.
     */
    private function send(
        string $method,
        string $path,
        array $body = []
    ): array {
        ob_start();

        try {

            $this->router->dispatch(
                new Request($method, $path, $body)
            );

        } finally {

            $output = ob_get_clean();
        }

        return json_decode($output, true) ?? [];
    }

    private function get(string $path): array
    {
        return $this->send('GET', $path);
    }

    private function post(string $path, array $body = []): array
    {
        return $this->send('POST', $path, $body);
    }

    private function put(string $path, array $body = []): array
    {
        return $this->send('PUT', $path, $body);
    }

    private function delete(string $path): array
    {
        return $this->send('DELETE', $path);
    }

    /**
     * Datos de una cita válida para el cliente autenticado.
     */
    private function bookingPayload(array $overrides = []): array
    {
        return array_merge([
            'id_servicio' => $this->serviceId,
            'id_profesional' => $this->professionalId,
            'fecha' => $this->futureDate(),
            'hora' => '10:00'
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Rutas públicas
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_catalogo_se_consulta_sin_sesion(): void
    {
        $response = $this->get('/api/services');

        $this->assertTrue($response['success']);
        $this->assertCount(1, $response['data']);
        $this->assertSame('Manicura', $response['data'][0]['nombre']);
    }

    #[Test]
    public function el_detalle_de_un_servicio_es_publico(): void
    {
        $response = $this->get('/api/services/' . $this->serviceId);

        $this->assertSame(
            $this->serviceId,
            (int) $response['data']['id_servicio']
        );
    }

    #[Test]
    public function el_equipo_se_consulta_sin_sesion(): void
    {
        $response = $this->get('/api/professionals');

        $this->assertSame('Laura', $response['data'][0]['nombre']);
    }

    #[Test]
    public function un_servicio_inexistente_es_no_encontrado(): void
    {
        $this->expectException(NotFoundException::class);

        $this->get('/api/services/999999');
    }

    /*
    |--------------------------------------------------------------------------
    | Guardas sobre la tabla de rutas real
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_listado_de_clientes_exige_sesion(): void
    {
        $this->expectException(AuthException::class);

        $this->get('/api/clients');
    }

    #[Test]
    public function el_listado_de_clientes_exige_rol_administrador(): void
    {
        $this->asClient();

        $this->expectException(ForbiddenException::class);

        $this->get('/api/clients');
    }

    #[Test]
    public function el_administrador_lista_los_clientes(): void
    {
        $this->asAdmin();

        $response = $this->get('/api/clients');

        $this->assertCount(2, $response['data']);
    }

    #[Test]
    public function las_citas_exigen_sesion(): void
    {
        $this->expectException(AuthException::class);

        $this->get('/api/appointments');
    }

    #[Test]
    public function el_perfil_devuelve_al_usuario_del_token(): void
    {
        $this->asClient();

        $response = $this->get('/api/profile');

        $this->assertSame(
            $this->clientUserId,
            $response['data']['id_usuario']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Pertenencia de extremo a extremo
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function un_cliente_solo_ve_sus_propias_citas(): void
    {
        $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->createAppointment(
            $this->otherClientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '12:00'
        );

        $this->asClient();

        $response = $this->get('/api/appointments');

        $this->assertCount(1, $response['data']);
        $this->assertSame(
            $this->clientId,
            (int) $response['data'][0]['id_cliente']
        );
    }

    #[Test]
    public function el_administrador_ve_las_citas_de_todos(): void
    {
        $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->createAppointment(
            $this->otherClientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '12:00'
        );

        $this->asAdmin();

        $this->assertCount(2, $this->get('/api/appointments')['data']);
    }

    #[Test]
    public function una_cita_ajena_se_reporta_como_inexistente(): void
    {
        $ajena = $this->createAppointment(
            $this->otherClientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->asClient();

        $this->expectException(NotFoundException::class);

        $this->get('/api/appointments/' . $ajena);
    }

    #[Test]
    public function el_cliente_consulta_su_propia_cita(): void
    {
        $propia = $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->asClient();

        $response = $this->get('/api/appointments/' . $propia);

        $this->assertSame($propia, (int) $response['data']['id_cita']);
    }

    #[Test]
    public function un_cliente_no_consulta_el_perfil_de_otro(): void
    {
        $this->asClient();

        $this->expectException(NotFoundException::class);

        $this->get('/api/clients/' . $this->otherClientId);
    }

    #[Test]
    public function un_cliente_consulta_su_propio_perfil(): void
    {
        $this->asClient();

        $response = $this->get('/api/clients/' . $this->clientId);

        $this->assertSame('ana@example.com', $response['data']['email']);
    }

    /*
    |--------------------------------------------------------------------------
    | Enrutado
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function una_ruta_desconocida_es_no_encontrada(): void
    {
        $this->expectException(NotFoundException::class);

        $this->get('/api/inventado');
    }

    #[Test]
    public function un_metodo_no_soportado_se_distingue_del_404(): void
    {
        $this->expectException(MethodNotAllowedException::class);

        $this->router->dispatch(new Request('PATCH', '/api/services'));
    }

    /*
    |--------------------------------------------------------------------------
    | Registro e inicio de sesión
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_registro_publico_crea_usuario_y_cliente(): void
    {
        $antes = $this->countRows('clientes');

        $response = $this->post('/api/auth/register', [
            'nombre' => 'Carla Ruiz',
            'email' => 'carla@example.com',
            'password' => 'Password123',
            'telefono' => '3007778899'
        ]);

        $this->assertTrue($response['success']);
        $this->assertSame($antes + 1, $this->countRows('clientes'));

        $usuario = $this->db
            ->query("SELECT id_rol FROM usuarios WHERE email = 'carla@example.com'")
            ->fetchColumn();

        $this->assertSame(Roles::CLIENT, (int) $usuario);
    }

    #[Test]
    public function el_registro_rechaza_un_correo_repetido(): void
    {
        $this->expectException(ConflictException::class);

        $this->post('/api/auth/register', [
            'nombre' => 'Otra Ana',
            'email' => 'ana@example.com',
            'password' => 'Password123',
            'telefono' => '3001112233'
        ]);
    }

    #[Test]
    public function el_registro_valida_los_datos(): void
    {
        $this->expectException(ValidationException::class);

        $this->post('/api/auth/register', [
            'nombre' => ' X',
            'email' => 'no-es-correo',
            'password' => 'corta',
            'telefono' => ''
        ]);
    }

    #[Test]
    public function el_inicio_de_sesion_devuelve_un_token_utilizable(): void
    {
        $response = $this->post('/api/auth/login', [
            'email' => 'ana@example.com',
            'password' => 'Password123'
        ]);

        $payload = JwtHelper::validate($response['data']['token']);

        $this->assertSame($this->clientUserId, $payload['id_usuario']);
        $this->assertArrayNotHasKey('password_hash', $response['data']['user']);
    }

    /*
    |--------------------------------------------------------------------------
    | Reserva
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function un_cliente_reserva_sin_enviar_su_identificador(): void
    {
        $this->asClient();

        $response = $this->post('/api/appointments', $this->bookingPayload());

        $this->assertTrue($response['success']);

        $duena = $this->db
            ->query('SELECT id_cliente FROM citas LIMIT 1')
            ->fetchColumn();

        $this->assertSame($this->clientId, (int) $duena);
    }

    #[Test]
    public function el_identificador_de_cliente_enviado_se_descarta(): void
    {
        // Intentar reservar a nombre de otro no cambia nada:
        // el dueño sale del token.
        $this->asClient();

        $this->post(
            '/api/appointments',
            $this->bookingPayload(['id_cliente' => $this->otherClientId])
        );

        $duena = $this->db
            ->query('SELECT id_cliente FROM citas LIMIT 1')
            ->fetchColumn();

        $this->assertSame($this->clientId, (int) $duena);
    }

    #[Test]
    public function no_se_reserva_un_horario_ya_ocupado(): void
    {
        $this->asClient();

        $this->post('/api/appointments', $this->bookingPayload());

        $this->expectException(ConflictException::class);

        // El servicio dura 60 minutos, así que las 10:30 se cruzan.
        $this->post(
            '/api/appointments',
            $this->bookingPayload(['hora' => '10:30'])
        );
    }

    #[Test]
    public function se_puede_reservar_en_un_horario_contiguo(): void
    {
        $this->asClient();

        $this->post('/api/appointments', $this->bookingPayload());

        $response = $this->post(
            '/api/appointments',
            $this->bookingPayload(['hora' => '11:00'])
        );

        $this->assertTrue($response['success']);
        $this->assertSame(2, $this->countRows('citas'));
    }

    #[Test]
    public function no_se_reserva_en_el_pasado(): void
    {
        $this->asClient();

        $this->expectException(ValidationException::class);

        $this->post(
            '/api/appointments',
            $this->bookingPayload(['fecha' => '2020-01-01'])
        );
    }

    #[Test]
    public function no_se_reserva_con_un_servicio_inexistente(): void
    {
        $this->asClient();

        $this->expectException(ValidationException::class);

        $this->post(
            '/api/appointments',
            $this->bookingPayload(['id_servicio' => 999999])
        );
    }

    #[Test]
    public function reservar_exige_sesion(): void
    {
        $this->expectException(AuthException::class);

        $this->post('/api/appointments', $this->bookingPayload());
    }

    /*
    |--------------------------------------------------------------------------
    | Reprogramación y cancelación
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function un_cliente_reprograma_su_propia_cita(): void
    {
        $id = $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->asClient();

        $response = $this->put(
            '/api/appointments/' . $id,
            $this->bookingPayload([
                'hora' => '15:00',
                'estado' => 'pendiente'
            ])
        );

        $this->assertSame('15:00:00', $response['data']['hora']);
    }

    #[Test]
    public function un_cliente_no_reprograma_una_cita_ajena(): void
    {
        $ajena = $this->createAppointment(
            $this->otherClientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->asClient();

        $this->expectException(NotFoundException::class);

        $this->put(
            '/api/appointments/' . $ajena,
            $this->bookingPayload(['estado' => 'pendiente'])
        );
    }

    #[Test]
    public function cancelar_una_cita_cambia_su_estado_y_libera_el_horario(): void
    {
        $id = $this->createAppointment(
            $this->clientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->asClient();

        $this->delete('/api/appointments/' . $id);

        $estado = $this->db
            ->query('SELECT estado FROM citas WHERE id_cita = ' . $id)
            ->fetchColumn();

        $this->assertSame('cancelada', $estado);

        // El horario vuelve a estar disponible.
        $response = $this->post('/api/appointments', $this->bookingPayload());

        $this->assertTrue($response['success']);
    }

    #[Test]
    public function un_cliente_no_cancela_una_cita_ajena(): void
    {
        $ajena = $this->createAppointment(
            $this->otherClientId,
            $this->serviceId,
            $this->professionalId,
            $this->futureDate(),
            '10:00'
        );

        $this->asClient();

        try {

            $this->delete('/api/appointments/' . $ajena);

            $this->fail('Se esperaba que la cita ajena fuera invisible.');

        } catch (NotFoundException) {
            // Esperado.
        }

        $estado = $this->db
            ->query('SELECT estado FROM citas WHERE id_cita = ' . $ajena)
            ->fetchColumn();

        $this->assertSame('pendiente', $estado);
    }

    /*
    |--------------------------------------------------------------------------
    | Escritura sobre clientes
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function el_alta_de_clientes_esta_reservada_al_administrador(): void
    {
        $this->asClient();

        $this->expectException(ForbiddenException::class);

        $this->post('/api/clients', [
            'nombre' => 'Colada',
            'email' => 'colada@example.com',
            'password' => 'Password123',
            'telefono' => '3000000000'
        ]);
    }

    #[Test]
    public function el_administrador_da_de_alta_un_cliente(): void
    {
        $this->asAdmin();

        $antes = $this->countRows('clientes');

        $response = $this->post('/api/clients', [
            'nombre' => 'Alta Admin',
            'email' => 'alta@example.com',
            'password' => 'Password123',
            'telefono' => '3005556677'
        ]);

        $this->assertTrue($response['success']);
        $this->assertSame($antes + 1, $this->countRows('clientes'));
    }

    #[Test]
    public function un_cliente_edita_su_propio_perfil(): void
    {
        $this->asClient();

        $response = $this->put('/api/clients/' . $this->clientId, [
            'nombre' => 'Ana Actualizada',
            'email' => 'ana@example.com',
            'telefono' => '3009998877'
        ]);

        $this->assertSame('Ana Actualizada', $response['data']['nombre']);
        $this->assertSame('3009998877', $response['data']['telefono']);
    }

    #[Test]
    public function un_cliente_no_edita_el_perfil_de_otro(): void
    {
        $this->asClient();

        $this->expectException(NotFoundException::class);

        $this->put('/api/clients/' . $this->otherClientId, [
            'nombre' => 'Secuestrada',
            'email' => 'hack@example.com',
            'telefono' => '3000000000'
        ]);
    }
}
