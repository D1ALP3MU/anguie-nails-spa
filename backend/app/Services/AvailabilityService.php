<?php

namespace App\Services;

use DateTimeImmutable;
use App\Config\SalonSchedule;
use App\Repositories\AppointmentRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\ServiceRepository;
use App\Exceptions\ValidationException;

/**
 * Horas libres para reservar.
 *
 * Una hora se ofrece si cumple lo mismo que AppointmentService exige
 * al crear la cita: cae dentro del horario del salón, no está en el
 * pasado y el servicio entero cabe sin cruzarse con otra cita de la
 * profesional. Así el frontend nunca ofrece una hora que luego se
 * rechace.
 *
 * La única diferencia posible es una carrera —dos clientas eligen la
 * misma hora a la vez—, y esa la resuelve la reserva con un 409.
 */
class AvailabilityService
{
    public function __construct(
        private SalonSchedule $schedule,
        private AppointmentRepository $appointmentRepository,
        private ServiceRepository $serviceRepository,
        private ProfessionalRepository $professionalRepository
    ) {}

    /**
     * El horario del salón, para que el frontend sepa qué días
     * mostrar como cerrados sin preguntar uno por uno.
     */
    public function schedule(): array
    {
        return $this->schedule->toArray();
    }

    /**
     * Horas libres de una profesional, un día, para un servicio.
     *
     * @param array $query id_profesional, id_servicio y fecha (Y-m-d).
     *
     * @return array{fecha: string, abierto: bool, horas: string[]}
     *         Horas en formato HH:MM. Vacío si el salón no abre ese
     *         día, si el día ya pasó o está fuera del plazo de reserva.
     *
     * @throws ValidationException
     */
    public function freeSlots(array $query): array
    {
        [$professionalId, $serviceId, $day] = $this->validate($query);

        $service = $this->serviceRepository->findById($serviceId);

        if ($service === null || !(bool) $service['activo']) {
            throw new ValidationException([
                'id_servicio' => 'El servicio seleccionado no está disponible.'
            ]);
        }

        $professional = $this->professionalRepository->findById($professionalId);

        if ($professional === null || !(bool) $professional['activo']) {
            throw new ValidationException([
                'id_profesional' => 'La profesional seleccionada no está disponible.'
            ]);
        }

        $result = [
            'fecha' => $day->format('Y-m-d'),
            'abierto' => $this->schedule->isOpenOn($day),
            'horas' => []
        ];

        $today = new DateTimeImmutable('today');

        if (
            !$result['abierto']
            || $day < $today
            || $day > $this->schedule->lastBookableDay($today)
        ) {
            return $result;
        }

        $duration = (int) $service['duracion'];

        $busy = $this->busyMinutes($professionalId, $result['fecha']);

        $now = new DateTimeImmutable();

        foreach ($this->schedule->startTimes($duration) as $start) {

            // La misma regla que al crear: no se reserva en el pasado.
            if ($day->modify("+{$start} minutes") < $now) {
                continue;
            }

            $end = $start + $duration;

            foreach ($busy as [$busyStart, $busyEnd]) {
                if ($start < $busyEnd && $end > $busyStart) {
                    continue 2;
                }
            }

            $result['horas'][] = sprintf('%02d:%02d', intdiv($start, 60), $start % 60);
        }

        return $result;
    }

    /**
     * @return array{int, int, DateTimeImmutable}
     *
     * @throws ValidationException
     */
    private function validate(array $query): array
    {
        $errors = [];

        $ids = [
            'id_profesional' => 'La profesional debe identificarse con un número entero.',
            'id_servicio' => 'El servicio debe identificarse con un número entero.'
        ];

        $values = [];

        foreach ($ids as $key => $message) {

            $value = trim((string) ($query[$key] ?? ''));

            if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0) {
                $errors[$key] = $message;
                continue;
            }

            $values[$key] = (int) $value;
        }

        $raw = trim((string) ($query['fecha'] ?? ''));

        // El «!» pone la hora a medianoche; comparar el formato de
        // vuelta descarta fechas que PHP corregiría, como el 31 de abril.
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);

        if ($day === false || $day->format('Y-m-d') !== $raw) {
            $errors['fecha'] = 'La fecha debe tener el formato AAAA-MM-DD.';
        }

        if ($errors) {
            throw new ValidationException($errors);
        }

        return [$values['id_profesional'], $values['id_servicio'], $day];
    }

    /**
     * Citas ocupadas del día, en minutos desde la medianoche.
     *
     * @return array<array{int, int}>
     */
    private function busyMinutes(int $professionalId, string $date): array
    {
        $toMinutes = function (string $time): int {
            // El fin puede pasar de 24:00 («25:30:00»): TIME lo admite.
            [$h, $m] = explode(':', $time);
            return (int) $h * 60 + (int) $m;
        };

        return array_map(
            fn ($row) => [$toMinutes($row['inicio']), $toMinutes($row['fin'])],
            $this->appointmentRepository->findBusyIntervals($professionalId, $date)
        );
    }
}
