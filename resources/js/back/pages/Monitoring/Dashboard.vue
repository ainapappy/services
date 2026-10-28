<script setup lang="ts">
// Dashboard Monitoring : bandeau d'etat global en tete (X/Y moniteurs
// operationnels + compteurs en badges), alertes actives avec resolution
// (POST vers alerts.resolve, le serveur re-affiche le dashboard), puis
// une carte par moniteur (nom, type, badge d'etat, cible, sparkline des
// dernieres latences, derniere latence, dernier controle relatif,
// message d'erreur eventuel). Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type {
    BackMonitoringDashboardPageProps,
    MonitorAlert,
    MonitorStatus,
} from '@/types';
import { MONITOR_TYPE_LABELS, monitorStatusLabel } from '@/types';
import { history } from '@/routes/admin/monitoring';
import { create } from '@/routes/admin/monitoring/monitors';
import { resolve as resolveAlert } from '@/routes/admin/monitoring/alerts';
import {
    HistoryIcon,
    PlusIcon,
    RadarIcon,
    TriangleAlertIcon,
} from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';
import BackSparkline from '../../components/BackSparkline.vue';

const props = defineProps<BackMonitoringDashboardPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants de badges                                       */
/* ------------------------------------------------------------------ */

/* Variante de Badge associee a chaque statut de controle (ui shadcn) ;
   aucun controle encore realise -> « jamais controle » en secondaire. */
const STATUS_BADGE_VARIANTS: Record<
    MonitorStatus,
    'success' | 'warning' | 'destructive'
> = {
    up: 'success',
    degraded: 'warning',
    down: 'destructive',
};

