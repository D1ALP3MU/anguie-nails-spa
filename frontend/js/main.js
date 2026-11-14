import { initRouter } from "./router/router.js";

import {
    initAuth
} from "./modules/auth/services/auth.service.js";

import {
    initAuthEvents
} from "./modules/auth/auth.controller.js";

import { setUser } from "./state/actions.js";

import { on } from "./core/events.js";

import {
    Sidebar,
    initSidebarEvents
} from "./components/layout/Sidebar.js";

import { initModalEvents } from "./components/ui/Modal.js";

import { initActionMenuEvents } from "./components/ui/ActionMenu.js";

document.addEventListener("DOMContentLoaded", () => {

    // El menú se repinta solo cuando cambia la sesión. Antes
    // dependía de que la navegación cambiara el hash, así que
    // cerrar sesión sin moverse de página lo dejaba desactualizado.
    on("userChanged", refreshSidebar);

    setUser(initAuth());

    initAuthEvents();

    initSidebarEvents();

    // Antes que el router: el cierre por cambio de ruta debe estar
    // escuchando desde la primera navegación.
    initModalEvents();

    /*
     * También antes que el router, y antes que cualquier página: así
     * un clic en una opción del menú lo cierra primero y luego lo
     * atiende el controlador de la página, que se registra después.
     */
    initActionMenuEvents();

    initRouter();

});

function refreshSidebar() {

    const sidebar = document.querySelector(".sidebar");

    if (!sidebar) return;

    /*
     * Sidebar() devuelve barra superior, capa de fondo y menú. Aquí
     * solo se reemplaza el menú, que es lo único que depende de la
     * sesión; los otros dos son fijos y quedan intactos.
     */
    const markup = document.createElement("div");

    markup.innerHTML = Sidebar();

    sidebar.outerHTML = markup.querySelector(".sidebar").outerHTML;

}
