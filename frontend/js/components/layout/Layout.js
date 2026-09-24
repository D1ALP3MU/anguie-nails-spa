import { Sidebar } from "./Sidebar.js";
import { Footer } from "./Footer.js";

/*
 * El menú lateral queda fuera de `.shell` porque está fijado a la
 * ventana. `.shell` es la columna de contenido: se desplaza a la
 * derecha con un margen del ancho de la barra y contiene el pie,
 * para que también respete ese margen.
 */
export function Layout(content) {

    return `

            ${Sidebar()}

            <div class="shell">

                <main class="shell__main">
                    ${content}
                </main>

                ${Footer()}

            </div>

    `;
}
