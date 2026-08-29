import { Modal } from "../../../components/ui/Modal.js";
import { ClientForm } from "./components/ClientForm.js";
import {
    createClient,
    fetchClient,
    updateClient
} from "./clients.api.js";
import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";

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

    const editButton = event.target.closest(
        "[data-edit-client]"
    );

    if (editButton) {
        openEditClientModal(
            Number(editButton.dataset.editClient)
        );
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

    const form = event.target.closest(
        "#client-form"
    );

    if (!form) {
        return;
    }

    event.preventDefault();

    const formData = new FormData(form);

    const data = Object.fromEntries(
        formData.entries()
    );

    const clientId = form.dataset.clientId;

    try {

        if (clientId) {

            await updateClient(
                Number(clientId),
                data
            );

            alert("Cliente actualizado correctamente.");

        } else {

            await createClient(data);

            alert("Cliente creado correctamente.");
        }

        closeModal();

        await renderRoute();

    } catch (error) {

        console.error(
            "Error al guardar cliente:",
            error
        );

        alert(
            "No fue posible guardar el cliente."
        );
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

async function openEditClientModal(id) {

    const existingModal = document.querySelector(
        ".modal-overlay"
    );

    if (existingModal) {
        return;
    }

    try {

        const client = await fetchClient(id);

        const modalHTML = Modal({
            title: "Editar cliente",
            content: ClientForm(client)
        });

        document.body.insertAdjacentHTML(
            "beforeend",
            modalHTML
        );

    } catch (error) {

        console.error(
            "Error al obtener cliente:",
            error
        );

        alert(
            "No fue posible cargar el cliente."
        );
    }
}

function closeModal() {

    const modal = document.querySelector(
        ".modal-overlay"
    );

    if (modal) {
        modal.remove();
    }
}
