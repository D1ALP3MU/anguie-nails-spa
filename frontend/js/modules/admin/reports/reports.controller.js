import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";

/**
 * Periodo del informe.
 *
 * Por defecto el mes en curso: es lo que un salón mira al cerrar
 * caja, y evita que el primer render traiga el histórico entero.
 */
let period = defaultPeriod();

function defaultPeriod() {

    const hoy = new Date();

    const iso = (fecha) => fecha.toISOString().slice(0, 10);

    return {
        desde: iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)),
        hasta: iso(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0))
    };
}

export function currentPeriod() {
    return { ...period };
}

export function initReportsEvents() {

    document.addEventListener("submit", handleSubmit);

    document.addEventListener("click", handleClick);

    registerCleanup(() => {

        document.removeEventListener("submit", handleSubmit);

        document.removeEventListener("click", handleClick);
    });
}

async function handleSubmit(event) {

    const form = event.target.closest("#reporte-periodo");

    if (!form) return;

    event.preventDefault();

    period = Object.fromEntries(new FormData(form).entries());

    await renderRoute();
}

function handleClick(event) {

    const preset = event.target.closest("[data-period]");

    if (!preset) return;

    period = periodFor(preset.dataset.period);

    renderRoute();
}

/**
 * Atajos de periodo.
 */
function periodFor(nombre) {

    const hoy = new Date();

    const iso = (fecha) => fecha.toISOString().slice(0, 10);

    if (nombre === "mes-anterior") {

        return {
            desde: iso(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1)),
            hasta: iso(new Date(hoy.getFullYear(), hoy.getMonth(), 0))
        };
    }

    if (nombre === "anio") {

        return {
            desde: iso(new Date(hoy.getFullYear(), 0, 1)),
            hasta: iso(new Date(hoy.getFullYear(), 11, 31))
        };
    }

    return defaultPeriod();
}
