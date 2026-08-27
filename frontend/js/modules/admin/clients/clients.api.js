import {
    httpGet,
    httpPost,
    httpPut,
    httpDelete
} from "../../../api/http.js";


function mapClient(client) {

    return {
        id: client.id_cliente,
        userId: client.id_usuario,
        name: client.nombre,
        email: client.email,
        phone: client.telefono,
        address: client.direccion,
        createdAt: client.created_at,
        updatedAt: client.updated_at
    };

}


export async function fetchClients() {

    const response = await httpGet("/clients");

    return response.data.map(mapClient);

}


export async function fetchClient(id) {

    const response = await httpGet(`/clients/${id}`);

    return mapClient(response.data);

}


export async function createClient(data) {

    const response = await httpPost("/clients", data);

    return response.data;

}


export async function updateClient(id, data) {

    const response = await httpPut(
        `/clients/${id}`,
        data
    );

    return response.data;

}


export async function deleteClient(id) {

    const response = await httpDelete(
        `/clients/${id}`
    );

    return response.data;

}