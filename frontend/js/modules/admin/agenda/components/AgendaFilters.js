import { escapeHtml } from "../../../../utils/html.js";

const ESTADOS = ["pendiente", "confirmada", "completada", "cancelada"];

export function AgendaFilters(professionals, filters = {}) {

    const selected = (value, current) =>
        String(value) === String(current) ? "selected" : "";

    return `
        <form class="agenda-filters" id="agenda-filters">

            <div class="form-group">
                <label for="filtro-desde">Desde</label>
                <input
                    type="date"
                    id="filtro-desde"
                    name="desde"
                    value="${escapeHtml(filters.desde ?? "")}"
                >
            </div>

            <div class="form-group">
                <label for="filtro-hasta">Hasta</label>
                <input
                    type="date"
                    id="filtro-hasta"
                    name="hasta"
                    value="${escapeHtml(filters.hasta ?? "")}"
                >
            </div>

            <div class="form-group">
                <label for="filtro-profesional">Profesional</label>
                <select id="filtro-profesional" name="id_profesional">
                    <option value="">Todas</option>
                    ${professionals.map(p => `
                        <option
                            value="${escapeHtml(p.id)}"
                            ${selected(p.id, filters.id_profesional)}
                        >${escapeHtml(p.name)}</option>
                    `).join("")}
                </select>
            </div>

            <div class="form-group">
                <label for="filtro-estado">Estado</label>
                <select id="filtro-estado" name="estado">
                    <option value="">Todos</option>
                    ${ESTADOS.map(e => `
                        <option
                            value="${e}"
                            ${selected(e, filters.estado)}
                        >${e.charAt(0).toUpperCase() + e.slice(1)}</option>
                    `).join("")}
                </select>
            </div>

            <div class="agenda-filters__actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <button type="button" class="btn btn-outline" data-clear-filters>
                    Limpiar
                </button>
            </div>

        </form>
    `;
}
