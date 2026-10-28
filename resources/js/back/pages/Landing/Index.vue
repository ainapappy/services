<script setup lang="ts">
// Liste des landing pages : tableau (titre, slug, statut, accueil, CTA)
// avec actions editer / supprimer. Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { BackLandingIndexPageProps } from '@/types';
import { create, destroy, edit } from '@/routes/admin/landing';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';

defineProps<BackLandingIndexPageProps>();

/* Suppression : confirmation explicite puis retour a l'index actualise. */
const deletingId = ref<number | null>(null);

function onDestroy(id: number, title: string): void {
    if (
        !window.confirm(
            `Supprimer la page « ${title} » ? Cette action est irréversible.`,
        )
    ) {
        return;
    }

    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = id;
    router.delete(destroy({ landingPage: id }).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function formatPublishedAt(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <BackLayout>
        <Head title="Landing" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Pages landing
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Créez et publiez les pages vitrine : héros, boutons
                        d'appel à l'action et référencement.
                    </p>
                </div>

                <a
                    :href="create.url()"
                    class="back-btn back-btn--primary shrink-0"
                >
                    Nouvelle page
                </a>
            </header>

            <!-- Etat vide -->
            <div
                v-if="landingPages.length === 0"
                class="back-card flex flex-col items-center gap-3 px-6 py-16 text-center"
            >
                <p class="text-base font-semibold text-[var(--color-ink)]">
                    Aucune page
                </p>
                <p class="max-w-sm text-sm text-[var(--color-muted)]">
                    Créez votre première landing page : elle sera rendue côté
                    vitrine dès sa publication.
                </p>
                <a :href="create.url()" class="back-btn back-btn--primary mt-2">
                    Nouvelle page
                </a>
            </div>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Landing pages : titre, statut de publication et actions
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Page</th>
                            <th scope="col" class="px-6 py-3">Statut</th>
                            <th scope="col" class="px-6 py-3">CTA</th>
                            <th scope="col" class="px-6 py-3">Publication</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="landingPage in landingPages"
                            :key="landingPage.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p
                                    class="flex items-center gap-2 font-medium text-[var(--color-ink)]"
                                >
                                    {{ landingPage.title }}
                                    <BackBadge v-if="landingPage.isHome">
                                        Accueil
                                    </BackBadge>
                                </p>
                                <p class="mt-0.5 text-[var(--color-muted)]">
                                    /{{ landingPage.slug }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="
                                        landingPage.publishedAt !== null
                                            ? 'success'
                                            : 'muted'
                                    "
                                >
                                    {{
                                        landingPage.publishedAt !== null
                                            ? 'Publiée'
                                            : 'Brouillon'
                                    }}
                                </BackBadge>
                            </td>
                            <td class="px-6 py-4">
                                {{ landingPage.ctasCount }}
                            </td>
                            <td class="px-6 py-4">
                                {{ formatPublishedAt(landingPage.publishedAt) }}
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <a
                                        :href="
                                            edit({
                                                landingPage: landingPage.id,
                                            }).url
                                        "
                                        class="back-link"
                                    >
                                        Éditer
                                    </a>
                                    <BackButton
                                        variant="danger"
                                        :loading="deletingId === landingPage.id"
                                        :disabled="
                                            deletingId !== null &&
                                            deletingId !== landingPage.id
                                        "
                                        @click="
                                            onDestroy(
                                                landingPage.id,
                                                landingPage.title,
                                            )
                                        "
                                    >
                                        Supprimer
                                    </BackButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </BackLayout>
</template>
