import { escapeHtml } from "../../utils/html.js";
import { trapFocus } from "../../utils/focus.js";

/**
 * Diálogo de confirmación.
 *
 * Sustituye a window.confirm() y devuelve una promesa, de modo que
 * quien llama pueda seguir usando await sin cambiar su estructura.
 *
 * Usa su propia clase de superposición, y no .modal-overlay, para
 * no chocar con los manejadores que cierran los modales de formulario.
 *
 * Si la acción es destructiva, el foco inicial va a «Cancelar»: así
 * un Enter dado por inercia no anula ni borra nada.
 */
export function confirmAction({
    title = "Confirmar",
    message = "¿Deseas continuar?",
    confirmText = "Confirmar",
    cancelText = "Cancelar",
    danger = false
} = {}) {

    return new Promise(resolve => {

        const overlay = document.createElement("div");

        overlay.className = "confirm-overlay";

        overlay.innerHTML = `
            <div
                class="confirm"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
                aria-describedby="confirm-message"
            >

                <h2 class="confirm__title" id="confirm-title">${escapeHtml(title)}</h2>

                <p class="confirm__message" id="confirm-message">${escapeHtml(message)}</p>

                <div class="confirm__actions">

                    <button class="btn btn-outline" data-confirm-cancel>
                        ${escapeHtml(cancelText)}
                    </button>

                    <button
                        class="btn ${danger ? "btn-danger-solid" : "btn-primary"}"
                        data-confirm-accept
                    >
                        ${escapeHtml(confirmText)}
                    </button>

                </div>

            </div>
        `;

        const returnFocusTo = document.activeElement;

        const close = (result) => {

            document.removeEventListener("keydown", onKeydown);

            window.removeEventListener("hashchange", onNavigate);

            overlay.remove();

            if (returnFocusTo?.isConnected) {
                returnFocusTo.focus();
            }

            resolve(result);
        };

        function onKeydown(event) {

            if (event.key === "Escape") {
                close(false);
                return;
            }

            trapFocus(overlay.querySelector(".confirm"), event);
        }

        // Navegar con el diálogo abierto equivale a no confirmar.
        function onNavigate() {

            close(false);
        }

        overlay.addEventListener("click", event => {

            if (event.target.closest("[data-confirm-accept]")) {
                close(true);
                return;
            }

            if (event.target.closest("[data-confirm-cancel]")) {
                close(false);
                return;
            }

            // Un clic fuera del cuadro cancela.
            if (event.target === overlay) {
                close(false);
            }
        });

        document.addEventListener("keydown", onKeydown);

        window.addEventListener("hashchange", onNavigate);

        document.body.appendChild(overlay);

        overlay.querySelector(
            danger ? "[data-confirm-cancel]" : "[data-confirm-accept]"
        ).focus();
    });
}
