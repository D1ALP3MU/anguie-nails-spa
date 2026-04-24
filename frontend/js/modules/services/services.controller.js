export function initServicesEvents() {

    document.addEventListener("click", (event) => {

        const button = event.target.closest("[data-book-service]");

        if (!button) return;

        const serviceId = button.dataset.bookService;

        console.log("Reservar servicio:", serviceId);
        
    });
    
}