import {
    httpGet,
    httpPost,
    httpPut,
    httpDelete
} from "../../../api/http.js";

/**
 * Traduce los nombres del backend, que están en español, a los
 * que usa la interfaz. Es el mismo criterio del módulo de clientes.
 */
function mapProfessional(professional) {

    return {
        id: professional.id_profesional,
        name: professional.nombre,
        specialty: professional.especialidad,
        phone: professional.telefono
    };
}

export async function fetchProfessionals() {

    const response = await httpGet("/professionals");

    return response.data.map(mapProfessional);
}

export async function fetchProfessional(id) {

    const response = await httpGet(`/professionals/${id}`);

    return mapProfessional(response.data);
}

export async function createProfessional(data) {

    const response = await httpPost("/professionals", data);

    return response.data;
}

export async function updateProfessional(id, data) {

    const response = await httpPut(`/professionals/${id}`, data);

    return response.data;
}

export async function deleteProfessional(id) {

    const response = await httpDelete(`/professionals/${id}`);

    return response.data;
}
