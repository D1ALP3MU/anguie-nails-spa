<?php

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\ValidationException;
use App\Validators\ProfessionalValidator;

/**
 * Formato de los datos de un profesional.
 */
class ProfessionalValidatorTest extends TestCase
{
    private function valid(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Laura Gómez',
            'especialidad' => 'Manicura',
            'telefono' => '3005554433'
        ], $overrides);
    }

    private function assertFailsOn(string $field, array $data): void
    {
        try {

            ProfessionalValidator::validate($data);

        } catch (ValidationException $e) {

            $this->assertArrayHasKey($field, $e->getErrors());

            return;
        }

        $this->fail("Se esperaba un error de validación en '{$field}'.");
    }

    #[Test]
    public function acepta_un_profesional_bien_formado(): void
    {
        ProfessionalValidator::validate($this->valid());

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function solo_el_nombre_es_obligatorio(): void
    {
        // El salón puede registrar a alguien antes de tener su
        // contacto o de definir su especialidad.
        ProfessionalValidator::validate(['nombre' => 'Laura Gómez']);

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
            'demasiado corto' => ['La'],
            'demasiado largo' => [str_repeat('a', 101)],
        ];
    }

    #[Test]
    public function limita_la_longitud_de_la_especialidad(): void
    {
        $this->assertFailsOn(
            'especialidad',
            $this->valid(['especialidad' => str_repeat('a', 101)])
        );
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
    public function acepta_los_valores_en_el_limite(): void
    {
        ProfessionalValidator::validate($this->valid([
            'nombre' => str_repeat('a', 100),
            'especialidad' => str_repeat('a', 100),
            'telefono' => str_repeat('3', 20)
        ]));

        $this->expectNotToPerformAssertions();
    }
}
