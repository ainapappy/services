<script setup lang="ts">
// Formulaire de creation / edition d'un endpoint webhook (shadcn) : url
// http(s), cases a cocher des evenements disponibles (libelles francais),
// bascule d'activation. Le secret n'est jamais rapatrie : en edition, une
// section dediee permet de le regenerer (confirmation explicite :
// l'ancien secret cesse de fonctionner). Nouveau secret affiche UNE SEULE
// FOIS via le flash `flash.secret`, dans le meme dialogue sans issue que
// les cles d'API du module Gateway.
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    ref,
    watch,
    type ComponentPublicInstance,
} from 'vue';
import { CheckIcon, CopyIcon, TriangleAlertIcon } from '@lucide/vue';
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
import BackPageHeader from '../../components/BackPageHeader.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';

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

/* Bascule d'un evenement dans le formulaire : meme semantique que les
   checkboxes natives liees a un tableau (ajout / retrait). */
function setEventChecked(event: string, checked: boolean): void {
    if (checked) {
        if (!form.events.includes(event)) {
            form.events.push(event);
        }

        return;
    }

    const index = form.events.indexOf(event);
    if (index !== -1) {
        form.events.splice(index, 1);
    }
}

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

/* La confirmation passe par l'AlertDialog de la section : l'action
   declenche la requete. */
const regenerating = ref(false);

