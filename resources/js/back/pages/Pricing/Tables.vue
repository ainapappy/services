<script setup lang="ts">
// Liste des tables de prix : titre, contenu (plans / FAQ), statut de
// publication et actions editer / supprimer. Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { BackPricingTablesPageProps, PricingTableListItem } from '@/types';
import { create, destroy, edit } from '@/routes/admin/pricing/tables';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';

defineProps<BackPricingTablesPageProps>();

/* Suppression : confirmation explicite puis retour a l'index actualise. */
const deletingId = ref<number | null>(null);

function onDestroy(table: PricingTableListItem): void {
    if (
        !window.confirm(
            `Supprimer la table « ${table.title} » ? Cette action est irréversible.`,
        )
    ) {
        return;
    }

    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = table.id;
    router.delete(destroy({ pricingTable: table.id }).url, {
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
        <Head title="Tables de prix" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Tables de prix
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Composez les tables publiées sur la page tarifs : plans
                        associés, FAQ et publication.
                    </p>
                </div>

                <a
                    :href="create.url()"
                    class="back-btn back-btn--primary shrink-0"
                >
                    Nouvelle table
                </a>
            </header>

            <!-- Etat vide -->
            <div
                v-if="tables.length === 0"
                class="back-card flex flex-col items-center gap-3 px-6 py-16 text-center"
            >
                <p class="text-base font-semibold text-[var(--color-ink)]">
                    Aucune table
                </p>
                <p class="max-w-sm text-sm text-[var(--color-muted)]">
                    Créez votre première table de prix : elle sera rendue sur la
                    page /pricing dès sa publication.
                </p>
                <a :href="create.url()" class="back-btn back-btn--primary mt-2">
                    Nouvelle table
                </a>
            </div>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Tables de prix : contenu, statut de publication et
                        actions
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Table</th>
                            <th scope="col" class="px-6 py-3">Plans</th>
                            <th scope="col" class="px-6 py-3">FAQ</th>
                            <th scope="col" class="px-6 py-3">Statut</th>
                            <th scope="col" class="px-6 py-3">Publication</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="table in tables"
                            :key="table.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-[var(--color-ink)]">
                                    {{ table.title }}
                                </p>
                                <p class="mt-0.5 text-[var(--color-muted)]">
                                    /{{ table.slug }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                {{ table.plansCount }}
                            </td>
                            <td class="px-6 py-4">
                                {{ table.faqCount }}
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="
                                        table.publishedAt !== null
                                            ? 'success'
                                            : 'muted'
                                    "
                                >
                                    {{
                                        table.publishedAt !== null
                                            ? 'Publiée'
                                            : 'Brouillon'
                                    }}
                                </BackBadge>
                            </td>
                            <td class="px-6 py-4">
                                {{ formatPublishedAt(table.publishedAt) }}
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <a
                                        :href="
                                            edit({ pricingTable: table.id }).url
                                        "
                                        class="back-link"
                                    >
                                        Éditer
                                    </a>
                                    <BackButton
                                        variant="danger"
                                        :loading="deletingId === table.id"
                                        :disabled="
                                            deletingId !== null &&
                                            deletingId !== table.id
                                        "
                                        @click="onDestroy(table)"
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
