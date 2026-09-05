<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\ProfessionalRepository;
use App\Services\ProfessionalService;

/**
 * Reglas del equipo del salón.
 */
class ProfessionalServiceTest extends TestCase
{
    private ProfessionalRepository $repository;
    private ProfessionalService $service;

    private const ACTIVA = [
        'id_profesional' => 1,
        'nombre' => 'Laura Gómez',
        'especialidad' => 'Manicura',
        'telefono' => '3005554433',
        'activo' => 1
    ];

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProfessionalRepository::class);

        $this->service = new ProfessionalService($this->repository);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Laura Gómez',
            'especialidad' => 'Manicura',
            'telefono' => '3005554433'
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Alta
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function registra_un_profesional(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->willReturn(9);

        $this->assertSame(9, $this->service->create($this->payload()));
    }

    #[Test]
    public function el_nombre_es_obligatorio(): void
    {
        $this->repository->expects($this->never())->method('create');

        $this->expectException(ValidationException::class);

        $this->service->create($this->payload(['nombre' => '  ']));
    }

    #[Test]
    public function los_campos_opcionales_vacios_se_guardan_como_nulos(): void
    {
        // Así la lectura es uniforme: siempre null, nunca cadena vacía.
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $d) => $d['especialidad'] === null
                    && $d['telefono'] === null
            ))
            ->willReturn(1);

        $this->service->create($this->payload([
            'especialidad' => '',
            'telefono' => '   '
        ]));
    }

    #[Test]
    public function el_nombre_se_guarda_sin_espacios_sobrantes(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $d) => $d['nombre'] === 'Laura Gómez'
            ))
            ->willReturn(1);

        $this->service->create($this->payload([
            'nombre' => '  Laura Gómez  '
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Edición
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function no_se_edita_un_profesional_inexistente(): void
    {
        $this->repository
            ->method('findByIdIncludingInactive')
            ->willReturn(null);

        $this->repository->expects($this->never())->method('update');

        $this->expectException(NotFoundException::class);

        $this->service->update(404, $this->payload());
    }

    #[Test]
    public function se_puede_editar_un_profesional_dado_de_baja(): void
    {
        // Uno inactivo sigue existiendo: corregirle el teléfono
        // no debería exigir reactivarlo antes.
        $this->repository
            ->method('findByIdIncludingInactive')
            ->willReturn([...self::ACTIVA, 'activo' => 0]);

        $this->repository->method('findById')->willReturn(self::ACTIVA);

        $this->repository->expects($this->once())->method('update');

        $this->service->update(1, $this->payload());
    }

    /*
    |--------------------------------------------------------------------------
    | Baja
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function da_de_baja_a_un_profesional_sin_agenda(): void
    {
        $this->repository
            ->method('findByIdIncludingInactive')
            ->willReturn(self::ACTIVA);

        $this->repository
            ->method('countUpcomingAppointments')
            ->willReturn(0);

        $this->repository
            ->expects($this->once())
            ->method('deactivate')
            ->with(1);

        $this->service->delete(1);
    }

    #[Test]
    public function no_se_da_de_baja_a_quien_tiene_citas_pendientes(): void
    {
        // Dejaría a clientas esperando a alguien que ya no atiende.
        $this->repository
            ->method('findByIdIncludingInactive')
            ->willReturn(self::ACTIVA);

        $this->repository
            ->method('countUpcomingAppointments')
            ->willReturn(3);

        $this->repository->expects($this->never())->method('deactivate');

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('3 citas pendientes');

        $this->service->delete(1);
    }

    #[Test]
    public function el_mensaje_concuerda_en_singular(): void
    {
        $this->repository
            ->method('findByIdIncludingInactive')
            ->willReturn(self::ACTIVA);

        $this->repository
            ->method('countUpcomingAppointments')
            ->willReturn(1);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessage('1 cita pendiente');

        $this->service->delete(1);
    }

    #[Test]
    public function no_se_da_de_baja_dos_veces(): void
    {
        $this->repository
            ->method('findByIdIncludingInactive')
            ->willReturn([...self::ACTIVA, 'activo' => 0]);

        $this->repository->expects($this->never())->method('deactivate');

        $this->expectException(ConflictException::class);

        $this->service->delete(1);
    }

    #[Test]
    public function no_se_da_de_baja_a_un_profesional_inexistente(): void
    {
        $this->repository
            ->method('findByIdIncludingInactive')
            ->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->delete(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Consulta
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function consultar_uno_inexistente_lanza_no_encontrado(): void
    {
        $this->repository->method('findById')->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->service->findById(404);
    }
}
