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
import { ClientResults, filterClients, countText } from "./components/ClientResults.js";

/**
 * Texto buscado. Vive en el módulo, como los filtros de la agenda:
 * se conserva al abrir el historial de una clienta y volver, pero no
 * tras recargar la página.
 */
let query = "";

/** Clientas del último render, sobre las que se filtra. */
let loaded = [];

export function currentQuery() {
    return query;
}

export function initClientsEvents(clients) {

    loaded = clients;

    document.addEventListener(
        "click",
        handleDocumentClick
    );

    document.addEventListener(
        "submit",
        handleClientSubmit
    );

    document.addEventListener(
        "input",
        handleSearchInput
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

        document.removeEventListener(
            "input",
            handleSearchInput
        );

    });
}

/*
|--------------------------------------------------------------------------
| Búsqueda
|--------------------------------------------------------------------------
| Se filtra en el navegador: la API ya entrega todas las clientas, así
| que cada tecla da el resultado al instante y sin pedir nada.
*/

function handleSearchInput(event) {

    if (event.target.id !== "clients-search") return;

    query = event.target.value;

    paintResults();
}

/**
 * Repinta la tabla y el contador, pero no la caja de búsqueda: así
 * no se pierde el foco ni la posición del cursor mientras se escribe.
 */
function paintResults() {

    const results = document.getElementById("clients-results");
    const count = document.getElementById("clients-count");

    if (results) {
        results.innerHTML = ClientResults(loaded, query);
    }

    if (count) {
        count.textContent = countText(
            filterClients(loaded, query).length,
            loaded.length,
            query
        );
    }
}

function clearSearch() {

    query = "";

    const input = document.getElementById("clients-search");

    if (input) {
        input.value = "";
        input.focus();
    }

    paintResults();
}

function handleDocumentClick(event) {

    if (event.target.closest("[data-clear-search]")) {
        clearSearch();
        return;
    }

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
