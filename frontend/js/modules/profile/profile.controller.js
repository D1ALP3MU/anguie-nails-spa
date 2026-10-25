import { changePassword } from "./profile.api.js";
import { registerCleanup } from "../../core/cleanup.js";
import { toastSuccess, toastError } from "../../components/ui/Toast.js";

export function initProfileEvents() {

    document.addEventListener("submit", handlePasswordSubmit);

    registerCleanup(() => {

        document.removeEventListener("submit", handlePasswordSubmit);

    });
}

async function handlePasswordSubmit(event) {

    const form = event.target.closest("#password-form");

    if (!form) return;

    event.preventDefault();

    const formData = new FormData(form);

    const button = form.querySelector("button[type='submit']");

    button.disabled = true;
    button.textContent = "Guardando...";

    try {

        await changePassword({
            password_actual: formData.get("password_actual"),
            password_nueva: formData.get("password_nueva"),
            password_confirmacion: formData.get("password_confirmacion")
        });

        /*
         * El formulario se vacía al terminar: dejar las contraseñas
         * escritas en pantalla no aporta nada y queda a la vista de
         * quien pase por detrás.
         */
        form.reset();

        toastSuccess("Contraseña actualizada.");

    } catch (error) {

        console.error("Error al cambiar la contraseña:", error);

        toastError(
            error.message ||
            "No fue posible cambiar la contraseña."
        );

    } finally {

        button.disabled = false;
        button.textContent = "Cambiar contraseña";

    }
}
