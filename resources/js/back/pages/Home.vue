<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { BackHomePageProps } from '@/types';
import BackLayout from '../layouts/BackLayout.vue';

defineProps<BackHomePageProps>();

const tiles = [
    { label: 'Modules', value: '0 / 6' },
    { label: 'Connecteurs', value: '—' },
    { label: 'Webhooks', value: '—' },
    { label: 'Monitoring', value: '—' },
];
</script>

<template>
    <BackLayout>
        <Head title="Tableau de bord" />

        <section>
            <h1
                class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
            >
                Tableau de bord
            </h1>
            <p class="mt-1 text-sm text-[var(--color-muted)]">
                Vue d’ensemble de la plateforme. Les indicateurs s’activeront
                module par module.
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="tile in tiles"
                    :key="tile.label"
                    class="rounded-lg border border-[rgba(23,23,28,0.09)] bg-[var(--color-surface)] p-5"
                >
                    <p
                        class="text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                    >
                        {{ tile.label }}
                    </p>
                    <p
                        class="mt-2 text-2xl font-semibold text-[var(--color-ink)]"
                    >
                        {{ tile.value }}
                    </p>
                </article>
            </div>
        </section>
    </BackLayout>
</template>
