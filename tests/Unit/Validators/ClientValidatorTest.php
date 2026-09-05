<?php

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\ValidationException;
use App\Validators\ClientValidator;

/**
 * Datos de alta y edición de un cliente.
 *
 * Es el validador del registro público desde que ambos caminos
 * de alta se unificaron en ClientService.
 */
class ClientValidatorTest extends TestCase
{
    private function valid(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'Password123',
            'telefono' => '3001112233',
            'direccion' => 'Calle 1 #2-3'
        ], $overrides);
    }

    private function assertFailsOn(string $field, array $data): void
    {
        try {

            ClientValidator::validate($data);

        } catch (ValidationException $e) {

            $this->assertArrayHasKey($field, $e->getErrors());

            return;
        }

        $this->fail("Se esperaba un error de validación en '{$field}'.");
    }

    #[Test]
    public function acepta_un_cliente_bien_formado(): void
    {
        ClientValidator::validate($this->valid());

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function la_direccion_es_opcional(): void
    {
        $data = $this->valid();

        unset($data['direccion']);

        ClientValidator::validate($data);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    #[DataProvider('nombresInvalidos')]
    public function valida_el_nombre(string $value): void
    {
        $this->assertFailsOn('nombre', $this->valid(['nombre' => $value]));
    }

    public static function nombresInvalidos(): array
    {
        return [
            'vacio' => [''],
            'solo espacios' => ['   '],
            'demasiado corto' => ['Al'],
            'demasiado largo' => [str_repeat('a', 101)],
        ];
    }

    #[Test]
    #[DataProvider('correosInvalidos')]
    public function valida_el_correo(string $value): void
    {
        $this->assertFailsOn('email', $this->valid(['email' => $value]));
    }

    public static function correosInvalidos(): array
    {
        return [
            'vacio' => [''],
            'sin arroba' => ['anaexample.com'],
            'sin dominio' => ['ana@'],
            'con espacios' => ['a na@example.com'],
        ];
    }

    #[Test]
    #[DataProvider('contrasenasInvalidas')]
    public function valida_la_contrasena(string $value): void
    {
        $this->assertFailsOn('password', $this->valid(['password' => $value]));
    }

    public static function contrasenasInvalidas(): array
    {
        return [
            'vacia' => [''],
            'menos de ocho' => ['Pass123'],
        ];
    }

    #[Test]
    public function acepta_una_contrasena_de_ocho_caracteres(): void
    {
        ClientValidator::validate($this->valid(['password' => '12345678']));

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function el_telefono_es_obligatorio(): void
    {
        // Sin teléfono no se puede confirmar una cita.
        $this->assertFailsOn('telefono', $this->valid(['telefono' => '']));
    }

    #[Test]
    public function limita_la_longitud_del_telefono(): void
    {
        $this->assertFailsOn(
            'telefono',
            $this->valid(['telefono' => str_repeat('3', 21)])
        );
    }

    #[Test]
    public function limita_la_longitud_de_la_direccion(): void
    {
        $this->assertFailsOn(
            'direccion',
            $this->valid(['direccion' => str_repeat('a', 256)])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Edición
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function al_editar_no_se_exige_la_contrasena(): void
    {
        $data = $this->valid();

        unset($data['password']);

        ClientValidator::validateUpdate($data);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function al_editar_se_siguen_validando_los_demas_campos(): void
    {
        $this->expectException(ValidationException::class);

        ClientValidator::validateUpdate(
            $this->valid(['email' => 'no-es-un-correo'])
        );
    }

    #[Test]
    public function acumula_todos_los_errores(): void
    {
        try {

            ClientValidator::validate([]);

        } catch (ValidationException $e) {

            $this->assertSame(
                ['nombre', 'email', 'password', 'telefono'],
                array_keys($e->getErrors())
            );

            return;
        }

        $this->fail('Se esperaba una excepción de validación.');
    }
}
