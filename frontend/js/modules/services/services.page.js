import { ServiceCard } from "./components/ServiceCard.js";

import { initServicesEvents } from "./services.controller.js";

import { fetchServices } from "./services.api.js";


export async function ServicesPage() {
    console.log("ServicesPage loaded");

    const services = await fetchServices();

    setTimeout(() => {
        initServicesEvents();
    }, 0);

    await new Promise(resolve => {
        setTimeout(resolve, 1200);
    });

    return `

        <section class="services-page">

            <div class="services-page__header">
                <h1>Nuestros Servicios</h1>
                <p>Experiencias premium para ti.</p>
            </div>

            <div class="services-grid">

                ${services.map(service => 
                    ServiceCard(service)
                ).join("")}

            </div>

        </section>

    `;
}