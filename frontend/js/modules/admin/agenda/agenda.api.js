import { httpGet, httpPut, httpDelete } from "../../../api/http.js";

/**
 * Construye la cadena de consulta descartando los filtros vacíos,
 * para no enviar condiciones que el backend rechazaría.
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

export async function fetchAgenda(filters = {}) {

    const response = await httpGet(
        `/appointments${buildQuery(filters)}`
    );

    return response.data;
}

/**
 * Cambia el estado de una cita.
 *
 * La API espera la cita completa, así que se reenvían los datos
 * que ya trae el listado con el estado nuevo.
 */
export async function changeStatus(appointment, estado) {

    const response = await httpPut(
        `/appointments/${appointment.id_cita}`,
        {
            id_cliente: appointment.id_cliente,
            id_servicio: appointment.id_servicio,
            id_profesional: appointment.id_profesional,
            fecha: appointment.fecha,
            hora: appointment.hora.slice(0, 5),
            estado,
            notas: appointment.notas
        }
    );

    return response.data;
}

export async function cancelAppointment(id) {

    const response = await httpDelete(`/appointments/${id}`);

    return response.data;
}
