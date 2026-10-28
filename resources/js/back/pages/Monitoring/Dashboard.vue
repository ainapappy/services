<script setup lang="ts">
// Dashboard Monitoring : etat global en tete (X/Y moniteurs operationnels),
// une carte par moniteur (nom, type en chip, badge d'etat, cible,
// sparkline des dernieres latences, derniere latence, dernier controle
// relatif, message d'erreur sur) et alertes actives avec resolution
// (POST vers alerts.resolve, le serveur re-affiche le dashboard).
// Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type {
    BackMonitoringDashboardPageProps,
    MonitorAlert,
    MonitorStatus,
} from '@/types';
import {
    MONITOR_STATUS_VARIANTS,
    MONITOR_TYPE_LABELS,
    monitorStatusLabel,
} from '@/types';
import { history as historyIndex } from '@/routes/admin/monitoring';
import {
    create,
    index as monitorsIndex,
} from '@/routes/admin/monitoring/monitors';
import { resolve as resolveAlert } from '@/routes/admin/monitoring/alerts';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackEmptyState from '../../components/BackEmptyState.vue';
import BackSparkline from '../../components/BackSparkline.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackMonitoringDashboardPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants de la charte                                    */
/* ------------------------------------------------------------------ */

function statusVariant(
    status: MonitorStatus | null,
): 'success' | 'warning' | 'danger' | 'muted' {
    return status === null ? 'muted' : MONITOR_STATUS_VARIANTS[status];
}

/* ------------------------------------------------------------------ */
/* Etat global                                                          */
/* ------------------------------------------------------------------ */

const upCount = computed(
    () => props.monitors.filter((monitor) => monitor.status === 'up').length,
);

const degradedCount = computed(
    () =>
        props.monitors.filter((monitor) => monitor.status === 'degraded')
            .length,
);

const downCount = computed(
    () => props.monitors.filter((monitor) => monitor.status === 'down').length,
);

const neverCount = computed(
    () => props.monitors.filter((monitor) => monitor.status === null).length,
);

const activeAlerts = computed(() =>
    props.monitors.flatMap((monitor) => monitor.alerts),
);

/* Une seule alerte critique suffit a passer le bandeau de tete en rouge. */
const alertBannerTone = computed(() =>
    activeAlerts.value.some((alert) => alert.level === 'critical')
        ? 'danger'
        : 'warning',
);

/* ------------------------------------------------------------------ */
/* Formatage : latence, dates                                           */
/* ------------------------------------------------------------------ */

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

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        month: 'short',
    });
}

/* ------------------------------------------------------------------ */
/* Resolution des alertes                                               */
/* ------------------------------------------------------------------ */

/* Une seule resolution a la fois ; le serveur redirige vers le dashboard
   (liste reelle d'ou vient l'alerte), la prop alerts se met a jour. */
const resolvingId = ref<number | null>(null);

function onResolve(alert: MonitorAlert): void {
    if (resolvingId.value !== null) {
        return;
    }

    resolvingId.value = alert.id;
    router.post(
        resolveAlert({ monitorAlert: alert.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                resolvingId.value = null;
            },
        },
    );
}
</script>

