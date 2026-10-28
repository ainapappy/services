<script setup lang="ts">
// Journal des deliveries webhooks : filtres (statut, evenement, endpoint)
// appliques via query params Inertia (preserveState), tableau (evenement,
// endpoint, badge de statut, tentatives, code reponse, duree, dates) et
// acces au detail. Etat vide explicite et etat « aucun resultat pour ces
// filtres » avec reinitialisation. Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type {
    BackWebhooksDeliveriesPageProps,
    WebhookDeliveryStatus,
} from '@/types';
import {
    WEBHOOK_DELIVERY_STATUS_LABELS,
    WEBHOOK_EVENTS,
    webhookEventLabel,
} from '@/types';
import { index, show } from '@/routes/admin/webhooks/deliveries';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackEmptyState from '../../components/BackEmptyState.vue';

const props = defineProps<BackWebhooksDeliveriesPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants de la charte                                    */
/* ------------------------------------------------------------------ */

const statusVariants: Record<
    WebhookDeliveryStatus,
    'success' | 'danger' | 'muted'
> = {
    pending: 'muted',
    success: 'success',
    failed: 'danger',
};

/* ------------------------------------------------------------------ */
/* Filtres : selects synchronises avec les query params                 */
/* ------------------------------------------------------------------ */

const filterStatus = ref(props.filters.status ?? '');
const filterEvent = ref(props.filters.event ?? '');
const filterEndpoint = ref(
    props.filters.endpointId === undefined || props.filters.endpointId === null
        ? ''
        : String(props.filters.endpointId),
);

/* Resynchronisation si les props changent (retour arriere Inertia). */
watch(
    () => props.filters,
    (next) => {
        filterStatus.value = next.status ?? '';
        filterEvent.value = next.event ?? '';
        filterEndpoint.value =
            next.endpointId === undefined || next.endpointId === null
                ? ''
                : String(next.endpointId);
    },
);

/* Evenements filtrables : `eventsDisponibles` (contrat) ou `events`
   (controleur), avec repli sur le registre canonique du client. */
const eventOptions = computed<string[]>(() => {
    if (props.eventsDisponibles !== undefined) {
        return props.eventsDisponibles;
    }

    return props.events ?? [...WEBHOOK_EVENTS];
});

/* Charge utile limitee aux filtres actifs : le serveur recoit des query
   params propres (status / event / endpoint), sans valeurs vides. */
function activeQuery(): Record<string, string> {
    const query: Record<string, string> = {};

    if (filterStatus.value !== '') {
        query.status = filterStatus.value;
    }

    if (filterEvent.value !== '') {
        query.event = filterEvent.value;
    }

    if (filterEndpoint.value !== '') {
        query.endpoint = filterEndpoint.value;
    }

    return query;
}

function applyFilters(): void {
    router.get(index.url(), activeQuery(), { preserveState: true });
}

function resetFilters(): void {
    filterStatus.value = '';
    filterEvent.value = '';
    filterEndpoint.value = '';
    applyFilters();
}

/* Un filtre serveur actif (props, pas l'etat local : c'est lui qui
   decide du message d'etat vide affiche). Les valeurs arrivent aussi
   bien a null qu'absentes selon la version du backend. */
const hasActiveFilters = computed(
    () =>
        (props.filters.status ?? '') !== '' ||
        (props.filters.event ?? '') !== '' ||
        (props.filters.endpointId ?? null) !== null,
);

/* ------------------------------------------------------------------ */
/* Formatage : dates et durees                                          */
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

function formatDuration(durationMs: number | null): string {
    if (durationMs === null) {
        return '—';
    }

    if (durationMs < 1000) {
        return `${durationMs.toLocaleString('fr-FR')} ms`;
    }

    return `${(durationMs / 1000).toLocaleString('fr-FR', {
        maximumFractionDigits: 2,
    })} s`;
}

function formatResponseCode(responseCode: number | null): string {
    return responseCode === null ? '—' : responseCode.toLocaleString('fr-FR');
}
</script>

