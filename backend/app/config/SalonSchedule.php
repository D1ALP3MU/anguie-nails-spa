<?php

namespace App\Config;

use DateTimeImmutable;
use RuntimeException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : SalonSchedule.php
 *
 * Descripción:
 * Horario del salón: qué días abre, de qué hora a qué hora, cada
 * cuánto se ofrecen horas para reservar y con cuánta antelación.
 *
 * Se lee del .env. Sin las variables, rige el horario por defecto
 * (lunes a sábado, de 9:00 a 19:00, cada 30 minutos, hasta 8
 * semanas vista), así que un .env antiguo sigue funcionando.
 *
 * Una configuración mal escrita lanza una excepción al construirse:
 * es preferible que la reserva falle con un error claro a que
 * acepte citas a deshoras sin que nadie lo note.
 *
 * Es una dependencia inyectable: las pruebas que no tratan del
 * horario usan alwaysOpen() y no dependen del día en que se ejecutan.
 * ---------------------------------------------------------
 */
final class SalonSchedule
{
    private const DEFAULTS = [
        'SALON_DIAS' => '1,2,3,4,5,6',
        'SALON_APERTURA' => '09:00',
        'SALON_CIERRE' => '19:00',
        'SALON_INTERVALO' => '30',
        'SALON_ANTELACION_MAX_DIAS' => '56'
    ];

    private const DAY_NAMES = [
        1 => 'lunes',
        2 => 'martes',
        3 => 'miércoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sábado',
        7 => 'domingo'
    ];

    /** @var int[] Días de apertura, ISO-8601: 1 = lunes, 7 = domingo. */
    private array $days;

    /** Minutos desde la medianoche. */
    private int $opensAt;

    /** Minutos desde la medianoche; 1440 equivale a las 24:00. */
    private int $closesAt;

    private int $interval;

    private int $maxDaysAhead;

    /** Sin restricciones: solo para pruebas (ver alwaysOpen). */
    private bool $unrestricted = false;

    /**
     * @param array|null $env Configuración; por defecto, $_ENV.
     *
     * @throws RuntimeException Si la configuración no es válida.
     */
    public function __construct(?array $env = null)
    {
        $env ??= $_ENV;

        $value = fn (string $key): string =>
            trim((string) ($env[$key] ?? self::DEFAULTS[$key]));

        $this->days = $this->parseDays($value('SALON_DIAS'));
        $this->opensAt = $this->parseTime($value('SALON_APERTURA'), 'SALON_APERTURA');
        $this->closesAt = $this->parseTime($value('SALON_CIERRE'), 'SALON_CIERRE');
        $this->interval = $this->parsePositiveInt($value('SALON_INTERVALO'), 'SALON_INTERVALO');
        $this->maxDaysAhead = $this->parsePositiveInt(
            $value('SALON_ANTELACION_MAX_DIAS'),
            'SALON_ANTELACION_MAX_DIAS'
        );

        if ($this->opensAt >= $this->closesAt) {
            throw new RuntimeException(
                'SALON_APERTURA debe ser anterior a SALON_CIERRE.'
            );
        }
    }

