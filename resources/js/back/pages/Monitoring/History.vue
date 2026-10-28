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
    MONITOR_TYPE_LABELS,
    monitorStatusLabel,
} from '@/types';
import { history as historyIndex } from '@/routes/admin/monitoring';
import { ClockIcon, HistoryIcon } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

const props = defineProps<BackMonitoringHistoryPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants de badges                                       */
/* ------------------------------------------------------------------ */

/* Variante de Badge associee a chaque statut de controle (ui shadcn). */
const STATUS_BADGE_VARIANTS: Record<
    MonitorStatus,
    'success' | 'warning' | 'destructive'
> = {
    up: 'success',
    degraded: 'warning',
    down: 'destructive',
};

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

/* Changement d'un select de filtre : l'etat local est mis a jour puis la
   liste rechargee immediatement (equivalent de l'ancien @change). */
function onFilterTypeChange(value: unknown): void {
    filterType.value = String(value);
    applyFilters();
}

function onFilterStatusChange(value: unknown): void {
    filterStatus.value = String(value);
    applyFilters();
}

function onFilterDaysChange(value: unknown): void {
    filterDays.value = String(value);
    applyFilters();
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
            <BackPageHeader
                title="Historique des contrôles"
                description="Journal des contrôles exécutés par la commande planifiée (toutes les 5 minutes) : statut, latence mesurée et éventuel message d'erreur."
            />

            <!-- Filtres -->
            <Card>
                <CardContent class="flex flex-wrap items-end gap-4">
                    <Field class="min-w-40">
                        <FieldLabel for="filter-type">Type</FieldLabel>
                        <Select
                            :model-value="filterType"
                            @update:model-value="onFilterTypeChange"
                        >
                            <SelectTrigger id="filter-type" class="w-full">
                                <SelectValue placeholder="Tous les types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="[value, label] in typeOptions"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field class="min-w-40">
                        <FieldLabel for="filter-status">Statut</FieldLabel>
                        <Select
                            :model-value="filterStatus"
                            @update:model-value="onFilterStatusChange"
                        >
                            <SelectTrigger id="filter-status" class="w-full">
                                <SelectValue placeholder="Tous les statuts" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="[value, label] in statusOptions"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field class="min-w-40">
                        <FieldLabel for="filter-days">Période</FieldLabel>
                        <Select
                            :model-value="filterDays"
                            @update:model-value="onFilterDaysChange"
                        >
                            <SelectTrigger id="filter-days" class="w-full">
                                <SelectValue
                                    placeholder="Toutes les périodes"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="period in periodOptions"
                                    :key="period.value"
                                    :value="period.value"
                                >
                                    {{ period.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>

                    <Button
                        v-if="hasActiveFilters"
                        variant="ghost"
                        size="sm"
                        @click="resetFilters"
                    >
                        Réinitialiser
                    </Button>
                </CardContent>
            </Card>

            <!-- Etat vide : aucun resultat pour ces filtres -->
            <Empty
                v-if="checks.length === 0 && hasActiveFilters"
                class="rounded-xl border"
            >
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <HistoryIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun contrôle pour ces filtres</EmptyTitle>
                    <EmptyDescription>
                        Aucun contrôle journalisé ne correspond au type, au
                        statut ou à la période sélectionnés.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button variant="outline" @click="resetFilters">
                        Réinitialiser les filtres
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Etat vide : aucun controle du tout -->
            <Empty v-else-if="checks.length === 0" class="rounded-xl border">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <ClockIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun contrôle</EmptyTitle>
                    <EmptyDescription>
                        Les contrôles apparaîtront ici dès que la commande
                        <code class="font-mono text-xs">monitoring:run</code>
                        aura tourné (elle est planifiée toutes les 5 minutes).
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>

            <!-- Tableau -->
            <template v-else>
                <Card class="overflow-hidden py-0">
                    <Table>
                        <TableCaption class="sr-only">
                            Contrôles : date, moniteur, type, statut, latence et
                            message
                        </TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="h-12 px-4">Date</TableHead>
                                <TableHead class="h-12 px-4">
                                    Moniteur
                                </TableHead>
                                <TableHead class="h-12 px-4">Type</TableHead>
                                <TableHead class="h-12 px-4">Statut</TableHead>
                                <TableHead class="h-12 px-4">Latence</TableHead>
                                <TableHead class="h-12 px-4">Message</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="check in visibleChecks"
                                :key="check.id"
                            >
                                <TableCell class="px-4 py-3 tabular-nums">
                                    {{ formatDateTime(check.checkedAt) }}
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <p class="font-medium">
                                        {{ check.monitorName }}
                                    </p>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge variant="outline">
                                        {{ MONITOR_TYPE_LABELS[check.type] }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge
                                        :variant="
                                            STATUS_BADGE_VARIANTS[check.status]
                                        "
                                    >
                                        {{ monitorStatusLabel(check.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3 tabular-nums">
                                    {{ formatLatency(check.latencyMs) }}
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <p
                                        v-if="check.message"
                                        class="max-w-[16rem] truncate text-xs text-muted-foreground"
                                        :title="check.message"
                                    >
                                        {{ check.message }}
                                    </p>
                                    <p
                                        v-else
                                        class="text-xs text-muted-foreground"
                                    >
                                        —
                                    </p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>

                <p v-if="hiddenCount > 0" class="text-xs text-muted-foreground">
                    Affichage limité aux {{ MAX_DISPLAY }} contrôles les plus
                    récents ; {{ hiddenCount.toLocaleString('fr-FR') }} entrées
                    supplémentaires existent dans le journal.
                </p>
            </template>
        </section>
    </BackLayout>
</template>
