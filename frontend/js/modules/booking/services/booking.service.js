import {
    httpGet,
    httpPost,
    httpDelete
} from "../../../api/http.js";

/**
 * Obtiene todas las citas.
 *
 * @returns {Promise<Array>}
 */
export async function fetchBookings() {

    const response = await httpGet("/appointments");

    return response.data;
}

/**
 * Crea una nueva cita.
 *
 * @param {Object} bookingData
 * @returns {Promise<Object>}
 */
export async function createBooking(bookingData) {

    const response = await httpPost(
        "/appointments",
        bookingData
    );

    return response.data;
}

/**
 * Cancela una cita.
 *
 * @param {number|string} bookingId
 * @returns {Promise<Object>}
 */
export async function removeBooking(bookingId) {

    const response = await httpDelete(
        `/appointments/${bookingId}`
    );

    return response.data;
}