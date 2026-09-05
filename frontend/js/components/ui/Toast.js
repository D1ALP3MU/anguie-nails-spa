import { escapeHtml } from "../../utils/html.js";

/**
 * Aviso breve y no bloqueante.
 *
 * Sustituye a alert(): no congela la pestaña, no obliga a un clic
 * para seguir y encaja con el resto de la interfaz.
 */

const CONTAINER_ID = "toast-container";

const DURATION = 4000;

function container() {

    let element = document.getElementById(CONTAINER_ID);

    if (!element) {

        element = document.createElement("div");

        element.id = CONTAINER_ID;
        element.className = "toast-container";
        element.setAttribute("aria-live", "polite");

        document.body.appendChild(element);
    }

    return element;
}

/**
 * @param {string} message Texto a mostrar.
 * @param {"success"|"error"|"info"} type Tono del aviso.
 */
export function showToast(message, type = "info") {

    const toast = document.createElement("div");

    toast.className = `toast toast--${type}`;
    toast.setAttribute("role", type === "error" ? "alert" : "status");

    toast.innerHTML = `
        <span class="toast__message">${escapeHtml(message)}</span>
        <button class="toast__close" aria-label="Cerrar aviso">&times;</button>
    `;

    const dismiss = () => {

        if (!toast.isConnected) return;

        toast.classList.add("toast--leaving");

        // Se espera a que termine la transición antes de quitarlo.
        toast.addEventListener("transitionend", () => toast.remove(), {
            once: true
        });
    };

    toast.querySelector(".toast__close")
        .addEventListener("click", dismiss);

    container().appendChild(toast);

    setTimeout(dismiss, DURATION);
}

export function toastSuccess(message) {
    showToast(message, "success");
}

export function toastError(message) {
    showToast(message, "error");
}
