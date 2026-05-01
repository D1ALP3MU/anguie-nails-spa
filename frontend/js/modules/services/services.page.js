import { services } from "./services.data.js";

import { ServiceCard } from "./components/ServiceCard.js";

import { initServicesEvents } from "./services.controller.js";


export async function ServicesPage() {
    console.log("ServicesPage loaded");

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