<script setup lang="ts">
// Detail d'une delivery webhook : recapitulatif (statut, evenement,
// endpoint, code reponse, duree, tentatives), payload JSON en pretty-print
// colore par tokens (sans v-html) et bloc d'erreur. Une delivery echouee
// peut etre relancee (POST retry, confirmation par AlertDialog, spinner).
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { CircleXIcon } from '@lucide/vue';
import type {
    BackWebhooksDeliveryPageProps,
    WebhookDeliveryStatus,
} from '@/types';
import { WEBHOOK_DELIVERY_STATUS_LABELS, webhookEventLabel } from '@/types';
import { index, retry } from '@/routes/admin/webhooks/deliveries';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<BackWebhooksDeliveryPageProps>();

const statusVariants: Record<
    WebhookDeliveryStatus,
    'success' | 'destructive' | 'secondary'
> = {
    pending: 'secondary',
    success: 'success',
    failed: 'destructive',
};

/* ------------------------------------------------------------------ */
/* Formatage : dates, duree, code reponse                               */
/* ------------------------------------------------------------------ */

function formatDateTime(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
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

/* ------------------------------------------------------------------ */
/* Payload : pretty-print JSON + coloration minimale                    */
/* ------------------------------------------------------------------ */

/* Les tokens sont rendus comme des <span> enfants d'un <pre> (pas de
   v-html) : la chaine est seulement decoupee, jamais interpretee. Les
   classes sont des utilitaires Tailwind (variantes sombre incluses). */
type JsonToken = { text: string; cls: string | null };

const JSON_TOKEN_RE =
    /("(?:\\.|[^"\\])*")(\s*:)?|\b(?:true|false|null)\b|-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?/g;

function tokenizeJson(source: string): JsonToken[] {
    const tokens: JsonToken[] = [];
    let lastIndex = 0;

    for (const match of source.matchAll(JSON_TOKEN_RE)) {
        const start = match.index ?? 0;

        if (start > lastIndex) {
            tokens.push({ text: source.slice(lastIndex, start), cls: null });
        }

        if (match[1] !== undefined) {
            if (match[2] !== undefined) {
                /* Chaine suivie de « : » : cle de l'objet. */
                tokens.push({
                    text: match[1],
                    cls: 'font-medium text-foreground',
                });
                tokens.push({ text: match[2], cls: null });
            } else {
                tokens.push({
                    text: match[1],
                    cls: 'text-emerald-600 dark:text-emerald-400',
                });
            }
        } else if (
            match[0] === 'true' ||
            match[0] === 'false' ||
            match[0] === 'null'
        ) {
            tokens.push({
                text: match[0],
                cls: 'text-violet-500 dark:text-violet-400',
            });
        } else {
            tokens.push({
                text: match[0],
                cls: 'text-amber-600 dark:text-amber-400',
            });
        }

        lastIndex = start + match[0].length;
    }

    if (lastIndex < source.length) {
        tokens.push({ text: source.slice(lastIndex), cls: null });
    }

    return tokens;
}

const payloadLines = computed<JsonToken[][]>(() => {
    let pretty: string;

    try {
        pretty = JSON.stringify(props.delivery.payload, null, 2);
    } catch {
        pretty = String(props.delivery.payload);
    }

    /* Decoupage en lignes : chaque ligne est un bloc, ce qui evite de
       dependre de sauts de ligne fragiles a la mise en forme du gabarit. */
    const lines: JsonToken[][] = [[]];

    for (const token of tokenizeJson(pretty)) {
        token.text.split('\n').forEach((part, index) => {
            if (index > 0) {
                lines.push([]);
            }

            if (part.length > 0) {
                lines[lines.length - 1].push({ text: part, cls: token.cls });
            }
        });
    }

    return lines;
});

/* ------------------------------------------------------------------ */
/* Relance manuelle                                                     */
/* ------------------------------------------------------------------ */

/* La confirmation passe par l'AlertDialog : l'action declenche la
   requete. */
const retrying = ref(false);

function onRetry(): void {
    if (retrying.value) {
        return;
    }

    retrying.value = true;
    /* Le serveur redirige vers la page de detail : les props Inertia sont
       rechargees avec le statut a jour. */
    router.post(
        retry({ webhookDelivery: props.delivery.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                retrying.value = false;
            },
        },
    );
}
</script>

