import type { Directive } from 'vue';

/**
 * Directive `v-reveal` : révélation au scroll (portage de la classe
 * `.reveal` du prototype .knowledge/design/landing.html).
 *
 * L'élément reçoit la classe `front-reveal` puis `front-reveal--in` quand
 * il entre dans le viewport (IntersectionObserver, révélation unique).
 * Un délai de cascade peut être passé : `v-reveal="{ delay: 80 }"`
 * (exprimé en ms, alimente la variable `--front-reveal-delay`).
 *
 * Avec `prefers-reduced-motion: reduce`, le contenu est visible
 * immédiatement, sans observer ni transition.
 */
export interface RevealOptions {
    delay?: number;
}

const observers = new WeakMap<HTMLElement, IntersectionObserver>();

const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export const vReveal: Directive<HTMLElement, RevealOptions | undefined> = {
    mounted(el, binding) {
        el.classList.add('front-reveal');

        const delay = binding.value?.delay ?? 0;
        if (delay > 0) {
            el.style.setProperty('--front-reveal-delay', `${delay}ms`);
        }

        if (
            prefersReducedMotion() ||
            typeof IntersectionObserver === 'undefined'
        ) {
            el.classList.add('front-reveal--in');
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) {
                        continue;
                    }

                    el.classList.add('front-reveal--in');
                    observer.unobserve(el);
                }
            },
            { threshold: 0.15 },
        );

        observers.set(el, observer);
        observer.observe(el);
    },
    unmounted(el) {
        observers.get(el)?.disconnect();
        observers.delete(el);
    },
};
