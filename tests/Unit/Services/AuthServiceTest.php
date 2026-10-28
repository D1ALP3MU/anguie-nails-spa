<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Constants\Roles;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Helpers\JwtHelper;
use App\Repositories\UserRepository;
use App\Services\AuthService;

/**
 * Inicio de sesión.
 *
 * El comportamiento más delicado que se fija aquí es que el mensaje
 * de error sea idéntico cuando el correo no existe y cuando la
 * contraseña es incorrecta: cualquier diferencia permitiría
 * averiguar qué correos están registrados.
 */
class AuthServiceTest extends TestCase
{
    private UserRepository $users;
    private AuthService $service;

    private const GENERIC_ERROR = 'Credenciales inválidas.';

    private array $activeUser;

    protected function setUp(): void
    {
        // HS256 exige al menos 32 bytes de clave.
        $_ENV['JWT_SECRET'] = str_repeat('clave-de-pruebas-', 4);
        $_ENV['JWT_EXPIRE'] = '3600';

        $this->users = $this->createMock(UserRepository::class);

        $this->service = new AuthService($this->users);

        $this->activeUser = [
            'id_usuario' => 7,
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'password_hash' => password_hash('Password123', PASSWORD_DEFAULT),
            'id_rol' => Roles::CLIENT,
            'activo' => 1
        ];
    }

    private function credentials(array $overrides = []): array
    {
        return array_merge([
            'email' => 'ana@example.com',
            'password' => 'Password123'
        ], $overrides);
    }

    #[Test]
    public function un_correo_inexistente_da_el_mensaje_generico(): void
    {
        $this->users->method('findByEmail')->willReturn(null);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage(self::GENERIC_ERROR);

        $this->service->login($this->credentials());
    }

