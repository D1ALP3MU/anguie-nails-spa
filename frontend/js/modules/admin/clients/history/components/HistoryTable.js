import { escapeHtml } from "../../../../../utils/html.js";
import { formatCOP } from "../../../../../utils/currency.js";
import { formatDate } from "../../../../../utils/date.js";

/**
 * Estado de cobro de una cita pasada.
 *
 * El listado ya trae el cobro vigente, igual que en la agenda.
 */
function paymentCell(appointment) {

    if (appointment.id_pago) {
        return `
            <span class="agenda-estado agenda-estado--${escapeHtml(appointment.estado_pago)}">
                ${formatCOP(Number(appointment.monto_pago))}
                ·
                ${escapeHtml(appointment.metodo_pago)}
            </span>
        `;
    }

    // Una cita cancelada nunca se cobra: no hubo servicio.
    if (appointment.estado === "cancelada") {
        return `<span class="agenda-table__closed">—</span>`;
    }

    return `
        <span class="historial-pendiente">
            ${formatCOP(Number(appointment.precio))}
            sin cobrar
        </span>
    `;
}

/**
 * Historial de citas, de la más reciente a la más antigua.
 *
 * No lleva acciones a propósito: es una consulta. Confirmar,
 * completar o cobrar se hace desde la agenda, que es donde el
 * salón trabaja el día a día.
 */
export function HistoryTable(appointments) {

    if (!appointments.length) {
        return `
            <div class="clients-empty">
                <p>Esta clienta todavía no tiene citas.</p>
            </div>
        `;
    }

    // Llegan en orden ascendente; un historial se lee al revés.
    const recientes = [...appointments].reverse();

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Servicio</th>
                        <th>Profesional</th>
                        <th>Estado</th>
                        <th class="num">Cobro</th>
                    </tr>
                </thead>

                <tbody>

                    ${recientes.map(appointment => `
                        <tr>
                            <td data-primary>
                                ${escapeHtml(formatDate(appointment.fecha))}
                                <span class="clients-table__sub">${escapeHtml(appointment.hora.slice(0, 5))}</span>
                            </td>
                            <td data-label="Servicio">${escapeHtml(appointment.servicio)}</td>
                            <td data-label="Profesional">${escapeHtml(appointment.profesional)}</td>
                            <td data-label="Estado">
                                <span class="agenda-estado agenda-estado--${escapeHtml(appointment.estado)}">
                                    ${escapeHtml(appointment.estado)}
                                </span>
                            </td>
                            <td data-label="Cobro" class="num">${paymentCell(appointment)}</td>
                        </tr>
                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}
