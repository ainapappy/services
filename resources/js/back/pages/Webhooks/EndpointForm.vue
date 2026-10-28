<script setup lang="ts">
// Formulaire de creation / edition d'un endpoint webhook : url http(s),
// cases a cocher des evenements disponibles (libelles francais), bascule
// d'activation. Le secret n'est jamais rapatrie : en edition, une section
// dediee permet de le regenerer (confirmation explicite : l'ancien secret
// cesse de fonctionner). Nouveau secret affiche UNE SEULE FOIS via le
// flash `flash.secret`, dans le meme dialogue sans issue que les cles
// d'API du module Gateway. Aucun import hors arbre back/.
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import type {
    BackWebhooksEndpointFormPageProps,
    WebhookSecretOrigin,
} from '@/types';
import {
    WEBHOOK_EVENT_DESCRIPTIONS,
    WEBHOOK_EVENT_LABELS,
    WEBHOOK_EVENTS,
    WEBHOOK_SECRET_ORIGIN_KEY,
} from '@/types';
import {
    index,
    regenerate,
    store,
    update,
} from '@/routes/admin/webhooks/endpoints';
import BackLayout from '../../layouts/BackLayout.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackField from '../../components/BackField.vue';
import BackInput from '../../components/BackInput.vue';
import BackStatus from '../../components/BackStatus.vue';
import BackToggle from '../../components/BackToggle.vue';

const props = defineProps<BackWebhooksEndpointFormPageProps>();

/* ------------------------------------------------------------------ */
/* Etat edite                                                          */
/* ------------------------------------------------------------------ */

const existing = computed(() => props.endpoint ?? null);
const isEdit = computed(() => existing.value !== null);

type EndpointFormData = {
    url: string;
    events: string[];
    active: boolean;
};

function toFormData(value: typeof existing.value): EndpointFormData {
    return {
        url: value?.url ?? '',
        events: value ? [...value.events] : [],
        active: value?.active ?? true,
    };
}

const form = useForm<EndpointFormData>(toFormData(existing.value));

/* Navigation creation -> edition (meme composant reutilise par Inertia) :
   on resynchronise le formulaire quand la page cible change. */
watch(
    () => props.endpoint?.id,
    () => {
        const next = toFormData(existing.value);
        form.url = next.url;
        form.events = next.events;
        form.active = next.active;
        form.clearErrors();
    },
);

/* ------------------------------------------------------------------ */
/* Soumission                                                           */
/* ------------------------------------------------------------------ */

/* Pose le contexte du secret avant l'envoi : le serveur redirige vers la
   liste avec le flash `secret`, et c'est le dialogue de cette liste (ou
   celui-ci si la redirection revient ici) qui adapte son avertissement
   (creation : rien de casse ; regeneration : l'ancien secret meurt). */
function storeSecretOrigin(origin: WebhookSecretOrigin): void {
    try {
        sessionStorage.setItem(WEBHOOK_SECRET_ORIGIN_KEY, origin);
    } catch {
        /* Stockage indisponible : le dialogue restera sur le message
           generique de creation. */
    }
}

function onSubmit(): void {
    storeSecretOrigin('creation');

    if (isEdit.value && existing.value !== null) {
        void form.patch(update({ webhookEndpoint: existing.value.id }).url);
        return;
    }

    void form.post(store.url());
}

/* ------------------------------------------------------------------ */
/* Regeneration du secret (edition uniquement)                          */
/* ------------------------------------------------------------------ */

const regenerating = ref(false);

function onRegenerate(): void {
    if (existing.value === null || regenerating.value) {
        return;
    }

    if (
        !window.confirm(
            'Régénérer le secret de cet endpoint ? L’ancien secret cesse immédiatement de fonctionner : les payloads signés avec lui seront rejetés. Le nouveau secret ne sera affiché qu’une seule fois.',
        )
    ) {
        return;
    }

    regenerating.value = true;
    storeSecretOrigin('regeneration');
    router.post(
        regenerate({ webhookEndpoint: existing.value.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                regenerating.value = false;
            },
        },
    );
}

/* ------------------------------------------------------------------ */
/* Dialogue « secret visible une seule fois »                           */
/* ------------------------------------------------------------------ */

/* Contexte du secret affiche : pose avant chaque envoi (voir
   storeSecretOrigin), consomme a l'ouverture du dialogue. */
const secretOrigin = ref<WebhookSecretOrigin>('creation');

const regenerated = computed(() => secretOrigin.value === 'regeneration');

/* Consomme le contexte pose avant l'envoi (creation ou regeneration). */
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

/* Le secret arrive en prop flash partagee par le middleware sous
   `flash.secret`. Dialogue sans issue : pas de fermeture par Echap ni par
   clic sur l'overlay, seulement « J'ai copie » qui recharge la page pour
   purger le flash. `immediate` couvre un flash deja vivant au montage du
   composant (redirection serveur vers cette page) ; le watch couvre la
   mise a jour des props du composant deja monte. */
const secretDialogOpen = ref(false);
const secretValue = ref('');
const copied = ref(false);
const copyFailed = ref(false);
const copyButtonEl = ref<HTMLButtonElement | null>(null);

const flashedSecret = computed<string | null>(
    () => props.flash?.secret ?? null,
);

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
    secretOrigin.value = 'creation';
    /* Recharge la page : le flash est consomme cote serveur, la prop
       `secret` disparait des props Inertia. */
    router.reload();
}
</script>

