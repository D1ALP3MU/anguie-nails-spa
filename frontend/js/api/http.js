const API_BASE_URL = "http://localhost:8001/api";

export async function httpGet(endpoint) {
    const response = await fetch(`${API_BASE_URL}${endpoint}`);

    if (!response.ok) {
        throw new Error("Error en la petición");
    }

    return response.json();
}