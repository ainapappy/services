<script setup lang="ts">
// Liste des connecteurs : tableau (nom + url mono tronquee, type en chip,
// statut en badge, dernier test relatif) avec test de connexion par ligne
// (spinner pendant l'aller-retour, resultat rendu en bandeau auto-
// dismissable via le flash `testResult`), edition et suppression avec
// confirmation. Aucun import hors arbre back/.
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type {
    BackConnectorsIndexPageProps,
    ConnectorListItem,
    ConnectorStatus,
    ConnectorType,
} from '@/types';
import { create, destroy, edit, test } from '@/routes/admin/connectors';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackConnectorsIndexPageProps>();

/* ------------------------------------------------------------------ */
/* Libelles et variants de la charte                                    */
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

const statusVariants: Record<ConnectorStatus, 'success' | 'danger' | 'muted'> =
    {
        connected: 'success',
        error: 'danger',
        unknown: 'muted',
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
/* Resultat du test : bandeau vert / rouge, auto-dismissable            */
/* ------------------------------------------------------------------ */

/* Le flash arrive en prop partagee par le middleware sous
   `flash.testResult` apres la redirection du test. Il est affiche puis
   masque automatiquement apres 8 s (ou via son bouton de fermeture) ;
   le watch couvre la mise a jour des props du composant deja monte,
   l'appel initial couvre un chargement complet avec le flash vivant. */
const testResult = computed(() => props.flash?.testResult ?? null);
const testResultVisible = ref(testResult.value !== null);
let testResultTimer: number | undefined;

/* Succes vert, echec rouge, test en file d'attente neutre (info). */
const testResultBannerTone = computed<'success' | 'danger' | 'info'>(() => {
    if (testResult.value?.status === 'connected') {
        return 'success';
    }

    return testResult.value?.status === 'error' ? 'danger' : 'info';
});

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
    if (
        !window.confirm(
            `Supprimer le connecteur « ${connector.name} » ? Ses credentials chiffrés seront définitivement effacés. Cette action est irréversible.`,
        )
    ) {
        return;
    }

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

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Connecteurs
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Intégrez vos services externes (WordPress, Caren, Erpen
                        ou API libre) : credentials chiffrés au repos et test de
                        connexion manuel.
                    </p>
                </div>

                <a
                    :href="create.url()"
                    class="back-btn back-btn--primary shrink-0"
                >
                    Nouveau connecteur
                </a>
            </header>

            <!-- Resultat du dernier test de connexion : vert (succes,
                 avec latence), rouge (echec, message sur) ou neutre
                 (test parti en file d'attente, resultat differe). -->
            <BackStatus
                v-if="testResult !== null && testResultVisible"
                :tone="testResultBannerTone"
            >
                <span class="flex-1">
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
                </span>
                <button
                    type="button"
                    class="back-test-banner-dismiss"
                    aria-label="Masquer le résultat du test"
                    @click="dismissTestResult"
                >
                    ×
                </button>
            </BackStatus>

            <!-- Etat vide -->
            <div
                v-if="connectors.length === 0"
                class="back-card flex flex-col items-center gap-3 px-6 py-16 text-center"
            >
                <p class="text-base font-semibold text-[var(--color-ink)]">
                    Aucun connecteur
                </p>
                <p class="max-w-sm text-sm text-[var(--color-muted)]">
                    Créez votre premier connecteur pour relier un service
                    externe : ses credentials seront chiffrés au repos et jamais
                    réaffichés.
                </p>
                <a :href="create.url()" class="back-btn back-btn--primary mt-2">
                    Créer un connecteur
                </a>
            </div>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Connecteurs : nom, type, statut, dernier test et actions
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Connecteur</th>
                            <th scope="col" class="px-6 py-3">Type</th>
                            <th scope="col" class="px-6 py-3">Statut</th>
                            <th scope="col" class="px-6 py-3">Dernier test</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="connector in connectors"
                            :key="connector.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-[var(--color-ink)]">
                                    {{ connector.name }}
                                </p>
                                <p
                                    class="mt-0.5 max-w-[18rem] truncate font-mono text-xs text-[var(--color-muted)]"
                                    :title="connector.baseUrl"
                                >
                                    {{ connector.baseUrl }}
                                </p>
                                <p
                                    class="mt-0.5 text-xs text-[var(--color-muted)]"
                                >
                                    Créé le
                                    {{ formatCreatedAt(connector.createdAt) }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="back-chip">
                                    {{ typeLabels[connector.type] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="statusVariants[connector.status]"
                                >
                                    {{ statusLabels[connector.status] }}
                                </BackBadge>
                                <p
                                    v-if="connector.lastError !== null"
                                    class="back-error mt-1 max-w-[16rem] truncate"
                                    :title="connector.lastError"
                                >
                                    {{ connector.lastError }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-[var(--color-ink)]">
                                {{ formatLastTested(connector.lastTestedAt) }}
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <BackButton
                                        :loading="testingId === connector.id"
                                        :disabled="
                                            testingId !== null &&
                                            testingId !== connector.id
                                        "
                                        @click="onTest(connector)"
                                    >
                                        Tester la connexion
                                    </BackButton>
                                    <a
                                        :href="
                                            edit({
                                                connector: connector.id,
                                            }).url
                                        "
                                        class="back-link"
                                    >
                                        Éditer
                                    </a>
                                    <BackButton
                                        variant="danger"
                                        :loading="deletingId === connector.id"
                                        :disabled="
                                            deletingId !== null &&
                                            deletingId !== connector.id
                                        "
                                        @click="onDestroy(connector)"
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
    </BackLayout>
</template>
