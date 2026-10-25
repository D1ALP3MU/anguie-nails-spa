import { fetchClient } from "../clients.api.js";
import { fetchAgenda } from "../../agenda/agenda.api.js";

/**
 * Datos de la ficha: la clienta y todas sus citas.
 *
 * Las dos peticiones no dependen entre sí, así que van en paralelo.
 *
 * El filtro `id_cliente` solo lo atiende un administrador: en una
 * sesión de clienta el backend lo sustituye por el suyo, de modo
 * que esta llamada nunca expone la agenda de otra.
 */
export async function fetchClientHistory(id) {

    const [client, appointments] = await Promise.all([
        fetchClient(id),
        fetchAgenda({ id_cliente: id })
    ]);

    return { client, appointments };
}
