export function ErrorState(message = "Ocurrión un error") {

    return `

        <section class="error-state">

            <h2>Algo salió mal</h2>

            <p>${message}</p>

        </section>
    
    `;
}