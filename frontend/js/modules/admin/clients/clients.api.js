import { httpGet } from "../../../api/http.js";

export async function fetchClients() {
    const response = await httpGet("/clients");

    return response.data.map(client => ({
        id: client.id_cliente,
        userId: client.id_usuario,
        name: client.nombre,
        email: client.email,
        phone: client.telefono,
        address: client.direccion,
        createdAt: client.created_at,
        updatedAt: client.updated_at
    }));
}