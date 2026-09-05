import { config } from "../config/env.js";
import { getToken, clearSession, saveRedirect } from "./session.js";
import { setUser } from "../state/actions.js";

const API_BASE_URL = config.API_URL;

async function request(endpoint, options = {}) {

    const token = getToken();

    const headers = {
        "Content-Type": "application/json",
        ...options.headers
    };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(
        `${API_BASE_URL}${endpoint}`,
        {
            ...options,
            headers
        }
    );

    const data = await response
        .json()
        .catch(() => ({}));

    if (response.status === 401) {

        handleExpiredSession();

        throw new Error(
            "Tu sesión no es válida o expiró. Inicia sesión de nuevo."
        );
    }

    if (!response.ok) {
        throw new Error(
            buildErrorMessage(data)
        );
    }

    return data;
}

/**
 * Deja la aplicación en un estado coherente cuando la API
 * rechaza el token: sin la sesión local la interfaz seguiría
 * mostrando al usuario como conectado mientras todo falla.
 */
function handleExpiredSession() {

    clearSession();

    setUser(null);

    const current = window.location.hash || "#/";

    if (current !== "#/login") {

        saveRedirect(current);

        window.location.hash = "#/login";
    }
}

/**
 * Construye el mensaje de error a partir de la respuesta de la API,
 * que puede traer un mensaje suelto o un mapa de errores de validación.
 */
function buildErrorMessage(data) {

    if (data.errors) {

        const messages = Object.values(data.errors);

        if (messages.length) {
            return messages.join(" ");
        }
    }

    return data.message || "Error en la petición.";
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
