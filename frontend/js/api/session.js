const TOKEN_KEY = "anguie_token";
const USER_KEY = "anguie_user";
const REDIRECT_KEY = "anguie_redirect";

/**
 * Punto único de acceso a la sesión almacenada.
 *
 * Vive en la capa de API, y no en el módulo de autenticación,
 * para que http.js pueda limpiarla ante un 401 sin importar
 * auth.service.js, que a su vez importa http.js.
 */

export function getToken() {

    return sessionStorage.getItem(TOKEN_KEY);
}

export function getCurrentUser() {

    const user = sessionStorage.getItem(USER_KEY);

    if (!user) return null;

    try {

        return JSON.parse(user);

    } catch (error) {

        // Sesión corrupta: se descarta en lugar de romper el arranque.
        console.error("Sesión ilegible, se descarta:", error);

        clearSession();

        return null;
    }
}

export function saveSession(token, user) {

    sessionStorage.setItem(TOKEN_KEY, token);

    sessionStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearSession() {

    sessionStorage.removeItem(TOKEN_KEY);

    sessionStorage.removeItem(USER_KEY);
}

/**
 * Guarda la ruta que el usuario quería visitar antes de que
 * se le pidiera iniciar sesión, para devolverlo allí después.
 */
export function saveRedirect(path) {

    sessionStorage.setItem(REDIRECT_KEY, path);
}

export function takeRedirect() {

    const path = sessionStorage.getItem(REDIRECT_KEY);

    sessionStorage.removeItem(REDIRECT_KEY);

    return path;
}
