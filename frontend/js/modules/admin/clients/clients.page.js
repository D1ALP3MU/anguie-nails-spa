import { fetchClients } from "./clients.api.js";
import { ClientTable } from "./components/ClientTable.js";

export async function ClientsPage() {

    console.log("ClientsPage loaded");

    const clients = await fetchClients();

    return `

        <section class="clients-page">

            <div class="clients-page__header">

                <div>
                    <h1>Clientes</h1>
                    <p>Gestiona los clientes registrados en Anguie Nails.</p>
                </div>

            </div>

            ${ClientTable(clients)}

        </section>

    `;
}