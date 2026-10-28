<script setup lang="ts">
// Liste des connecteurs : tableau shadcn (nom + url mono tronquee, type en
// badge, statut en badge, dernier test relatif) avec test de connexion par
// ligne (spinner pendant l'aller-retour, resultat rendu en Alert dis-
// missable via le flash `testResult`), edition et suppression confirmee
// par AlertDialog.
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import {
    CableIcon,
    CircleCheckIcon,
    CircleXIcon,
    InfoIcon,
    XIcon,
} from '@lucide/vue';
import type {
    BackConnectorsIndexPageProps,
    ConnectorListItem,
    ConnectorStatus,
    ConnectorType,
} from '@/types';
import { create, destroy, edit, test } from '@/routes/admin/connectors';
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

const props = defineProps<BackConnectorsIndexPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants                                                 */
/* ------------------------------------------------------------------ */

const typeLabels: Record<ConnectorType, string> = {
    wordpress: 'WordPress',
    caren: 'Caren',
    erpen: 'Erpen',
    custom: 'Custom',
};

const statusLabels: Record<ConnectorStatus, string> = {
    connected: 'Connecté',
    error: 'Erreur',
    unknown: 'Inconnu',
};

const statusVariants: Record<
    ConnectorStatus,
    'success' | 'destructive' | 'secondary'
> = {
    connected: 'success',
    error: 'destructive',
    unknown: 'secondary',
};

/* ------------------------------------------------------------------ */
/* Dernier test : relatif en fr-FR, date au-dela d'une semaine          */
/* ------------------------------------------------------------------ */

const relativeFormat = new Intl.RelativeTimeFormat('fr-FR', {
    numeric: 'auto',
});

function formatLastTested(value: string | null): string {
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

function formatLatency(latencyMs: number): string {
    return `${latencyMs.toLocaleString('fr-FR')} ms`;
}

/* ------------------------------------------------------------------ */
/* Test de connexion                                                    */
/* ------------------------------------------------------------------ */

/* Un seul test a la fois, comme les autres allers-retours du back-office
   (le serveur renvoie l'index actualise avec le flash `testResult`). */
const testingId = ref<number | null>(null);

function onTest(connector: ConnectorListItem): void {
    if (testingId.value !== null) {
        return;
    }

    testingId.value = connector.id;
    router.post(
        test({ connector: connector.id }).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                testingId.value = null;
            },
        },
    );
}

/* ------------------------------------------------------------------ */
/* Resultat du test : Alert verte / rouge, dismissable                  */
/* ------------------------------------------------------------------ */

/* Le flash arrive en prop partagee par le middleware sous
   `flash.testResult` apres la redirection du test. Il est affiche puis
   masque automatiquement apres 8 s (ou via son bouton de fermeture) ;
   le watch couvre la mise a jour des props du composant deja monte,
   l'appel initial couvre un chargement complet avec le flash vivant. */
const testResult = computed(() => props.flash?.testResult ?? null);
const testResultVisible = ref(testResult.value !== null);
let testResultTimer: number | undefined;

/* Succes vert, echec rouge, test en file d'attente neutre. */
const testResultAlertVariant = computed<'success' | 'destructive' | 'default'>(
    () => {
        if (testResult.value?.status === 'connected') {
            return 'success';
        }

        return testResult.value?.status === 'error' ? 'destructive' : 'default';
    },
);

function announceTestResult(): void {
    if (testResult.value === null) {
        return;
    }

    testResultVisible.value = true;
    window.clearTimeout(testResultTimer);
    testResultTimer = window.setTimeout(dismissTestResult, 8000);
}

function dismissTestResult(): void {
    testResultVisible.value = false;
    window.clearTimeout(testResultTimer);
}

announceTestResult();
watch(testResult, () => announceTestResult());
onBeforeUnmount(() => window.clearTimeout(testResultTimer));

/* ------------------------------------------------------------------ */
/* Suppression                                                          */
/* ------------------------------------------------------------------ */

const deletingId = ref<number | null>(null);

