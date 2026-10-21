import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";

export function PaymentsTable(payments) {

    if (!payments.length) {
        return `
            <section class="reporte-bloque">
                <h2>Detalle de cobros</h2>
                <p class="reporte-vacio">No hay cobros en el periodo.</p>
            </section>
        `;
    }

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
                            <th>Precio</th>
                            <th>Cobrado</th>
                            <th>Forma</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        ${payments.map(p => `
                            <tr>
                                <td>${escapeHtml(p.fecha)}</td>
                                <td>${escapeHtml(p.cliente)}</td>
                                <td>${escapeHtml(p.servicio)}</td>
                                <td>${formatCOP(Number(p.precio_cita))}</td>
                                <td>${formatCOP(Number(p.monto))}</td>
                                <td>${escapeHtml(p.metodo)}</td>
                                <td>
                                    <span class="agenda-estado agenda-estado--${escapeHtml(p.estado)}">
                                        ${escapeHtml(p.estado)}
                                    </span>
                                </td>
                            </tr>
                        `).join("")}
                    </tbody>

                </table>

            </div>

        </section>
    `;
}
