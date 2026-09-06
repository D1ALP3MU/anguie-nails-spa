import { Modal } from "../../../components/ui/Modal.js";
import { ServiceForm } from "./components/ServiceForm.js";
import {
    createService,
    fetchService,
    updateService,
    deleteService
} from "./services.api.js";
import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";
import { toastSuccess, toastError } from "../../../components/ui/Toast.js";
import { confirmAction } from "../../../components/ui/ConfirmDialog.js";

export function initServicesAdminEvents() {

    document.addEventListener("click", handleDocumentClick);

    document.addEventListener("submit", handleSubmit);

    registerCleanup(() => {

        document.removeEventListener("click", handleDocumentClick);

        document.removeEventListener("submit", handleSubmit);
    });
}

function handleDocumentClick(event) {

    if (event.target.closest("[data-create-service]")) {
        openModal("Nuevo servicio", ServiceForm());
        return;
    }

    const editButton = event.target.closest("[data-edit-service]");

    if (editButton) {
        openEditModal(Number(editButton.dataset.editService));
        return;
    }

    const deleteButton = event.target.closest("[data-delete-service]");

    if (deleteButton) {
        handleDelete(Number(deleteButton.dataset.deleteService));
        return;
    }

    if (event.target.closest("[data-close-modal]")) {
        closeModal();
        return;
    }

    if (event.target.classList.contains("modal-overlay")) {
        closeModal();
    }
}

async function handleSubmit(event) {

    const form = event.target.closest("#service-form");

    if (!form) return;

    event.preventDefault();

    const data = Object.fromEntries(
        new FormData(form).entries()
    );

    // El backend espera números, no las cadenas que devuelve FormData.
    data.duracion = Number(data.duracion);
    data.precio = Number(data.precio);

    const id = form.dataset.serviceId;

    const button = form.querySelector("button[type='submit']");

    button.disabled = true;

    try {

        if (id) {

            await updateService(Number(id), data);

            toastSuccess("Servicio actualizado correctamente.");

        } else {

            await createService(data);

            toastSuccess("Servicio creado correctamente.");
        }

        closeModal();

        await renderRoute();

    } catch (error) {

        console.error("Error al guardar el servicio:", error);

        toastError(
            error.message || "No fue posible guardar el servicio."
        );

        button.disabled = false;
    }
}

async function handleDelete(id) {

    const confirmed = await confirmAction({
        title: "Retirar del catálogo",
        message: "Dejará de ofrecerse y no podrá reservarse. "
            + "Las citas que ya lo usan se conservan.",
        confirmText: "Retirar",
        danger: true
    });

    if (!confirmed) return;

    try {

        await deleteService(id);

        toastSuccess("Servicio retirado del catálogo.");

        await renderRoute();

    } catch (error) {

        console.error("Error al retirar el servicio:", error);

        toastError(
            error.message || "No fue posible retirar el servicio."
        );
    }
}

async function openEditModal(id) {

    if (document.querySelector(".modal-overlay")) return;

    try {

        const service = await fetchService(id);

        openModal("Editar servicio", ServiceForm(service));

    } catch (error) {

        console.error("Error al obtener el servicio:", error);

        toastError("No fue posible cargar el servicio.");
    }
}

function openModal(title, content) {

    if (document.querySelector(".modal-overlay")) return;

    document.body.insertAdjacentHTML(
        "beforeend",
        Modal({ title, content })
    );
}

function closeModal() {

    document.querySelector(".modal-overlay")?.remove();
}
