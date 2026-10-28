<script setup lang="ts">
// Liste des endpoints webhooks sortants : tableau (url mono tronquee,
// evenements souscrits en chips, statut actif, creation) avec lien vers le
// journal des deliveries filtre, edition et suppression avec confirmation.
// Un secret fraichement cree arrive en flash (`flash.secret`) et s'affiche
// UNE SEULE FOIS dans un dialogue sans issue (memes mecanique que les cles
// d'API du module Gateway). Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import type {
    BackWebhooksEndpointsPageProps,
    WebhookSecretOrigin,
} from '@/types';
import { WEBHOOK_SECRET_ORIGIN_KEY } from '@/types';
import { create, destroy, edit } from '@/routes/admin/webhooks/endpoints';
import { index as deliveriesIndex } from '@/routes/admin/webhooks/deliveries';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';
import BackEmptyState from '../../components/BackEmptyState.vue';

const props = defineProps<BackWebhooksEndpointsPageProps>();

function formatCreatedAt(value: string): string {
    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/* ------------------------------------------------------------------ */
/* Suppression                                                          */
/* ------------------------------------------------------------------ */

/* Une seule suppression a la fois, comme les autres allers-retours du
   back-office (le serveur renvoie l'index actualise). */
const deletingId = ref<number | null>(null);

function onDestroy(endpointId: number, endpointUrl: string): void {
    if (
        !window.confirm(
            `Supprimer l'endpoint « ${endpointUrl} » ? Les deliveries déjà journalisées seront conservées, mais plus aucun événement ne lui sera envoyé. Cette action est irréversible.`,
        )
    ) {
        return;
    }

    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = endpointId;
    router.delete(destroy({ webhookEndpoint: endpointId }).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

/* ------------------------------------------------------------------ */
/* Dialogue « secret visible une seule fois »                           */
/* ------------------------------------------------------------------ */

/* Le secret arrive en prop flash partagee par le middleware sous
   `flash.secret` apres la redirection de creation ou de regeneration
   (le formulaire pose le contexte dans le stockage de session avant
   chaque envoi, le flash ne portant que la valeur). On l'affiche dans un
   dialogue sans issue : pas de fermeture par Echap ni par clic sur
   l'overlay, seulement « J'ai copie » qui recharge la page pour purger
   le flash. */
const secretDialogOpen = ref(false);
const secretValue = ref('');
const secretOrigin = ref<WebhookSecretOrigin>('creation');
const copied = ref(false);
const copyFailed = ref(false);
const copyButtonEl = ref<HTMLButtonElement | null>(null);

const flashedSecret = computed<string | null>(
    () => props.flash?.secret ?? null,
);

/* Consomme le contexte pose par le formulaire (creation ou regeneration)
   pour adapter l'avertissement affiche sous le secret. */
function consumeSecretOrigin(): WebhookSecretOrigin {
    try {
        const stored = sessionStorage.getItem(WEBHOOK_SECRET_ORIGIN_KEY);

        if (stored === 'creation' || stored === 'regeneration') {
            sessionStorage.removeItem(WEBHOOK_SECRET_ORIGIN_KEY);
            return stored;
        }
    } catch {
        /* Stockage indisponible : contexte par defaut. */
    }

    return 'creation';
}

/* `immediate` : la creation comme la regeneration redirigent depuis le
   formulaire vers cette liste — le composant est monte avec le flash
   deja vivant en props, sans changement ulterieur a observer. */
watch(
    flashedSecret,
    (secret) => {
        if (typeof secret !== 'string' || secret.length === 0) {
            return;
        }

        secretValue.value = secret;
        secretOrigin.value = consumeSecretOrigin();
        copied.value = false;
        copyFailed.value = false;
        secretDialogOpen.value = true;
    },
    { immediate: true },
);

watch(secretDialogOpen, async (open) => {
    if (!open) {
        return;
    }

    await nextTick();
    copyButtonEl.value?.focus();
});

async function copySecret(): Promise<void> {
    copyFailed.value = false;

    try {
        await navigator.clipboard.writeText(secretValue.value);
        copied.value = true;
        return;
    } catch {
        /* Contexte non securise ou permission refusee : repli ci-dessous. */
    }

    const area = document.createElement('textarea');
    area.value = secretValue.value;
    area.setAttribute('readonly', 'true');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();

    let fallback = false;
    try {
        fallback = document.execCommand('copy');
    } catch {
        fallback = false;
    }

    document.body.removeChild(area);
    copied.value = fallback;
    copyFailed.value = !fallback;
}

function onSecretCopied(): void {
    secretDialogOpen.value = false;
    /* Recharge l'index : le flash est consomme cote serveur, la prop
       `secret` disparait des props Inertia. */
    router.reload();
}

/* ------------------------------------------------------------------ */
/* Etat derive                                                          */
/* ------------------------------------------------------------------ */

const hasEndpoints = computed(() => props.endpoints.length > 0);
</script>

<template>
    <BackLayout>
        <Head title="Endpoints webhooks" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Endpoints webhooks
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Poussez les événements du domaine (landing publiée,
                        pricing publié, statut de connecteur) vers vos URLs,
                        avec payloads signés et journal des tentatives.
                    </p>
                </div>

                <a
                    :href="create.url()"
                    class="back-btn back-btn--primary shrink-0"
                >
                    Nouvel endpoint
                </a>
            </header>

            <!-- Etat vide -->
            <BackEmptyState v-if="!hasEndpoints" title="Aucun endpoint">
                Déclarez votre premier endpoint : il recevra les événements
                cochés avec un payload signé (HMAC-SHA256) vérifiable via son
                secret.
                <template #action>
                    <a :href="create.url()" class="back-btn back-btn--primary">
                        Créer un endpoint
                    </a>
                </template>
            </BackEmptyState>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Endpoints webhooks : url, événements souscrits, statut,
                        création et actions
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Endpoint</th>
                            <th scope="col" class="px-6 py-3">Événements</th>
                            <th scope="col" class="px-6 py-3">Statut</th>
                            <th scope="col" class="px-6 py-3">Créé le</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="endpoint in endpoints"
                            :key="endpoint.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p
                                    class="max-w-[18rem] truncate font-mono text-xs text-[var(--color-ink)]"
                                    :title="endpoint.url"
                                >
                                    {{ endpoint.url }}
                                </p>
                                <p
                                    class="mt-0.5 text-xs text-[var(--color-muted)]"
                                >
                                    Créé le
                                    {{ formatCreatedAt(endpoint.createdAt) }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="event in endpoint.events"
                                        :key="event"
                                        class="back-chip"
                                    >
                                        {{ event }}
                                    </span>
                                    <span
                                        v-if="endpoint.events.length === 0"
                                        class="text-[var(--color-muted)]"
                                    >
                                        —
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="
                                        endpoint.active ? 'success' : 'muted'
                                    "
                                >
                                    {{ endpoint.active ? 'Actif' : 'Inactif' }}
                                </BackBadge>
                            </td>
                            <td class="px-6 py-4 text-[var(--color-ink)]">
                                {{ formatCreatedAt(endpoint.createdAt) }}
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <a
                                        :href="
                                            deliveriesIndex.url({
                                                query: {
                                                    endpoint: endpoint.id,
                                                },
                                            })
                                        "
                                        class="back-link"
                                    >
                                        Deliveries ({{
                                            endpoint.deliveriesCount.toLocaleString(
                                                'fr-FR',
                                            )
                                        }})
                                    </a>
                                    <a
                                        :href="
                                            edit({
                                                webhookEndpoint: endpoint.id,
                                            }).url
                                        "
                                        class="back-link"
                                    >
                                        Éditer
                                    </a>
                                    <BackButton
                                        variant="danger"
                                        :loading="deletingId === endpoint.id"
                                        :disabled="
                                            deletingId !== null &&
                                            deletingId !== endpoint.id
                                        "
                                        @click="
                                            onDestroy(endpoint.id, endpoint.url)
                                        "
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

        <!-- Dialogue « secret visible une seule fois » : sans issue, la
             fermeture passe uniquement par « J'ai copié » (rechargement). -->
        <div v-if="secretDialogOpen" class="back-modal-overlay">
            <div
                class="back-modal"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="webhooks-secret-title"
                aria-describedby="webhooks-secret-description"
            >
                <div class="flex flex-col gap-4 px-6 py-6">
                    <h2
                        id="webhooks-secret-title"
                        class="text-lg font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        {{
                            secretOrigin === 'regeneration'
                                ? 'Nouveau secret généré'
                                : 'Copiez ce secret maintenant'
                        }}
                    </h2>
                    <p
                        id="webhooks-secret-description"
                        class="text-sm text-[var(--color-muted)]"
                    >
                        <template v-if="secretOrigin === 'regeneration'">
                            L'ancien secret cesse immédiatement de fonctionner :
                            mettez à jour votre endpoint avec ce nouveau secret.
                            Il ne sera plus jamais affiché.
                        </template>
                        <template v-else>
                            Il ne sera plus jamais affiché : il est conservé
                            chiffré côté serveur (nécessaire pour signer) et
                            n'est jamais réaffiché. Rangez-le immédiatement dans
                            votre gestionnaire de secrets — c'est lui qui signe
                            (HMAC-SHA256) les payloads envoyés à votre endpoint.
                        </template>
                    </p>

                    <p class="back-secret-box">{{ secretValue }}</p>

                    <p v-if="copyFailed" class="back-error" role="alert">
                        Copie impossible : sélectionnez le secret ci-dessus à la
                        souris pour le copier manuellement.
                    </p>
                    <span v-else class="sr-only" role="status">
                        {{
                            copied ? 'Secret copié dans le presse-papiers.' : ''
                        }}
                    </span>

                    <div class="flex items-center justify-end gap-3">
                        <button
                            ref="copyButtonEl"
                            type="button"
                            class="back-btn back-btn--primary"
                            @click="copySecret"
                        >
                            {{ copied ? 'Copié' : 'Copier' }}
                        </button>
                        <BackButton @click="onSecretCopied">
                            J'ai copié
                        </BackButton>
                    </div>
                </div>
            </div>
        </div>
    </BackLayout>
</template>
