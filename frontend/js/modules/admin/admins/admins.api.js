import { httpGet, httpPost, httpDelete } from "../../../api/http.js";

/**
 * No hay edición: un administrador solo se da de alta o de baja.
 * El nombre y el correo los cambia cada quien desde «Mi cuenta».
 */
function mapAdmin(admin) {

    return {
        id: admin.id_usuario,
        name: admin.nombre,
        email: admin.email,
        createdAt: admin.created_at
    };
}

export async function fetchAdmins() {

    const response = await httpGet("/admins");

    return response.data.map(mapAdmin);
}

export async function createAdmin(data) {

    const response = await httpPost("/admins", data);

    return response.data;
}

export async function deleteAdmin(id) {

    const response = await httpDelete(`/admins/${id}`);

    return response.data;
}
