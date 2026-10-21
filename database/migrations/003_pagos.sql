-- =====================================================
-- 003 · Precio congelado en la cita y cobro único
-- =====================================================

-- -----------------------------------------------------
-- citas.precio
-- -----------------------------------------------------
-- El precio vivía solo en `servicios`, así que subir una tarifa
-- revaluaba hacia atrás todas las citas pasadas. La cita guarda
-- ahora lo que costaba el día que se reservó.
--
-- Las citas que ya existen se rellenan con el precio actual de su
-- servicio: es la única aproximación disponible, y queda anotado
-- que para ellas el dato es estimado, no histórico.

ALTER TABLE citas
    ADD COLUMN precio DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER id_profesional;

UPDATE citas c
    INNER JOIN servicios s ON s.id_servicio = c.id_servicio
    SET c.precio = s.precio;

ALTER TABLE citas
    ALTER COLUMN precio DROP DEFAULT;

-- -----------------------------------------------------
-- pagos: un cobro vigente por cita
-- -----------------------------------------------------
-- Mismo recurso que en uq_citas_agenda: un UNIQUE corriente dejaría
-- la cita bloqueada para siempre tras un cobro anulado, porque la
-- fila permanece. La columna generada vale NULL cuando el cobro ya
-- no cuenta, y MySQL admite NULL repetidos en un índice único.

ALTER TABLE pagos
    MODIFY COLUMN estado ENUM(
        'pendiente',
        'pagado',
        'rechazado',
        'reembolsado'
    ) DEFAULT 'pendiente';

ALTER TABLE pagos
    ADD COLUMN cobro_vigente TINYINT
        GENERATED ALWAYS AS (
            IF(estado IN ('rechazado', 'reembolsado'), NULL, 1)
        ) STORED,
    ADD UNIQUE KEY uq_pagos_cita (id_cita, cobro_vigente);

-- Reversión:
-- ALTER TABLE pagos DROP INDEX uq_pagos_cita, DROP COLUMN cobro_vigente;
-- ALTER TABLE citas DROP COLUMN precio;
