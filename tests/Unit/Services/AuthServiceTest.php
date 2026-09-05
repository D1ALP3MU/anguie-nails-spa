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
}
