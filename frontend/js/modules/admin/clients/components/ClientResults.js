import { escapeHtml } from "../../../../utils/html.js";
import { matchesQuery } from "../../../../utils/search.js";
import { ClientTable } from "./ClientTable.js";

/**
 * Busca en nombre, correo, dirección y teléfono.
 */
export function filterClients(clients, query) {

    return clients.filter(c => matchesQuery(query, {
        text: [c.name, c.email, c.address],
        phones: [c.phone]
    }));
}

/**
 * «11 clientes» o, buscando, «3 de 11 clientes».
 */
export function countText(shown, total, query) {

    const word = total === 1 ? "cliente" : "clientes";

    return query.trim()
        ? `${shown} de ${total} ${word}`
        : `${total} ${word}`;
}

/**
 * La tabla filtrada, o un aviso si nada coincide.
 */
export function ClientResults(clients, query) {

    const shown = filterClients(clients, query);

    if (clients.length && !shown.length) {
        return `
            <div class="clients-empty">
                <p>Ninguna clienta coincide con «${escapeHtml(query.trim())}».</p>
                <button type="button" class="btn btn-sm btn-outline" data-clear-search>
                    Borrar la búsqueda
                </button>
            </div>
        `;
    }

    return ClientTable(shown);
}
