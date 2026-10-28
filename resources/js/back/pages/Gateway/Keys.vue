<script setup lang="ts">
// Gestion des cles d'API du module Gateway : tableau shadcn (nom, prefixe
// mono, abilities en badges, dernier usage, statut), creation avec
// affichage du secret UNE SEULE FOIS (Dialog controle, sans issue) et
// revocation confirmee par AlertDialog.
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    ref,
    watch,
    type ComponentPublicInstance,
} from 'vue';
import {
    CheckIcon,
    CopyIcon,
    KeyRoundIcon,
    TriangleAlertIcon,
} from '@lucide/vue';
import type {
    ApiKey,
    ApiKeyForm,
    ApiKeyStatus,
    BackGatewayKeysPageProps,
} from '@/types';
import { API_KEY_ABILITIES } from '@/types';
import { index, revoke, store } from '@/routes/admin/gateway/keys';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
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

const props = defineProps<BackGatewayKeysPageProps>();

/* ------------------------------------------------------------------ */
/* Abilities connues                                                    */
/* ------------------------------------------------------------------ */

const abilityDescriptions: Record<string, string> = {
    'services:read':
        'Lire les modules activés de la plateforme (GET /api/v1/services).',
    'landings:read': 'Lire les landing pages publiées (GET /api/v1/landings).',
    'connectors:read':
        'Lire les connecteurs enregistrés : nom, type et statut (GET /api/v1/connectors).',
};

/* Bascule d'une ability dans le tableau du formulaire : meme semantique
   que les checkboxes natives liees a un tableau (ajout / retrait). */
function toggleAbility(ability: string, checked: boolean): void {
    if (checked) {
        if (!form.abilities.includes(ability)) {
            form.abilities.push(ability);
        }

        return;
    }

    const index = form.abilities.indexOf(ability);
    if (index !== -1) {
        form.abilities.splice(index, 1);
    }
}

/* ------------------------------------------------------------------ */
/* Statut : calcule cote client depuis revokedAt / expiresAt            */
/* ------------------------------------------------------------------ */

const statusLabels: Record<ApiKeyStatus, string> = {
    active: 'Actif',
    revoked: 'Révoquée',
    expired: 'Expirée',
};

const statusVariants: Record<
    ApiKeyStatus,
    'success' | 'secondary' | 'warning'
> = {
    active: 'success',
    revoked: 'secondary',
    expired: 'warning',
};

function apiKeyStatus(key: ApiKey): ApiKeyStatus {
    if (key.revokedAt !== null) {
        return 'revoked';
    }

    if (
        key.expiresAt !== null &&
        new Date(key.expiresAt).getTime() <= Date.now()
    ) {
        return 'expired';
    }

    return 'active';
}

/* ------------------------------------------------------------------ */
/* Dernier usage : relatif en fr-FR, date au-dela d'une semaine         */
/* ------------------------------------------------------------------ */

const relativeFormat = new Intl.RelativeTimeFormat('fr-FR', {
    numeric: 'auto',
});

