import { formatCOP } from "../../../../utils/currency.js";

/**
 * Cifras del periodo.
 *
 * «Por cobrar» sale de las citas sin cobro vigente, no de la tabla
 * de pagos: es la respuesta a «¿cuánto me deben?».
 */
export function SummaryCards(summary) {

    const { totales, sin_cobrar: sinCobrar } = summary;

    const tarjetas = [
        {
            etiqueta: "Facturado",
            valor: formatCOP(Number(totales.facturado)),
            detalle: `${totales.cobros} ${Number(totales.cobros) === 1 ? "cobro" : "cobros"}`,
            tono: "exito"
        },
        {
            etiqueta: "Pendiente de verificar",
            valor: formatCOP(Number(totales.pendiente)),
            detalle: "Cobros anunciados sin confirmar",
            tono: "aviso"
        },
        {
            etiqueta: "Por cobrar",
            valor: formatCOP(Number(sinCobrar.total)),
            detalle: `${sinCobrar.citas} ${Number(sinCobrar.citas) === 1 ? "cita" : "citas"} sin cobrar`,
            tono: "deuda"
        },
        {
            etiqueta: "Anulado",
            valor: formatCOP(Number(totales.anulado)),
            detalle: "Reembolsos y cobros rechazados",
            tono: "neutro"
        }
    ];

    return `
        <div class="reporte-tarjetas">
            ${tarjetas.map(t => `
                <article class="reporte-tarjeta reporte-tarjeta--${t.tono}">
                    <p class="reporte-tarjeta__etiqueta">${t.etiqueta}</p>
                    <p class="reporte-tarjeta__valor">${t.valor}</p>
                    <p class="reporte-tarjeta__detalle">${t.detalle}</p>
                </article>
            `).join("")}
        </div>
    `;
}
