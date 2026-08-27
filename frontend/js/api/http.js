const API_BASE_URL = "http://localhost:8001/api";

async function request(endpoint, options = {}) {

    const response = await fetch(
        `${API_BASE_URL}${endpoint}`,
        {
            ...options,
            headers: {
                "Content-Type": "application/json",
                ...options.headers
            },
        }
    );

    const data = await response.json();

    if (!response.ok) {
        throw new Error(
            data.message || "Error en la petición."
        );
    }

    return data;
}

export async function httpGet(endpoint) {

    return request(endpoint, {
        method: "GET"
    });
}

export async function httpPost(endpoint, data) {

    return request(endpoint, {
        method: "POST",
        body: JSON.stringify(data)
    });
}

export async function httpPut(endpoint, data) {

    return request(endpoint, {
        method: "PUT",
        body: JSON.stringify(data)
    });
}

export async function httpDelete(endpoint) {

    return request(endpoint, {
        method: "DELETE"
    });
}