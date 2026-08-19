export function ClientTable(clients) {

    if (!clients.length) {
        return `
            <div class="clients-empty">
                <p>No hay clientes registrados.</p>
            </div>
        `;
    }

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                    </tr>
                </thead>

                <tbody>

                    ${clients.map(client => `
                        <tr>
                            <td>${client.name}</td>
                            <td>${client.email}</td>
                            <td>${client.phone}</td>
                            <td>${client.address ?? "Sin dirección"}</td>
                        </tr>
                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}