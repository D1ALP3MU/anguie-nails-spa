const API_BASE_URL = "http://localhost/anguie-nails/backend/api";

export async function httpGet(endpoint) {
    const response = await fetch(`${API_BASE_URL}${endpoint}`);

    if (!response.ok) {
        throw new Error("Error en la petición");
    }

    return response.json();
}