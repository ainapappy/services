<script setup lang="ts">
// Journal des controles Monitoring : filtres (type, statut, periode)
// synchronises avec les query params Inertia (preserveState, pattern
// Webhooks/Deliveries), tableau (date, moniteur, type, badge d'etat,
// latence, message tronque) limite a l'affichage des 200 controles les
// plus recents, deux etats vides (aucun controle / aucun pour ces
// filtres avec reinitialisation). Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackMonitoringHistoryPageProps, MonitorStatus } from '@/types';
import {
    MONITOR_STATUS_LABELS,
    MONITOR_STATUS_VARIANTS,
    MONITOR_TYPE_LABELS,
    monitorStatusLabel,
} from '@/types';
import { dashboard, history as historyIndex } from '@/routes/admin/monitoring';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackEmptyState from '../../components/BackEmptyState.vue';

const props = defineProps<BackMonitoringHistoryPageProps>();

/* ------------------------------------------------------------------ */
/* Options des filtres                                                  */
/* ------------------------------------------------------------------ */

const typeOptions = Object.entries(MONITOR_TYPE_LABELS);

const statusOptions = Object.entries(MONITOR_STATUS_LABELS) as Array<
    [MonitorStatus, string]
>;

const periodOptions: Array<{ value: string; label: string }> = [
    { value: '1', label: '24 heures' },
    { value: '7', label: '7 jours' },
    { value: '30', label: '30 jours' },
];

/* ------------------------------------------------------------------ */
/* Filtres : selects synchronises avec les query params                 */
/* ------------------------------------------------------------------ */

const filterType = ref(props.filters.type ?? '');
const filterStatus = ref(props.filters.status ?? '');
const filterDays = ref(
    props.filters.days === undefined || props.filters.days === null
        ? ''
        : String(props.filters.days),
);

/* Resynchronisation si les props changent (retour arriere Inertia). */
watch(
    () => props.filters,
    (next) => {
        filterType.value = next.type ?? '';
        filterStatus.value = next.status ?? '';
        filterDays.value =
            next.days === undefined || next.days === null
                ? ''
                : String(next.days);
    },
);

/* Charge utile limitee aux filtres actifs : le serveur recoit des query
   params propres (type / status / days), sans valeurs vides. */
function activeQuery(): Record<string, string> {
    const query: Record<string, string> = {};

    if (filterType.value !== '') {
        query.type = filterType.value;
    }

    if (filterStatus.value !== '') {
        query.status = filterStatus.value;
    }

    if (filterDays.value !== '') {
        query.days = filterDays.value;
    }

    return query;
}

function applyFilters(): void {
    router.get(historyIndex.url(), activeQuery(), { preserveState: true });
}

function resetFilters(): void {
    filterType.value = '';
    filterStatus.value = '';
    filterDays.value = '';
    applyFilters();
}

/* Un filtre serveur actif (props, pas l'etat local : c'est lui qui
   decide du message d'etat vide affiche). */
const hasActiveFilters = computed(
    () =>
        (props.filters.type ?? '') !== '' ||
        (props.filters.status ?? '') !== '' ||
        (props.filters.days ?? null) !== null,
);

/* ------------------------------------------------------------------ */
/* Affichage : 200 controles les plus recents maximum                   */
/* ------------------------------------------------------------------ */

const MAX_DISPLAY = 200;

const visibleChecks = computed(() => props.checks.slice(0, MAX_DISPLAY));

const hiddenCount = computed(() =>
    Math.max(0, props.checks.length - MAX_DISPLAY),
);

/* ------------------------------------------------------------------ */
/* Formatage : dates, latence                                           */
/* ------------------------------------------------------------------ */

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function formatLatency(latencyMs: number | null): string {
    if (latencyMs === null) {
        return '—';
    }

    if (latencyMs < 1000) {
        return `${latencyMs.toLocaleString('fr-FR')} ms`;
    }

    return `${(latencyMs / 1000).toLocaleString('fr-FR', {
        maximumFractionDigits: 2,
    })} s`;
}
</script>

