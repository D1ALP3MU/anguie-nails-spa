import { httpGet } from "../../../api/http.js";

/**
 * Construye la cadena de consulta descartando lo vacío.
 */
function buildQuery(filters = {}) {

    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value !== null && value !== undefined && String(value).trim() !== "") {
            params.append(key, value);
        }
    });

    const query = params.toString();

    return query ? `?${query}` : "";
}

/**
 * Resumen agregado del periodo. Las sumas las hace la base.
 */
export async function fetchSummary(filters = {}) {

    const response = await httpGet(`/payments/summary${buildQuery(filters)}`);

    return response.data;
}

/**
 * Detalle de los cobros del periodo.
 */
export async function fetchPayments(filters = {}) {

    const response = await httpGet(`/payments${buildQuery(filters)}`);

    return response.data;
}
