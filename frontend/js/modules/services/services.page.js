import { ServiceCard } from "./components/ServiceCard.js";

import { fetchServices } from "./services.api.js";

/*
 * Catálogo público. No necesita controlador: «Reservar» es un enlace
 * a #/reservar/{id}, que es donde ocurre la reserva.
 */
export async function ServicesPage() {

    const services = await fetchServices();

    return `

        <section class="services-page">

            <div class="services-page__header">
                <h1>Nuestros servicios</h1>
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
