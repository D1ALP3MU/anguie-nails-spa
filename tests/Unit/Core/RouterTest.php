<?php

namespace Tests\Unit\Core;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Core\Container;
use App\Core\Request;
use App\Core\Route;
use App\Core\Router;
use App\Exceptions\AuthException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;
use App\Helpers\JwtHelper;

/**
 * Despacho de peticiones: emparejamiento, guardas e inyección
 * de argumentos en el controlador.
 */
class RouterTest extends TestCase
{
    private Container $container;
    private ControladorEspia $spy;

    protected function setUp(): void
    {
        $_ENV['JWT_SECRET'] = str_repeat('clave-de-pruebas-', 4);
        $_ENV['JWT_EXPIRE'] = '3600';

        unset($_SERVER['HTTP_AUTHORIZATION']);

        $this->spy = new ControladorEspia();

        $this->container = new Container();

        $this->container->bind(ControladorEspia::class, $this->spy);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    /**
     * @param array<Route> $routes
     */
    private function router(array $routes): Router
    {
        return new Router($this->container, $routes);
    }

    private function authenticateAs(int $role): void
    {
        $token = JwtHelper::generate([
            'id_usuario' => 7,
            'id_rol' => $role
        ]);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    /*
    |--------------------------------------------------------------------------
    | Emparejamiento
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function ejecuta_la_accion_de_la_ruta_que_coincide(): void
    {
        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
        ])->dispatch(new Request('GET', '/api/cosas'));

        $this->assertSame('sinArgumentos', $this->spy->llamada);
    }

    #[Test]
    public function una_ruta_desconocida_es_no_encontrada(): void
    {
        $this->expectException(NotFoundException::class);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
        ])->dispatch(new Request('GET', '/api/otra'));
    }

    #[Test]
    public function una_ruta_existente_con_otro_metodo_es_metodo_no_permitido(): void
    {
        // Distinguirlo de un 404 ayuda a depurar clientes de la API.
        $this->expectException(MethodNotAllowedException::class);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
        ])->dispatch(new Request('PATCH', '/api/cosas'));
    }

    #[Test]
    public function toma_la_primera_ruta_que_coincide(): void
    {
        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos'),
            Route::get('/api/cosas', ControladorEspia::class, 'otraAccion')
        ])->dispatch(new Request('GET', '/api/cosas'));

        $this->assertSame('sinArgumentos', $this->spy->llamada);
    }

    /*
    |--------------------------------------------------------------------------
    | Inyección de argumentos
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function inyecta_el_parametro_de_la_ruta_convertido_a_entero(): void
    {
        $this->router([
            Route::get('/api/cosas/{id}', ControladorEspia::class, 'conId')
        ])->dispatch(new Request('GET', '/api/cosas/42'));

        $this->assertSame(42, $this->spy->id);
        $this->assertIsInt($this->spy->id);
    }

    #[Test]
    public function inyecta_el_usuario_autenticado(): void
    {
        $this->authenticateAs(Roles::CLIENT);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'conUsuario')
                ->requireAuth()
        ])->dispatch(new Request('GET', '/api/cosas'));

        $this->assertSame(7, $this->spy->authUser['id_usuario']);
    }

    #[Test]
    public function inyecta_parametro_y_usuario_a_la_vez(): void
    {
        $this->authenticateAs(Roles::CLIENT);

        $this->router([
            Route::get('/api/cosas/{id}', ControladorEspia::class, 'conIdYUsuario')
                ->requireAuth()
        ])->dispatch(new Request('GET', '/api/cosas/9'));

        $this->assertSame(9, $this->spy->id);
        $this->assertSame(7, $this->spy->authUser['id_usuario']);
    }

    #[Test]
    public function inyecta_la_peticion_por_tipo(): void
    {
        $request = new Request(
            'POST',
            '/api/cosas',
            ['nombre' => 'Ana']
        );

        $this->router([
            Route::post('/api/cosas', ControladorEspia::class, 'conPeticion')
        ])->dispatch($request);

        $this->assertSame(['nombre' => 'Ana'], $this->spy->body);
    }

    #[Test]
    public function inyecta_peticion_parametro_y_usuario_juntos(): void
    {
        $this->authenticateAs(Roles::CLIENT);

        $this->router([
            Route::put('/api/cosas/{id}', ControladorEspia::class, 'conTodo')
                ->requireAuth()
        ])->dispatch(
            new Request('PUT', '/api/cosas/5', ['hora' => '10:00'])
        );

        $this->assertSame(5, $this->spy->id);
        $this->assertSame(['hora' => '10:00'], $this->spy->body);
        $this->assertSame(7, $this->spy->authUser['id_usuario']);
    }

    #[Test]
    public function avisa_si_el_controlador_pide_usuario_en_una_ruta_publica(): void
    {
        // Es el error que el diseño anterior permitía cometer en
        // silencio: aquí revienta de inmediato y con explicación.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no exige autenticación');

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'conUsuario')
        ])->dispatch(new Request('GET', '/api/cosas'));
    }

    /*
    |--------------------------------------------------------------------------
    | Guardas
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function una_ruta_publica_no_exige_token(): void
    {
        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
        ])->dispatch(new Request('GET', '/api/cosas'));

        $this->assertSame('sinArgumentos', $this->spy->llamada);
    }

    #[Test]
    public function una_ruta_protegida_sin_token_falla(): void
    {
        $this->expectException(AuthException::class);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
                ->requireAuth()
        ])->dispatch(new Request('GET', '/api/cosas'));
    }

    #[Test]
    public function no_se_ejecuta_el_controlador_si_falta_el_token(): void
    {
        try {

            $this->router([
                Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
                    ->requireAuth()
            ])->dispatch(new Request('GET', '/api/cosas'));

        } catch (AuthException) {
            // Esperado.
        }

        $this->assertNull($this->spy->llamada);
    }

    #[Test]
    public function un_rol_no_autorizado_recibe_prohibido(): void
    {
        $this->authenticateAs(Roles::CLIENT);

        $this->expectException(ForbiddenException::class);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
                ->allowRoles(Roles::ADMIN)
        ])->dispatch(new Request('GET', '/api/cosas'));
    }

    #[Test]
    public function el_rol_autorizado_pasa(): void
    {
        $this->authenticateAs(Roles::ADMIN);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
                ->allowRoles(Roles::ADMIN)
        ])->dispatch(new Request('GET', '/api/cosas'));

        $this->assertSame('sinArgumentos', $this->spy->llamada);
    }

    #[Test]
    public function un_token_invalido_no_pasa_la_guarda(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer basura.no.valida';

        $this->expectException(AuthException::class);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
                ->requireAuth()
        ])->dispatch(new Request('GET', '/api/cosas'));
    }

    #[Test]
    public function un_token_sin_prefijo_bearer_no_pasa(): void
    {
        $this->authenticateAs(Roles::ADMIN);

        $_SERVER['HTTP_AUTHORIZATION'] = str_replace(
            'Bearer ',
            '',
            $_SERVER['HTTP_AUTHORIZATION']
        );

        $this->expectException(AuthException::class);

        $this->router([
            Route::get('/api/cosas', ControladorEspia::class, 'sinArgumentos')
                ->requireAuth()
        ])->dispatch(new Request('GET', '/api/cosas'));
    }
}

/**
 * Controlador de apoyo: registra qué se le llamó y con qué.
 */
class ControladorEspia
{
    public ?string $llamada = null;
    public ?int $id = null;
    public ?array $authUser = null;
    public ?array $body = null;

    public function sinArgumentos(): void
    {
        $this->llamada = 'sinArgumentos';
    }

    public function otraAccion(): void
    {
        $this->llamada = 'otraAccion';
    }

    public function conId(int $id): void
    {
        $this->llamada = 'conId';
        $this->id = $id;
    }

    public function conUsuario(array $authUser): void
    {
        $this->llamada = 'conUsuario';
        $this->authUser = $authUser;
    }

    public function conIdYUsuario(int $id, array $authUser): void
    {
        $this->llamada = 'conIdYUsuario';
        $this->id = $id;
        $this->authUser = $authUser;
    }

    public function conPeticion(Request $request): void
    {
        $this->llamada = 'conPeticion';
        $this->body = $request->body();
    }

    public function conTodo(
        int $id,
        Request $request,
        array $authUser
    ): void {
        $this->llamada = 'conTodo';
        $this->id = $id;
        $this->body = $request->body();
        $this->authUser = $authUser;
    }
}
