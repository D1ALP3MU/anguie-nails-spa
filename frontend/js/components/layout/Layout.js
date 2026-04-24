import { Navbar } from "./Navbar.js";

export function Layout(content) {
    return `
        <div class="app-layout">

            ${Navbar()}

            <main>
                ${content}
            </main>

            <footer>
                <p>&copy; 2025 Anguie Nails - Manicura & Pedicura. Todos los derechos reservados.</p>
            </footer>

        </div>    
    `;
}