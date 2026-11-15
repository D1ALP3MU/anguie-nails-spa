<?php

namespace Tests\Integration\Api;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use App\Config\SalonSchedule;
use App\Constants\Roles;
use App\Core\Container;
use App\Core\Request;
use App\Core\Router;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Helpers\JwtHelper;
use Tests\Integration\IntegrationTestCase;

/**
 * Horario del salón y horas libres, de extremo a extremo y con el
 * horario real (lunes a sábado, 9:00 a 19:00).
 *
 * Lo que solo se comprueba aquí: la consulta de intervalos ocupados
 * contra MySQL, y que una hora ofrecida como libre se pueda reservar
 * de verdad. Si el cálculo y la validación divergieran, sería aquí
 * donde se vería.
 */
class ScheduleFlowTest extends IntegrationTestCase
{
    private Router $router;

    private int $clientId;
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

        // El horario por defecto, sin depender del .env de la máquina.
        $container->bind(SalonSchedule::class, new SalonSchedule([]));

        $this->router = new Router(
            $container,
            require dirname(__DIR__, 3) . '/backend/app/Routes/api.php'
        );

        $this->clientId = $this->createClient('ana@example.com', 'Ana');

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

    private function asClient(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . JwtHelper::generate([
            'id_usuario' => $this->clientUserId,
            'id_rol' => Roles::CLIENT
        ]);
    }

    private function send(string $method, string $path, array $body = [], array $query = []): array
    {
        ob_start();

        try {
            $this->router->dispatch(new Request($method, $path, $body, $query));
        } finally {
            $output = ob_get_clean();
        }

        return json_decode($output, true) ?? [];
    }

    /**
     * Un lunes de dentro de 8 a 14 días: futuro, día de apertura y
     * dentro del plazo de reserva, ejecute la prueba quien la ejecute.
     */
    private static function nextMonday(): string
    {
        return (new DateTimeImmutable('today'))
            ->modify('next monday')
            ->modify('+7 days')
            ->format('Y-m-d');
    }

    private function freeSlots(string $date): array
    {
        return $this->send('GET', '/api/availability', [], [
            'id_profesional' => (string) $this->professionalId,
            'id_servicio' => (string) $this->serviceId,
            'fecha' => $date
        ])['data'];
    }

    #[Test]
    public function el_horario_es_publico(): void
    {
        $response = $this->send('GET', '/api/schedule');

        $this->assertSame([1, 2, 3, 4, 5, 6], $response['data']['dias']);
        $this->assertSame('09:00', $response['data']['apertura']);
        $this->assertSame('19:00', $response['data']['cierre']);
    }

    #[Test]
    public function las_horas_libres_exigen_sesion(): void
    {
        $this->expectException(AuthException::class);

        $this->freeSlots(self::nextMonday());
    }

    #[Test]
    public function las_citas_ocupan_su_duracion_y_las_canceladas_no(): void
    {
        $monday = self::nextMonday();

        // De 10:00 a 11:00 ocupado; la de las 15:00 está cancelada.
        $this->createAppointment($this->clientId, $this->serviceId, $this->professionalId, $monday, '10:00:00');
        $this->createAppointment($this->clientId, $this->serviceId, $this->professionalId, $monday, '15:00:00', 'cancelada');

        $this->asClient();

        $horas = $this->freeSlots($monday)['horas'];

        // Termina justo cuando empieza la otra: cabe.
        $this->assertContains('09:00', $horas);
        // Se cruzan con la de las 10:00.
        $this->assertNotContains('09:30', $horas);
        $this->assertNotContains('10:00', $horas);
        $this->assertNotContains('10:30', $horas);
        // Libre de nuevo al terminar.
        $this->assertContains('11:00', $horas);
        // La cancelada no ocupa.
        $this->assertContains('15:00', $horas);
    }

    #[Test]
    public function la_agenda_de_otra_profesional_no_influye(): void
    {
        $monday = self::nextMonday();

        $other = $this->createProfessional('Sofía');

        $this->createAppointment($this->clientId, $this->serviceId, $other, $monday, '10:00:00');

        $this->asClient();

        $this->assertContains('10:00', $this->freeSlots($monday)['horas']);
    }

    #[Test]
    public function una_hora_ofrecida_como_libre_se_puede_reservar(): void
    {
        $monday = self::nextMonday();

        $this->createAppointment($this->clientId, $this->serviceId, $this->professionalId, $monday, '09:00:00');

        $this->asClient();

        $first = $this->freeSlots($monday)['horas'][0];

        $response = $this->send('POST', '/api/appointments', [
            'id_servicio' => $this->serviceId,
            'id_profesional' => $this->professionalId,
            'fecha' => $monday,
            'hora' => $first
        ]);

        $this->assertTrue($response['success']);
        // Y deja de ofrecerse.
        $this->assertNotContains($first, $this->freeSlots($monday)['horas']);
    }

    #[Test]
    public function la_api_rechaza_reservar_fuera_de_horario(): void
    {
        $this->asClient();

        $this->expectException(ValidationException::class);

        // Lo que el formulario ya no ofrece, pedido a mano a la API.
        $this->send('POST', '/api/appointments', [
            'id_servicio' => $this->serviceId,
            'id_profesional' => $this->professionalId,
            'fecha' => self::nextMonday(),
            'hora' => '20:00'
        ]);
    }
}
