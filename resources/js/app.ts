import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Résolution Inertia par namespace : les pages du starter vivent sous
// resources/js/pages, la vitrine sous resources/js/front/pages et
// l'administration sous resources/js/back/pages (arbres étanches).
const pages = import.meta.glob<{ default: DefineComponent }>([
    './pages/**/*.vue',
    './front/pages/**/*.vue',
    './back/pages/**/*.vue',
]);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const candidates = [
            `./pages/${name}.vue`,
            `./front/pages/${name}.vue`,
            `./back/pages/${name}.vue`,
        ];

        for (const path of candidates) {
            const load = pages[path];

            if (!load) {
                continue;
            }

            return (await load()).default;
        }

        throw new Error(`Page not found: ${name}`);
    },
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('front/') || name.startsWith('back/'):
                // Les pages front/ et back/ portent leur layout elles-mêmes.
                return null;
            default:
                return AppLayout;
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
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
