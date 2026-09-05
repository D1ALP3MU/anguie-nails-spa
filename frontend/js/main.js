import { initRouter } from "./router/router.js";

import {
    initAuth
} from "./modules/auth/services/auth.service.js";

import {
    initAuthEvents
} from "./modules/auth/auth.controller.js";

import { setUser } from "./state/actions.js";

import { on } from "./core/events.js";

import { Navbar } from "./components/layout/Navbar.js";

document.addEventListener("DOMContentLoaded", () => {

    // La barra se repinta sola cuando cambia la sesión. Antes
    // dependía de que la navegación cambiara el hash, así que
    // cerrar sesión sin moverse de página la dejaba desactualizada.
    on("userChanged", refreshNavbar);

    setUser(initAuth());

    initAuthEvents();

    initRouter();

});

function refreshNavbar() {

    const navbar = document.querySelector(".navbar");

    if (navbar) {
        navbar.outerHTML = Navbar();
    }
}