    /**
     * Abierto siempre, a cualquier hora y sin límite de antelación.
     *
     * Es el comportamiento que tenía la aplicación antes de existir
     * el horario. Lo usan las pruebas que no tratan de él, para no
     * depender del día ni de la hora en que se ejecutan.
     */
    public static function alwaysOpen(): self
    {
        $schedule = new self([
            'SALON_DIAS' => '1,2,3,4,5,6,7',
            'SALON_APERTURA' => '00:00',
            'SALON_CIERRE' => '24:00',
            'SALON_INTERVALO' => '30',
            'SALON_ANTELACION_MAX_DIAS' => '36500'
        ]);

        $schedule->unrestricted = true;

        return $schedule;
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    public function isOpenOn(DateTimeImmutable $day): bool
    {
        return in_array((int) $day->format('N'), $this->days, true);
    }

    /**
     * Indica si una cita cabe entera dentro del horario: abre ese día,
     * empieza a la hora de apertura o después y termina, a más tardar,
     * a la hora de cierre.
     */
    public function fits(DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
        if ($this->unrestricted) {
            return true;
        }

        if (!$this->isOpenOn($start)) {
            return false;
        }

        $day = $start->setTime(0, 0);

        return $start >= $day->modify("+{$this->opensAt} minutes")
            && $end <= $day->modify("+{$this->closesAt} minutes");
    }

    /**
     * Último día que se puede reservar, contando desde hoy.
     */
    public function lastBookableDay(DateTimeImmutable $today): DateTimeImmutable
    {
        return $today->setTime(0, 0)->modify("+{$this->maxDaysAhead} days");
    }

    /**
     * Horas de inicio que ofrece el salón un día, sin mirar la agenda:
     * cada intervalo desde la apertura, mientras el servicio termine
     * antes del cierre.
     *
     * @param int $duration Duración del servicio, en minutos.
     *
     * @return int[] Minutos desde la medianoche.
     */
    public function startTimes(int $duration): array
    {
        $times = [];

        for ($t = $this->opensAt; $t + $duration <= $this->closesAt; $t += $this->interval) {
            $times[] = $t;
        }

        return $times;
    }

    /**
     * El horario tal como lo consume el frontend.
     */
    public function toArray(): array
    {
        return [
            'dias' => $this->days,
            'apertura' => $this->formatMinutes($this->opensAt),
            'cierre' => $this->formatMinutes($this->closesAt),
            'intervalo' => $this->interval,
            'antelacion_max_dias' => $this->maxDaysAhead
        ];
    }

    /**
     * «de lunes a sábado» o «lunes, miércoles y viernes».
     */
    public function describeDays(): string
    {
        $days = $this->days;

        $consecutive = $days === range($days[0], $days[array_key_last($days)]);

        if ($consecutive && count($days) > 2) {
            return 'de ' . self::DAY_NAMES[$days[0]]
                . ' a ' . self::DAY_NAMES[$days[array_key_last($days)]];
        }

        $names = array_map(fn ($d) => self::DAY_NAMES[$d], $days);

        $last = array_pop($names);

        return $names ? implode(', ', $names) . ' y ' . $last : $last;
    }

    /**
     * «de 9:00 a 19:00».
     */
    public function describeHours(): string
    {
        return 'de ' . $this->formatMinutes($this->opensAt, false)
            . ' a ' . $this->formatMinutes($this->closesAt, false);
    }

    public function maxDaysAhead(): int
    {
        return $this->maxDaysAhead;
    }

    /**
     * «8 semanas» si el plazo son semanas exactas; si no, «10 días».
     */
    public function describeMaxAhead(): string
    {
        $days = $this->maxDaysAhead;

        if ($days % 7 === 0) {
            $weeks = intdiv($days, 7);
            return $weeks === 1 ? '1 semana' : "{$weeks} semanas";
        }

        return $days === 1 ? '1 día' : "{$days} días";
    }

    /*
    |--------------------------------------------------------------------------
    | Lectura de la configuración
    |--------------------------------------------------------------------------
    */

    /**
     * @return int[] Días ordenados y sin repetir.
     */
    private function parseDays(string $raw): array
    {
        $days = [];

        foreach (explode(',', $raw) as $part) {

            $part = trim($part);

            if (!ctype_digit($part) || (int) $part < 1 || (int) $part > 7) {
                throw new RuntimeException(
                    "SALON_DIAS debe ser una lista de números del 1 (lunes) al 7 (domingo), "
                    . "separados por comas. Valor recibido: «{$raw}»."
                );
            }

            $days[] = (int) $part;
        }

        $days = array_values(array_unique($days));

        sort($days);

        return $days;
    }

    /**
     * Admite 24:00 como cierre a medianoche.
     *
     * @return int Minutos desde la medianoche.
     */
    private function parseTime(string $raw, string $key): int
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $raw, $m)) {
            throw new RuntimeException(
                "{$key} debe tener el formato HH:MM. Valor recibido: «{$raw}»."
            );
        }

        $minutes = (int) $m[1] * 60 + (int) $m[2];

        if ((int) $m[2] > 59 || $minutes > 24 * 60) {
            throw new RuntimeException(
                "{$key} no es una hora válida: «{$raw}»."
            );
        }

        return $minutes;
    }

    private function parsePositiveInt(string $raw, string $key): int
    {
        if (!ctype_digit($raw) || (int) $raw <= 0) {
            throw new RuntimeException(
                "{$key} debe ser un número entero mayor que cero. Valor recibido: «{$raw}»."
            );
        }

        return (int) $raw;
    }

    /**
     * @param bool $padHours «09:00» para la API; «9:00» para los mensajes.
     */
    private function formatMinutes(int $minutes, bool $padHours = true): string
    {
        $hours = intdiv($minutes, 60);

        return ($padHours ? str_pad((string) $hours, 2, '0', STR_PAD_LEFT) : (string) $hours)
            . ':' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }
}
