<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Core\Route;

/**
 * Emparejamiento de rutas y declaración de guardas.
 */
class RouteTest extends TestCase
{
    #[Test]
    public function una_ruta_estatica_coincide_sin_parametros(): void
    {
        $route = Route::get('/api/services', 'C', 'index');

        $this->assertSame(
            [],
            $route->match('GET', '/api/services')
        );
    }

    #[Test]
    public function el_metodo_debe_coincidir(): void
    {
        $route = Route::get('/api/services', 'C', 'index');

        $this->assertNull($route->match('POST', '/api/services'));
    }

    #[Test]
    public function captura_el_parametro_de_la_ruta(): void
    {
        $route = Route::get('/api/clients/{id}', 'C', 'show');

        $this->assertSame(
            ['id' => '42'],
            $route->match('GET', '/api/clients/42')
        );
    }

    #[Test]
    public function captura_varios_parametros(): void
    {
        $route = Route::get('/api/clients/{clientId}/citas/{id}', 'C', 'show');

        $this->assertSame(
            ['clientId' => '7', 'id' => '3'],
            $route->match('GET', '/api/clients/7/citas/3')
        );
    }

    #[Test]
    public function un_parametro_no_abarca_varios_segmentos(): void
    {
        $route = Route::get('/api/clients/{id}', 'C', 'show');

        $this->assertNull($route->match('GET', '/api/clients/7/citas'));
    }

    #[Test]
    public function un_parametro_no_puede_ir_vacio(): void
    {
        $route = Route::get('/api/clients/{id}', 'C', 'show');

        $this->assertNull($route->match('GET', '/api/clients/'));
    }

    #[Test]
    public function no_coincide_con_una_ruta_mas_larga(): void
    {
        $route = Route::get('/api/services', 'C', 'index');

        $this->assertNull($route->match('GET', '/api/services/extra'));
    }

    #[Test]
    public function los_tramos_literales_no_actuan_como_comodin(): void
    {
        // Sin escapar, el punto de la ruta aceptaría cualquier carácter.
        $route = Route::get('/api/v1.0/servicios', 'C', 'index');

        $this->assertNotNull($route->match('GET', '/api/v1.0/servicios'));
        $this->assertNull($route->match('GET', '/api/v1X0/servicios'));
    }

    /*
    |--------------------------------------------------------------------------
    | Guardas declaradas
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function una_ruta_es_publica_por_defecto(): void
    {
        $route = Route::get('/api/services', 'C', 'index');

        $this->assertFalse($route->needsAuthentication());
        $this->assertSame([], $route->roles());
    }

    #[Test]
    public function require_auth_exige_sesion_sin_restringir_rol(): void
    {
        $route = Route::get('/api/appointments', 'C', 'index')
            ->requireAuth();

        $this->assertTrue($route->needsAuthentication());
        $this->assertSame([], $route->roles());
    }

    #[Test]
    public function allow_roles_implica_exigir_sesion(): void
    {
        // Declarar solo el rol no debe dejar la ruta sin autenticar.
        $route = Route::get('/api/clients', 'C', 'index')
            ->allowRoles(Roles::ADMIN);

        $this->assertTrue($route->needsAuthentication());
        $this->assertSame([Roles::ADMIN], $route->roles());
    }

    #[Test]
    public function los_verbos_construyen_el_metodo_correcto(): void
    {
        $this->assertSame('GET', Route::get('/x', 'C', 'a')->method);
        $this->assertSame('POST', Route::post('/x', 'C', 'a')->method);
        $this->assertSame('PUT', Route::put('/x', 'C', 'a')->method);
        $this->assertSame('DELETE', Route::delete('/x', 'C', 'a')->method);
    }
}
