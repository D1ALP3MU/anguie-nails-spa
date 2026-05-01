import { SkeletonCard } from "../../../components/ui/SkeletonCard.js";

export function ServicesSkeleton() {
    
    return `

        <section class="services-page">
            <div class="services-card__header">
                <div class="skeleton skeleton__title"></div>
                <div class="skeleton skeleton__text"></div>
            </div>

            <div class="services-grid">
                ${Array(6).fill(null).map(() => SkeletonCard()).join("")}
            </div>
        </section>
    `;

}