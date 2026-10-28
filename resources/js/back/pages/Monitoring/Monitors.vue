<script setup lang="ts">
// Gestion des moniteurs : tableau (nom, type, cible, seuil de latence,
// badge actif/inactif, dernier etat + date relative), menu d'actions par
// ligne (edition, liens dashboard et historique, suppression confirmee
// par AlertDialog) et creation. La bascule d'activation passe par le
// formulaire (pas de route dediee : store/update font foi). Aucun import
// hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type {
    BackMonitoringMonitorsPageProps,
    MonitorListItem,
    MonitorStatus,
} from '@/types';
import { MONITOR_TYPE_LABELS, monitorStatusLabel } from '@/types';
import { dashboard, history as historyIndex } from '@/routes/admin/monitoring';
import { create, destroy, edit } from '@/routes/admin/monitoring/monitors';
import {
    EllipsisVerticalIcon,
    HistoryIcon,
    LayoutDashboardIcon,
    PencilIcon,
    PlusIcon,
    RadarIcon,
    Trash2Icon,
} from '@lucide/vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';

const props = defineProps<BackMonitoringMonitorsPageProps>();

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

/* Confirmee par AlertDialog, puis retour a l'index actualise. */
const deletingId = ref<number | null>(null);

function onDestroy(monitor: MonitorListItem): void {
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
            <BackPageHeader
                title="Moniteurs"
                description="Cibles supervisées périodiquement : connecteurs (test de connexion), gateway (endpoint de santé) et webhooks (deliveries récentes). L'état courant est visible sur le tableau de bord Monitoring."
            >
                <template #actions>
                    <Button as-child>
                        <a :href="create.url()">
                            <PlusIcon />
                            Nouveau moniteur
                        </a>
                    </Button>
                </template>
            </BackPageHeader>

            <!-- Etat vide -->
            <Empty v-if="props.monitors.length === 0" class="rounded-xl border">
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
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <a :href="create.url()">Créer un moniteur</a>
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Tableau -->
            <Card v-else class="overflow-hidden py-0">
                <Table>
                    <TableCaption class="sr-only">
                        Moniteurs : nom, type, cible, seuil de latence, état,
                        dernier contrôle et actions
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="h-12 px-4">Moniteur</TableHead>
                            <TableHead class="h-12 px-4">Type</TableHead>
                            <TableHead class="h-12 px-4">Cible</TableHead>
                            <TableHead class="h-12 px-4">Seuil</TableHead>
                            <TableHead class="h-12 px-4">État</TableHead>
                            <TableHead class="h-12 px-4">
                                Dernier contrôle
                            </TableHead>
                            <TableHead class="h-12 px-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="monitor in props.monitors"
                            :key="monitor.id"
                        >
                            <TableCell class="px-4 py-3">
                                <p class="font-medium">{{ monitor.name }}</p>
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <Badge variant="outline">
                                    {{ MONITOR_TYPE_LABELS[monitor.type] }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <p
                                    class="max-w-[16rem] truncate font-mono text-xs"
                                    :title="monitor.targetSummary"
                                >
                                    {{ monitor.targetSummary }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-3 tabular-nums">
                                {{ formatLatency(monitor.latencyThresholdMs) }}
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <Badge
                                    :variant="
                                        monitor.active ? 'success' : 'secondary'
                                    "
                                >
                                    {{ monitor.active ? 'Actif' : 'Inactif' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-3">
                                <Badge
                                    :variant="statusVariant(monitor.lastStatus)"
                                >
                                    {{ monitorStatusLabel(monitor.lastStatus) }}
                                </Badge>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ formatRelative(monitor.lastCheckedAt) }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-3 text-right">
                                <div class="flex justify-end">
                                    <AlertDialog>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    :aria-label="`Actions pour le moniteur ${monitor.name}`"
                                                >
                                                    <EllipsisVerticalIcon />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent
                                                align="end"
                                                class="w-48"
                                            >
                                                <DropdownMenuItem as-child>
                                                    <a
                                                        :href="
                                                            edit({
                                                                monitor:
                                                                    monitor.id,
                                                            }).url
                                                        "
                                                    >
                                                        <PencilIcon />
                                                        Éditer
                                                    </a>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem as-child>
                                                    <a :href="dashboard.url()">
                                                        <LayoutDashboardIcon />
                                                        Tableau de bord
                                                    </a>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem as-child>
                                                    <a
                                                        :href="
                                                            historyIndex.url()
                                                        "
                                                    >
                                                        <HistoryIcon />
                                                        Historique
                                                    </a>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <AlertDialogTrigger as-child>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                    >
                                                        <Trash2Icon />
                                                        Supprimer
                                                    </DropdownMenuItem>
                                                </AlertDialogTrigger>
                                            </DropdownMenuContent>
                                        </DropdownMenu>

                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>
                                                    Supprimer le moniteur ?
                                                </AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Supprimer le moniteur «
                                                    {{ monitor.name }} » ? Son
                                                    historique de contrôles et
                                                    ses alertes seront
                                                    définitivement effacés.
                                                    Cette action est
                                                    irréversible.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>
                                                    Annuler
                                                </AlertDialogCancel>
                                                <AlertDialogAction as-child>
                                                    <Button
                                                        variant="destructive"
                                                        :disabled="
                                                            deletingId !== null
                                                        "
                                                        @click="
                                                            onDestroy(monitor)
                                                        "
                                                    >
                                                        <Spinner
                                                            v-if="
                                                                deletingId ===
                                                                monitor.id
                                                            "
                                                            class="size-4"
                                                        />
                                                        Supprimer
                                                    </Button>
                                                </AlertDialogAction>
                                            </AlertDialogFooter>
                                        </AlertDialogContent>
                                    </AlertDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>
        </section>
    </BackLayout>
</template>
