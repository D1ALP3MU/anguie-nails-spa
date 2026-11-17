import { escapeHtml } from "../../utils/html.js";

/**
 * Campo de contraseña con botón para mostrarla.
 *
 * El botón es un conmutador con etiqueta fija —«Mostrar contraseña»—
 * y su estado en aria-pressed: es el patrón que los lectores de
 * pantalla anuncian bien («activado» / «desactivado»), en lugar de
 * cambiarle el nombre al botón en cada pulsación.
 *
 * El comportamiento vive en initPasswordToggles(), registrado una
 * sola vez al arrancar: los formularios se repintan y un listener por
 * campo se perdería.
 *
 * @param {object} options
 * @param {string} options.id
 * @param {string} options.name
 * @param {string} options.autocomplete «current-password» o «new-password».
 * @param {string} [options.placeholder]
 * @param {number} [options.minlength]
 * @param {number} [options.maxlength]
 * @param {string} [options.describedBy] id del texto de ayuda del campo.
 * @param {boolean} [options.required=true]
 */
export function PasswordInput({
    id,
    name,
    autocomplete,
    placeholder = "",
    minlength,
    maxlength,
    describedBy,
    required = true
}) {

    const attr = (key, value) => value !== undefined && value !== ""
        ? ` ${key}="${escapeHtml(value)}"`
        : "";

    return `
        <div class="password-field">
            <input
                type="password"
                id="${escapeHtml(id)}"
                name="${escapeHtml(name)}"
                autocomplete="${escapeHtml(autocomplete)}"${attr("placeholder", placeholder)}${attr("minlength", minlength)}${attr("maxlength", maxlength)}${attr("aria-describedby", describedBy)}
                ${required ? "required" : ""}
            >
            <button
                type="button"
                class="password-field__toggle"
                data-password-toggle
                aria-controls="${escapeHtml(id)}"
                aria-label="Mostrar contraseña"
                aria-pressed="false"
            >
                <svg class="password-field__show" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <svg class="password-field__hide" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path d="M3 3l18 18"/>
                    <path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.7 17.7 0 0 1-3.2 4.1M6.6 6.6A17.4 17.4 0 0 0 2 12s3.6 7 10 7a9.8 9.8 0 0 0 5.4-1.6"/>
                    <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                </svg>
            </button>
        </div>
    `;
}

function setVisible(toggle, visible) {

    const input = document.getElementById(toggle.getAttribute("aria-controls"));

    if (!input) return;

    input.type = visible ? "text" : "password";

    toggle.setAttribute("aria-pressed", String(visible));
}

/**
 * Oculta todas las contraseñas de un formulario.
 */
function hideAll(form) {

    form.querySelectorAll("[data-password-toggle]").forEach(
        toggle => setVisible(toggle, false)
    );
}

export function initPasswordToggles() {

    document.addEventListener("click", event => {

        const toggle = event.target.closest("[data-password-toggle]");

        if (!toggle) return;

        setVisible(toggle, toggle.getAttribute("aria-pressed") !== "true");
    });

    /*
     * Al enviar, todas vuelven a ocultarse. En fase de captura, para
     * que ocurra antes de que el controlador del formulario lo trate:
     * tras cambiar la contraseña el formulario se vacía, pero si no se
     * ocultara, la siguiente que se escribiera quedaría a la vista.
     */
    document.addEventListener("submit", event => hideAll(event.target), true);

    document.addEventListener("reset", event => hideAll(event.target), true);
}
