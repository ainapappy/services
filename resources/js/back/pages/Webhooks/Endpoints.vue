<script setup lang="ts">
// Liste des endpoints webhooks sortants : tableau shadcn (url mono
// tronquee, evenements souscrits en badges, statut actif, creation) avec
// lien vers le journal des deliveries filtre et menu d'actions (edition,
// suppression confirmee par AlertDialog). Un secret fraichement cree ou
// regenere arrive en flash (`flash.secret`) et s'affiche UNE SEULE FOIS
// dans un dialogue sans issue (mecanique des cles d'API du module
// Gateway).
import { Head, router } from '@inertiajs/vue3';
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
    EllipsisVerticalIcon,
    PencilIcon,
    Trash2Icon,
    WebhookIcon,
} from '@lucide/vue';
import type {
    BackWebhooksEndpointsPageProps,
    WebhookSecretOrigin,
} from '@/types';
import { WEBHOOK_SECRET_ORIGIN_KEY } from '@/types';
import { create, destroy, edit } from '@/routes/admin/webhooks/endpoints';
import { index as deliveriesIndex } from '@/routes/admin/webhooks/deliveries';
import BackLayout from '../../layouts/BackLayout.vue';
import BackPageHeader from '../../components/BackPageHeader.vue';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
   back-office (le serveur renvoie l'index actualise). La confirmation
   passe par l'AlertDialog de la ligne : l'action declenche la requete. */
const deletingId = ref<number | null>(null);

function onDestroy(endpointId: number): void {
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
const copyButtonEl = ref<ComponentPublicInstance | null>(null);

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

const hasEndpoints = computed(() => props.endpoints.length > 0);
</script>

<template>
    <BackLayout>
        <Head title="Endpoints webhooks" />

        <section class="flex flex-col gap-6">
            <BackPageHeader
                title="Endpoints webhooks"
                description="Poussez les événements du domaine (landing publiée, pricing publié, statut de connecteur) vers vos URLs, avec payloads signés et journal des tentatives."
            >
                <template #actions>
                    <Button as-child>
                        <a :href="create.url()">Nouvel endpoint</a>
                    </Button>
                </template>
            </BackPageHeader>

            <!-- Etat vide -->
            <Empty v-if="!hasEndpoints" class="border border-dashed">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <WebhookIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun endpoint</EmptyTitle>
                    <EmptyDescription>
                        Déclarez votre premier endpoint : il recevra les
                        événements cochés avec un payload signé (HMAC-SHA256)
                        vérifiable via son secret.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <a :href="create.url()">Créer un endpoint</a>
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Tableau -->
            <div v-else class="overflow-hidden rounded-xl border">
                <Table>
                    <TableCaption class="sr-only">
                        Endpoints webhooks : url, événements souscrits, statut,
                        création et actions
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Endpoint
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Événements
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Statut
                            </TableHead>
                            <TableHead
                                class="h-12 px-4 text-xs tracking-wide uppercase"
                            >
                                Créé le
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
                            v-for="endpoint in endpoints"
                            :key="endpoint.id"
                        >
                            <TableCell class="px-4 py-4 align-top">
                                <p
                                    class="max-w-[18rem] truncate font-mono text-xs"
                                    :title="endpoint.url"
                                >
                                    {{ endpoint.url }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Créé le
                                    {{ formatCreatedAt(endpoint.createdAt) }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    <Badge
                                        v-for="event in endpoint.events"
                                        :key="event"
                                        variant="outline"
                                        class="font-mono"
                                    >
                                        {{ event }}
                                    </Badge>
                                    <span
                                        v-if="endpoint.events.length === 0"
                                        class="text-muted-foreground"
                                    >
                                        —
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <Badge
                                    :variant="
                                        endpoint.active
                                            ? 'success'
                                            : 'secondary'
                                    "
                                >
                                    {{ endpoint.active ? 'Actif' : 'Inactif' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                {{ formatCreatedAt(endpoint.createdAt) }}
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right align-top">
                                <div class="flex justify-end">
                                    <AlertDialog>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    :aria-label="`Actions pour l'endpoint ${endpoint.url}`"
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
                                                                webhookEndpoint:
                                                                    endpoint.id,
                                                            }).url
                                                        "
                                                    >
                                                        <PencilIcon />
                                                        Éditer
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
                                                    Supprimer l'endpoint «
                                                    {{ endpoint.url }} » ?
                                                </AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Les deliveries déjà
                                                    journalisées seront
                                                    conservées, mais plus aucun
                                                    événement ne lui sera
                                                    envoyé. Cette action est
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
                                                            onDestroy(
                                                                endpoint.id,
                                                            )
                                                        "
                                                    >
                                                        <Spinner
                                                            v-if="
                                                                deletingId ===
                                                                endpoint.id
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
            </div>
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
                            secretOrigin === 'regeneration'
                                ? 'Nouveau secret généré'
                                : 'Copiez ce secret maintenant'
                        }}
                    </DialogTitle>
                    <DialogDescription>
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
