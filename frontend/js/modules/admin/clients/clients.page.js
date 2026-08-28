import { fetchClients } from "./clients.api.js";
import { ClientTable } from "./components/ClientTable.js";
import { initClientsEvents } from "./clients.controller.js";

export async function ClientsPage() {

    console.log("ClientsPage loaded");

    const clients = await fetchClients();

    setTimeout(() => {
        initClientsEvents();
    }, 0);

    return `

        <section class="clients-page">

            <div class="clients-page__header">

                <div>
                    <h1>Clientes</h1>
                    <p>Gestiona los clientes registrados en Anguie Nails.</p>
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-create-client
                >
                    Nuevo Cliente
                </button>

            </div>

            ${ClientTable(clients)}

        </section>

    `;
}