<template>
    <BackLayout>
        <Head title="Delivery webhook" />

        <section class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <p class="text-sm text-muted-foreground">
                    <a
                        :href="index.url()"
                        class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                    >
                        Deliveries webhooks
                    </a>
                    <span class="mx-1">/</span>
                    <span class="font-mono text-xs"> #{{ delivery.id }} </span>
                </p>

                <BackPageHeader :title="webhookEventLabel(delivery.event)">
                    <template #actions>
                        <Badge :variant="statusVariants[delivery.status]">
                            {{
                                WEBHOOK_DELIVERY_STATUS_LABELS[delivery.status]
                            }}
                        </Badge>
                    </template>
                </BackPageHeader>

                <p class="text-sm text-muted-foreground">
                    <span class="font-mono text-xs text-foreground">
                        {{ delivery.endpointUrl }}
                    </span>
                    — créée le {{ formatDateTime(delivery.createdAt) }},
                    <template v-if="delivery.deliveredAt !== null">
                        délivrée le {{ formatDateTime(delivery.deliveredAt) }}.
                    </template>
                    <template v-else> pas encore délivrée. </template>
                </p>
            </div>

            <!-- Bloc d'erreur (echec journalise par le job de livraison) -->
            <Alert v-if="delivery.error !== null" variant="destructive">
                <CircleXIcon />
                <AlertTitle>Échec de la dernière tentative</AlertTitle>
                <AlertDescription>
                    <span class="font-mono text-xs break-all">
                        {{ delivery.error }}
                    </span>
                </AlertDescription>
            </Alert>

            <!-- Recapitulatif -->
            <Card>
                <CardHeader>
                    <CardTitle>Récapitulatif</CardTitle>
                    <CardDescription>
                        Compte rendu du dernier envoi : nombre de tentatives,
                        réponse de l'endpoint et durée.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted-foreground">
                                Statut
                            </dt>
                            <dd>
                                <Badge
                                    :variant="statusVariants[delivery.status]"
                                >
                                    {{
                                        WEBHOOK_DELIVERY_STATUS_LABELS[
                                            delivery.status
                                        ]
                                    }}
                                </Badge>
                            </dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted-foreground">
                                Événement
                            </dt>
                            <dd>
                                <p class="text-base font-medium">
                                    {{ webhookEventLabel(delivery.event) }}
                                </p>
                                <p
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ delivery.event }}
                                </p>
                            </dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted-foreground">
                                Endpoint
                            </dt>
                            <dd
                                class="max-w-[16rem] truncate font-mono text-xs"
                                :title="delivery.endpointUrl"
                            >
                                {{ delivery.endpointUrl }}
                            </dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted-foreground">
                                Code réponse
                            </dt>
                            <dd class="text-base font-medium tabular-nums">
                                {{ formatResponseCode(delivery.responseCode) }}
                            </dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted-foreground">Durée</dt>
                            <dd class="text-base font-medium tabular-nums">
                                {{ formatDuration(delivery.durationMs) }}
                            </dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-xs text-muted-foreground">
                                Tentatives
                            </dt>
                            <dd class="text-base font-medium tabular-nums">
                                {{ delivery.attempts.toLocaleString('fr-FR') }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="delivery.status === 'failed'" class="mt-5">
                        <AlertDialog>
                            <AlertDialogTrigger as-child>
                                <Button :disabled="retrying">
                                    <Spinner v-if="retrying" class="size-4" />
                                    Relancer
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>
                                        Relancer cette delivery ?
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Une nouvelle tentative immédiate sera
                                        journalisée, avec le même payload et une
                                        signature recalculée.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>
                                        Annuler
                                    </AlertDialogCancel>
                                    <AlertDialogAction as-child>
                                        <Button
                                            :disabled="retrying"
                                            @click="onRetry"
                                        >
                                            <Spinner
                                                v-if="retrying"
                                                class="size-4"
                                            />
                                            Relancer
                                        </Button>
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </CardContent>
            </Card>

            <!-- Payload -->
            <Card>
                <CardHeader>
                    <CardTitle>Payload</CardTitle>
                    <CardDescription>
                        Charge utile JSON transmise à l'endpoint, signée via
                        X-Signature (HMAC-SHA256).
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <pre
                        class="overflow-x-auto rounded-md bg-muted p-4 font-mono text-xs leading-relaxed"
                    ><code><span
                        v-for="(line, lineIndex) in payloadLines"
                        :key="lineIndex"
                        class="block"
                    ><span
                        v-for="(token, tokenIndex) in line"
                        :key="tokenIndex"
                        :class="token.cls ?? 'text-muted-foreground'"
                    >{{ token.text }}</span></span></code></pre>
                </CardContent>
            </Card>
        </section>
    </BackLayout>
</template>
