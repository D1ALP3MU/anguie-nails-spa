import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";

/**
 * Desglose de lo facturado por una dimensión.
 *
 * El porcentaje se calcula sobre el total mostrado, no sobre el
 * facturado global: así las barras siempre suman 100 aunque la
 * agrupación deje algo fuera.
 */
export function BreakdownTable(titulo, filas, etiquetaColumna) {

    if (!filas.length) {
        return `
            <section class="reporte-bloque">
                <h2>${escapeHtml(titulo)}</h2>
                <p class="reporte-vacio">Sin cobros en el periodo.</p>
            </section>
        `;
    }

    const total = filas.reduce((suma, f) => suma + Number(f.total), 0);

    return `
        <section class="reporte-bloque">

            <h2>${escapeHtml(titulo)}</h2>

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>${escapeHtml(etiquetaColumna)}</th>
                        <th>Cobros</th>
                        <th>Total</th>
                        <th>Participación</th>
                    </tr>
                </thead>

                <tbody>
                    ${filas.map(f => {

                        const parte = total > 0
                            ? Math.round((Number(f.total) / total) * 100)
                            : 0;

                        return `
                            <tr>
                                <td>${escapeHtml(f.etiqueta)}</td>
                                <td>${escapeHtml(f.cobros)}</td>
                                <td>${formatCOP(Number(f.total))}</td>
                                <td>
                                    <div class="reporte-barra">
                                        <span style="width: ${parte}%"></span>
                                    </div>
                                    ${parte}%
                                </td>
                            </tr>
                        `;
                    }).join("")}
                </tbody>

            </table>

        </section>
    `;
}
