import { Modal } from "../../../components/ui/Modal.js";
import { ProfessionalForm } from "./components/ProfessionalForm.js";
import {
    createProfessional,
    fetchProfessional,
    updateProfessional,
    deleteProfessional
} from "./professionals.api.js";
import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";
import { toastSuccess, toastError } from "../../../components/ui/Toast.js";
import { confirmAction } from "../../../components/ui/ConfirmDialog.js";

export function initProfessionalsAdminEvents() {

    document.addEventListener("click", handleDocumentClick);

    document.addEventListener("submit", handleSubmit);

    registerCleanup(() => {

        document.removeEventListener("click", handleDocumentClick);

        document.removeEventListener("submit", handleSubmit);
    });
}

function handleDocumentClick(event) {

    if (event.target.closest("[data-create-professional]")) {
        openModal("Nueva profesional", ProfessionalForm());
        return;
    }

    const editButton = event.target.closest("[data-edit-professional]");

    if (editButton) {
        openEditModal(Number(editButton.dataset.editProfessional));
        return;
    }

    const deleteButton = event.target.closest("[data-delete-professional]");

    if (deleteButton) {
        handleDelete(Number(deleteButton.dataset.deleteProfessional));
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

    const form = event.target.closest("#professional-form");

    if (!form) return;

    event.preventDefault();

    const data = Object.fromEntries(
        new FormData(form).entries()
    );

    const id = form.dataset.professionalId;

    const button = form.querySelector("button[type='submit']");

    button.disabled = true;

    try {

        if (id) {

            await updateProfessional(Number(id), data);

            toastSuccess("Profesional actualizada correctamente.");

        } else {

            await createProfessional(data);

            toastSuccess("Profesional creada correctamente.");
        }

        closeModal();

        await renderRoute();

    } catch (error) {

        console.error("Error al guardar la profesional:", error);

        toastError(
            error.message || "No fue posible guardar la profesional."
        );

        button.disabled = false;
    }
}

async function handleDelete(id) {

    const confirmed = await confirmAction({
        title: "Dar de baja",
        message: "Dejará de aparecer en el catálogo y no podrá recibir "
            + "nuevas citas. Su historial se conserva.",
        confirmText: "Dar de baja",
        danger: true
    });

    if (!confirmed) return;

    try {

        await deleteProfessional(id);

        toastSuccess("Profesional dada de baja correctamente.");

        await renderRoute();

    } catch (error) {

        console.error("Error al dar de baja a la profesional:", error);

        // El backend explica si tiene citas pendientes.
        toastError(
            error.message || "No fue posible dar de baja a la profesional."
        );
    }
}

async function openEditModal(id) {

    if (document.querySelector(".modal-overlay")) return;

    try {

        const professional = await fetchProfessional(id);

        openModal("Editar profesional", ProfessionalForm(professional));

    } catch (error) {

        console.error("Error al obtener la profesional:", error);

        toastError("No fue posible cargar la profesional.");
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
