import { httpGet } from "../../api/http.js";

export async function fetchService(id) {

    const { data } = await httpGet(`/services/${encodeURIComponent(id)}`);

    return {
        id: data.id_servicio,
        name: data.nombre,
        description: data.descripcion,
        duration: Number(data.duracion),
        price: Number(data.precio)
    };
}

/**
 * Días de apertura (1 = lunes … 7 = domingo), horas y plazo.
 */
export async function fetchSchedule() {

    const { data } = await httpGet("/schedule");

    return data;
}

/**
 * @returns {Promise<{fecha: string, abierto: boolean, horas: string[]}>}
 */
export async function fetchFreeSlots({ professionalId, serviceId, date }) {

    const query = new URLSearchParams({
        id_profesional: professionalId,
        id_servicio: serviceId,
        fecha: date
    });

    const { data } = await httpGet(`/availability?${query}`);

    return data;
}
