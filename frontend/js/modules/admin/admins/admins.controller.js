import { Modal } from "../../../components/ui/Modal.js";
import { AdminForm } from "./components/AdminForm.js";
import { createAdmin, deleteAdmin } from "./admins.api.js";
import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";
import { toastSuccess, toastError } from "../../../components/ui/Toast.js";
import { confirmAction } from "../../../components/ui/ConfirmDialog.js";

export function initAdminsEvents() {

    document.addEventListener("click", handleDocumentClick);

    document.addEventListener("submit", handleSubmit);

    registerCleanup(() => {

        document.removeEventListener("click", handleDocumentClick);

        document.removeEventListener("submit", handleSubmit);
    });
}

function handleDocumentClick(event) {

    if (event.target.closest("[data-create-admin]")) {
        openModal("Nuevo administrador", AdminForm());
        return;
    }

    const deleteButton = event.target.closest("[data-delete-admin]");

    if (deleteButton) {
        handleDelete(Number(deleteButton.dataset.deleteAdmin));
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

    const form = event.target.closest("#admin-form");

    if (!form) return;

    event.preventDefault();

    const data = Object.fromEntries(
        new FormData(form).entries()
    );

    const button = form.querySelector("button[type='submit']");

    button.disabled = true;

    try {

        await createAdmin(data);

        closeModal();

        toastSuccess(
            "Administrador creado. Recuérdale que cambie la "
            + "contraseña desde «Mi cuenta»."
        );

        await renderRoute();

    } catch (error) {

        console.error("Error al crear el administrador:", error);

        // El backend explica si el correo ya está registrado.
        toastError(
            error.message || "No fue posible crear el administrador."
        );

        button.disabled = false;
    }
}

async function handleDelete(id) {

    const confirmed = await confirmAction({
        title: "Desactivar administrador",
        message: "Perderá el acceso al panel. Su cuenta y su "
            + "historial se conservan.",
        confirmText: "Desactivar",
        danger: true
    });

    if (!confirmed) return;

    try {

        await deleteAdmin(id);

        toastSuccess("Administrador desactivado correctamente.");

        await renderRoute();

    } catch (error) {

        console.error("Error al desactivar el administrador:", error);

        toastError(
            error.message || "No fue posible desactivar el administrador."
        );
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
