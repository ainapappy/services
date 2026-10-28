<script setup lang="ts">
// Liste des plans tarifaires : tableau trié par `sort` avec prix (centimes
// affiches en euros), badges populaire / actif, bascule d'activation et
// actions editer / supprimer. Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { BackPricingPlansPageProps, PricingPlan } from '@/types';
import { create, destroy, edit, toggle } from '@/routes/admin/pricing/plans';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';
import BackToggle from '../../components/BackToggle.vue';

const props = defineProps<BackPricingPlansPageProps>();

/* Affichage ordonne par `sort` (tri stable : les ex aequo gardent l'ordre
   recu du backend). */
const sortedPlans = computed(() =>
    [...props.plans].sort((a, b) => a.sort - b.sort),
);

/* ------------------------------------------------------------------ */
/* Prix : centimes -> euros, calculs entiers uniquement                */
/* ------------------------------------------------------------------ */

function formatEuros(cents: number): string {
    const safe = Math.round(cents);
    const sign = safe < 0 ? '-' : '';
    const abs = Math.abs(safe);
    const unit = Math.floor(abs / 100).toLocaleString('fr-FR');
    const rem = (abs % 100).toString().padStart(2, '0');
    return `${sign}${unit},${rem} €`;
}

/* ------------------------------------------------------------------ */
/* Activation                                                          */
/* ------------------------------------------------------------------ */

/* Une seule bascule a la fois : les autres interrupteurs sont neutralises
   pendant l'aller-retour Inertia (le serveur renvoie l'index actualise). */
const pendingId = ref<number | null>(null);

function onToggleActive(plan: PricingPlan): void {
    if (pendingId.value !== null) {
        return;
    }

    pendingId.value = plan.id;
    router.patch(
        toggle({ plan: plan.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                pendingId.value = null;
            },
        },
    );
}

/* ------------------------------------------------------------------ */
/* Suppression                                                         */
/* ------------------------------------------------------------------ */

const deletingId = ref<number | null>(null);

function onDestroy(plan: PricingPlan): void {
    if (
        !window.confirm(
            `Supprimer le plan « ${plan.name} » ? Cette action est irréversible.`,
        )
    ) {
        return;
    }

    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = plan.id;
    router.delete(destroy({ plan: plan.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <BackLayout>
        <Head title="Plans tarifaires" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Plans tarifaires
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Définissez les offres affichées sur la page tarifs :
                        prix mensuel et annuel en centimes, inclus et mise en
                        avant.
                    </p>
                </div>

                <a
                    :href="create.url()"
                    class="back-btn back-btn--primary shrink-0"
                >
                    Nouveau plan
                </a>
            </header>

            <!-- Etat vide -->
            <div
                v-if="sortedPlans.length === 0"
                class="back-card flex flex-col items-center gap-3 px-6 py-16 text-center"
            >
                <p class="text-base font-semibold text-[var(--color-ink)]">
                    Aucun plan
                </p>
                <p class="max-w-sm text-sm text-[var(--color-muted)]">
                    Créez votre premier plan tarifaire : il pourra ensuite être
                    associé à une table de prix publiée côté vitrine.
                </p>
                <a :href="create.url()" class="back-btn back-btn--primary mt-2">
                    Nouveau plan
                </a>
            </div>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Plans tarifaires : prix, ordre d'affichage, statut et
                        actions
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Plan</th>
                            <th scope="col" class="px-6 py-3">Prix</th>
                            <th scope="col" class="px-6 py-3">Ordre</th>
                            <th scope="col" class="px-6 py-3">État</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="plan in sortedPlans"
                            :key="plan.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p
                                    class="flex items-center gap-2 font-medium text-[var(--color-ink)]"
                                >
                                    {{ plan.name }}
                                    <BackBadge v-if="plan.popular">
                                        Populaire
                                    </BackBadge>
                                </p>
                                <p class="mt-0.5 text-[var(--color-muted)]">
                                    /{{ plan.slug }}
                                </p>
                                <p
                                    v-if="plan.tagline"
                                    class="mt-0.5 text-[var(--color-muted)]"
                                >
                                    {{ plan.tagline }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-[var(--color-ink)]">
                                    {{ formatEuros(plan.priceMonthly) }} / mois
                                </p>
                                <p class="mt-0.5 text-[var(--color-muted)]">
                                    {{ formatEuros(plan.priceYearly) }} / mois
                                    en annuel
                                </p>
                            </td>
                            <td class="px-6 py-4 tabular-nums">
                                {{ plan.sort }}
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="plan.active ? 'success' : 'muted'"
                                >
                                    {{ plan.active ? 'Actif' : 'Inactif' }}
                                </BackBadge>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <BackToggle
                                        :checked="plan.active"
                                        :label="`${plan.active ? 'Désactiver' : 'Activer'} le plan ${plan.name}`"
                                        :disabled="
                                            pendingId !== null &&
                                            pendingId !== plan.id
                                        "
                                        @toggle="onToggleActive(plan)"
                                    />
                                    <a
                                        :href="edit({ plan: plan.id }).url"
                                        class="back-link"
                                    >
                                        Éditer
                                    </a>
                                    <BackButton
                                        variant="danger"
                                        :loading="deletingId === plan.id"
                                        :disabled="
                                            deletingId !== null &&
                                            deletingId !== plan.id
                                        "
                                        @click="onDestroy(plan)"
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
