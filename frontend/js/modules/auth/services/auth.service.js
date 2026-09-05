import { httpPost } from "../../../api/http.js";

import {
    saveSession,
    clearSession,
    getToken,
    getCurrentUser
} from "../../../api/session.js";

export async function login(credentials) {

    const response = await httpPost(
        "/auth/login",
        credentials
    );

    const { token, user } = response.data;

    saveSession(token, user);

    return {
        token,
        user
    };
}

/**
 * Registra una cuenta de cliente.
 *
 * Apunta a /auth/register, el único camino que crea el usuario
 * y su perfil de cliente dentro de una misma transacción.
 * POST /clients quedó reservado al panel administrativo.
 */
export async function register(data) {

    const response = await httpPost(
        "/auth/register",
        data
    );

    return response.data;
}

export { getToken, getCurrentUser };

export function logout() {

    clearSession();
}

export function initAuth() {

    return getCurrentUser();
}
