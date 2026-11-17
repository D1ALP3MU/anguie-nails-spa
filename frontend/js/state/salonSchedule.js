import { httpGet } from "../api/http.js";

/**
 * Horario del salón, pedido una vez al arrancar.
 *
 * Lo usa el pie de página, que se pinta en cada ruta y no puede
 * esperar a una petición: mientras no llega —o si la API no
 * responde— el pie se pinta sin el bloque de horario.
 */
let schedule = null;

export function getSalonSchedule() {
    return schedule;
}

/**
 * @returns {Promise<object|null>} El horario, o null si no se pudo
 *          obtener: un pie sin horario es mejor que una página rota.
 */
export async function loadSalonSchedule() {

    try {

        const { data } = await httpGet("/schedule");

        schedule = data;

    } catch (error) {

        console.warn("No se pudo cargar el horario del salón:", error);

        schedule = null;
    }

    return schedule;
}
