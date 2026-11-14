const FOCUSABLE = [
    "a[href]",
    "button:not([disabled])",
    "input:not([disabled]):not([type='hidden'])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "[tabindex]:not([tabindex='-1'])"
].join(",");

/**
 * Mantiene el tabulador dentro de un diálogo.
 *
 * Sin esto, Tab sale del cuadro y recorre la página que hay detrás,
 * que está tapada y no se ve: el foco se pierde a ojos de quien usa
 * el teclado.
 *
 * @param {HTMLElement} container El cuadro del diálogo.
 * @param {KeyboardEvent} event Pulsación de tecla en curso.
 */
export function trapFocus(container, event) {

    if (event.key !== "Tab") return;

    const items = [...container.querySelectorAll(FOCUSABLE)];

    if (!items.length) {
        event.preventDefault();
        return;
    }

    const first = items[0];
    const last = items.at(-1);
    const current = document.activeElement;

    // El propio cuadro cuenta como inicio: es donde cae el foco al abrir.
    const atStart = current === first || current === container;

    if (event.shiftKey && (atStart || !container.contains(current))) {
        event.preventDefault();
        last.focus();
        return;
    }

    if (!event.shiftKey && (current === last || !container.contains(current))) {
        event.preventDefault();
        first.focus();
    }
}
