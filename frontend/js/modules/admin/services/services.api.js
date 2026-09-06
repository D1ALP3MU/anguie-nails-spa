import {
    httpGet,
    httpPost,
    httpPut,
    httpDelete
} from "../../../api/http.js";

/**
 * Mapeo para el panel de administración.
 *
 * Se mantiene aparte del de modules/services, que devuelve la
 * duración ya formateada para mostrar ("60 min"). Aquí hace falta
 * el número crudo, porque estos datos se editan.
 */
function mapService(service) {

    return {
        id: service.id_servicio,
        name: service.nombre,
        description: service.descripcion,
        duration: Number(service.duracion),
        price: Number(service.precio)
    };
}

export async function fetchServices() {

    const response = await httpGet("/services");

    return response.data.map(mapService);
}

export async function fetchService(id) {

    const response = await httpGet(`/services/${id}`);

    return mapService(response.data);
}

export async function createService(data) {

    const response = await httpPost("/services", data);

    return response.data;
}

export async function updateService(id, data) {

    const response = await httpPut(`/services/${id}`, data);

    return response.data;
}

export async function deleteService(id) {

    const response = await httpDelete(`/services/${id}`);

    return response.data;
}
