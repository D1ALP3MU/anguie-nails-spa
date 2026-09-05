<?php

namespace Tests\Unit\Validators;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Exceptions\ValidationException;
use App\Validators\UserValidator;

/**
 * Datos del formulario de inicio de sesión.
 *
 * Este validador quedó reducido al login: el registro se valida
 * en ClientValidator desde que ambos caminos de alta se unificaron.
 */
class UserValidatorTest extends TestCase
{
    private function assertFailsOn(string $field, array $data): void
    {
        try {

            UserValidator::validateLogin($data);

        } catch (ValidationException $e) {

            $this->assertArrayHasKey($field, $e->getErrors());

            return;
        }

        $this->fail("Se esperaba un error de validación en '{$field}'.");
    }

    #[Test]
    public function acepta_credenciales_bien_formadas(): void
    {
        UserValidator::validateLogin([
            'email' => 'ana@example.com',
            'password' => 'Password123'
        ]);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function exige_el_correo(): void
    {
        $this->assertFailsOn('email', ['password' => 'Password123']);
    }

    #[Test]
    public function exige_un_correo_con_formato_valido(): void
    {
        $this->assertFailsOn('email', [
            'email' => 'no-es-un-correo',
            'password' => 'Password123'
        ]);
    }

    #[Test]
    public function exige_la_contrasena(): void
    {
        $this->assertFailsOn('password', ['email' => 'ana@example.com']);
    }

    #[Test]
    public function no_aplica_longitud_minima_al_iniciar_sesion(): void
    {
        // La regla de longitud es del registro. Al entrar solo importa
        // que se haya escrito algo: si es corta, fallará la verificación
        // del hash con el mismo mensaje genérico que cualquier otro fallo.
        UserValidator::validateLogin([
            'email' => 'ana@example.com',
            'password' => 'x'
        ]);

        $this->expectNotToPerformAssertions();
    }
}
