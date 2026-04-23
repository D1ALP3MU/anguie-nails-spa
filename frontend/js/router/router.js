import { HomePage } from "../modules/home/home.page.js";

import { ServicesPage  } from "../modules/services/services.page.js";

import { BookingPage } from "../modules/services/booking.page.js";

import { Layout } from "../components/layout/Layout.js";

const routes = {
    "/": HomePage,
    "/": ServicesPage,
    "/": BookingPage,
}

export function initRouter() {
    window.addEventListener("hashchange", renderRoute);

    renderRoute();
}

function renderRoute() {
    const path = location.hash.slice(1) || "/";
    const page = routes[path];

    if (!page) return;

    const app = document.querySelector("#app");

    app.innerHTML = Layout(
        page()
    );   
}