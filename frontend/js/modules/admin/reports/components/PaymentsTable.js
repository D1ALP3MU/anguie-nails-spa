import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";
import { formatDate } from "../../../../utils/date.js";

export function PaymentsTable(payments) {

    return `
        <section class="reporte-bloque">

            <h2>Detalle de cobros</h2>

            <div class="clients-table-wrapper">

                <table class="clients-table">

                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Clienta</th>
                            <th>Servicio</th>
                            <th>Forma</th>
                            <th>Estado</th>
                            <th class="num">Precio</th>
                            <th class="num">Cobrado</th>
                        </tr>
                    </thead>

                    <tbody>
                        ${payments.map(p => `
                            <tr>
                                <td data-primary>${escapeHtml(formatDate(p.fecha))}</td>
                                <td data-label="Clienta">${escapeHtml(p.cliente)}</td>
                                <td data-label="Servicio">${escapeHtml(p.servicio)}</td>
                                <td data-label="Forma" class="capitalize">${escapeHtml(p.metodo)}</td>
                                <td data-label="Estado">
                                    <span class="agenda-estado agenda-estado--${escapeHtml(p.estado)}">
                                        ${escapeHtml(p.estado)}
                                    </span>
                                </td>
                                <td data-label="Precio" class="num">${formatCOP(Number(p.precio_cita))}</td>
                                <td data-label="Cobrado" class="num strong">${formatCOP(Number(p.monto))}</td>
                            </tr>
                        `).join("")}
                    </tbody>

                </table>

            </div>

        </section>
    `;
}
