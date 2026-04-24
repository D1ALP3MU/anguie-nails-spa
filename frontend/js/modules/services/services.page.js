import { services } from "./services.data.js";

import { ServiceCard } from "./components/ServiceCard.js";

import { initServicesEvents } from "./services.controller.js";

export function ServicesPage() {
    console.log("ServicesPage loaded");

    setTimeout(() => {
        initServicesEvents();
    }, 0);

    return `

        <section class="services-page">

            <div class="services-page__header">
                <h1>Nuestros Servicios</h1>
                <p>Experiencias premium para ti.</p>
            </div>

            <div class="services-grid">
                ${services.map(service => {

                    console.log(service);
                    return ServiceCard(service);

                }).join("")}

            </div>

        </section>

    `;
}