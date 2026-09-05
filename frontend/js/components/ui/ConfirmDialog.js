import { escapeHtml } from "../../utils/html.js";

/**
 * Diálogo de confirmación.
 *
 * Sustituye a window.confirm() y devuelve una promesa, de modo que
 * quien llama pueda seguir usando await sin cambiar su estructura.
 *
 * Usa su propia clase de superposición, y no .modal-overlay, para
 * no chocar con los manejadores que cierran los modales de formulario.
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
            <div class="confirm" role="alertdialog" aria-modal="true">

                <h2 class="confirm__title">${escapeHtml(title)}</h2>

                <p class="confirm__message">${escapeHtml(message)}</p>

                <div class="confirm__actions">

                    <button class="btn btn-outline" data-confirm-cancel>
                        ${escapeHtml(cancelText)}
                    </button>

                    <button
                        class="btn ${danger ? "btn-danger" : "btn-primary"}"
                        data-confirm-accept
                    >
                        ${escapeHtml(confirmText)}
                    </button>

                </div>

            </div>
        `;

        const close = (result) => {

            document.removeEventListener("keydown", onKeydown);

            overlay.remove();

            resolve(result);
        };

        function onKeydown(event) {

            if (event.key === "Escape") close(false);
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

        document.body.appendChild(overlay);

        overlay.querySelector("[data-confirm-accept]").focus();
    });
}
