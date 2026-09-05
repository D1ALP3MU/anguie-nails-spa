<?php

namespace Tests\Unit\Core;

use PDO;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Core\Container;
use App\Controllers\AppointmentController;
use App\Repositories\ClientRepository;
use App\Services\AppointmentService;

/**
 * Resolución de dependencias.
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    #[Test]
    public function construye_una_clase_sin_constructor(): void
    {
        $this->assertInstanceOf(
            SinDependencias::class,
            $this->container->get(SinDependencias::class)
        );
    }

    #[Test]
    public function resuelve_dependencias_anidadas(): void
    {
        $resuelto = $this->container->get(DependeDeOtra::class);

        $this->assertInstanceOf(
            SinDependencias::class,
            $resuelto->dependencia
        );
    }

    #[Test]
    public function reutiliza_la_misma_instancia(): void
    {
        // Un repositorio no debe construirse dos veces por petición.
        $this->assertSame(
            $this->container->get(SinDependencias::class),
            $this->container->get(SinDependencias::class)
        );
    }

    #[Test]
    public function comparte_la_dependencia_entre_consumidores(): void
    {
        $uno = $this->container->get(DependeDeOtra::class);
        $dos = $this->container->get(TambienDepende::class);

        $this->assertSame($uno->dependencia, $dos->dependencia);
    }

    #[Test]
    public function una_instancia_registrada_tiene_prioridad(): void
    {
        $preexistente = new SinDependencias();

        $this->container->bind(SinDependencias::class, $preexistente);

        $this->assertSame(
            $preexistente,
            $this->container->get(SinDependencias::class)
        );
    }

    #[Test]
    public function usa_el_valor_por_defecto_de_un_parametro_simple(): void
    {
        $resuelto = $this->container->get(ConValorPorDefecto::class);

        $this->assertSame(10, $resuelto->limite);
    }

    #[Test]
    public function falla_con_un_mensaje_util_si_no_puede_resolver(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('$nombre');

        $this->container->get(ExigeUnEscalar::class);
    }

    #[Test]
    public function falla_si_la_clase_no_existe(): void
    {
        $this->expectException(RuntimeException::class);

        $this->container->get('App\\Services\\NoExiste');
    }

    /*
    |--------------------------------------------------------------------------
    | Cableado real de la aplicación
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function arma_un_controlador_real_a_partir_del_pdo(): void
    {
        // Es el cableado que antes se escribía a mano en cada ruta.
        $this->container->bind(
            PDO::class,
            $this->createMock(PDO::class)
        );

        $controlador = $this->container->get(
            AppointmentController::class
        );

        $this->assertInstanceOf(
            AppointmentController::class,
            $controlador
        );
    }

    #[Test]
    public function los_repositorios_comparten_la_conexion(): void
    {
        $pdo = $this->createMock(PDO::class);

        $this->container->bind(PDO::class, $pdo);

        $this->container->get(AppointmentService::class);

        // El mismo repositorio pedido dos veces es el mismo objeto,
        // y por tanto comparte la conexión registrada.
        $this->assertSame(
            $this->container->get(ClientRepository::class),
            $this->container->get(ClientRepository::class)
        );
    }
}

/*
|--------------------------------------------------------------------------
| Clases de apoyo
|--------------------------------------------------------------------------
*/

class SinDependencias
{
}

class DependeDeOtra
{
    public function __construct(
        public SinDependencias $dependencia
    ) {}
}

class TambienDepende
{
    public function __construct(
        public SinDependencias $dependencia
    ) {}
}

class ConValorPorDefecto
{
    public function __construct(
        public int $limite = 10
    ) {}
}

class ExigeUnEscalar
{
    public function __construct(
        public string $nombre
    ) {}
}
