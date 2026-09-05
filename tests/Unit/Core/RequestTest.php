<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Core\Request;
use App\Exceptions\ValidationException;

/**
 * La petición entrante.
 *
 * Construirla con datos explícitos es justo lo que antes impedía
 * php://input, y lo que ahora hace verificable la escritura a
 * través de la API.
 */
class RequestTest extends TestCase
{
    #[Test]
    public function expone_metodo_y_ruta(): void
    {
        $request = new Request('POST', '/api/appointments');

        $this->assertSame('POST', $request->method);
        $this->assertSame('/api/appointments', $request->path);
    }

    #[Test]
    public function devuelve_el_cuerpo_completo(): void
    {
        $body = ['id_servicio' => 2, 'hora' => '10:00'];

        $this->assertSame(
            $body,
            (new Request('POST', '/x', $body))->body()
        );
    }

    #[Test]
    public function el_cuerpo_vacio_es_un_arreglo_vacio(): void
    {
        $this->assertSame([], (new Request('GET', '/x'))->body());
    }

    #[Test]
    public function lee_un_campo_del_cuerpo(): void
    {
        $request = new Request('POST', '/x', ['nombre' => 'Ana']);

        $this->assertSame('Ana', $request->input('nombre'));
    }

    #[Test]
    public function devuelve_el_valor_por_defecto_si_falta_el_campo(): void
    {
        $request = new Request('POST', '/x', []);

        $this->assertNull($request->input('nombre'));
        $this->assertSame('sin nombre', $request->input('nombre', 'sin nombre'));
    }

    #[Test]
    public function distingue_un_campo_nulo_de_uno_ausente(): void
    {
        // Enviar "notas": null es borrar las notas; no enviarlas
        // es no tocarlas. La diferencia importa al actualizar.
        $request = new Request('PUT', '/x', ['notas' => null]);

        $this->assertTrue($request->has('notas'));
        $this->assertFalse($request->has('otro'));
    }

    #[Test]
    public function lee_la_cadena_de_consulta(): void
    {
        $request = new Request('GET', '/x', [], ['pagina' => '2']);

        $this->assertSame('2', $request->query('pagina'));
        $this->assertSame(1, $request->query('limite', 1));
    }

    /*
    |--------------------------------------------------------------------------
    | Análisis del cuerpo
    |--------------------------------------------------------------------------
    */

    #[Test]
    #[DataProvider('cuerposValidos')]
    public function acepta_un_cuerpo_json_valido(
        string $raw,
        array $expected
    ): void {
        $this->assertSame($expected, $this->parse($raw));
    }

    public static function cuerposValidos(): array
    {
        return [
            'objeto' => ['{"nombre":"Ana"}', ['nombre' => 'Ana']],
            'vacio' => ['', []],
            'solo espacios' => ["  \n ", []],
            'objeto vacio' => ['{}', []],
            'arreglo' => ['[1,2]', [1, 2]],
        ];
    }

    #[Test]
    #[DataProvider('cuerposInvalidos')]
    public function rechaza_un_cuerpo_malformado(string $raw): void
    {
        // Sin esto, un JSON roto se reportaba como una lista de
        // campos obligatorios, que no dice cuál es el problema real.
        $this->expectException(ValidationException::class);

        $this->parse($raw);
    }

    public static function cuerposInvalidos(): array
    {
        return [
            'json truncado' => ['{"nombre":'],
            'comillas simples' => ["{'nombre':'Ana'}"],
            'texto suelto' => ['esto no es json'],
            'escalar' => ['42'],
        ];
    }

    /**
     * Ejercita el análisis privado del cuerpo a través de
     * fromGlobals(), sustituyendo la entrada por un flujo propio.
     */
    private function parse(string $raw): array
    {
        $method = new \ReflectionMethod(Request::class, 'parseBody');

        return $method->invoke(null, $raw);
    }
}
