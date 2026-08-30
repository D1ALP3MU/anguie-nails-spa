import { httpGet } from "../../api/http.js";

export async function getProfessionals() {

    const response = await httpGet("/professionals");

    return response.data.map(professional => ({
        id: professional.id_profesional,
        name: professional.nombre,
        specialty: professional.especialidad,
        phone: professional.telefono
    }));
}