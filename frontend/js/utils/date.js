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

/**
 * @param {string} iso AAAA-MM-DD.
 * @returns {Date} Medianoche local de ese día.
 */
export function fromISODate(iso) {

    const [year, month, day] = iso.split("-").map(Number);

    return new Date(year, month - 1, day);
}

/**
 * @param {string} iso AAAA-MM-DD.
 * @param {number} days Días a sumar; negativo para restar.
 * @returns {string} AAAA-MM-DD.
 */
export function addDays(iso, days) {

    const date = fromISODate(iso);

    date.setDate(date.getDate() + days);

    return toISODate(date);
}

const LONG = new Intl.DateTimeFormat("es-CO", {
    weekday: "long",
    day: "numeric",
    month: "long"
});

const SHORT = new Intl.DateTimeFormat("es-CO", {
    day: "numeric",
    month: "short"
});

/**
 * @returns {string} «sábado 14 de noviembre».
 */
export function formatLongDate(iso) {

    // Intl pone una coma tras el día de la semana.
    return LONG.format(fromISODate(iso)).replace(",", "");
}

/**
 * @returns {string} «14 nov».
 */
export function formatShortDate(iso) {

    return SHORT.format(fromISODate(iso)).replace(" de ", " ");
}

const WITH_YEAR = new Intl.DateTimeFormat("es-CO", {
    day: "numeric",
    month: "short",
    year: "numeric"
});

/**
 * Para listados que abarcan más de un año: historial, facturación.
 *
 * @returns {string} «14 nov 2026».
 */
export function formatDate(iso) {

    return WITH_YEAR.format(fromISODate(iso)).replaceAll(" de ", " ");
}

/**
 * @returns {string} La hora actual como HH:MM.
 */
export function nowHHMM() {

    const now = new Date();

    return `${pad(now.getHours())}:${pad(now.getMinutes())}`;
}
