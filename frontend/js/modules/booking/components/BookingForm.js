export function BookingForm(serviceId) {

    return `

        <form class="booking-form" id="booking-form">
            <input type="hidden" name="serviceId" value="${serviceId}">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" placeholder="Tu nombre" required>
            </div>
            <div class="form-group">
                <label>Fecha</label>
                <input type="date" name="date" required>
            </div>
            <button type="submit" class="btn btn-primary">
                Confirmar Reserva
            </button>
        </form>
    
    `;
}