function onDestroy(connector: ConnectorListItem): void {
    if (deletingId.value !== null) {
        return;
    }

    deletingId.value = connector.id;
    router.delete(destroy({ connector: connector.id }).url, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <BackLayout>
        <Head title="Connecteurs" />

        <div class="flex flex-col gap-6">
            <BackPageHeader
                title="Connecteurs"
                description="Intégrez vos services externes (WordPress, Caren, Erpen ou API libre) : credentials chiffrés au repos et test de connexion manuel."
            >
                <template #actions>
                    <Button as-child>
                        <a :href="create.url()">Nouveau connecteur</a>
                    </Button>
                </template>
            </BackPageHeader>

            <!-- Resultat du dernier test de connexion : vert (succes,
                 avec latence), rouge (echec, message sur) ou neutre
                 (test parti en file d'attente, resultat differe). -->
            <Alert
                v-if="testResult !== null && testResultVisible"
                :variant="testResultAlertVariant"
                :role="testResult.status === 'connected' ? 'status' : 'alert'"
                class="pr-12"
            >
                <CircleCheckIcon v-if="testResult.status === 'connected'" />
                <CircleXIcon v-else-if="testResult.status === 'error'" />
                <InfoIcon v-else />
                <AlertDescription>
                    <template v-if="testResult.status === 'connected'">
                        Connexion réussie ({{
                            formatLatency(testResult.latencyMs)
                        }}).
                    </template>
                    <template v-else-if="testResult.status === 'error'">
                        Échec :
                        {{
                            testResult.message ??
                            'le connecteur n’a pas répondu au test'
                        }}
                    </template>
                    <template v-else>
                        Test de connexion lancé : le résultat s’affichera dans
                        la liste dès la fin de son exécution.
                    </template>
                </AlertDescription>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    class="absolute top-2 right-2"
                    aria-label="Masquer le résultat du test"
                    @click="dismissTestResult"
                >
                    <XIcon />
                </Button>
            </Alert>

            <!-- Etat vide -->
            <Empty v-if="connectors.length === 0" class="border border-dashed">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <CableIcon />
                    </EmptyMedia>
                    <EmptyTitle>Aucun connecteur</EmptyTitle>
                    <EmptyDescription>
                        Créez votre premier connecteur pour relier un service
                        externe : ses credentials seront chiffrés au repos et
                        jamais réaffichés.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button as-child>
                        <a :href="create.url()">Créer un connecteur</a>
                    </Button>
                </EmptyContent>
            </Empty>

            <!-- Tableau -->
            <div v-else class="overflow-hidden rounded-xl border">
                <Table>
                    <TableCaption class="sr-only">
                        Connecteurs : nom, type, statut, dernier test et actions
                    </TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Connecteur
                            </TableHead>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Type
                            </TableHead>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Statut
                            </TableHead>
                            <TableHead
                                class="px-4 text-xs tracking-wide uppercase"
                            >
                                Dernier test
                            </TableHead>
                            <TableHead
                                class="px-4 text-right text-xs tracking-wide uppercase"
                            >
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="connector in connectors"
                            :key="connector.id"
                        >
                            <TableCell class="px-4 py-4 align-top">
                                <p class="font-medium">
                                    {{ connector.name }}
                                </p>
                                <p
                                    class="mt-0.5 max-w-[18rem] truncate font-mono text-xs text-muted-foreground"
                                    :title="connector.baseUrl"
                                >
                                    {{ connector.baseUrl }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Créé le
                                    {{ formatCreatedAt(connector.createdAt) }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <Badge variant="outline">
                                    {{ typeLabels[connector.type] }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                <Badge
                                    :variant="statusVariants[connector.status]"
                                >
                                    {{ statusLabels[connector.status] }}
                                </Badge>
                                <p
                                    v-if="connector.lastError !== null"
                                    class="mt-1 max-w-[16rem] truncate text-xs text-destructive"
                                    :title="connector.lastError"
                                >
                                    {{ connector.lastError }}
                                </p>
                            </TableCell>
                            <TableCell class="px-4 py-4 align-top">
                                {{ formatLastTested(connector.lastTestedAt) }}
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right align-top">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :disabled="
                                            testingId !== null &&
                                            testingId !== connector.id
                                        "
                                        @click="onTest(connector)"
                                    >
                                        <Spinner
                                            v-if="testingId === connector.id"
                                            class="size-4"
                                        />
                                        Tester la connexion
                                    </Button>
                                    <a
                                        :href="
                                            edit({
                                                connector: connector.id,
                                            }).url
                                        "
                                        class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        Éditer
                                    </a>
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                class="text-destructive hover:text-destructive"
                                                :disabled="
                                                    deletingId !== null &&
                                                    deletingId !== connector.id
                                                "
                                            >
                                                <Spinner
                                                    v-if="
                                                        deletingId ===
                                                        connector.id
                                                    "
                                                    class="size-4"
                                                />
                                                Supprimer
                                            </Button>
                                        </AlertDialogTrigger>
                                        <AlertDialogContent>
                                            <AlertDialogHeader>
                                                <AlertDialogTitle>
                                                    Supprimer le connecteur «
                                                    {{ connector.name }} » ?
                                                </AlertDialogTitle>
                                                <AlertDialogDescription>
                                                    Ses credentials chiffrés
                                                    seront définitivement
                                                    effacés. Cette action est
                                                    irréversible.
                                                </AlertDialogDescription>
                                            </AlertDialogHeader>
                                            <AlertDialogFooter>
                                                <AlertDialogCancel>
                                                    Annuler
                                                </AlertDialogCancel>
                                                <AlertDialogAction
                                                    class="bg-destructive text-white hover:bg-destructive/90"
                                                    @click="
                                                        onDestroy(connector)
                                                    "
                                                >
                                                    Supprimer
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
        </div>
    </BackLayout>
</template>