function onRegenerate(): void {
    if (existing.value === null || regenerating.value) {
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
const copyButtonEl = ref<ComponentPublicInstance | null>(null);

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
    copyButtonEl.value?.$el?.focus();
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
            <div class="flex flex-col gap-1">
                <p class="text-sm text-muted-foreground">
                    <a
                        :href="index.url()"
                        class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                    >
                        Endpoints webhooks
                    </a>
                    <span class="mx-1">/</span>
                    <span>{{ isEdit ? 'Modifier' : 'Nouvel endpoint' }}</span>
                </p>

                <BackPageHeader
                    :title="
                        isEdit
                            ? 'Modifier l’endpoint'
                            : 'Nouvel endpoint webhook'
                    "
                />
            </div>

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Identite -->
                <Card>
                    <CardHeader>
                        <CardTitle>Endpoint</CardTitle>
                        <CardDescription>
                            Destination des événements : une URL http(s) qui
                            recevra des POST signés.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5">
                        <Field>
                            <FieldLabel for="url">
                                URL de l'endpoint
                            </FieldLabel>
                            <Input
                                id="url"
                                name="url"
                                type="url"
                                v-model="form.url"
                                required
                                autocomplete="off"
                                placeholder="https://exemple.com/webhooks/services"
                            />
                            <FieldDescription>
                                Doit répondre en HTTP ou HTTPS : chaque
                                événement souscrit y est envoyé en POST, signé
                                via les en-têtes X-Signature, X-Event et
                                X-Timestamp.
                            </FieldDescription>
                            <FieldError
                                :errors="
                                    form.errors.url
                                        ? [form.errors.url]
                                        : undefined
                                "
                            />
                        </Field>

                        <fieldset class="flex flex-col gap-3">
                            <legend class="text-sm font-medium">
                                Événements souscrits
                            </legend>
                            <div
                                v-for="event in WEBHOOK_EVENTS"
                                :key="event"
                                class="flex items-start gap-2.5"
                            >
                                <Checkbox
                                    :id="`event-${event}`"
                                    :model-value="form.events.includes(event)"
                                    class="mt-0.5"
                                    @update:model-value="
                                        (checked) =>
                                            setEventChecked(
                                                event,
                                                checked === true,
                                            )
                                    "
                                />
                                <div class="flex flex-col gap-0.5">
                                    <FieldLabel :for="`event-${event}`">
                                        {{ WEBHOOK_EVENT_LABELS[event] }}
                                    </FieldLabel>
                                    <span
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ event }}
                                    </span>
                                    <FieldDescription>
                                        {{ WEBHOOK_EVENT_DESCRIPTIONS[event] }}
                                    </FieldDescription>
                                </div>
                            </div>
                            <FieldError
                                :errors="
                                    form.errors.events
                                        ? [form.errors.events]
                                        : undefined
                                "
                            />
                        </fieldset>

                        <div class="flex flex-col gap-2">
                            <div class="flex items-start gap-2.5">
                                <Switch
                                    id="endpoint-active"
                                    v-model="form.active"
                                />
                                <div class="flex flex-col gap-0.5">
                                    <FieldLabel for="endpoint-active">
                                        Endpoint actif
                                        <Badge
                                            :variant="
                                                form.active
                                                    ? 'success'
                                                    : 'secondary'
                                            "
                                        >
                                            {{
                                                form.active
                                                    ? 'Actif'
                                                    : 'Inactif'
                                            }}
                                        </Badge>
                                    </FieldLabel>
                                    <FieldDescription>
                                        Un endpoint inactif ne reçoit plus aucun
                                        événement ; ses deliveries passées
                                        restent dans le journal.
                                    </FieldDescription>
                                </div>
                            </div>
                            <FieldError
                                :errors="
                                    form.errors.active
                                        ? [form.errors.active]
                                        : undefined
                                "
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Secret (edition uniquement) -->
                <Card v-if="isEdit">
                    <CardHeader>
                        <CardTitle>Secret de signature</CardTitle>
                        <CardDescription>
                            Sert à signer les payloads (HMAC-SHA256) ; il n'est
                            jamais réaffiché après sa création.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <Alert variant="warning">
                            <TriangleAlertIcon />
                            <AlertDescription>
                                Régénérer le secret est une opération sensible :
                                l'ancien secret cesse immédiatement de
                                fonctionner et les payloads signés avec lui
                                seront rejetés par quiconque vérifie la
                                signature. Le nouveau secret ne sera affiché
                                qu'une seule fois.
                            </AlertDescription>
                        </Alert>

                        <div class="flex justify-end">
                            <AlertDialog>
                                <AlertDialogTrigger as-child>
                                    <Button
                                        variant="destructive"
                                        :disabled="regenerating"
                                    >
                                        <Spinner
                                            v-if="regenerating"
                                            class="size-4"
                                        />
                                        Régénérer le secret
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>
                                            Régénérer le secret de cet endpoint
                                            ?
                                        </AlertDialogTitle>
                                        <AlertDialogDescription>
                                            L'ancien secret cesse immédiatement
                                            de fonctionner : les payloads signés
                                            avec lui seront rejetés. Le nouveau
                                            secret ne sera affiché qu'une seule
                                            fois.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>
                                            Annuler
                                        </AlertDialogCancel>
                                        <AlertDialogAction as-child>
                                            <Button
                                                variant="destructive"
                                                :disabled="regenerating"
                                                @click="onRegenerate"
                                            >
                                                <Spinner
                                                    v-if="regenerating"
                                                    class="size-4"
                                                />
                                                Régénérer le secret
                                            </Button>
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2">
                    <Button as-child variant="outline">
                        <a :href="index.url()">Annuler</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="size-4" />
                        {{
                            isEdit
                                ? 'Enregistrer les modifications'
                                : 'Créer l’endpoint'
                        }}
                    </Button>
                </div>
            </form>
        </section>

        <!-- Dialogue « secret visible une seule fois » : sans issue, la
             fermeture passe uniquement par « J'ai copié » (rechargement). -->
        <Dialog
            :open="secretDialogOpen"
            @update:open="secretDialogOpen = $event"
        >
            <DialogContent
                class="sm:max-w-md"
                :show-close-button="false"
                role="alertdialog"
                aria-modal="true"
                @escape-key-down.prevent
                @pointer-down-outside.prevent
                @interact-outside.prevent
            >
                <DialogHeader>
                    <DialogTitle>
                        {{
                            regenerated
                                ? 'Nouveau secret généré'
                                : 'Copiez ce secret maintenant'
                        }}
                    </DialogTitle>
                    <DialogDescription>
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
                    </DialogDescription>
                </DialogHeader>

                <p
                    class="rounded-md bg-muted px-3 py-2 font-mono text-xs break-all select-all"
                >
                    {{ secretValue }}
                </p>

                <p
                    v-if="copyFailed"
                    class="text-sm text-destructive"
                    role="alert"
                >
                    Copie impossible : sélectionnez le secret ci-dessus à la
                    souris pour le copier manuellement.
                </p>
                <span v-else class="sr-only" role="status">
                    {{ copied ? 'Secret copié dans le presse-papiers.' : '' }}
                </span>

                <DialogFooter>
                    <Button
                        ref="copyButtonEl"
                        type="button"
                        @click="copySecret"
                    >
                        <CheckIcon v-if="copied" />
                        <CopyIcon v-else />
                        {{ copied ? 'Copié' : 'Copier' }}
                    </Button>
                    <Button type="button" @click="onSecretCopied">
                        J'ai copié
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </BackLayout>
</template>
