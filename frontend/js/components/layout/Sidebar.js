import { store } from "../../state/store.js";
import { escapeHtml } from "../../utils/html.js";
import { ROLES } from "../../constants/roles.js";

/*
 * Menú lateral.
 *
 * En escritorio la barra queda fija a la izquierda. Por debajo del
 * punto de corte declarado en css/layout/sidebar.css se esconde y se
 * abre como cajón desde la barra superior, que solo existe ahí.
 *
 * El marcado se reconstruye en cada navegación, así que los listeners
 * se delegan en `document`: un listener directo sobre el botón se
 * perdería en el primer repintado.
 */

/**
 * @returns {string} Barra superior, capa de fondo y menú lateral.
 */
export function Sidebar() {

    const user = store.user;

    const isAdmin = Number(user?.id_rol) === ROLES.ADMIN;

    const current = currentPath();

    return `

        <header class="topbar">

            <button
                type="button"
                class="topbar__toggle"
                data-action="toggle-menu"
                aria-controls="sidebar"
                aria-expanded="false"
                aria-label="Abrir menú"
            >
                <span class="topbar__bar"></span>
                <span class="topbar__bar"></span>
                <span class="topbar__bar"></span>
            </button>

            <a href="#/" class="topbar__logo">
                Anguie Nails
            </a>

        </header>

        <div
            class="sidebar__overlay"
            data-action="close-menu"
        ></div>

        <aside class="sidebar" id="sidebar">

            <a href="#/" class="sidebar__logo">
                Anguie Nails
            </a>

            <nav class="sidebar__nav" aria-label="Menú principal">

                <ul class="sidebar__list">

                    ${link("#/", "Inicio", current)}

                    ${link("#/services", "Servicios", current)}

                    ${/*
                        «Nuestro equipo» y no «Equipo» a secas: cada
                        etiqueta repite el encabezado de su página, y
                        así no choca con el «Equipo» del panel, que
                        lleva al CRUD y no a la vista pública.
                      */ ""}
                    ${link("#/professionals", "Nuestro equipo", current)}

                    ${user && !isAdmin
                        ? link("#/booking", "Mis citas", current)
                        : ""}

                </ul>

                ${isAdmin
                    ? `
                        <p class="sidebar__section">
                            Administración
                        </p>

                        <ul class="sidebar__list">

                            ${link("#/admin/agenda", "Agenda", current)}

                            ${link("#/admin/clients", "Clientes", current)}

                            ${link("#/admin/services", "Catálogo", current)}

                            ${link("#/admin/professionals", "Equipo", current)}

                        </ul>
                    `
                    : ""}

            </nav>

            <div class="sidebar__account">

                ${user
                    ? `
                        <span class="sidebar__user">
                            Hola, ${escapeHtml(user.nombre)}
                        </span>

                        <button
                            class="btn btn-outline"
                            data-action="logout"
                        >
                            Cerrar sesión
                        </button>
                    `
                    : `
                        <button
                            class="btn btn-outline"
                            data-action="login"
                        >
                            Iniciar Sesión
                        </button>

                        <button
                            class="btn btn-primary"
                            data-action="register"
                        >
                            Registrarse
                        </button>
                    `}

            </div>

        </aside>

    `;
}

/**
 * Registra los listeners del menú. Se llama una sola vez al arrancar.
 */
export function initSidebarEvents() {

    document.addEventListener("click", handleSidebarClick);

    document.addEventListener("keydown", handleEscape);

    /*
     * La clase que mantiene el cajón abierto vive en <body>, que no se
     * repinta al navegar: sin esto el menú quedaría abierto encima de
     * la página recién cargada.
     */
    window.addEventListener("hashchange", closeMenu);

}

function currentPath() {

    return window.location.hash.slice(1) || "/";

}

/**
 * Pinta un elemento del menú, marcado si es la ruta actual.
 */
function link(href, label, current) {

    const isActive = href.slice(1) === current;

    return `
        <li>
            <a
                href="${href}"
                class="sidebar__link${isActive ? " is-active" : ""}"
                ${isActive ? 'aria-current="page"' : ""}
            >
                ${label}
            </a>
        </li>
    `;
}

function handleSidebarClick(event) {

    /*
     * `closest` y no `event.target`: el botón de hamburguesa está hecho
     * de tres <span>, así que el clic llega en uno de ellos.
     */
    const trigger = event.target.closest("[data-action]");

    if (trigger?.dataset.action === "toggle-menu") {

        setMenu(!isMenuOpen());

        return;
    }

    if (trigger?.dataset.action === "close-menu") {

        closeMenu();

        return;
    }

    /*
     * Navegar cierra el cajón. Hace falta además de `hashchange` para
     * el caso en que se pulsa el enlace de la página que ya se ve: el
     * hash no cambia y el evento nunca se dispara.
     */
    if (event.target.closest(".sidebar__link, .sidebar__logo")) {

        closeMenu();

    }
}

function handleEscape(event) {

    if (event.key === "Escape" && isMenuOpen()) {

        closeMenu();

    }
}

function isMenuOpen() {

    return document.body.classList.contains("menu-open");

}

function closeMenu() {

    setMenu(false);

}

function setMenu(open) {

    document.body.classList.toggle("menu-open", open);

    const toggle = document.querySelector(".topbar__toggle");

    if (toggle) {

        toggle.setAttribute("aria-expanded", String(open));

        toggle.setAttribute(
            "aria-label",
            open ? "Cerrar menú" : "Abrir menú"
        );

    }
}
