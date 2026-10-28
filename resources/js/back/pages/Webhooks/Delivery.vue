<script setup lang="ts">
// Detail d'une delivery webhook : statut, evenement, endpoint vise, dates,
// payload JSON en pretty-print (coloration minimale par tokens, sans
// v-html), tentatives, code reponse, duree et bloc d'erreur. Une delivery
// echouee peut etre relancee (POST retry, confirmation, spinner). Aucun
// import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type {
    BackWebhooksDeliveryPageProps,
    WebhookDeliveryStatus,
} from '@/types';
import { WEBHOOK_DELIVERY_STATUS_LABELS, webhookEventLabel } from '@/types';
import { index, retry } from '@/routes/admin/webhooks/deliveries';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackWebhooksDeliveryPageProps>();

const statusVariants: Record<
    WebhookDeliveryStatus,
    'success' | 'danger' | 'muted'
> = {
    pending: 'muted',
    success: 'success',
    failed: 'danger',
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
   v-html) : la chaine est seulement decoupee, jamais interpretee. */
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
                tokens.push({ text: match[1], cls: 'back-json-tok--key' });
                tokens.push({ text: match[2], cls: null });
            } else {
                tokens.push({ text: match[1], cls: 'back-json-tok--string' });
            }
        } else if (
            match[0] === 'true' ||
            match[0] === 'false' ||
            match[0] === 'null'
        ) {
            tokens.push({ text: match[0], cls: 'back-json-tok--literal' });
        } else {
            tokens.push({ text: match[0], cls: 'back-json-tok--number' });
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

const retrying = ref(false);

function onRetry(): void {
    if (retrying.value) {
        return;
    }

    if (
        !window.confirm(
            'Relancer cette delivery ? Une nouvelle tentative immédiate sera journalisée, avec le même payload et une signature recalculée.',
        )
    ) {
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
            <header class="flex flex-col gap-2">
                <p class="text-sm text-muted">
                    <a :href="index.url()" class="back-link">
                        Deliveries webhooks
                    </a>
                    <span class="mx-1">/</span>
                    <span class="font-mono text-xs"> #{{ delivery.id }} </span>
                </p>

                <div class="flex flex-wrap items-center gap-3">
                    <h1
                        class="text-xl font-semibold tracking-tight text-(--color-ink)"
                    >
                        {{ webhookEventLabel(delivery.event) }}
                    </h1>
                    <BackBadge :variant="statusVariants[delivery.status]">
                        {{ WEBHOOK_DELIVERY_STATUS_LABELS[delivery.status] }}
                    </BackBadge>
                </div>

                <p class="text-sm text-muted">
                    <span class="font-mono text-xs text-(--color-ink)">
                        {{ delivery.endpointUrl }}
                    </span>
                    — créée le {{ formatDateTime(delivery.createdAt) }},
                    <template v-if="delivery.deliveredAt !== null">
                        délivrée le {{ formatDateTime(delivery.deliveredAt) }}.
                    </template>
                    <template v-else> pas encore délivrée. </template>
                </p>
            </header>

            <!-- Bloc d'erreur (echec journalise par le job de livraison) -->
            <BackStatus v-if="delivery.error !== null" tone="danger">
                <span class="flex flex-col gap-1">
                    <span class="font-semibold">
                        Échec de la dernière tentative
                    </span>
                    <span class="font-mono text-xs">
                        {{ delivery.error }}
                    </span>
                </span>
            </BackStatus>

            <!-- Tentatives et reponse -->
            <BackCard
                title="Tentative"
                description="Compte rendu du dernier envoi : nombre de tentatives, réponse de l'endpoint et durée."
            >
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="flex flex-col gap-1">
                        <dt class="back-help">Tentatives</dt>
                        <dd
                            class="text-base font-medium text-(--color-ink) tabular-nums"
                        >
                            {{ delivery.attempts.toLocaleString('fr-FR') }}
                        </dd>
                    </div>
                    <div class="flex flex-col gap-1">
                        <dt class="back-help">Code réponse</dt>
                        <dd
                            class="text-base font-medium text-(--color-ink) tabular-nums"
                        >
                            {{ formatResponseCode(delivery.responseCode) }}
                        </dd>
                    </div>
                    <div class="flex flex-col gap-1">
                        <dt class="back-help">Durée</dt>
                        <dd
                            class="text-base font-medium text-(--color-ink) tabular-nums"
                        >
                            {{ formatDuration(delivery.durationMs) }}
                        </dd>
                    </div>
                </dl>

                <div v-if="delivery.status === 'failed'" class="mt-5">
                    <BackButton :loading="retrying" @click="onRetry">
                        Relancer
                    </BackButton>
                </div>
            </BackCard>

            <!-- Payload -->
            <BackCard
                title="Payload"
                description="Charge utile JSON transmise à l'endpoint, signée via X-Signature (HMAC-SHA256)."
            >
                <pre class="back-json"><code><span
                        v-for="(line, lineIndex) in payloadLines"
                        :key="lineIndex"
                        class="back-json-line"
                    ><span
                        v-for="(token, tokenIndex) in line"
                        :key="tokenIndex"
                        :class="token.cls"
                    >{{ token.text }}</span></span></code></pre>
            </BackCard>
        </section>
    </BackLayout>
</template>
