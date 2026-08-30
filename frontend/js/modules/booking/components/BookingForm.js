export function BookingForm(serviceId, professionals) {

    return `

        <form class="booking-form" id="booking-form">

            <input
                type="hidden"
                name="serviceId"
                value="${serviceId}"
            >

            <div class="form-group">

                <label for="name">
                    Nombre
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Tu nombre"
                    required
                >

            </div>

            <div class="form-group">

                <label for="email">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="tu@email.com"
                    required
                >

            </div>

            <div class="form-group">

                <label for="phone">
                    Teléfono
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="Tu teléfono"
                    required
                >

            </div>

            <div class="form-group">

                <label for="professional">
                    Profesional
                </label>

                <select
                    id="professional"
                    name="professionalId"
                    required
                >

                    <option value="">
                        Selecciona un profesional
                    </option>

                    ${professionals.map(professional => `
                        <option value="${professional.id}">
                            ${professional.name}
                            ${professional.specialty
            ? ` - ${professional.specialty}`
            : ""
        }
                        </option>
                    `).join("")}

                </select>

            </div>

            <div class="form-group">

                <label for="date">
                    Fecha
                </label>

                <input
                    type="date"
                    id="date"
                    name="date"
                    required
                >

            </div>

            <div class="form-group">

                <label for="time">
                    Hora
                </label>

                <input
                    type="time"
                    id="time"
                    name="time"
                    required
                >

            </div>

            <div class="form-group">

                <label for="notes">
                    Notas
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    maxlength="1000"
                    placeholder="Información adicional (opcional)"
                ></textarea>

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Confirmar cita
            </button>

        </form>

    `;
}