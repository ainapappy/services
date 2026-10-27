<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import type { BackDashboardPageProps } from '@/types';
import BackLayout from '../layouts/BackLayout.vue';
import BackCard from '../components/BackCard.vue';

defineProps<BackDashboardPageProps>();

const page = usePage<BackDashboardPageProps>();
const firstName = computed(() => {
    const name = page.props.auth.user?.name ?? '';
    return name.split(/\s+/)[0] ?? '';
});

/* Cartes de statistiques volontairement vides : chaque indicateur
   s'activera avec le module metier correspondant (phases 03+). */
const placeholders = [
    {
        label: 'Modules actifs',
        hint: 'Suivi des domaines activés sur la plateforme.',
    },
    {
        label: 'Connecteurs',
        hint: 'Connexions aux services tiers.',
    },
    {
        label: 'Webhooks',
        hint: 'Livraisons sortantes et tentatives.',
    },
    {
        label: 'Monitoring',
        hint: 'Disponibilité des services surveillés.',
    },
];
</script>

<template>
    <BackLayout>
        <Head title="Tableau de bord" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    Bonjour{{ firstName ? `, ${firstName}` : '' }}
                </h1>
                <p class="text-sm text-[var(--color-muted)]">
                    Vue d’ensemble de la plateforme. Les statistiques
                    s’activeront au fil de l’arrivée des modules métier.
                </p>
            </header>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <BackCard
                    v-for="placeholder in placeholders"
                    :key="placeholder.label"
                >
                    <div class="flex flex-col gap-2">
                        <p
                            class="text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            {{ placeholder.label }}
                        </p>
                        <p
                            class="text-2xl font-semibold text-[rgba(23,23,28,0.25)]"
                            aria-hidden="true"
                        >
                            —
                        </p>
                        <p
                            class="text-xs leading-relaxed text-[var(--color-muted)]"
                        >
                            {{ placeholder.hint }}
                        </p>
                        <p class="sr-only">
                            Aucune donnée disponible pour le moment.
                        </p>
                    </div>
                </BackCard>
            </div>
        </section>
    </BackLayout>
</template>
