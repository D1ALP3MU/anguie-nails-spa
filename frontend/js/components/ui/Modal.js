export function Modal ({title, content,}) {
    console.log("Modal:", title)
    return `
        <div class="modal-overlay">
            <div class="modal" role="dialog" aria-modal="true">
                <div class="modal__header">
                    <h2>${title}</h2>
                    <button class="modal__close" data-close-modal aria-label="Cerrar modal">
                        x
                    </button>
                </div>
                <div class="modal__body">
                    ${content}
                </div>
            </div>
        </div>
    `;

}