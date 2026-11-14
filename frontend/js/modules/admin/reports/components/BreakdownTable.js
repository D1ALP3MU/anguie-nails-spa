import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";

/**
 * Desglose de lo facturado por una dimensión.
 *
 * El porcentaje se calcula sobre el total mostrado, no sobre el
 * facturado global: así las barras siempre suman 100 aunque la
 * agrupación deje algo fuera.
 *
 * Sin filas no pinta nada: si el periodo no tiene cobros, la página
 * muestra un único aviso en lugar de un bloque vacío por desglose.
 */
export function BreakdownTable(titulo, filas, etiquetaColumna) {

    if (!filas.length) return "";

    const total = filas.reduce((suma, f) => suma + Number(f.total), 0);

    return `
        <section class="reporte-bloque">

            <h2>${escapeHtml(titulo)}</h2>

            <div class="clients-table-wrapper">

                <table class="clients-table">

                    <thead>
                        <tr>
                            <th>${escapeHtml(etiquetaColumna)}</th>
                            <th class="num">Cobros</th>
                            <th class="num">Total</th>
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
                                    <td data-primary class="capitalize">${escapeHtml(f.etiqueta)}</td>
                                    <td data-label="Cobros" class="num">${escapeHtml(f.cobros)}</td>
                                    <td data-label="Total" class="num strong">${formatCOP(Number(f.total))}</td>
                                    <td data-label="Participación">
                                        <span class="reporte-barra" aria-hidden="true">
                                            <span style="width: ${parte}%"></span>
                                        </span>
                                        ${parte}%
                                    </td>
                                </tr>
                            `;
                        }).join("")}
                    </tbody>

                </table>

            </div>

        </section>
    `;
}
