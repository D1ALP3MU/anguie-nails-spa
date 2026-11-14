import { escapeHtml } from "../../utils/html.js";

/**
 * Página que ocupa el lugar de otra que no se pudo mostrar: no
 * existe, no hay permiso o falló la carga.
 *
 * Recibe texto, no HTML, y lo escapa: el mensaje puede venir de
 * una excepción, y una excepción no es una fuente de confianza.
 *
 * Siempre ofrece una salida: quien llega aquí no está donde quería.
 */
export function ErrorState({
    title = "Algo salió mal",
    message = ""
} = {}) {

    return `

        <section class="error-state">

            <h1>${escapeHtml(title)}</h1>

            ${message ? `<p>${escapeHtml(message)}</p>` : ""}

            <a href="#/" class="btn btn-primary">Volver al inicio</a>

        </section>

    `;
}
