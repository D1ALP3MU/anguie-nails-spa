import { formatCOP } from "../../../../../utils/currency.js";
import { escapeHtml } from "../../../../../utils/html.js";

/**
 * Cifras de la clienta, calculadas sobre las citas que ya están
 * en memoria: son cuatro recorridos de una lista acotada, así que
 * no compensa pedirle al backend un resumen aparte.
 */
function summarize(appointments) {

    const completadas = appointments.filter(
        a => a.estado === "completada"
    );

    const canceladas = appointments.filter(
        a => a.estado === "cancelada"
    );

    const pagado = appointments
        .filter(a => a.estado_pago === "pagado")
        .reduce((total, a) => total + Number(a.monto_pago), 0);

    /*
     * Por cobrar: citas vivas sin cobro vigente. Se usa el precio
     * congelado de la cita, que es lo que se le facturaría hoy.
     */
    const sinCobrar = appointments.filter(
        a => a.estado !== "cancelada" && !a.id_pago
    );

    const porCobrar = sinCobrar.reduce(
        (total, a) => total + Number(a.precio),
        0
    );

    return {
        citas: appointments.length,
        completadas: completadas.length,
        canceladas: canceladas.length,
        pagado,
        porCobrar,
        citasSinCobrar: sinCobrar.length,
        // Las citas llegan ordenadas por fecha ascendente.
        ultimaVisita: completadas.at(-1)?.fecha ?? null
    };
}

function plural(cantidad, singular, pluralForma) {

    return `${cantidad} ${cantidad === 1 ? singular : pluralForma}`;
}

export function HistorySummary(appointments) {

    const datos = summarize(appointments);

    const tarjetas = [
        {
            etiqueta: "Citas",
            valor: String(datos.citas),
            detalle: `${datos.completadas} completadas · ${datos.canceladas} canceladas`,
            tono: "neutro"
        },
        {
            etiqueta: "Total pagado",
            valor: formatCOP(datos.pagado),
            detalle: "Cobros confirmados",
            tono: "exito"
        },
        {
            etiqueta: "Por cobrar",
            valor: formatCOP(datos.porCobrar),
            detalle: plural(datos.citasSinCobrar, "cita sin cobrar", "citas sin cobrar"),
            tono: "deuda"
        },
        {
            etiqueta: "Última visita",
            valor: datos.ultimaVisita
                ? escapeHtml(datos.ultimaVisita)
                : "—",
            detalle: datos.ultimaVisita
                ? "Última cita completada"
                : "Todavía sin visitas",
            tono: "aviso"
        }
    ];

    return `
        <div class="historial-tarjetas">
            ${tarjetas.map(t => `
                <article class="historial-tarjeta historial-tarjeta--${t.tono}">
                    <p class="historial-tarjeta__etiqueta">${t.etiqueta}</p>
                    <p class="historial-tarjeta__valor">${t.valor}</p>
                    <p class="historial-tarjeta__detalle">${t.detalle}</p>
                </article>
            `).join("")}
        </div>
    `;
}
