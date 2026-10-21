import { httpPost, httpPut, httpDelete } from "../../../api/http.js";

/**
 * Cobros de una cita.
 *
 * No hay un `fetch` por cita: el listado de la agenda ya trae el
 * cobro vigente de cada una, así que aquí solo viven las escrituras.
 */

export async function registerPayment(appointmentId, data) {

    const response = await httpPost(
        `/appointments/${appointmentId}/payment`,
        data
    );

    return response.data;
}

export async function confirmPayment(paymentId) {

    const response = await httpPut(`/payments/${paymentId}`);

    return response.data;
}

export async function voidPayment(paymentId) {

    const response = await httpDelete(`/payments/${paymentId}`);

    return response.data;
}
