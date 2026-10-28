import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Résolution Inertia par namespace : les pages du starter vivent sous
// resources/js/pages, la vitrine sous resources/js/front/pages et
// l'administration sous resources/js/back/pages (arbres étanches).
// La regle front|back est la meme que $pageEntry dans app.blade.php :
// le prefixe du composant designe l'arbre, pas un sous-dossier de pages.
const pages = import.meta.glob<{ default: DefineComponent }>([
    './pages/**/*.vue',
    './front/pages/**/*.vue',
    './back/pages/**/*.vue',
]);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const path = /^(front|back)\//.test(name)
            ? `./${name.replace(/^([^/]+)\//, '$1/pages/')}.vue`
            : `./pages/${name}.vue`;

        const load = pages[path];

        if (!load) {
            throw new Error(`Page not found: ${name}`);
        }

        return (await load()).default;
    },
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                // Écrans Fortify hors périmètre (2FA, confirmation de mot de
                // passe) : conservent la charte du starter.
                return AuthLayout;
            case name.startsWith('front/') || name.startsWith('back/'):
                // Les pages front/ et back/ portent leur layout elles-mêmes.
                return null;
            default:
                return null;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#6366f1',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
