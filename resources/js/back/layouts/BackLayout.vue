<script setup lang="ts">
// Coquille back-office : sidebar sombre + en-tete avec menu utilisateur.
// Aucun import hors vue / @inertiajs / types / back.scss : les arbres
// front/ et back/ restent etanches.
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { AppSharedProps } from '@/types';
import { dashboard } from '@/routes/admin';
import { index as landingIndex } from '@/routes/admin/landing';
import { index as modulesIndex } from '@/routes/admin/modules';
import { edit as profileEdit } from '@/routes/admin/profile';
import { logout } from '@/routes';
import '../../../css/back.scss';

const page = usePage<AppSharedProps>();
const user = computed(() => page.props.auth.user);

const roleLabels: Record<string, string> = {
    admin: 'Administrateur',
    editor: 'Éditeur',
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

/* Navigation principale de la section « Général ». L'entrée Modules reste
   reservee aux administrateurs. */
const mainLinks = computed(() => {
    const links = [
        { label: 'Tableau de bord', href: dashboard.url(), base: '/admin' },
    ];

    if (user.value?.role === 'admin') {
        links.push({
            label: 'Modules',
            href: modulesIndex.url(),
            base: '/admin/modules',
        });
    }

    return links;
});

/* Catalogue des modules metier, dans l'ordre du registre. Un module
   active expose un vrai lien vers son ecran d'administration ; les
   autres restent grises avec un repere « a venir ». */
const moduleCatalog = [
    { slug: 'landing', label: 'Landing' },
    { slug: 'pricing', label: 'Pricing' },
    { slug: 'gateway', label: 'Gateway' },
    { slug: 'connectors', label: 'Connectors' },
    { slug: 'webhooks', label: 'Webhooks' },
    { slug: 'monitoring', label: 'Monitoring' },
];

const landingEnabled = computed(
    () =>
        page.props.modules.find((module) => module.slug === 'landing')
            ?.enabled ?? false,
);

const landingIndexUrl = landingIndex.url();

const isActive = (base: string): boolean =>
    base === '/admin' ? page.url === '/admin' : page.url.startsWith(base);

const sectionTitle = computed(() => {
    if (page.url.startsWith('/admin/landing')) {
        return 'Landing';
    }

    if (page.url.startsWith('/admin/modules')) {
        return 'Modules';
    }

    if (page.url.startsWith('/admin/profile')) {
        return 'Profil';
    }

    return 'Tableau de bord';
});

/* Menu utilisateur : fermeture au clic exterieur, Echap ou navigation. */
const menuOpen = ref(false);
const menuRoot = ref<HTMLElement | null>(null);
const loggingOut = ref(false);

function onDocumentClick(event: MouseEvent): void {
    if (
        menuOpen.value &&
        menuRoot.value &&
        !menuRoot.value.contains(event.target as Node)
    ) {
        menuOpen.value = false;
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        menuOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});

watch(
    () => page.url,
    () => {
        menuOpen.value = false;
    },
);

function onLogout(): void {
    if (loggingOut.value) {
        return;
    }

    loggingOut.value = true;
    router.post(
        logout.url(),
        {},
        {
            onFinish: () => {
                loggingOut.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="flex min-h-screen">
        <aside class="flex w-60 shrink-0 flex-col bg-[var(--color-ink)]">
            <div class="px-6 pt-6 pb-4">
                <p class="text-sm font-semibold tracking-wide text-white">
                    DevToolbox
                </p>
                <p class="mt-0.5 text-xs text-white/40">Administration</p>
            </div>

            <nav
                class="flex flex-1 flex-col gap-6 overflow-y-auto px-3 py-2"
                aria-label="Navigation principale"
            >
                <div class="flex flex-col gap-1">
                    <p class="back-nav-label">Général</p>
                    <Link
                        v-for="link in mainLinks"
                        :key="link.href"
                        :href="link.href"
                        class="back-nav-item"
                        :aria-current="isActive(link.base) ? 'page' : undefined"
                    >
                        {{ link.label }}
                    </Link>
                </div>

                <div class="flex flex-col gap-1">
                    <p class="back-nav-label">Modules</p>
                    <template
                        v-for="module in moduleCatalog"
                        :key="module.slug"
                    >
                        <Link
                            v-if="module.slug === 'landing' && landingEnabled"
                            :href="landingIndexUrl"
                            class="back-nav-item"
                            :aria-current="
                                isActive('/admin/landing') ? 'page' : undefined
                            "
                        >
                            {{ module.label }}
                        </Link>
                        <span
                            v-else
                            class="back-nav-item back-nav-item--soon flex items-center justify-between"
                            aria-disabled="true"
                        >
                            {{ module.label }}
                            <span class="back-soon-chip">À venir</span>
                        </span>
                    </template>
                </div>
            </nav>

            <p class="px-6 py-4 text-xs text-white/40">
                Back-office DevToolbox
            </p>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header
                class="flex h-14 shrink-0 items-center justify-between border-b border-[rgba(23,23,28,0.09)] bg-[var(--color-surface)] px-6"
            >
                <p
                    class="text-sm font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    {{ sectionTitle }}
                </p>

                <div v-if="user" ref="menuRoot" class="relative">
                    <button
                        type="button"
                        class="back-menu-trigger"
                        aria-haspopup="menu"
                        :aria-expanded="menuOpen"
                        @click="menuOpen = !menuOpen"
                    >
                        <span
                            class="flex size-7 items-center justify-center rounded-full bg-[var(--color-accent)] text-xs font-semibold text-white"
                            aria-hidden="true"
                        >
                            {{ initials(user.name) }}
                        </span>
                        <span class="flex flex-col items-start leading-tight">
                            <span class="text-sm text-[var(--color-ink)]">{{
                                user.name
                            }}</span>
                            <span class="text-xs text-[var(--color-muted)]">{{
                                roleLabels[user.role]
                            }}</span>
                        </span>
                    </button>

                    <div
                        v-if="menuOpen"
                        class="absolute right-0 z-10 mt-2 w-60 rounded-lg border border-[rgba(23,23,28,0.09)] bg-[var(--color-surface)] p-1.5 shadow-lg"
                        role="menu"
                        aria-label="Menu utilisateur"
                    >
                        <div class="px-3 py-2">
                            <p
                                class="truncate text-sm font-medium text-[var(--color-ink)]"
                            >
                                {{ user.name }}
                            </p>
                            <p
                                class="truncate text-xs text-[var(--color-muted)]"
                            >
                                {{ user.email }}
                            </p>
                            <span class="back-badge back-badge--muted mt-2">{{
                                roleLabels[user.role]
                            }}</span>
                        </div>

                        <div
                            class="my-1 border-t border-[rgba(23,23,28,0.09)]"
                        />

                        <Link
                            :href="profileEdit.url()"
                            class="back-menu-item"
                            role="menuitem"
                        >
                            Mon profil
                        </Link>

                        <button
                            type="button"
                            class="back-menu-item back-menu-item--danger"
                            role="menuitem"
                            :disabled="loggingOut"
                            @click="onLogout"
                        >
                            <span
                                v-if="loggingOut"
                                class="back-spinner"
                                aria-hidden="true"
                            />
                            Se déconnecter
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-6 py-8">
                <div class="mx-auto w-full max-w-5xl">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
