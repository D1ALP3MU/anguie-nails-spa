import { trapFocus } from "../../utils/focus.js";

/**
 * Modal de formulario.
 *
 * Abrir y cerrar viven aquí, y no en cada controlador, por una razón:
 * el modal se inserta en <body>, fuera de #app, así que sobrevive al
 * cambio de ruta. Si su cierre dependiera de los manejadores de la
 * página que lo abrió —que la limpieza de rutas desregistra al
 * navegar—, pulsar «atrás» con el modal abierto lo dejaría encima de
 * la página nueva y sin forma de cerrarlo.
 *
 * Por eso los manejadores de cierre son globales: se registran una
 * sola vez al arrancar, con initModalEvents().
 */

/** Elemento que tenía el foco antes de abrir, para devolvérselo. */
let returnFocusTo = null;

export function Modal({ title, content }) {

    return `
        <div class="modal-overlay">
            <div
                class="modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
                tabindex="-1"
            >
                <div class="modal__header">
                    <h2 id="modal-title">${title}</h2>
                    <button class="modal__close" data-close-modal aria-label="Cerrar modal">
                        x
                    </button>
                </div>
                <div class="modal__body">
                    ${content}
                </div>
            </div>
        </div>
    `;
}

export function isModalOpen() {

    return document.querySelector(".modal-overlay") !== null;
}

/**
 * Abre un modal. Si ya hay uno abierto no hace nada: un doble clic
 * en «Nuevo» no debe apilar dos.
 *
 * El foco va al cuadro y no al primer campo: en móvil, enfocar un
 * campo despliega el teclado y tapa medio formulario antes de que se
 * haya leído.
 */
export function openModal(title, content) {

    if (isModalOpen()) return;

    returnFocusTo = document.activeElement;

    document.body.insertAdjacentHTML(
        "beforeend",
        Modal({ title, content })
    );

    document.querySelector(".modal").focus();
}

export function closeModal() {

    const overlay = document.querySelector(".modal-overlay");

    if (!overlay) return;

    overlay.remove();

    // Si la página se repintó, el botón que lo abrió ya no existe.
    if (returnFocusTo?.isConnected) {
        returnFocusTo.focus();
    }

    returnFocusTo = null;
}

/**
 * Registra los manejadores de cierre. Se llama una sola vez al
 * arrancar.
 */
export function initModalEvents() {

    document.addEventListener("click", event => {

        if (
            event.target.closest("[data-close-modal]")
            || event.target.classList.contains("modal-overlay")
        ) {
            closeModal();
        }
    });

    document.addEventListener("keydown", event => {

        const modal = document.querySelector(".modal");

        // Un diálogo de confirmación abierto encima atiende él solo
        // sus teclas.
        if (!modal || document.querySelector(".confirm-overlay")) return;

        if (event.key === "Escape") {
            closeModal();
            return;
        }

        trapFocus(modal, event);
    });

    // Un modal pertenece a la página que lo abrió.
    window.addEventListener("hashchange", closeModal);
}