<template>
    <BackLayout>
        <Head title="Historique des contrôles" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <p class="text-sm text-[var(--color-muted)]">
                    <a :href="dashboard.url()" class="back-link">Monitoring</a>
                    <span class="mx-1">/</span>
                    <span>Historique</span>
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    Historique des contrôles
                </h1>
                <p class="text-sm text-[var(--color-muted)]">
                    Journal des contrôles exécutés par la commande planifiée
                    (toutes les 5 minutes) : statut, latence mesurée et éventuel
                    message d'erreur.
                </p>
            </header>

            <!-- Filtres -->
            <div class="back-card back-filters px-6 py-4">
                <div class="back-filter min-w-40">
                    <label class="back-help" for="filter-type">Type</label>
                    <select
                        id="filter-type"
                        v-model="filterType"
                        class="back-input back-select"
                        @change="applyFilters"
                    >
                        <option value="">Tous les types</option>
                        <option
                            v-for="[value, label] in typeOptions"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div class="back-filter min-w-40">
                    <label class="back-help" for="filter-status">Statut</label>
                    <select
                        id="filter-status"
                        v-model="filterStatus"
                        class="back-input back-select"
                        @change="applyFilters"
                    >
                        <option value="">Tous les statuts</option>
                        <option
                            v-for="[value, label] in statusOptions"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div class="back-filter min-w-40">
                    <label class="back-help" for="filter-days">Période</label>
                    <select
                        id="filter-days"
                        v-model="filterDays"
                        class="back-input back-select"
                        @change="applyFilters"
                    >
                        <option value="">Toutes les périodes</option>
                        <option
                            v-for="period in periodOptions"
                            :key="period.value"
                            :value="period.value"
                        >
                            {{ period.label }}
                        </option>
                    </select>
                </div>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="back-btn back-btn--secondary"
                    @click="resetFilters"
                >
                    Réinitialiser
                </button>
            </div>

            <!-- Etat vide : aucun resultat pour ces filtres -->
            <BackEmptyState
                v-if="checks.length === 0 && hasActiveFilters"
                title="Aucun contrôle pour ces filtres"
            >
                Aucun contrôle journalisé ne correspond au type, au statut ou à
                la période sélectionnés.
                <template #action>
                    <button
                        type="button"
                        class="back-btn back-btn--secondary"
                        @click="resetFilters"
                    >
                        Réinitialiser les filtres
                    </button>
                </template>
            </BackEmptyState>

            <!-- Etat vide : aucun controle du tout -->
            <BackEmptyState
                v-else-if="checks.length === 0"
                title="Aucun contrôle"
            >
                Les contrôles apparaîtront ici dès que la commande
                <code class="font-mono text-xs">monitoring:run</code>
                aura tourné (elle est planifiée toutes les 5 minutes).
            </BackEmptyState>

            <!-- Tableau -->
            <template v-else>
                <div class="back-card overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">
                            Contrôles : date, moniteur, type, statut, latence et
                            message
                        </caption>
                        <thead>
                            <tr
                                class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                            >
                                <th scope="col" class="px-6 py-3">Date</th>
                                <th scope="col" class="px-6 py-3">Moniteur</th>
                                <th scope="col" class="px-6 py-3">Type</th>
                                <th scope="col" class="px-6 py-3">Statut</th>
                                <th scope="col" class="px-6 py-3">Latence</th>
                                <th scope="col" class="px-6 py-3">Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="check in visibleChecks"
                                :key="check.id"
                                class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                            >
                                <td
                                    class="px-6 py-4 text-[var(--color-ink)] tabular-nums"
                                >
                                    {{ formatDateTime(check.checkedAt) }}
                                </td>
                                <td class="px-6 py-4">
                                    <p
                                        class="font-medium text-[var(--color-ink)]"
                                    >
                                        {{ check.monitorName }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="back-chip">
                                        {{ MONITOR_TYPE_LABELS[check.type] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <BackBadge
                                        :variant="
                                            MONITOR_STATUS_VARIANTS[
                                                check.status
                                            ]
                                        "
                                    >
                                        {{ monitorStatusLabel(check.status) }}
                                    </BackBadge>
                                </td>
                                <td
                                    class="px-6 py-4 text-[var(--color-ink)] tabular-nums"
                                >
                                    {{ formatLatency(check.latencyMs) }}
                                </td>
                                <td class="px-6 py-4">
                                    <p
                                        v-if="check.message"
                                        class="max-w-[16rem] truncate text-xs text-[var(--color-muted)]"
                                        :title="check.message"
                                    >
                                        {{ check.message }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-xs text-[var(--color-muted)]"
                                    >
                                        —
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p
                    v-if="hiddenCount > 0"
                    class="text-xs text-[var(--color-muted)]"
                >
                    Affichage limité aux {{ MAX_DISPLAY }} contrôles les plus
                    récents ; {{ hiddenCount.toLocaleString('fr-FR') }} entrées
                    supplémentaires existent dans le journal.
                </p>
            </template>
        </section>
    </BackLayout>
</template>
