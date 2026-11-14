import { escapeHtml } from "../../utils/html.js";

/**
 * Menú de acciones «⋯».
 *
 * Agrupa las acciones secundarias de una fila para que solo quede a
 * la vista la principal. Cada opción es un botón con los mismos
 * atributos data-* que tendría suelto, así que el controlador de la
 * página la atiende igual que antes: el menú solo la esconde.
 *
 * Abrir, cerrar y moverse con el teclado viven aquí, con manejadores
 * globales que se registran una vez con initActionMenuEvents(): las
 * filas se repintan a menudo y un listener por menú se perdería.
 */

let counter = 0;

/**
 * @typedef {object} MenuItem
 * @property {string} label Texto de la opción.
 * @property {object} [data] Atributos data-*, sin el prefijo: { "cancel-appointment": 7 }.
 * @property {string} [href] Si existe, la opción es un enlace.
 * @property {boolean} [danger] Acción destructiva: va al final y en rojo.
 */

/**
 * @param {MenuItem[]} items
 * @param {string} label Nombre accesible del botón: «Más acciones para Ana, 10:00».
 * @returns {string}
 */
export function ActionMenu(items, label) {

    if (!items.length) return "";

    const id = `menu-${++counter}`;

    const safe = items.filter(item => !item.danger);
    const danger = items.filter(item => item.danger);

    return `
        <div class="menu">
            <button
                type="button"
                class="menu__toggle"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="${id}"
                aria-label="${escapeHtml(label)}"
                data-menu-toggle
            >⋯</button>

            <div class="menu__list" id="${id}" role="menu" hidden>
                ${safe.map(menuItem).join("")}
                ${safe.length && danger.length
                    ? `<div class="menu__separator" role="separator"></div>`
                    : ""}
                ${danger.map(menuItem).join("")}
            </div>
        </div>
    `;
}

function menuItem(item) {

    const label = escapeHtml(item.label);

    if (item.href) {
        return `
            <a class="menu__item" role="menuitem" href="${escapeHtml(item.href)}" tabindex="-1">
                ${label}
            </a>
        `;
    }

    const data = Object.entries(item.data ?? {})
        .map(([key, value]) => `data-${key}="${escapeHtml(value)}"`)
        .join(" ");

    return `
        <button
            type="button"
            class="menu__item${item.danger ? " menu__item--danger" : ""}"
            role="menuitem"
            tabindex="-1"
            ${data}
        >${label}</button>
    `;
}

/* -------------------------------------------------------------------
   Comportamiento
   ------------------------------------------------------------------- */

function itemsOf(list) {

    return [...list.querySelectorAll("[role='menuitem']")];
}

function openMenu(toggle) {

    closeMenus();

    const list = document.getElementById(toggle.getAttribute("aria-controls"));

    list.hidden = false;
    list.classList.remove("menu__list--up");
    toggle.setAttribute("aria-expanded", "true");

    // Si no cabe hacia abajo, se abre hacia arriba.
    if (list.getBoundingClientRect().bottom > window.innerHeight) {
        list.classList.add("menu__list--up");
    }

    itemsOf(list)[0]?.focus();
}

/**
 * @param {boolean} returnFocus Devolver el foco al botón «⋯».
 */
function closeMenus(returnFocus = false) {

    document.querySelectorAll("[data-menu-toggle][aria-expanded='true']").forEach(toggle => {

        toggle.setAttribute("aria-expanded", "false");

        document.getElementById(toggle.getAttribute("aria-controls"))?.setAttribute("hidden", "");

        if (returnFocus) toggle.focus();
    });
}

export function initActionMenuEvents() {

    document.addEventListener("click", event => {

        const toggle = event.target.closest("[data-menu-toggle]");

        if (toggle) {

            const isOpen = toggle.getAttribute("aria-expanded") === "true";

            isOpen ? closeMenus() : openMenu(toggle);

            return;
        }

        /*
         * Una opción elegida cierra el menú y devuelve el foco al «⋯»
         * antes de que el controlador actúe: si abre un diálogo, al
         * cerrarlo el foco vuelve a un botón visible y no a una opción
         * escondida.
         */
        if (event.target.closest(".menu__list [role='menuitem']")) {
            closeMenus(true);
            return;
        }

        if (!event.target.closest(".menu__list")) {
            closeMenus();
        }
    });

    document.addEventListener("keydown", event => {

        const list = event.target.closest?.(".menu__list");

        if (!list) return;

        const items = itemsOf(list);
        const index = items.indexOf(document.activeElement);

        if (event.key === "Escape") {
            event.preventDefault();
            closeMenus(true);
            return;
        }

        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();
            const step = event.key === "ArrowDown" ? 1 : -1;
            items[(index + step + items.length) % items.length].focus();
            return;
        }

        if (event.key === "Home" || event.key === "End") {
            event.preventDefault();
            items[event.key === "Home" ? 0 : items.length - 1].focus();
            return;
        }

        // Tab sale del menú, como de cualquier otro control.
        if (event.key === "Tab") {
            closeMenus();
        }
    });

    window.addEventListener("hashchange", () => closeMenus());
}
