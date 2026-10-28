<script setup lang="ts">
// Journal des deliveries webhooks : filtres (statut, evenement, endpoint)
// en selects shadcn appliques via query params Inertia (preserveState),
// tableau (evenement, endpoint, badge de statut, tentatives, code
// reponse, duree, dates) et acces au detail. Deux etats vides explicites :
// aucun resultat pour ces filtres (avec reinitialisation) et aucun envoi.
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { RotateCcwIcon, SearchXIcon, WebhookIcon } from '@lucide/vue';
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
import BackPageHeader from '../../components/BackPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

const props = defineProps<BackWebhooksDeliveriesPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants de badges                                       */
/* ------------------------------------------------------------------ */

/* Options du filtre de statut : couples (valeur, libelle). */
const statusOptions = Object.entries(WEBHOOK_DELIVERY_STATUS_LABELS) as Array<
    [WebhookDeliveryStatus, string]
>;

/* Variante de Badge associee a chaque statut de delivery (ui shadcn). */
const statusVariants: Record<
    WebhookDeliveryStatus,
    'success' | 'destructive' | 'secondary'
> = {
    pending: 'secondary',
    success: 'success',
    failed: 'destructive',
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

/* Changement d'un select de filtre : l'etat local est mis a jour puis la
   liste rechargee immediatement (equivalent de l'ancien @change). */
function onFilterStatusChange(value: unknown): void {
    filterStatus.value = String(value);
    applyFilters();
}

function onFilterEventChange(value: unknown): void {
    filterEvent.value = String(value);
    applyFilters();
}

function onFilterEndpointChange(value: unknown): void {
    filterEndpoint.value = String(value);
    applyFilters();
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
            <BackPageHeader
                title="Journal des envois"
                description="Journal des envois vers les endpoints : tentatives, code de réponse, durée et erreurs. Une delivery échouée peut être relancée depuis son détail."
            />

            <!-- Filtres -->
            <Card>
                <CardContent class="flex flex-wrap items-end gap-4">
                    <Field class="w-auto min-w-40">
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

                    <Field class="w-auto min-w-40">
                        <FieldLabel for="filter-event">Événement</FieldLabel>
                        <Select
                            :model-value="filterEvent"
                            @update:model-value="onFilterEventChange"
                        >
                            <SelectTrigger id="filter-event" class="w-full">
                                <SelectValue
                                    placeholder="Tous les événements"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="event in eventOptions"
                                    :key="event"
                                    :value="event"
                                >
                                    {{ webhookEventLabel(event) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field class="w-auto min-w-52">
                        <FieldLabel for="filter-endpoint">Endpoint</FieldLabel>
                        <Select
                            :model-value="filterEndpoint"
                            @update:model-value="onFilterEndpointChange"
                        >
                            <SelectTrigger id="filter-endpoint" class="w-full">
                                <SelectValue placeholder="Tous les endpoints" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="endpoint in endpoints"
                                    :key="endpoint.id"
                                    :value="String(endpoint.id)"
                                >
                                    {{ endpoint.url }}
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
                        <RotateCcwIcon />
                        Réinitialiser
                    </Button>
                </CardContent>
            </Card>

            <!-- Etat vide : aucun resultat pour ces filtres -->
            <Empty
                v-if="deliveries.length === 0 && hasActiveFilters"
                class="rounded-xl border"
            >
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <SearchXIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucune delivery pour ces filtres</EmptyTitle>
                    <EmptyDescription>
                        Aucun envoi journalisé ne correspond au statut, à
                        l'événement ou à l'endpoint sélectionné.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button variant="outline" @click="resetFilters">
                        Réinitialiser les filtres
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Etat vide : aucune delivery du tout -->
            <Empty
                v-else-if="deliveries.length === 0"
                class="rounded-xl border"
            >
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <WebhookIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucune delivery</EmptyTitle>
                    <EmptyDescription>
                        Les envois apparaîtront ici dès qu'un événement souscrit
                        par un endpoint actif sera déclenché (publication d'une
                        landing, d'une table de prix, changement de statut d'un
                        connecteur).
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>

            <!-- Tableau -->
            <div v-else class="overflow-hidden rounded-xl border">
                <Table>
                    <TableCaption class="sr-only">
                        Deliveries webhooks : événement, endpoint, statut,
                        tentatives, code réponse, durée et dates
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Événement
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Endpoint
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Statut
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Tentatives
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Code
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Durée
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Date
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-right text-xs tracking-wide uppercase"
                            >
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="delivery in deliveries"
                            :key="delivery.id"
                        >
                            <TableCell class="px-4 py-4 align-top">
                                <Badge variant="outline">
                                    {{ webhookEventLabel(delivery.event) }}
                                </Badge>
                                <p
                                    class="mt-1 font-mono text-xs text-muted-foreground"
                                >
                                    {{ delivery.event }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <p
                                    class="max-w-[16rem] truncate font-mono text-xs"
                                    :title="delivery.endpointUrl"
                                >
                                    {{ delivery.endpointUrl }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <Badge
                                    :variant="statusVariants[delivery.status]"
                                >
                                    {{
                                        WEBHOOK_DELIVERY_STATUS_LABELS[
                                            delivery.status
                                        ]
                                    }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top tabular-nums">
                                {{ delivery.attempts.toLocaleString('fr-FR') }}
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top tabular-nums">
                                {{ formatResponseCode(delivery.responseCode) }}
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top tabular-nums">
                                {{ formatDuration(delivery.durationMs) }}
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <p class="tabular-nums">
                                    Créée le
                                    {{ formatDateTime(delivery.createdAt) }}
                                </p>
                                <p
                                    v-if="delivery.deliveredAt !== null"
                                    class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                                >
                                    Délivrée le
                                    {{ formatDateTime(delivery.deliveredAt) }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right align-top">
                                <a
                                    :href="
                                        show({
                                            webhookDelivery: delivery.id,
                                        }).url
                                    "
                                    class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    Détail
                                </a>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </section>
    </BackLayout>
</template>
