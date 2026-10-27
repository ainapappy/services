<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { FrontHomePageProps } from '@/types';
import FrontLayout from '../layouts/FrontLayout.vue';

defineProps<FrontHomePageProps>();

const pillars = [
    {
        title: 'Modulaire',
        description:
            'Six domaines indépendants — landing, pricing, gateway, connectors, webhooks, monitoring — activés depuis l’administration.',
    },
    {
        title: 'Deux chartes',
        description:
            'Front-office public et back-office admin vivent sur des templates, des assets et des chartes strictement séparés.',
    },
    {
        title: 'Prêt à étendre',
        description:
            'Un socle typé de bout en bout, des routes générées par Wayfinder et des tokens design partagés.',
    },
];
</script>

<template>
    <FrontLayout>
        <Head title="DevToolbox" />

        <section
            class="mx-auto w-full max-w-5xl px-6 py-24 text-center sm:py-32"
        >
            <h1
                class="mx-auto max-w-3xl text-4xl font-semibold tracking-tight text-[var(--color-ink)] sm:text-5xl"
            >
                La boîte à outils de vos services en ligne
            </h1>
            <p
                class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-[var(--color-muted)]"
            >
                Un socle modulaire pour la vitrine, la tarification, la
                passerelle de paiement, les connecteurs, les webhooks et le
                monitoring — activé module par module.
            </p>
            <a
                href="#"
                class="mt-9 inline-flex items-center justify-center rounded-md bg-[var(--color-accent)] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[var(--color-accent-2)]"
            >
                Découvrir la plateforme
            </a>
        </section>

        <section class="mx-auto w-full max-w-5xl px-6 pb-24">
            <div class="grid gap-4 sm:grid-cols-3">
                <article
                    v-for="pillar in pillars"
                    :key="pillar.title"
                    class="rounded-lg border border-[rgba(23,23,28,0.09)] bg-[var(--color-surface)] p-6 text-left"
                >
                    <h2 class="text-sm font-semibold text-[var(--color-ink)]">
                        {{ pillar.title }}
                    </h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-[var(--color-muted)]"
                    >
                        {{ pillar.description }}
                    </p>
                </article>
            </div>
        </section>
    </FrontLayout>
</template>
