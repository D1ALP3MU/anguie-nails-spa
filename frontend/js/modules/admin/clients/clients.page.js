import { fetchClients } from "./clients.api.js";
import { ClientResults, filterClients, countText } from "./components/ClientResults.js";
import { initClientsEvents, currentQuery } from "./clients.controller.js";
import { escapeHtml } from "../../../utils/html.js";

export async function ClientsPage() {

    const clients = await fetchClients();

    initClientsEvents(clients);

    const query = currentQuery();

    // Sin clientas no hay nada que buscar: solo el aviso de la tabla.
    const search = clients.length
        ? `
            <div class="clients-search" role="search">
                <label for="clients-search" class="sr-only">Buscar clientas</label>
                <input
                    type="search"
                    id="clients-search"
                    placeholder="Buscar por nombre, correo, teléfono o dirección"
                    value="${escapeHtml(query)}"
                    autocomplete="off"
                    spellcheck="false"
                >
                ${/*
                    Fuera del bloque que se repinta: un aviso para
                    lectores de pantalla que se reemplaza en cada
                    tecla no siempre llega a leerse.
                  */ ""}
                <p class="clients-search__count" id="clients-count" aria-live="polite">
                    ${countText(filterClients(clients, query).length, clients.length, query)}
                </p>
            </div>
        `
        : "";

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
                    Nueva clienta
                </button>

            </div>

            ${search}

            <div id="clients-results">
                ${ClientResults(clients, query)}
            </div>

        </section>

    `;
}