<template>
    <BackLayout>
        <Head
            :title="isEdit ? 'Modifier l’endpoint' : 'Nouvel endpoint webhook'"
        />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <p class="text-sm text-[var(--color-muted)]">
                    <a :href="index.url()" class="back-link">
                        Endpoints webhooks
                    </a>
                    <span class="mx-1">/</span>
                    <span>{{ isEdit ? 'Modifier' : 'Nouvel endpoint' }}</span>
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    {{
                        isEdit
                            ? 'Modifier l’endpoint'
                            : 'Nouvel endpoint webhook'
                    }}
                </h1>
            </header>

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Identite -->
                <BackCard
                    title="Endpoint"
                    description="Destination des événements : une URL http(s) qui recevra des POST signés."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="URL de l'endpoint"
                            field-id="url"
                            :error="form.errors.url"
                            help="Doit répondre en HTTP ou HTTPS : chaque événement souscrit y est envoyé en POST, signé via les en-têtes X-Signature, X-Event et X-Timestamp."
                        >
                            <BackInput
                                id="url"
                                name="url"
                                type="url"
                                v-model="form.url"
                                required
                                autocomplete="off"
                                placeholder="https://exemple.com/webhooks/services"
                            />
                        </BackField>

                        <fieldset class="flex flex-col gap-2.5">
                            <legend class="back-label mb-1">
                                Événements souscrits
                            </legend>
                            <label
                                v-for="event in WEBHOOK_EVENTS"
                                :key="event"
                                class="flex items-start gap-2.5 text-sm text-[var(--color-ink)]"
                                :for="`event-${event}`"
                            >
                                <input
                                    :id="`event-${event}`"
                                    name="events[]"
                                    type="checkbox"
                                    :value="event"
                                    v-model="form.events"
                                    class="back-checkbox mt-0.5"
                                />
                                <span class="flex flex-col">
                                    <span class="font-medium">
                                        {{ WEBHOOK_EVENT_LABELS[event] }}
                                    </span>
                                    <span
                                        class="font-mono text-xs text-[var(--color-muted)]"
                                    >
                                        {{ event }}
                                    </span>
                                    <span
                                        class="text-xs text-[var(--color-muted)]"
                                    >
                                        {{ WEBHOOK_EVENT_DESCRIPTIONS[event] }}
                                    </span>
                                </span>
                            </label>
                            <p v-if="form.errors.events" class="back-error">
                                {{ form.errors.events }}
                            </p>
                        </fieldset>

                        <div class="flex items-center gap-3">
                            <BackToggle
                                :checked="form.active"
                                label="Endpoint actif"
                                @toggle="form.active = !form.active"
                            />
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-medium text-[var(--color-ink)]"
                                >
                                    {{ form.active ? 'Actif' : 'Inactif' }}
                                </span>
                                <span class="text-xs text-[var(--color-muted)]">
                                    Un endpoint inactif ne reçoit plus aucun
                                    événement ; ses deliveries passées restent
                                    dans le journal.
                                </span>
                            </div>
                            <p v-if="form.errors.active" class="back-error">
                                {{ form.errors.active }}
                            </p>
                        </div>
                    </div>
                </BackCard>

                <!-- Secret (edition uniquement) -->
                <BackCard
                    v-if="isEdit"
                    title="Secret de signature"
                    description="Sert à signer les payloads (HMAC-SHA256) ; il n'est jamais réaffiché après sa création."
                >
                    <div class="flex flex-col gap-4">
                        <BackStatus tone="warning">
                            Régénérer le secret est une opération sensible :
                            l'ancien secret cesse immédiatement de fonctionner
                            et les payloads signés avec lui seront rejetés par
                            quiconque vérifie la signature. Le nouveau secret ne
                            sera affiché qu'une seule fois.
                        </BackStatus>

                        <div class="flex items-center justify-end">
                            <BackButton
                                variant="danger"
                                :loading="regenerating"
                                @click="onRegenerate"
                            >
                                Régénérer le secret
                            </BackButton>
                        </div>
                    </div>
                </BackCard>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a :href="index.url()" class="back-btn back-btn--secondary">
                        Annuler
                    </a>
                    <BackButton type="submit" :loading="form.processing">
                        {{
                            isEdit
                                ? 'Enregistrer les modifications'
                                : 'Créer l’endpoint'
                        }}
                    </BackButton>
                </div>
            </form>
        </section>

        <!-- Dialogue « secret visible une seule fois » : sans issue, la
             fermeture passe uniquement par « J'ai copié » (rechargement). -->
        <div v-if="secretDialogOpen" class="back-modal-overlay">
            <div
                class="back-modal"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="webhooks-form-secret-title"
                aria-describedby="webhooks-form-secret-description"
            >
                <div class="flex flex-col gap-4 px-6 py-6">
                    <h2
                        id="webhooks-form-secret-title"
                        class="text-lg font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        {{
                            regenerated
                                ? 'Nouveau secret généré'
                                : 'Copiez ce secret maintenant'
                        }}
                    </h2>
                    <p
                        id="webhooks-form-secret-description"
                        class="text-sm text-[var(--color-muted)]"
                    >
                        <template v-if="regenerated">
                            L'ancien secret cesse immédiatement de fonctionner :
                            mettez à jour votre endpoint avec ce nouveau secret.
                            Il ne sera plus jamais affiché.
                        </template>
                        <template v-else>
                            Il ne sera plus jamais affiché : il est conservé
                            chiffré côté serveur (nécessaire pour signer) et
                            n'est jamais réaffiché. Rangez-le immédiatement dans
                            votre gestionnaire de secrets.
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
