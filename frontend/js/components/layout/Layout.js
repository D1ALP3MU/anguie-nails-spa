import { Navbar } from "./Navbar.js";
import { Footer } from "./Footer.js";

export function Layout(content) {
    
    return `

            ${Navbar()}

            <main>
                ${content}
            </main>

            ${Footer()}
            
    `;
}