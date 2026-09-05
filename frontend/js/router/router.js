import { HomePage } from "../modules/home/home.page.js";
import { ServicesPage } from "../modules/services/services.page.js";
import { BookingPage } from "../modules/booking/booking.page.js";
import { Layout } from "../components/layout/Layout.js";
import { Loading } from "../components/ui/Loading.js";
import { ErrorState } from "../components/ui/ErrorState.js";
import { runCleanup } from "../core/cleanup.js";
import { ServicesSkeleton } from "../modules/services/components/ServicesSkeleton.js";
import { ClientsPage } from "../modules/admin/clients/clients.page.js";
import { ProfessionalsPage } from "../modules/professionals/professionals.page.js";
import { store } from "../state/store.js";
import { ROLES } from "../constants/roles.js";
import { saveRedirect } from "../api/session.js";
import { escapeHtml } from "../utils/html.js";
import {
    LoginPage,
    RegisterPage
} from "../modules/auth/auth.page.js";

/*
 * Cada ruta declara qué exige para renderizarse:
 *
 *   auth      : requiere sesión iniciada.
 *   roles     : lista de roles permitidos (implica auth).
 *   guestOnly : solo visible sin sesión.
 *
 * Estas guardas son de interfaz, no de seguridad: la API vuelve
 * a comprobar autenticación y rol en cada petición.
 */
const routes = {
    "/": { page: HomePage },
    "/services": { page: ServicesPage },
    "/professionals": { page: ProfessionalsPage },
    "/booking": { page: BookingPage, auth: true },
    "/clients": { page: ClientsPage, roles: [ROLES.ADMIN] },
    "/login": { page: LoginPage, guestOnly: true },
    "/register": { page: RegisterPage, guestOnly: true },
};

const routeLoaders = {
    "/services": ServicesSkeleton,
};

export function initRouter() {

    window.addEventListener("hashchange", renderRoute);

    renderRoute();

}

function getCurrentPath() {

    return window.location.hash.slice(1) || "/";

}

/**
 * Navega sin re-entrar si ya estamos en el destino.
 *
 * @returns {boolean} true si se produjo la navegación.
 */
function redirectTo(hash) {

    if (window.location.hash === hash) {
        return false;
    }

    window.location.hash = hash;

    return true;
}

/**
 * Decide si la ruta puede renderizarse para la sesión actual.
 *
 * @returns {"allow"|"redirected"|"forbidden"}
 */
function checkAccess(path, route) {

    const user = store.user;

    if (route.guestOnly && user) {

        redirectTo("#/");

        return "redirected";
    }

    const requiresAuth = route.auth || Boolean(route.roles);

    if (requiresAuth && !user) {

        // Se recuerda el destino para volver tras iniciar sesión.
        saveRedirect(`#${path}`);

        redirectTo("#/login");

        return "redirected";
    }

    if (
        route.roles
        && !route.roles.includes(Number(user.id_rol))
    ) {
        return "forbidden";
    }

    return "allow";
}

export async function renderRoute() {

    // Fuera del try: el bloque catch también necesita el contenedor
    // para poder pintar el estado de error.
    const app = document.querySelector("#app");

    try {

        runCleanup();

        const path = getCurrentPath();

        const route = routes[path];

        if (!route) {
            app.innerHTML = Layout(
                ErrorState("<h1>404 página no encontrada</h1>")
            );

            return;
        }

        const access = checkAccess(path, route);

        // La navegación dispara hashchange y vuelve a entrar aquí.
        if (access === "redirected") {
            return;
        }

        if (access === "forbidden") {
            app.innerHTML = Layout(
                ErrorState(
                    "<h1>No tienes permiso para ver esta página</h1>"
                )
            );

            return;
        }

        // SHOW LOADING
        const loader = routeLoaders[path];

        app.innerHTML = loader ? loader() : Loading();

        // LOAD PAGE
        const html = Layout(await route.page());

        // RENDER PAGE
        app.innerHTML = html;

    }
    catch (error) {

        console.error(error);

        app.innerHTML = Layout(
            ErrorState(
                `<h1>Error cargando la página</h1>
                 <p>${escapeHtml(error.message)}</p>`
            )
        );

    }
}