function statusVariant(
    status: MonitorStatus | null,
): 'success' | 'warning' | 'destructive' | 'secondary' {
    return status === null ? 'secondary' : STATUS_BADGE_VARIANTS[status];
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

/* Resume des alertes : rouge si au moins une alerte critique, sinon
   avertissement (default tant qu'il n'y a rien a examiner). */
const alertBannerVariant = computed(() => {
    if (activeAlerts.value.length === 0) {
        return 'default' as const;
    }

    return activeAlerts.value.some((alert) => alert.level === 'critical')
        ? ('destructive' as const)
        : ('warning' as const);
});

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
            <BackPageHeader
                title="Monitoring"
                description="Santé des connecteurs, de la gateway et des webhooks : état courant, latences récentes et alertes actives."
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <a :href="history.url()">
                            <HistoryIcon />
                            Historique
                        </a>
                    </Button>
                    <Button as-child>
                        <a :href="create.url()">
                            <PlusIcon />
                            Nouveau moniteur
                        </a>
                    </Button>
                </template>
            </BackPageHeader>

            <template v-if="monitors.length > 0">
                <!-- Etat global en tete -->
                <Card>
                    <CardHeader>
                        <CardAction class="flex flex-wrap items-center gap-2">
                            <Badge variant="success">
                                {{ upCount }} opérationnel{{
                                    upCount > 1 ? 's' : ''
                                }}
                            </Badge>
                            <Badge variant="warning">
                                {{ degradedCount }} dégradé{{
                                    degradedCount > 1 ? 's' : ''
                                }}
                            </Badge>
                            <Badge variant="destructive">
                                {{ downCount }} hors ligne
                            </Badge>
                            <Badge variant="secondary">
                                {{ neverCount }} jamais contrôlé{{
                                    neverCount > 1 ? 's' : ''
                                }}
                            </Badge>
                        </CardAction>
                        <CardTitle class="text-3xl" role="status">
                            {{ upCount }}/{{ monitors.length }} moniteurs
                            opérationnels
                        </CardTitle>
                    </CardHeader>
                </Card>

                <!-- Alertes actives : resume puis une alerte par bloc, avec
                     resolution (role alert porte par le composant Alert) -->
                <div v-if="activeAlerts.length > 0" class="flex flex-col gap-3">
                    <Alert :variant="alertBannerVariant">
                        <TriangleAlertIcon />
                        <AlertTitle>
                            {{
                                activeAlerts.length === 1
                                    ? '1 alerte active à examiner.'
                                    : `${activeAlerts.length} alertes actives à examiner.`
                            }}
                        </AlertTitle>
                        <AlertDescription>
                            Le détail par moniteur figure dans les cartes
                            ci-dessous.
                        </AlertDescription>
                    </Alert>

                    <Alert
                        v-for="alert in activeAlerts"
                        :key="alert.id"
                        :variant="
                            alert.level === 'critical'
                                ? 'destructive'
                                : 'warning'
                        "
                    >
                        <AlertTitle>
                            {{
                                alert.level === 'critical'
                                    ? 'Alerte critique'
                                    : 'Avertissement'
                            }}
                        </AlertTitle>
                        <AlertDescription class="flex flex-col gap-3">
                            <span>{{ alert.message }}</span>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDateTime(alert.createdAt) }}
                            </span>
                            <div>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    :disabled="resolvingId !== null"
                                    @click="onResolve(alert)"
                                >
                                    <Spinner
                                        v-if="resolvingId === alert.id"
                                        class="size-4"
                                    />
                                    Résoudre
                                </Button>
                            </div>
                        </AlertDescription>
                    </Alert>
                </div>

                <!-- Cartes par moniteur -->
                <div class="grid gap-4 lg:grid-cols-2">
                    <Card
                        v-for="monitor in monitors"
                        :key="monitor.id"
                        class="gap-4 py-5"
                    >
                        <CardHeader>
                            <CardAction class="flex flex-col items-end gap-1.5">
                                <Badge :variant="statusVariant(monitor.status)">
                                    {{ monitorStatusLabel(monitor.status) }}
                                </Badge>
                                <Badge
                                    v-if="!monitor.active"
                                    variant="secondary"
                                >
                                    Inactif
                                </Badge>
                            </CardAction>
                            <CardTitle
                                class="flex flex-wrap items-center gap-2 text-base"
                            >
                                <span class="truncate">{{ monitor.name }}</span>
                                <Badge variant="outline">
                                    {{ MONITOR_TYPE_LABELS[monitor.type] }}
                                </Badge>
                            </CardTitle>
                            <CardDescription
                                class="max-w-[20rem] truncate font-mono text-xs"
                                :title="monitor.targetSummary"
                            >
                                {{ monitor.targetSummary }}
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="flex flex-col gap-4">
                            <!-- Sparkline : latences recentes, coupures visibles -->
                            <div class="flex flex-col gap-1">
                                <p class="text-xs text-muted-foreground">
                                    Latences des derniers contrôles
                                </p>
                                <BackSparkline
                                    v-if="monitor.sparkline.length > 0"
                                    :points="monitor.sparkline"
                                />
                                <p v-else class="text-xs text-muted-foreground">
                                    Aucun contrôle enregistré pour l'instant.
                                </p>
                            </div>

                            <Separator />

                            <dl
                                class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm"
                            >
                                <div class="flex flex-col">
                                    <dt class="text-xs text-muted-foreground">
                                        Dernière latence
                                    </dt>
                                    <dd class="font-medium tabular-nums">
                                        {{ formatLatency(monitor.latencyMs) }}
                                        <span
                                            v-if="
                                                monitor.latencyMs !== null &&
                                                monitor.latencyThresholdMs !==
                                                    null
                                            "
                                            class="text-xs font-normal text-muted-foreground"
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
                                    <dt class="text-xs text-muted-foreground">
                                        Dernier contrôle
                                    </dt>
                                    <dd>
                                        {{ formatRelative(monitor.checkedAt) }}
                                    </dd>
                                </div>
                            </dl>

                            <!-- Message d'erreur sur du dernier controle -->
                            <p
                                v-if="monitor.message"
                                class="text-sm text-destructive"
                                :title="monitor.message"
                            >
                                {{ monitor.message }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </template>

            <!-- Etat vide : aucun moniteur -->
            <Empty v-else class="rounded-xl border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <RadarIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun moniteur</EmptyTitle>
                    <EmptyDescription>
                        Créez un moniteur pour superviser un connecteur, la
                        gateway ou les webhooks : chaque contrôle est journalisé
                        et une alerte est levée dès qu'une cible passe hors
                        ligne.
                        <template v-if="connectors.length > 0">
                            {{ connectors.length }} connecteur{{
                                connectors.length > 1 ? 's' : ''
                            }}
                            sont disponibles pour la supervision.
                        </template>
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <a :href="create.url()">Créer un moniteur</a>
                    </Button>
                </EmptyContent>
            </Empty>
        </section>
    </BackLayout>
</template>
