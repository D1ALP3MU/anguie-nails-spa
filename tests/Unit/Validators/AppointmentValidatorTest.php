<?php

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\ValidationException;
use App\Validators\AppointmentValidator;

/**
 * Formato de los datos de una cita.
 *
 * Este validador solo mira la forma. Que el servicio exista o que
 * el horario esté libre lo comprueba AppointmentService.
 */
class AppointmentValidatorTest extends TestCase
{
    private function valid(array $overrides = []): array
    {
        return array_merge([
            'id_cliente' => 5,
            'id_servicio' => 2,
            'id_profesional' => 1,
            'fecha' => '2027-05-10',
            'hora' => '10:00'
        ], $overrides);
    }

    /**
     * Comprueba que el campo indicado aparezca en los errores.
     */
    private function assertFailsOn(string $field, array $data): void
    {
        try {

            AppointmentValidator::validate($data);

        } catch (ValidationException $e) {

            $this->assertArrayHasKey($field, $e->getErrors());

            return;
        }

        $this->fail("Se esperaba un error de validación en '{$field}'.");
    }

    #[Test]
    public function acepta_una_cita_bien_formada(): void
    {
        AppointmentValidator::validate($this->valid());

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function el_estado_es_opcional_y_asume_pendiente(): void
    {
        $data = $this->valid();

        unset($data['estado']);

        AppointmentValidator::validate($data);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    #[DataProvider('camposObligatorios')]
    public function exige_los_identificadores(string $field): void
    {
        $data = $this->valid();

        unset($data[$field]);

        $this->assertFailsOn($field, $data);
    }

    public static function camposObligatorios(): array
    {
        return [
            'cliente' => ['id_cliente'],
            'servicio' => ['id_servicio'],
            'profesional' => ['id_profesional'],
            'fecha' => ['fecha'],
            'hora' => ['hora'],
        ];
    }

    #[Test]
    #[DataProvider('identificadoresInvalidos')]
    public function rechaza_identificadores_no_positivos(mixed $value): void
    {
        $this->assertFailsOn(
            'id_cliente',
            $this->valid(['id_cliente' => $value])
        );
    }

    public static function identificadoresInvalidos(): array
    {
        return [
            'cero' => [0],
            'negativo' => [-3],
            'texto' => ['abc'],
            'decimal' => [1.5],
        ];
    }

    #[Test]
    #[DataProvider('fechasInvalidas')]
    public function exige_el_formato_de_fecha(string $value): void
    {
        $this->assertFailsOn('fecha', $this->valid(['fecha' => $value]));
    }

    public static function fechasInvalidas(): array
    {
        return [
            'formato local' => ['10/05/2027'],
            'mes inexistente' => ['2027-13-01'],
            'dia inexistente' => ['2027-02-30'],
            'texto' => ['mañana'],
        ];
    }

    #[Test]
    #[DataProvider('horasInvalidas')]
    public function exige_el_formato_de_hora(string $value): void
    {
        $this->assertFailsOn('hora', $this->valid(['hora' => $value]));
    }

    public static function horasInvalidas(): array
    {
        return [
            'con segundos' => ['10:00:00'],
            'hora imposible' => ['25:00'],
            'minuto imposible' => ['10:75'],
            'texto' => ['diez'],
        ];
    }

    #[Test]
    public function rechaza_un_estado_fuera_del_catalogo(): void
    {
        $this->assertFailsOn(
            'estado',
            $this->valid(['estado' => 'inventado'])
        );
    }

    #[Test]
    #[DataProvider('estadosValidos')]
    public function acepta_los_estados_del_catalogo(string $estado): void
    {
        AppointmentValidator::validate($this->valid(['estado' => $estado]));

        $this->expectNotToPerformAssertions();
    }

    public static function estadosValidos(): array
    {
        return [
            ['pendiente'],
            ['confirmada'],
            ['cancelada'],
            ['completada'],
        ];
    }

    #[Test]
    public function limita_la_longitud_de_las_notas(): void
    {
        $this->assertFailsOn(
            'notas',
            $this->valid(['notas' => str_repeat('a', 1001)])
        );
    }

    #[Test]
    public function acepta_notas_en_el_limite(): void
    {
        AppointmentValidator::validate(
            $this->valid(['notas' => str_repeat('a', 1000)])
        );

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function acumula_todos_los_errores_en_una_sola_respuesta(): void
    {
        // La API devuelve el mapa completo para que el formulario
        // marque todos los campos de una vez.
        try {

            AppointmentValidator::validate([]);

        } catch (ValidationException $e) {

            $this->assertSame(
                ['id_cliente', 'id_servicio', 'id_profesional', 'fecha', 'hora'],
                array_keys($e->getErrors())
            );

            return;
        }

        $this->fail('Se esperaba una excepción de validación.');
    }
}
