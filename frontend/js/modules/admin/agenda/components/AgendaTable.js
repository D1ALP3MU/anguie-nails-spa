import { escapeHtml } from "../../../../utils/html.js";

/**
 * Una cita solo se confirma si todavía no ha ocurrido: el backend
 * rechaza pasar a "confirmada" un horario que ya pasó. Para esas,
 * lo que corresponde es completarla o cancelarla.
 */
function isUpcoming(appointment) {

    const when = new Date(`${appointment.fecha}T${appointment.hora}`);

    return when.getTime() >= Date.now();
}

function actionsFor(appointment) {

    const id = escapeHtml(appointment.id_cita);

    if (["cancelada", "completada"].includes(appointment.estado)) {
        return `<span class="agenda-table__closed">Sin acciones</span>`;
    }

    const buttons = [];

    if (appointment.estado === "pendiente" && isUpcoming(appointment)) {
        buttons.push(`
            <button type="button" class="btn btn-outline"
                data-confirm-appointment="${id}">Confirmar</button>
        `);
    }

    buttons.push(`
        <button type="button" class="btn btn-outline"
            data-complete-appointment="${id}">Completar</button>
    `);

    buttons.push(`
        <button type="button" class="btn btn-danger"
            data-cancel-appointment="${id}">Cancelar</button>
    `);

    return buttons.join("");
}

export function AgendaTable(appointments) {

    if (!appointments.length) {
        return `
            <div class="clients-empty">
                <p>No hay citas que coincidan con los filtros.</p>
            </div>
        `;
    }

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Clienta</th>
                        <th>Servicio</th>
                        <th>Profesional</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    ${appointments.map(appointment => `
                        <tr>
                            <td>${escapeHtml(appointment.fecha)}</td>
                            <td>${escapeHtml(appointment.hora.slice(0, 5))}</td>
                            <td>${escapeHtml(appointment.cliente)}</td>
                            <td>${escapeHtml(appointment.servicio)}</td>
                            <td>${escapeHtml(appointment.profesional)}</td>
                            <td>
                                <span class="agenda-estado agenda-estado--${escapeHtml(appointment.estado)}">
                                    ${escapeHtml(appointment.estado)}
                                </span>
                            </td>
                            <td>${actionsFor(appointment)}</td>
                        </tr>
                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}
