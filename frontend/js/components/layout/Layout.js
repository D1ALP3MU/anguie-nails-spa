import { Navbar } from "./Navbar.js";

export function Layout(content) {
    
    return `

            ${Navbar()}

            <main>
                ${content}
            </main>

            <footer>
                <p>&copy; 2025 Anguie Nails - Manicura & Pedicura. Todos los derechos reservados.</p>
            </footer>
            
    `;
}