    #[Test]
    public function una_contrasena_incorrecta_da_el_mismo_mensaje(): void
    {
        // Idéntico al caso anterior: no se filtra si el correo existe.
        $this->users->method('findByEmail')->willReturn($this->activeUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage(self::GENERIC_ERROR);

        $this->service->login(
            $this->credentials(['password' => 'otra-cosa'])
        );
    }

    #[Test]
    public function un_usuario_desactivado_no_puede_entrar(): void
    {
        $this->users->method('findByEmail')->willReturn(
            [...$this->activeUser, 'activo' => 0]
        );

        $this->expectException(UnauthorizedException::class);

        $this->service->login($this->credentials());
    }

    #[Test]
    public function unas_credenciales_validas_devuelven_token_y_usuario(): void
    {
        $this->users->method('findByEmail')->willReturn($this->activeUser);

        $result = $this->service->login($this->credentials());

        $this->assertArrayHasKey('token', $result);
        $this->assertSame(7, $result['user']['id_usuario']);
        $this->assertSame('ana@example.com', $result['user']['email']);
    }

    #[Test]
    public function la_respuesta_nunca_expone_el_hash_de_la_contrasena(): void
    {
        $this->users->method('findByEmail')->willReturn($this->activeUser);

        $result = $this->service->login($this->credentials());

        $this->assertStringNotContainsString(
            $this->activeUser['password_hash'],
            json_encode($result)
        );

        $this->assertArrayNotHasKey('password_hash', $result['user']);
    }

    #[Test]
    public function el_token_lleva_el_usuario_y_el_rol(): void
    {
        $this->users->method('findByEmail')->willReturn($this->activeUser);

        $token = $this->service->login($this->credentials())['token'];

        $payload = JwtHelper::validate($token);

        $this->assertSame(7, $payload['id_usuario']);
        $this->assertSame(Roles::CLIENT, $payload['id_rol']);
        $this->assertArrayNotHasKey('password_hash', $payload);
    }

    #[Test]
    public function los_campos_obligatorios_se_validan(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->login(['email' => '', 'password' => '']);
    }

    /*
    |--------------------------------------------------------------------------
    | Cambio de contraseña
    |--------------------------------------------------------------------------
    */

    /**
     * Sesión tal y como la entrega el token.
     */
    private function session(): array
    {
        return [
            'id_usuario' => 7,
            'nombre' => 'Ana',
            'email' => 'ana@example.com',
            'id_rol' => Roles::CLIENT
        ];
    }

    private function passwordPayload(array $overrides = []): array
    {
        return array_merge([
            'password_actual' => 'Password123',
            'password_nueva' => 'clave-nueva-de-prueba',
            'password_confirmacion' => 'clave-nueva-de-prueba'
        ], $overrides);
    }

    #[Test]
    public function la_contrasena_nueva_se_guarda_cifrada(): void
    {
        $this->users->method('findById')->willReturn($this->activeUser);

        $guardado = null;

        $this->users
            ->expects($this->once())
            ->method('updatePassword')
            ->with(7, $this->callback(
                function (string $hash) use (&$guardado) {
                    $guardado = $hash;
                    return true;
                }
            ))
            ->willReturn(true);

        $this->service->changePassword(
            $this->session(),
            $this->passwordPayload()
        );

        $this->assertNotSame('clave-nueva-de-prueba', $guardado);
        $this->assertTrue(password_verify('clave-nueva-de-prueba', $guardado));
    }

    #[Test]
    public function el_usuario_sale_del_token_y_no_del_cuerpo(): void
    {
        // Enviar otro identificador no debe cambiar a quién se afecta.
        $this->users
            ->expects($this->once())
            ->method('findById')
            ->with(7)
            ->willReturn($this->activeUser);

        $this->users
            ->expects($this->once())
            ->method('updatePassword')
            ->with(7, $this->anything())
            ->willReturn(true);

        $this->service->changePassword(
            $this->session(),
            $this->passwordPayload(['id_usuario' => 999])
        );
    }

    #[Test]
    public function una_contrasena_actual_incorrecta_no_cierra_la_sesion(): void
    {
        /*
         * Debe ser 422 y no 401: el cliente HTTP del frontend cierra
         * la sesión ante un 401, así que un error de tecleo echaría
         * fuera a quien solo quería cambiar la contraseña.
         */
        $this->users->method('findById')->willReturn($this->activeUser);

        $this->users
            ->expects($this->never())
            ->method('updatePassword');

        $this->expectException(ValidationException::class);

        $this->service->changePassword(
            $this->session(),
            $this->passwordPayload(['password_actual' => 'me-equivoque'])
        );
    }

    #[Test]
    public function una_cuenta_desactivada_no_cambia_su_contrasena(): void
    {
        $this->users->method('findById')->willReturn(
            [...$this->activeUser, 'activo' => 0]
        );

        $this->users
            ->expects($this->never())
            ->method('updatePassword');

        $this->expectException(UnauthorizedException::class);

        $this->service->changePassword(
            $this->session(),
            $this->passwordPayload()
        );
    }

    #[Test]
    public function la_contrasena_nueva_debe_ser_distinta_de_la_actual(): void
    {
        $this->users
            ->expects($this->never())
            ->method('updatePassword');

        $this->expectException(ValidationException::class);

        $this->service->changePassword($this->session(), [
            'password_actual' => 'Password123',
            'password_nueva' => 'Password123',
            'password_confirmacion' => 'Password123'
        ]);
    }

    #[Test]
    public function la_confirmacion_debe_coincidir(): void
    {
        $this->users
            ->expects($this->never())
            ->method('updatePassword');

        $this->expectException(ValidationException::class);

        $this->service->changePassword(
            $this->session(),
            $this->passwordPayload(['password_confirmacion' => 'otra-cosa'])
        );
    }

    #[Test]
    public function la_contrasena_nueva_respeta_la_longitud_minima(): void
    {
        $this->users
            ->expects($this->never())
            ->method('updatePassword');

        $this->expectException(ValidationException::class);

        $this->service->changePassword(
            $this->session(),
            $this->passwordPayload([
                'password_nueva' => 'corta',
                'password_confirmacion' => 'corta'
            ])
        );
    }
}
