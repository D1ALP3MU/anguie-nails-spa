import { todayISO } from "./date.js";

export function isEmpty(value) {
    return !value.trim();
}

/**
 * Se comparan las cadenas AAAA-MM-DD, que ordenan igual que las
 * fechas, en vez de construir un Date: ver utils/date.js.
 */
export function isPasteDate(date) {
    return date < todayISO();
}