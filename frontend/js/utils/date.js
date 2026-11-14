/**
 * Fechas de calendario en la zona horaria del navegador.
 *
 * No se usa toISOString(): convierte a UTC, y en Colombia (UTC−5)
 * desde las 7 de la noche ya devuelve el día siguiente. Tampoco
 * new Date("2026-11-13"): una fecha sin hora se interpreta como
 * medianoche UTC, que aquí es la tarde del día anterior.
 */

const pad = (n) => String(n).padStart(2, "0");

/**
 * @param {Date} date
 * @returns {string} La fecha como AAAA-MM-DD, en hora local.
 */
export function toISODate(date) {

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/**
 * @returns {string} Hoy, como AAAA-MM-DD.
 */
export function todayISO() {

    return toISODate(new Date());
}
