import { Sidebar } from "./Sidebar.js";
import { Footer, CtaBand } from "./Footer.js";

/*
 * El menú lateral queda fuera de `.shell` porque está fijado a la
 * ventana. `.shell` es la columna de contenido: se desplaza a la
 * derecha con un margen del ancho de la barra y contiene el pie,
 * para que también respete ese margen.
 */

/*
 * La franja «Reservar cita» va donde se termina de leer sobre el
 * salón: la portada y el equipo. No en el catálogo, que ya es donde
 * se reserva, ni en el resto, donde se está haciendo otra cosa.
 */
const CTA_ROUTES = ["/", "/professionals"];

function currentPath() {

    return window.location.hash.slice(1) || "/";
}

/**
 * En el panel de administración, pie mínimo; en el resto, completo.
 */
export function footerVariant() {

    return currentPath().startsWith("/admin") ? "minimal" : "full";
}

export function Layout(content) {

    return `

            ${Sidebar()}

            <div class="shell">

                <main class="shell__main">
                    ${content}
                </main>

                ${CTA_ROUTES.includes(currentPath()) ? CtaBand() : ""}

                ${Footer(footerVariant())}

            </div>

    `;
}
