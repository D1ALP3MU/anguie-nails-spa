import { httpPost } from "../../../api/http.js";

const TOKEN_KEY = "anguie_token";
const USER_KEY = "anguie_user";

export async function login(credentials) {

    const response = await httpPost(
        "/auth/login",
        credentials
    );

    const { token, user } = response.data;

    sessionStorage.setItem(
        TOKEN_KEY,
        token
    );

    sessionStorage.setItem(
        USER_KEY,
        JSON.stringify(user)
    );

    return {
        token,
        user
    };
}

export function getToken() {

    return sessionStorage.getItem(
        TOKEN_KEY
    );
}

export function getCurrentUser() {

    const user = sessionStorage.getItem(
        USER_KEY
    );

    return user
        ? JSON.parse(user)
        : null;
}

export function logout() {

    sessionStorage.removeItem(
        TOKEN_KEY
    );

    sessionStorage.removeItem(
        USER_KEY
    );
}
