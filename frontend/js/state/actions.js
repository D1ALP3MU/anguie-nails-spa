import { store } from "./store.js";

import { emit } from "../core/events.js";

/**
 * Único punto de escritura del usuario en sesión.
 *
 * Avisar por evento es lo que permite que la barra de navegación
 * se actualice al iniciar o cerrar sesión sin depender de que
 * cambie el hash de la URL.
 *
 * @param {object|null} user Usuario autenticado, o null al salir.
 */
export function setUser(user) {

    store.user = user;

    emit("userChanged", user);

}
