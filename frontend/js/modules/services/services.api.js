import { httpGet } from "../../api/http.js";

export async function fetchServices() {
    const response = await httpGet("/services");

    return response.data.map(service => ({
        id: service.id_servicio,
        title: service.nombre,
        description: service.descripcion ?? "Servicio premium",
        duration: `${service.duracion} min`,
        price: Number(service.precio)
    }));
}