import { Modal } from "../../../components/ui/Modal.js";
import { ClientForm } from "./components/ClientForm.js";
import { createClient } from "./clients.api.js";
import { registerCleanup } from "../../../core/cleanup.js";

export function initClientsEvents() {

    document.addEventListener(
        "click",
        handleDocumentClick
    );

    document.addEventListener(
        "submit",
        handleClientSubmit
    );

    registerCleanup(() => {

        document.removeEventListener(
            "click",
            handleDocumentClick
        );

        document.removeEventListener(
            "submit",
            handleClientSubmit
        );

    });
}

function handleDocumentClick(event) {

    const createButton = event.target.closest(
        "[data-create-client]"
    );

    if (createButton) {
        openCreateClientModal();
        return;
    }

    const closeButton = event.target.closest(
        "[data-close-modal]"
    );

    if (closeButton) {
        closeModal();
        return;
    }

    if (
        event.target.classList.contains(
            "modal-overlay"
        )
    ) {
        closeModal();
    }
}

async function handleClientSubmit(event) {

    const form = event.target;

    if (form.id !== "client-form") {
        return;
    }

    event.preventDefault();

    const formData = new FormData(form);

    const data = {
        nombre: formData.get("nombre"),
        email: formData.get("email"),
        password: formData.get("password"),
        telefono: formData.get("telefono"),
        direccion: formData.get("direccion")
    };

    try {

        const client = await createClient(data);

        console.log("CLIENT CREATED", client);

        alert("Cliente creado correctamente.");

        closeModal();

    } catch (error) {

        console.error(error);

        alert(error.message);

    }
}

function openCreateClientModal() {

    const existingModal = document.querySelector(
        ".modal-overlay"
    );

    if (existingModal) {
        return;
    }

    const modalHTML = Modal({
        title: "Nuevo cliente",
        content: ClientForm()
    });

    document.body.insertAdjacentHTML(
        "beforeend",
        modalHTML
    );
}

function closeModal() {

    const modal = document.querySelector(
        ".modal-overlay"
    );

    if (modal) {
        modal.remove();
    }
}