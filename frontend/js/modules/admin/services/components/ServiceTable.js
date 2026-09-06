import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";

/**
 * Recorta un texto largo para que la tabla no se desborde.
 */
function shorten(text, limit = 60) {

    if (!text) return "Sin descripción";

    return text.length > limit
        ? text.slice(0, limit).trimEnd() + "…"
        : text;
}

export function ServiceTable(services) {

    if (!services.length) {
        return `
            <div class="clients-empty">
                <p>No hay servicios en el catálogo.</p>
            </div>
        `;
    }

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Descripción</th>
                        <th>Duración</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    ${services.map(service => `
                        <tr>
                            <td>${escapeHtml(service.name)}</td>
                            <td>${escapeHtml(shorten(service.description))}</td>
                            <td>${escapeHtml(service.duration)} min</td>
                            <td>${formatCOP(service.price)}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-outline"
                                    data-edit-service="${escapeHtml(service.id)}"
                                >
                                    Editar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    data-delete-service="${escapeHtml(service.id)}"
                                >
                                    Retirar
                                </button>
                            </td>
                        </tr>
                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}
