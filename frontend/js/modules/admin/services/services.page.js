import { fetchServices } from "./services.api.js";
import { ServiceTable } from "./components/ServiceTable.js";
import { initServicesAdminEvents } from "./services.controller.js";

export async function ServicesAdminPage() {

    const services = await fetchServices();

    initServicesAdminEvents();

    return `

        <section class="clients-page">

            <div class="clients-page__header">

                <div>
                    <h1>Catálogo</h1>
                    <p>Gestiona los servicios que ofrece el salón.</p>
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-create-service
                >
                    Nuevo servicio
                </button>

            </div>

            ${ServiceTable(services)}

        </section>

    `;
}
