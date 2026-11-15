<?php

namespace Tests\Unit\Config;

use DateTimeImmutable;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Config\SalonSchedule;

/**
 * Horario del salón.
 *
 * Las fechas son fijas y de día conocido —el 16 de noviembre de 2026
 * es lunes; el 15, domingo— porque fits() no depende de hoy.
 */
class SalonScheduleTest extends TestCase
{
    private const LUNES = '2026-11-16';
    private const SABADO = '2026-11-21';
    private const DOMINGO = '2026-11-15';

    private static function at(string $date, string $time): DateTimeImmutable
    {
        return new DateTimeImmutable("{$date} {$time}");
    }

    #[Test]
    public function sin_configuracion_rige_el_horario_por_defecto(): void
    {
        $this->assertSame([
            'dias' => [1, 2, 3, 4, 5, 6],
            'apertura' => '09:00',
            'cierre' => '19:00',
            'intervalo' => 30,
            'antelacion_max_dias' => 56
        ], (new SalonSchedule([]))->toArray());
    }

    #[Test]
    public function abre_de_lunes_a_sabado_y_no_el_domingo(): void
    {
        $schedule = new SalonSchedule([]);

        $this->assertTrue($schedule->isOpenOn(self::at(self::LUNES, '10:00')));
        $this->assertTrue($schedule->isOpenOn(self::at(self::SABADO, '10:00')));
        $this->assertFalse($schedule->isOpenOn(self::at(self::DOMINGO, '10:00')));
    }

    #[Test]
    public function una_cita_puede_empezar_al_abrir_y_terminar_al_cerrar(): void
    {
        $schedule = new SalonSchedule([]);

        $this->assertTrue($schedule->fits(
            self::at(self::LUNES, '09:00'),
            self::at(self::LUNES, '10:30')
        ));

        $this->assertTrue($schedule->fits(
            self::at(self::LUNES, '17:30'),
            self::at(self::LUNES, '19:00')
        ));
    }

    #[Test]
    public function una_cita_no_puede_empezar_antes_de_abrir(): void
    {
        $this->assertFalse((new SalonSchedule([]))->fits(
            self::at(self::LUNES, '08:59'),
            self::at(self::LUNES, '10:00')
        ));
    }

    #[Test]
    public function una_cita_no_puede_terminar_despues_de_cerrar(): void
    {
        // Empieza en horario, pero el servicio no termina a tiempo.
        $this->assertFalse((new SalonSchedule([]))->fits(
            self::at(self::LUNES, '18:00'),
            self::at(self::LUNES, '19:30')
        ));
    }

    #[Test]
    public function el_domingo_no_cabe_ninguna_cita(): void
    {
        $this->assertFalse((new SalonSchedule([]))->fits(
            self::at(self::DOMINGO, '10:00'),
            self::at(self::DOMINGO, '11:00')
        ));
    }

    #[Test]
    public function las_horas_de_inicio_dejan_terminar_el_servicio_antes_del_cierre(): void
    {
        // 90 minutos: la última hora que se ofrece es 17:30.
        $times = (new SalonSchedule([]))->startTimes(90);

        $this->assertSame(9 * 60, $times[0]);
        $this->assertSame(17 * 60 + 30, end($times));
        $this->assertCount(18, $times);
    }

    #[Test]
    public function un_servicio_mas_largo_que_la_jornada_no_tiene_horas(): void
    {
        $this->assertSame([], (new SalonSchedule([]))->startTimes(11 * 60));
    }

    #[Test]
    public function la_configuracion_se_lee_del_env(): void
    {
        $schedule = new SalonSchedule([
            'SALON_DIAS' => '2, 4, 6',
            'SALON_APERTURA' => '10:00',
            'SALON_CIERRE' => '14:00',
            'SALON_INTERVALO' => '45',
            'SALON_ANTELACION_MAX_DIAS' => '10'
        ]);

        $this->assertSame([2, 4, 6], $schedule->toArray()['dias']);
        // 10:00, 10:45, 11:30, 12:15 y 13:00, que termina justo al cierre.
        $this->assertSame([600, 645, 690, 735, 780], $schedule->startTimes(60));
        $this->assertSame('martes, jueves y sábado', $schedule->describeDays());
        $this->assertSame('de 10:00 a 14:00', $schedule->describeHours());
        $this->assertSame('10 días', $schedule->describeMaxAhead());
    }

    #[Test]
    public function los_textos_describen_el_horario_por_defecto(): void
    {
        $schedule = new SalonSchedule([]);

        $this->assertSame('de lunes a sábado', $schedule->describeDays());
        $this->assertSame('de 9:00 a 19:00', $schedule->describeHours());
        $this->assertSame('8 semanas', $schedule->describeMaxAhead());
    }

    #[Test]
    public function el_plazo_maximo_cuenta_desde_hoy(): void
    {
        $last = (new SalonSchedule([]))->lastBookableDay(
            new DateTimeImmutable('2026-11-14 15:20')
        );

        $this->assertSame('2027-01-09 00:00', $last->format('Y-m-d H:i'));
    }

    public static function configuracionesInvalidas(): array
    {
        return [
            'día fuera de rango' => [['SALON_DIAS' => '1,8']],
            'día no numérico' => [['SALON_DIAS' => 'lunes']],
            'hora sin formato' => [['SALON_APERTURA' => '9']],
            'minutos imposibles' => [['SALON_CIERRE' => '19:75']],
            'abre después de cerrar' => [['SALON_APERTURA' => '19:00', 'SALON_CIERRE' => '09:00']],
            'intervalo cero' => [['SALON_INTERVALO' => '0']],
            'plazo negativo' => [['SALON_ANTELACION_MAX_DIAS' => '-5']]
        ];
    }

    #[Test]
    #[DataProvider('configuracionesInvalidas')]
    public function una_configuracion_mal_escrita_falla_al_arrancar(array $env): void
    {
        // Mejor un error claro que aceptar reservas a deshoras.
        $this->expectException(RuntimeException::class);

        new SalonSchedule($env);
    }

    #[Test]
    public function siempre_abierto_acepta_cualquier_dia_y_hora(): void
    {
        $schedule = SalonSchedule::alwaysOpen();

        $this->assertTrue($schedule->fits(
            self::at(self::DOMINGO, '23:30'),
            self::at('2026-11-16', '00:30')
        ));
    }
}
