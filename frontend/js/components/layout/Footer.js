import { escapeHtml } from "../../utils/html.js";
import { salon } from "../../config/salon.js";
import { getSalonSchedule } from "../../state/salonSchedule.js";

/*
 * Pie de página.
 *
 * Dos versiones: el pie completo —marca, horario, dirección y
 * contacto— en las páginas de cara a las clientas, y una línea
 * mínima en el panel de administración, donde se trabaja y esa
 * información no aporta.
 */

const DAY_NAMES = ["", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"];

const capitalize = text => text.charAt(0).toUpperCase() + text.slice(1);

/**
 * «Lunes a sábado» o «Lunes, miércoles y viernes».
 * @param {number[]} days 1 = lunes … 7 = domingo, ordenados.
 */
function describeDays(days) {

    const consecutive = days.every((d, i) => i === 0 || d === days[i - 1] + 1);

    if (consecutive && days.length > 2) {
        return capitalize(`${DAY_NAMES[days[0]]} a ${DAY_NAMES[days.at(-1)]}`);
    }

    const names = days.map(d => DAY_NAMES[d]);
    const last = names.pop();

    return capitalize(names.length ? `${names.join(", ")} y ${last}` : last);
}

/** «09:00» → «9:00». */
const hour = time => time.replace(/^0(?=\d:)/, "");

function ScheduleBlock() {

    const schedule = getSalonSchedule();

    if (!schedule) return "";

    const open = [...schedule.dias].sort((a, b) => a - b);
    const closed = [1, 2, 3, 4, 5, 6, 7].filter(d => !open.includes(d));

    return `
        <div class="site-footer__col">
            <h2 class="site-footer__title">Horario</h2>
            <dl class="site-footer__hours">
                <dt>${escapeHtml(describeDays(open))}</dt>
                <dd>${escapeHtml(hour(schedule.apertura))} – ${escapeHtml(hour(schedule.cierre))}</dd>
                ${closed.length
                    ? `<dt>${escapeHtml(describeDays(closed))}</dt><dd>Cerrado</dd>`
                    : ""}
            </dl>
        </div>
    `;
}

const ICONS = {
    instagram: `
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <rect x="3" y="3" width="18" height="18" rx="5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.5" cy="6.5" r=".6"/>
        </svg>`,
    whatsapp: `
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z"/>
            <path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1a3.5 3.5 0 0 1-2-2l1-1-1-2z"/>
        </svg>`
};

/** Enlaces externos: pestaña nueva y sin dar acceso a esta página. */
const external = `target="_blank" rel="noopener noreferrer"`;

function FullFooter(year) {

    return `
        <footer class="site-footer">

            <div class="site-footer__grid">

                <div class="site-footer__col">
                    <p class="site-footer__brand">${escapeHtml(salon.name)}</p>
                    <p class="site-footer__tagline">${escapeHtml(salon.tagline)}</p>

                    <div class="site-footer__social">
                        <a href="${escapeHtml(salon.instagramUrl)}" ${external}
                            aria-label="Instagram de ${escapeHtml(salon.name)} (${escapeHtml(salon.instagramHandle)})">
                            ${ICONS.instagram}
                        </a>
                        <a href="${escapeHtml(salon.whatsappUrl)}" ${external}
                            aria-label="Escribir por WhatsApp">
                            ${ICONS.whatsapp}
                        </a>
                    </div>
                </div>

                ${ScheduleBlock()}

                <div class="site-footer__col">
                    <h2 class="site-footer__title">Visítanos</h2>
                    <address class="site-footer__address">
                        ${escapeHtml(salon.address)}<br>
                        ${escapeHtml(salon.city)}
                    </address>
                    <a class="site-footer__link" href="${escapeHtml(salon.mapsUrl)}" ${external}>
                        Cómo llegar <span aria-hidden="true">→</span>
                    </a>
                    <a class="site-footer__whatsapp" href="${escapeHtml(salon.whatsappUrl)}" ${external}>
                        ${ICONS.whatsapp}
                        <span>Escríbenos · ${escapeHtml(salon.whatsappDisplay)}</span>
                    </a>
                </div>

            </div>

            <div class="site-footer__bottom">
                <span>&copy; ${year} ${escapeHtml(salon.name)}</span>
                <a href="${escapeHtml(salon.instagramUrl)}" ${external}>${escapeHtml(salon.instagramHandle)}</a>
            </div>

        </footer>
    `;
}

/**
 * @param {"full"|"minimal"} variant
 */
export function Footer(variant = "full") {

    // El año sale del reloj: escrito a mano se quedó en 2025.
    const year = new Date().getFullYear();

    if (variant === "minimal") {
        return `
            <footer class="site-footer site-footer--minimal">
                <span>&copy; ${year} ${escapeHtml(salon.name)}</span>
            </footer>
        `;
    }

    return FullFooter(year);
}

/**
 * Franja de llamada a la acción, sobre el pie, en las páginas
 * públicas que no son ya el catálogo.
 */
export function CtaBand() {

    return `
        <section class="cta-band" aria-labelledby="cta-band-title">
            <div>
                <h2 id="cta-band-title">¿Lista para consentirte?</h2>
                <p>Elige tu servicio y la hora que mejor te quede.</p>
            </div>
            <a href="#/services" class="btn btn-primary">Reservar cita</a>
        </section>
    `;
}
