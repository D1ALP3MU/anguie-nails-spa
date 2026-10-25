import { httpPut } from "../../api/http.js";

/**
 * Cambia la contraseña de la sesión actual.
 *
 * No lleva identificador: el usuario lo resuelve el backend desde
 * el token, de modo que nadie puede cambiar la contraseña ajena.
 */
export async function changePassword(data) {

    const response = await httpPut("/profile/password", data);

    return response.data;
}