function formatLastUsed(value: string | null): string {
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

function formatCreatedAt(value: string): string {
    return new Date(value).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

/* ------------------------------------------------------------------ */
/* Revocation                                                           */
/* ------------------------------------------------------------------ */

/* Une seule revocation a la fois, comme les autres allers-retours du
   back-office (le serveur renvoie l'index actualise). La confirmation
   passe par l'AlertDialog de la ligne : l'action declenche la requete. */
const revokingId = ref<number | null>(null);

function onRevoke(key: ApiKey): void {
    if (revokingId.value !== null) {
        return;
    }

    revokingId.value = key.id;
    router.patch(
        revoke({ apiKey: key.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                revokingId.value = null;
            },
        },
    );
}

/* ------------------------------------------------------------------ */
/* Creation d'une cle                                                   */
/* ------------------------------------------------------------------ */

const formOpen = ref(false);

const form = useForm<ApiKeyForm>({
    name: '',
    abilities: [],
    expires_at: null,
});

function openForm(): void {
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function closeForm(): void {
    form.reset();
    form.clearErrors();
    formOpen.value = false;
}

function onSubmit(): void {
    /* Le champ date natif renvoie '' quand il est vide : on n'envoie null. */
    if (form.expires_at === '') {
        form.expires_at = null;
    }

    void form.post(store.url(), { preserveScroll: true });
}

/* ------------------------------------------------------------------ */
/* Dialogue « secret visible une seule fois »                           */
/* ------------------------------------------------------------------ */

/* Le secret arrive en prop flash partagee par le middleware sous
   `flash.secret` apres la redirection de creation. On l'affiche dans un
   Dialog sans issue : pas de fermeture par Echap ni par clic sur
   l'overlay, seulement « J'ai copie » qui recharge la page pour purger le
   flash. Le watch couvre la mise a jour des props du composant deja
   monte ; un flash encore vivant lors d'un chargement complet s'ouvre
   via la valeur initiale des props de page. */
const secretDialogOpen = ref(false);
const secretValue = ref('');
const copied = ref(false);
const copyFailed = ref(false);
const copyButtonEl = ref<ComponentPublicInstance | null>(null);

const flashedSecret = computed<string | null>(
    () => props.flash?.secret ?? null,
);

watch(flashedSecret, (secret) => {
    if (typeof secret !== 'string' || secret.length === 0) {
        return;
    }

    secretValue.value = secret;
    copied.value = false;
    copyFailed.value = false;
    closeForm();
    secretDialogOpen.value = true;
});

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
    /* Recharge l'index : le flash est consomme cote serveur, la prop
       `secret` disparait des props Inertia. */
    router.reload();
}

/* ------------------------------------------------------------------ */
/* Etat derive                                                          */
/* ------------------------------------------------------------------ */

const hasKeys = computed(() => props.keys.length > 0);
</script>

<template>
    <BackLayout>
        <Head title="Clés d'API" />

        <div class="flex flex-col gap-6">
            <BackPageHeader
                title="Clés d'API"
                description="Créez et révoquez les clés qui sécurisent les endpoints /api/v1 : abilities, expiration et révocation immédiate."
            >
                <template #actions>
                    <Button
                        variant="outline"
                        :aria-expanded="formOpen"
                        aria-controls="new-key-form"
                        @click="formOpen ? closeForm() : openForm()"
                    >
                        {{ formOpen ? 'Annuler' : 'Nouvelle clé' }}
                    </Button>
                </template>
            </BackPageHeader>

            <!-- Formulaire de creation -->
            <Card v-if="formOpen" id="new-key-form">
                <CardHeader>
                    <CardTitle>Nouvelle clé d'API</CardTitle>
                    <CardDescription>
                        Le secret ne sera affiché qu'une seule fois, à la
                        création : seul son hash est conservé côté serveur.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        class="flex flex-col gap-6"
                        @submit.prevent="onSubmit"
                    >
                        <Field>
                            <FieldLabel for="name">Nom</FieldLabel>
                            <Input
                                id="name"
                                name="name"
                                v-model="form.name"
                                required
                                autocomplete="off"
                                placeholder="Ex. Intégration mobile"
                            />
                            <FieldDescription>
                                Ex. Intégration mobile, script d'import nightly.
                            </FieldDescription>
                            <FieldError
                                :errors="
                                    form.errors.name
                                        ? [form.errors.name]
                                        : undefined
                                "
                            />
                        </Field>

                        <fieldset class="flex flex-col gap-3">
                            <legend class="text-sm font-medium">
                                Abilities
                            </legend>
                            <div
                                v-for="ability in API_KEY_ABILITIES"
                                :key="ability"
                                class="flex items-start gap-2.5"
                            >
                                <Checkbox
                                    :id="`ability-${ability}`"
                                    :model-value="
                                        form.abilities.includes(ability)
                                    "
                                    class="mt-0.5"
                                    @update:model-value="
                                        (checked) =>
                                            toggleAbility(
                                                ability,
                                                checked === true,
                                            )
                                    "
                                />
                                <div class="flex flex-col gap-0.5">
                                    <label
                                        :for="`ability-${ability}`"
                                        class="font-mono text-xs font-medium"
                                    >
                                        {{ ability }}
                                    </label>
                                    <p class="text-xs text-muted-foreground">
                                        {{ abilityDescriptions[ability] }}
                                    </p>
                                </div>
                            </div>
                            <FieldError
                                :errors="
                                    form.errors.abilities
                                        ? [form.errors.abilities]
                                        : undefined
                                "
                            />
                        </fieldset>

                        <Field>
                            <FieldLabel for="expires_at">Expiration</FieldLabel>
                            <Input
                                id="expires_at"
                                name="expires_at"
                                type="date"
                                :model-value="form.expires_at ?? ''"
                                @update:model-value="
                                    (value) =>
                                        (form.expires_at =
                                            value === '' ? null : String(value))
                                "
                            />
                            <FieldDescription>
                                Optionnel : la clé sera rejetée automatiquement
                                après cette date.
                            </FieldDescription>
                            <FieldError
                                :errors="
                                    form.errors.expires_at
                                        ? [form.errors.expires_at]
                                        : undefined
                                "
                            />
                        </Field>

                        <Alert variant="warning">
                            <TriangleAlertIcon />
                            <AlertDescription>
                                Chaque clé donne accès aux endpoints
                                <code class="font-mono">/api/v1</code> selon ses
                                abilities. Stockez son secret dans un
                                gestionnaire de secrets : il ne sera jamais
                                réaffiché après la création.
                            </AlertDescription>
                        </Alert>

                        <div class="flex items-center justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                @click="closeForm"
                            >
                                Annuler
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Spinner
                                    v-if="form.processing"
                                    class="size-4"
                                />
                                Créer la clé
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Etat vide -->
            <Empty v-if="!hasKeys" class="border border-dashed">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <KeyRoundIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucune clé API</EmptyTitle>
                    <EmptyDescription>
                        Créez votre première clé pour consommer les endpoints
                        sécurisés du module Gateway.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button @click="openForm">Créer une clé API</Button>
                </EmptyContent>
            </Empty>

            <!-- Tableau -->
            <div v-else class="overflow-hidden rounded-xl border">
                <Table>
                    <TableCaption class="sr-only">
                        Clés d'API : nom, préfixe, abilities, dernier usage,
                        statut et actions
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Clé
                            </TableHead>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Abilities
                            </TableHead>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Dernier usage
                            </TableHead>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Statut
                            </TableHead>
                            <TableHead
                                class="px-4 text-right text-xs tracking-wide uppercase"
                            >
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="key in props.keys" :key="key.id">
                            <TableCell class="px-4 py-4 align-top">
                                <p class="font-medium">
                                    {{ key.name }}
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-xs text-muted-foreground"
                                >
                                    {{ key.prefix }}…
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Créée le
                                    {{ formatCreatedAt(key.createdAt) }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    <Badge
                                        v-for="ability in key.abilities"
                                        :key="ability"
                                        variant="outline"
                                        class="font-mono"
                                    >
                                        {{ ability }}
                                    </Badge>
                                    <span
                                        v-if="key.abilities.length === 0"
                                        class="text-muted-foreground"
                                    >
                                        —
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                {{ formatLastUsed(key.lastUsedAt) }}
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <Badge
                                    :variant="statusVariants[apiKeyStatus(key)]"
                                >
                                    {{ statusLabels[apiKeyStatus(key)] }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right align-top">
                                <AlertDialog
                                    v-if="apiKeyStatus(key) === 'active'"
                                >
                                    <AlertDialogTrigger as-child>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            :disabled="
                                                revokingId !== null &&
                                                revokingId !== key.id
                                            "
                                        >
                                            <Spinner
                                                v-if="revokingId === key.id"
                                                class="size-4"
                                            />
                                            Révoquer
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>
                                                Révoquer la clé «
                                                {{ key.name }} » ?
                                            </AlertDialogTitle>
                                            <AlertDialogDescription>
                                                Tout appel avec cette clé sera
                                                immédiatement rejeté.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>
                                                Annuler
                                            </AlertDialogCancel>
                                            <AlertDialogAction
                                                class="bg-destructive text-white hover:bg-destructive/90"
                                                @click="onRevoke(key)"
                                            >
                                                Révoquer
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                >
                                    —
                                </span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

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
                    <DialogTitle> Copiez ce secret maintenant </DialogTitle>
                    <DialogDescription>
                        Il ne sera plus jamais affiché : seul son hash est
                        conservé côté serveur. Rangez-le immédiatement dans
                        votre gestionnaire de secrets.
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