<template>
    <BackLayout>
        <Head title="Monitoring" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Monitoring
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Santé des connecteurs, de la gateway et des webhooks :
                        état courant, latences récentes et alertes actives.
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <a
                        :href="historyIndex.url()"
                        class="back-btn back-btn--secondary"
                    >
                        Historique des contrôles
                    </a>
                    <a
                        :href="monitorsIndex.url()"
                        class="back-btn back-btn--secondary"
                    >
                        Gérer les moniteurs
                    </a>
                </div>
            </header>

            <template v-if="monitors.length > 0">
                <!-- Etat global en tete -->
                <div
                    class="back-card flex flex-wrap items-center justify-between gap-3 px-6 py-4"
                >
                    <p
                        class="text-sm font-medium text-[var(--color-ink)]"
                        role="status"
                    >
                        {{ upCount }}/{{ monitors.length }} moniteurs
                        opérationnels
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <BackBadge variant="success">
                            {{ upCount }} opérationnel{{
                                upCount > 1 ? 's' : ''
                            }}
                        </BackBadge>
                        <BackBadge variant="warning">
                            {{ degradedCount }} dégradé{{
                                degradedCount > 1 ? 's' : ''
                            }}
                        </BackBadge>
                        <BackBadge variant="danger">
                            {{ downCount }} hors ligne
                        </BackBadge>
                        <BackBadge variant="muted">
                            {{ neverCount }} jamais contrôlé{{
                                neverCount > 1 ? 's' : ''
                            }}
                        </BackBadge>
                    </div>
                </div>

                <!-- Alerte active : bandeau de tete (danger si au moins une
                     alerte critique, sinon avertissement) -->
                <BackStatus
                    v-if="activeAlerts.length > 0"
                    :tone="alertBannerTone"
                >
                    {{
                        activeAlerts.length === 1
                            ? '1 alerte active à examiner.'
                            : `${activeAlerts.length} alertes actives à examiner.`
                    }}
                    Le détail par moniteur figure dans les cartes ci-dessous.
                </BackStatus>

                <!-- Cartes par moniteur -->
                <div class="grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="monitor in monitors"
                        :key="monitor.id"
                        class="back-card flex flex-col gap-4 p-5"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="truncate font-semibold text-[var(--color-ink)]"
                                    >
                                        {{ monitor.name }}
                                    </h3>
                                    <span class="back-chip">
                                        {{ MONITOR_TYPE_LABELS[monitor.type] }}
                                    </span>
                                </div>
                                <p
                                    class="mt-0.5 max-w-[20rem] truncate font-mono text-xs text-[var(--color-muted)]"
                                    :title="monitor.targetSummary"
                                >
                                    {{ monitor.targetSummary }}
                                </p>
                            </div>
                            <div
                                class="flex shrink-0 flex-col items-end gap-1.5"
                            >
                                <BackBadge
                                    :variant="statusVariant(monitor.status)"
                                >
                                    {{ monitorStatusLabel(monitor.status) }}
                                </BackBadge>
                                <BackBadge v-if="!monitor.active">
                                    Inactif
                                </BackBadge>
                            </div>
                        </div>

                        <!-- Sparkline : latences recentes, coupures visibles -->
                        <div class="flex flex-col gap-1">
                            <p class="back-help">
                                Latences des derniers contrôles
                            </p>
                            <BackSparkline
                                v-if="monitor.sparkline.length > 0"
                                :points="monitor.sparkline"
                            />
                            <p v-else class="back-help">
                                Aucun contrôle enregistré pour l'instant.
                            </p>
                        </div>

                        <dl
                            class="grid grid-cols-2 gap-x-4 gap-y-2 border-t border-[rgba(23,23,28,0.06)] pt-3 text-sm"
                        >
                            <div class="flex flex-col">
                                <dt class="back-help">Dernière latence</dt>
                                <dd
                                    class="font-medium text-[var(--color-ink)] tabular-nums"
                                >
                                    {{ formatLatency(monitor.latencyMs) }}
                                    <span
                                        v-if="
                                            monitor.latencyMs !== null &&
                                            monitor.latencyThresholdMs !== null
                                        "
                                        class="text-xs font-normal text-[var(--color-muted)]"
                                    >
                                        (seuil
                                        {{
                                            formatLatency(
                                                monitor.latencyThresholdMs,
                                            )
                                        }})
                                    </span>
                                </dd>
                            </div>
                            <div class="flex flex-col">
                                <dt class="back-help">Dernier contrôle</dt>
                                <dd class="text-[var(--color-ink)]">
                                    {{ formatRelative(monitor.checkedAt) }}
                                </dd>
                            </div>
                        </dl>

                        <!-- Message d'erreur sur du dernier controle -->
                        <p
                            v-if="monitor.message"
                            class="back-error"
                            :title="monitor.message"
                        >
                            {{ monitor.message }}
                        </p>

                        <!-- Alertes actives du moniteur -->
                        <div
                            v-if="monitor.alerts.length > 0"
                            class="flex flex-col gap-2"
                        >
                            <div
                                v-for="alert in monitor.alerts"
                                :key="alert.id"
                                class="back-alert"
                                :class="
                                    alert.level === 'critical'
                                        ? 'back-alert--critical'
                                        : 'back-alert--warning'
                                "
                                :role="
                                    alert.level === 'critical'
                                        ? 'alert'
                                        : undefined
                                "
                            >
                                <div class="back-alert-body">
                                    <p class="back-alert-title">
                                        {{
                                            alert.level === 'critical'
                                                ? 'Alerte critique'
                                                : 'Avertissement'
                                        }}
                                    </p>
                                    <p>{{ alert.message }}</p>
                                    <p class="back-alert-meta">
                                        {{ formatDateTime(alert.createdAt) }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="back-alert-action"
                                    :disabled="resolvingId !== null"
                                    @click="onResolve(alert)"
                                >
                                    <span
                                        v-if="resolvingId === alert.id"
                                        class="back-spinner"
                                        aria-hidden="true"
                                    />
                                    Résoudre
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </template>

            <!-- Etat vide : aucun moniteur -->
            <BackEmptyState v-else title="Aucun moniteur" :wide="true">
                Créez un moniteur pour superviser un connecteur, la gateway ou
                les webhooks : chaque contrôle est journalisé et une alerte est
                levée dès qu'une cible passe hors ligne.
                <template v-if="connectors.length > 0">
                    {{ connectors.length }} connecteur{{
                        connectors.length > 1 ? 's' : ''
                    }}
                    sont disponibles pour la supervision.
                </template>
                <template #action>
                    <a :href="create.url()" class="back-btn back-btn--primary">
                        Créer un moniteur
                    </a>
                </template>
            </BackEmptyState>
        </section>
    </BackLayout>
</template>
