import { escapeHtml } from "../../../../utils/html.js";

export function ProfessionalForm(professional = null) {

    const isEdit = professional !== null;

    const name = escapeHtml(professional?.name ?? "");
    const specialty = escapeHtml(professional?.specialty ?? "");
    const phone = escapeHtml(professional?.phone ?? "");

    return `
        <form
            class="client-form"
            id="professional-form"
            ${isEdit ? `data-professional-id="${escapeHtml(professional.id)}"` : ""}
        >

            <div class="form-group">
                <label for="professional-name">Nombre</label>
                <input
                    type="text"
                    id="professional-name"
                    name="nombre"
                    placeholder="Nombre completo"
                    value="${name}"
                    minlength="3"
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="professional-specialty">Especialidad</label>
                <input
                    type="text"
                    id="professional-specialty"
                    name="especialidad"
                    placeholder="Manicura, pedicura, nail art..."
                    value="${specialty}"
                    maxlength="100"
                >
            </div>

            <div class="form-group">
                <label for="professional-phone">Teléfono</label>
                <input
                    type="tel"
                    id="professional-phone"
                    name="telefono"
                    placeholder="Número de contacto"
                    value="${phone}"
                    maxlength="20"
                >
            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                ${isEdit ? "Actualizar profesional" : "Crear profesional"}
            </button>

        </form>
    `;
}