<template>
    <BackLayout>
        <Head title="Deliveries webhooks" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    Deliveries webhooks
                </h1>
                <p class="text-sm text-[var(--color-muted)]">
                    Journal des envois vers les endpoints : tentatives, code de
                    réponse, durée et erreurs. Une delivery échouée peut être
                    relancée depuis son détail.
                </p>
            </header>

            <!-- Filtres -->
            <div class="back-card back-filters px-6 py-4">
                <div class="back-filter min-w-40">
                    <label class="back-help" for="filter-status">
                        Statut
                    </label>
                    <select
                        id="filter-status"
                        v-model="filterStatus"
                        class="back-input back-select"
                        @change="applyFilters"
                    >
                        <option value="">Tous les statuts</option>
                        <option
                            v-for="(
                                label, status
                            ) in WEBHOOK_DELIVERY_STATUS_LABELS"
                            :key="status"
                            :value="status"
                        >
                            {{ label }}
                        </option>
                    </select>
                </div>

                <div class="back-filter min-w-40">
                    <label class="back-help" for="filter-event">
                        Événement
                    </label>
                    <select
                        id="filter-event"
                        v-model="filterEvent"
                        class="back-input back-select"
                        @change="applyFilters"
                    >
                        <option value="">Tous les événements</option>
                        <option
                            v-for="event in eventOptions"
                            :key="event"
                            :value="event"
                        >
                            {{ webhookEventLabel(event) }}
                        </option>
                    </select>
                </div>

                <div class="back-filter min-w-52">
                    <label class="back-help" for="filter-endpoint">
                        Endpoint
                    </label>
                    <select
                        id="filter-endpoint"
                        v-model="filterEndpoint"
                        class="back-input back-select"
                        @change="applyFilters"
                    >
                        <option value="">Tous les endpoints</option>
                        <option
                            v-for="endpoint in endpoints"
                            :key="endpoint.id"
                            :value="String(endpoint.id)"
                        >
                            {{ endpoint.url }}
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
                v-if="deliveries.length === 0 && hasActiveFilters"
                title="Aucune delivery pour ces filtres"
            >
                Aucun envoi journalisé ne correspond au statut, à l'événement ou
                à l'endpoint sélectionné.
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

            <!-- Etat vide : aucune delivery du tout -->
            <BackEmptyState
                v-else-if="deliveries.length === 0"
                title="Aucune delivery"
            >
                Les envois apparaîtront ici dès qu'un événement souscrit par un
                endpoint actif sera déclenché (publication d'une landing, d'une
                table de prix, changement de statut d'un connecteur).
            </BackEmptyState>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Deliveries webhooks : événement, endpoint, statut,
                        tentatives, code réponse, durée et dates
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Événement</th>
                            <th scope="col" class="px-6 py-3">Endpoint</th>
                            <th scope="col" class="px-6 py-3">Statut</th>
                            <th scope="col" class="px-6 py-3">Tentatives</th>
                            <th scope="col" class="px-6 py-3">Code</th>
                            <th scope="col" class="px-6 py-3">Durée</th>
                            <th scope="col" class="px-6 py-3">Date</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="delivery in deliveries"
                            :key="delivery.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-[var(--color-ink)]">
                                    {{ webhookEventLabel(delivery.event) }}
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-xs text-[var(--color-muted)]"
                                >
                                    {{ delivery.event }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <p
                                    class="max-w-[16rem] truncate font-mono text-xs text-[var(--color-ink)]"
                                    :title="delivery.endpointUrl"
                                >
                                    {{ delivery.endpointUrl }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="statusVariants[delivery.status]"
                                >
                                    {{
                                        WEBHOOK_DELIVERY_STATUS_LABELS[
                                            delivery.status
                                        ]
                                    }}
                                </BackBadge>
                            </td>
                            <td
                                class="px-6 py-4 text-[var(--color-ink)] tabular-nums"
                            >
                                {{ delivery.attempts.toLocaleString('fr-FR') }}
                            </td>
                            <td
                                class="px-6 py-4 text-[var(--color-ink)] tabular-nums"
                            >
                                {{ formatResponseCode(delivery.responseCode) }}
                            </td>
                            <td
                                class="px-6 py-4 text-[var(--color-ink)] tabular-nums"
                            >
                                {{ formatDuration(delivery.durationMs) }}
                            </td>
                            <td class="px-6 py-4 text-[var(--color-ink)]">
                                <p>
                                    Créée le
                                    {{ formatDateTime(delivery.createdAt) }}
                                </p>
                                <p
                                    v-if="delivery.deliveredAt !== null"
                                    class="mt-0.5 text-xs text-[var(--color-muted)]"
                                >
                                    Délivrée le
                                    {{ formatDateTime(delivery.deliveredAt) }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end">
                                    <a
                                        :href="
                                            show({
                                                webhookDelivery: delivery.id,
                                            }).url
                                        "
                                        class="back-link"
                                    >
                                        Détail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </BackLayout>
</template>
