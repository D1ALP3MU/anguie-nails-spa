import { openModal, closeModal, isModalOpen } from "../../../components/ui/Modal.js";
import { ClientForm } from "./components/ClientForm.js";
import {
    createClient,
    fetchClient,
    updateClient,
    deleteClient
} from "./clients.api.js";
import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";
import { toastSuccess, toastError } from "../../../components/ui/Toast.js";
import { confirmAction } from "../../../components/ui/ConfirmDialog.js";

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

    const deleteButton = event.target.closest(
        "[data-delete-client]"
    );

    if (deleteButton) {
        handleClientDelete(
            Number(deleteButton.dataset.deleteClient)
        );
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

            toastSuccess("Clienta actualizada correctamente.");

        } else {

            await createClient(data);

            toastSuccess("Clienta creada correctamente.");
        }

        closeModal();

        await renderRoute();

    } catch (error) {

        console.error(
            "Error al guardar cliente:",
            error
        );

        toastError(
            error.message ||
            "No fue posible guardar la clienta."
        );
    }
}

async function handleClientDelete(id) {

    const confirmed = await confirmAction({
        title: "Eliminar clienta",
        message: "La clienta dejará de aparecer en el listado. Sus citas se conservan.",
        confirmText: "Eliminar",
        danger: true
    });

    if (!confirmed) {
        return;
    }

    try {

        await deleteClient(id);

        toastSuccess("Clienta eliminada correctamente.");

        await renderRoute();

    } catch (error) {

        console.error(
            "Error al eliminar cliente:",
            error
        );

        toastError(
            error.message ||
            "No fue posible eliminar la clienta."
        );
    }
}

function openCreateClientModal() {

    openModal("Nueva clienta", ClientForm());
}

async function openEditClientModal(id) {

    if (isModalOpen()) {
        return;
    }

    try {

        const client = await fetchClient(id);

        openModal("Editar clienta", ClientForm(client));

    } catch (error) {

        console.error(
            "Error al obtener cliente:",
            error
        );

        toastError(
            error.message ||
            "No fue posible cargar la clienta."
        );
    }
}
