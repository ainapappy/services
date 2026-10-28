import type { Directive } from 'vue';

/**
 * Directive `v-count` : compteur animé au scroll (portage des compteurs
 * `[data-count]` du prototype .knowledge/design/landing.html).
 *
 * `v-count="{ target: 99.98, dec: 2, suffix: ' %' }"` anime la valeur de 0
 * vers `target` avec un easing ease-out cubic dès que l'élément est visible
 * (IntersectionObserver, seuil 0.5). Le rendu utilise `Intl.NumberFormat`
 * en français ; le texte final intègre préfixe et suffixe.
 *
 * Avec `prefers-reduced-motion: reduce`, la valeur finale est affichée
 * immédiatement, sans animation.
 */
export interface CountOptions {
    target: number;
    /** Nombre de décimales affichées (défaut 0). */
    dec?: number;
    prefix?: string;
    suffix?: string;
    /** Durée de l'animation en ms (défaut 1600). */
    duration?: number;
}

const observers = new WeakMap<HTMLElement, IntersectionObserver>();

const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** Ease-out cubic, identique au prototype. */
const easeOutCubic = (t: number): number => 1 - (1 - t) ** 3;

const format = (value: number, dec: number): string =>
    value.toLocaleString('fr-FR', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec,
    });

export const vCount: Directive<HTMLElement, CountOptions> = {
    mounted(el, binding) {
        const { target, dec = 0, prefix = '', suffix = '' } = binding.value;
        const duration = binding.value.duration ?? 1600;

        const render = (value: number): void => {
            el.textContent = `${prefix}${format(value, dec)}${suffix}`;
        };

        if (
            prefersReducedMotion() ||
            typeof IntersectionObserver === 'undefined'
        ) {
            render(target);
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }

                observer.unobserve(el);

                const start = performance.now();
                const tick = (now: number): void => {
                    const progress = Math.min(1, (now - start) / duration);
                    render(target * easeOutCubic(progress));

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };
                requestAnimationFrame(tick);
            },
            { threshold: 0.5 },
        );

        observers.set(el, observer);
        observer.observe(el);
    },
    unmounted(el) {
        observers.get(el)?.disconnect();
        observers.delete(el);
    },
};
