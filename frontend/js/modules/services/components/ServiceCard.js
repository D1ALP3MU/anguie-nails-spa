import { formatCOP } from "../../../utils/currency.js";
import { escapeHtml } from "../../../utils/html.js";

export function ServiceCard(service) {

    const title = escapeHtml(service.title);

    return `

    <article class="service-card">
    
        <div class="service-card__content">

            <h3>${title}</h3>
            <p>${escapeHtml(service.description)}</p>

            <div class="service-card__meta">
                <span>${escapeHtml(service.duration)}</span>
                <strong>${formatCOP(service.price)}</strong>
            </div>

            ${/*
                Enlace y no botón: la reserva tiene página propia, y
                si hace falta iniciar sesión, al volver se llega aquí.
              */ ""}
            <a
                class="btn btn-primary"
                href="#/reservar/${encodeURIComponent(service.id)}"
                aria-label="Reservar ${title}"
            >
                Reservar
            </a>
        
        </div>
    
    </article>
    
    `;
}
