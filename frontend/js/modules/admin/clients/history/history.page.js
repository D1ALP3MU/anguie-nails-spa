import { escapeHtml } from "../../../../utils/html.js";
import { fetchClientHistory } from "./history.api.js";
import { HistorySummary } from "./components/HistorySummary.js";
import { HistoryTable } from "./components/HistoryTable.js";

/**
 * Ficha de una clienta: sus datos, sus cifras y todas sus citas.
 *
 * El identificador llega del router como parámetro de la ruta
 * `/admin/clients/{id}/historial`.
 *
 * No registra listeners: la página es de solo lectura, así que no
 * necesita controlador.
 */
export async function ClientHistoryPage({ id }) {

    const { client, appointments } = await fetchClientHistory(id);

    return `

        ${/*
            `clients-page` es la caja común del panel —la reutilizan
            agenda y facturación— y es la que trae los márgenes.
          */ ""}
        <section class="clients-page historial-page">

            <a href="#/admin/clients" class="historial-volver">
                ← Volver a clientes
            </a>

            <header class="historial-encabezado">

                <h1>${escapeHtml(client.name)}</h1>

                <dl class="historial-datos">

                    <div>
                        <dt>Correo</dt>
                        <dd>${escapeHtml(client.email)}</dd>
                    </div>

                    <div>
                        <dt>Teléfono</dt>
                        <dd>${escapeHtml(client.phone)}</dd>
                    </div>

                    <div>
                        <dt>Dirección</dt>
                        <dd>${escapeHtml(client.address ?? "Sin dirección")}</dd>
                    </div>

                </dl>

            </header>

            ${HistorySummary(appointments)}

            <h2 class="historial-titulo">Citas</h2>

            ${HistoryTable(appointments)}

        </section>

    `;
}
