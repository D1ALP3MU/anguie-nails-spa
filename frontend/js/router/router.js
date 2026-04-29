import { HomePage } from "../modules/home/home.page.js";

import { ServicesPage } from "../modules/services/services.page.js";

import { BookingPage } from "../modules/booking/booking.page.js";

import { Layout } from "../components/layout/Layout.js";

import { Loading } from "../components/ui/Loading.js";

import { ErrorState } from "../components/ui/ErrorState.js";

const routes = {
    "/": HomePage,
    "/services": ServicesPage,
    "/booking": BookingPage,
}

export function initRouter() {

    window.addEventListener("hashchange", renderRoute);

    renderRoute();

}

function getCurrentPath() {

    return window.location.hash.slice(1) || "/";

}

async function renderRoute() {

    try {
        const app = document.querySelector("#app");

        const path = getCurrentPath();
    
        const page = routes[path];
    
        if (!page) {
            app.innerHTML = Layout(ErrorState("<h1>404 página no encontrada</h1>"));
    
            return;
        }
        // SHOW LOADING
        app.innerHTML = Loading();

        // LOAD PAGE
        const html = Layout(await page());

        // RENDER PAGE
        app.innerHTML = html;
    }
    catch (error) {
        console.error(error);

        app.innerHTML = Layout(ErrorState("Error cargando la página"));
    }
}