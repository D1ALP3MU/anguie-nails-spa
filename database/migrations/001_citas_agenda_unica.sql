-- =====================================================
-- 001 · Evitar doble reserva del mismo profesional
-- =====================================================
--
-- Un UNIQUE(id_profesional, fecha, hora) directo no sirve:
-- las citas se cancelan de forma lógica (estado = 'cancelada')
-- y sus filas permanecen, así que un horario cancelado quedaría
-- bloqueado para siempre.
--
-- La columna generada vale NULL cuando la cita está cancelada.
-- Un índice UNIQUE de MySQL admite NULL repetidos, de modo que
-- solo las citas vigentes compiten por el horario.
--
-- Esta restricción cubre la coincidencia exacta de horario.
-- El cruce por duración del servicio (una cita de 90 min que
-- pisa a la siguiente) se valida en AppointmentService.
-- =====================================================

ALTER TABLE citas
    ADD COLUMN reserva_activa TINYINT
        GENERATED ALWAYS AS (
            IF(estado = 'cancelada', NULL, 1)
        ) STORED,
    ADD UNIQUE KEY uq_citas_agenda (
        id_profesional,
        fecha,
        hora,
        reserva_activa
    );

-- Reversión:
-- ALTER TABLE citas
--     DROP INDEX uq_citas_agenda,
--     DROP COLUMN reserva_activa;
