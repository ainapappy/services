<script setup lang="ts">
// Gestion des moniteurs : tableau (nom, type en chip, cible, seuil de
// latence, badge actif/inactif, dernier etat + date relative), actions
// editer / supprimer confirmee, acces a l'historique des controles et
// creation. La bascule d'activation passe par le formulaire (pas de
// route dediee : store/update font foi). Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type {
    BackMonitoringMonitorsPageProps,
    MonitorListItem,
    MonitorStatus,
} from '@/types';
import {
    MONITOR_STATUS_VARIANTS,
    MONITOR_TYPE_LABELS,
    monitorStatusLabel,
} from '@/types';
import { dashboard, history as historyIndex } from '@/routes/admin/monitoring';
import { create, destroy, edit } from '@/routes/admin/monitoring/monitors';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';

const props = defineProps<BackMonitoringMonitorsPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants de la charte                                    */
/* ------------------------------------------------------------------ */

function statusVariant(
    status: MonitorStatus | null,
): 'success' | 'warning' | 'danger' | 'muted' {
    return status === null ? 'muted' : MONITOR_STATUS_VARIANTS[status];
}

/* ------------------------------------------------------------------ */
/* Formatage : seuil, dates                                             */
/* ------------------------------------------------------------------ */

function formatLatency(latencyMs: number | null): string {
    if (latencyMs === null) {
        return '—';
    }

    return `${latencyMs.toLocaleString('fr-FR')} ms`;
}

const relativeFormat = new Intl.RelativeTimeFormat('fr-FR', {
    numeric: 'auto',
});

function formatRelative(value: string | null): string {
    if (value === null) {
        return 'Jamais';
    }

    const date = new Date(value);
    const diffMs = date.getTime() - Date.now();
    const absMs = Math.abs(diffMs);

    if (absMs < 3_600_000) {
        return relativeFormat.format(Math.round(diffMs / 60_000), 'minute');
    }

    if (absMs < 86_400_000) {
        return relativeFormat.format(Math.round(diffMs / 3_600_000), 'hour');
    }

    if (absMs < 7 * 86_400_000) {
        return relativeFormat.format(Math.round(diffMs / 86_400_000), 'day');
    }

    return date.toLocaleString('fr-FR', {
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

/* ------------------------------------------------------------------ */
/* Suppression                                                          */
/* ------------------------------------------------------------------ */

const deletingId = ref<number | null>(null);

function onDestroy(monitor: MonitorListItem): void {
    if (
        !window.confirm(
            `Supprimer le moniteur « ${monitor.name} » ? Son historique de contrôles et ses alertes seront définitivement effacés. Cette action est irréversible.`,
        )
    ) {
        return;
    }

    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = monitor.id;
    router.delete(destroy({ monitor: monitor.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <BackLayout>
        <Head title="Moniteurs de supervision" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <p class="text-sm text-[var(--color-muted)]">
                        <a :href="dashboard.url()" class="back-link">
                            Monitoring
                        </a>
                        <span class="mx-1">/</span>
                        <span>Moniteurs</span>
                    </p>
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Moniteurs de supervision
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Cibles supervisées périodiquement : connecteurs (test de
                        connexion), gateway (endpoint de santé) et webhooks
                        (deliveries récentes). L'état courant est visible sur le
                        <a :href="dashboard.url()" class="back-link"
                            >tableau de bord Monitoring</a
                        >.
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <a
                        :href="historyIndex.url()"
                        class="back-btn back-btn--secondary"
                    >
                        Historique des contrôles
                    </a>
                    <a :href="create.url()" class="back-btn back-btn--primary">
                        Nouveau moniteur
                    </a>
                </div>
            </header>

            <!-- Etat vide -->
            <div
                v-if="props.monitors.length === 0"
                class="back-card flex flex-col items-center gap-3 px-6 py-16 text-center"
            >
                <p class="text-base font-semibold text-[var(--color-ink)]">
                    Aucun moniteur
                </p>
                <p class="max-w-md text-sm text-[var(--color-muted)]">
                    Créez un moniteur pour superviser un connecteur, la gateway
                    ou les webhooks : chaque contrôle est journalisé et une
                    alerte est levée dès qu'une cible passe hors ligne.
                </p>
                <a :href="create.url()" class="back-btn back-btn--primary mt-2">
                    Créer un moniteur
                </a>
            </div>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Moniteurs : nom, type, cible, seuil de latence, état,
                        dernier contrôle et actions
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Moniteur</th>
                            <th scope="col" class="px-6 py-3">Type</th>
                            <th scope="col" class="px-6 py-3">Cible</th>
                            <th scope="col" class="px-6 py-3">Seuil</th>
                            <th scope="col" class="px-6 py-3">État</th>
                            <th scope="col" class="px-6 py-3">
                                Dernier contrôle
                            </th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="monitor in props.monitors"
                            :key="monitor.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-[var(--color-ink)]">
                                    {{ monitor.name }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="back-chip">
                                    {{ MONITOR_TYPE_LABELS[monitor.type] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p
                                    class="max-w-[16rem] truncate font-mono text-xs text-[var(--color-ink)]"
                                    :title="monitor.targetSummary"
                                >
                                    {{ monitor.targetSummary }}
                                </p>
                            </td>
                            <td
                                class="px-6 py-4 text-[var(--color-ink)] tabular-nums"
                            >
                                {{ formatLatency(monitor.latencyThresholdMs) }}
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="
                                        monitor.active ? 'success' : 'muted'
                                    "
                                >
                                    {{ monitor.active ? 'Actif' : 'Inactif' }}
                                </BackBadge>
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="statusVariant(monitor.lastStatus)"
                                >
                                    {{ monitorStatusLabel(monitor.lastStatus) }}
                                </BackBadge>
                                <p
                                    class="mt-0.5 text-xs text-[var(--color-muted)]"
                                >
                                    {{ formatRelative(monitor.lastCheckedAt) }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <a
                                        :href="
                                            edit({ monitor: monitor.id }).url
                                        "
                                        class="back-link"
                                    >
                                        Éditer
                                    </a>
                                    <BackButton
                                        variant="danger"
                                        :loading="deletingId === monitor.id"
                                        :disabled="
                                            deletingId !== null &&
                                            deletingId !== monitor.id
                                        "
                                        @click="onDestroy(monitor)"
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
