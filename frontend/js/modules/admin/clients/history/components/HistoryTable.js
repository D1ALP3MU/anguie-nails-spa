import { escapeHtml } from "../../../../../utils/html.js";
import { formatCOP } from "../../../../../utils/currency.js";

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
                        <th>Hora</th>
                        <th>Servicio</th>
                        <th>Profesional</th>
                        <th>Estado</th>
                        <th>Cobro</th>
                    </tr>
                </thead>

                <tbody>

                    ${recientes.map(appointment => `
                        <tr>
                            <td>${escapeHtml(appointment.fecha)}</td>
                            <td>${escapeHtml(appointment.hora.slice(0, 5))}</td>
                            <td>${escapeHtml(appointment.servicio)}</td>
                            <td>${escapeHtml(appointment.profesional)}</td>
                            <td>
                                <span class="agenda-estado agenda-estado--${escapeHtml(appointment.estado)}">
                                    ${escapeHtml(appointment.estado)}
                                </span>
                            </td>
                            <td>${paymentCell(appointment)}</td>
                        </tr>
                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}
