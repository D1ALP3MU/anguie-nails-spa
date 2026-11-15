export function Footer() {

    // El año sale del reloj: escrito a mano se quedó en 2025.
    const year = new Date().getFullYear();

    return `

        <footer>
                <p>&copy; ${year} Anguie Nails · Manicura y pedicura. Todos los derechos reservados.</p>
        </footer>

    `;
}
