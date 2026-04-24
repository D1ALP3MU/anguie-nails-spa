import { formatCOP } from "../../../utils/currency.js";

export function ServiceCard(service) {

    return `

    <article class="service-card">
    
        <div class="service-card__content">

            <h3>${service.title}</h3>
            <p>${service.description}</p>

            <div class="service-card__meta">
                <span>${service.duration}</span>
                <strong>${formatCOP(service.price)}</strong>
            </div>

            <button class="btn btn-primary" 
                    data-book-service="${service.id}"
                    aria-label="Reservar servicio ${service.title}"
                >
                Reservar
            </button>
        
        </div>
    
    </article>
    
    `;
}