<script setup lang="ts">
// Gestion des cles d'API du module Gateway : tableau (nom, prefixe mono,
// abilities en chips, dernier usage, statut), creation avec affichage du
// secret UNE SEULE FOIS (dialogue non contournable) et revocation avec
// confirmation. Aucun import hors arbre back/.
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import type {
    ApiKey,
    ApiKeyForm,
    ApiKeyStatus,
    BackGatewayKeysPageProps,
} from '@/types';
import { API_KEY_ABILITIES } from '@/types';
import { index, revoke, store } from '@/routes/admin/gateway/keys';
import BackLayout from '../../layouts/BackLayout.vue';
import BackBadge from '../../components/BackBadge.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackField from '../../components/BackField.vue';
import BackInput from '../../components/BackInput.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackGatewayKeysPageProps>();

/* ------------------------------------------------------------------ */
/* Abilities connues                                                    */
/* ------------------------------------------------------------------ */

const abilityDescriptions: Record<string, string> = {
    'services:read':
        'Lire les modules activés de la plateforme (GET /api/v1/services).',
    'landings:read': 'Lire les landing pages publiées (GET /api/v1/landings).',
};

/* ------------------------------------------------------------------ */
/* Statut : calcule cote client depuis revokedAt / expiresAt            */
/* ------------------------------------------------------------------ */

const statusLabels: Record<ApiKeyStatus, string> = {
    active: 'Actif',
    revoked: 'Révoquée',
    expired: 'Expirée',
};

const statusVariants: Record<ApiKeyStatus, 'success' | 'danger' | 'muted'> = {
    active: 'success',
    revoked: 'danger',
    expired: 'muted',
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
   back-office (le serveur renvoie l'index actualise). */
const revokingId = ref<number | null>(null);

function onRevoke(key: ApiKey): void {
    if (
        !window.confirm(
            `Révoquer la clé « ${key.name} » ? Tout appel avec cette clé sera immédiatement rejeté.`,
        )
    ) {
        return;
    }

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
   dialogue sans issue : pas de fermeture par Echap ni par clic sur
   l'overlay, seulement « J'ai copie » qui recharge la page pour purger le
   flash. Le watch couvre la mise a jour des props du composant deja
   monte ; un flash encore vivant lors d'un chargement complet s'ouvre
   via la valeur initiale des props de page. */
const secretDialogOpen = ref(false);
const secretValue = ref('');
const copied = ref(false);
const copyFailed = ref(false);
const copyButtonEl = ref<HTMLButtonElement | null>(null);

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

const hasKeys = computed(() => props.keys.length > 0);
</script>

<template>
    <BackLayout>
        <Head title="Clés d'API" />

        <section class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1
                        class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Clés d'API
                    </h1>
                    <p class="text-sm text-[var(--color-muted)]">
                        Créez et révoquez les clés qui sécurisent les endpoints
                        <code class="font-mono">/api/v1</code> : abilities,
                        expiration et révocation immédiate.
                    </p>
                </div>

                <BackButton
                    :aria-expanded="formOpen"
                    aria-controls="new-key-form"
                    @click="formOpen ? closeForm() : openForm()"
                >
                    {{ formOpen ? 'Annuler' : 'Nouvelle clé' }}
                </BackButton>
            </header>

            <!-- Formulaire de creation -->
            <BackCard
                v-if="formOpen"
                id="new-key-form"
                title="Nouvelle clé d'API"
                description="Le secret ne sera affiché qu'une seule fois, à la création : seul son hash est conservé côté serveur."
            >
                <form class="flex flex-col gap-5" @submit.prevent="onSubmit">
                    <BackField
                        label="Nom"
                        field-id="name"
                        :error="form.errors.name"
                        help="Ex. Intégration mobile, script d'import nightly."
                    >
                        <BackInput
                            id="name"
                            name="name"
                            v-model="form.name"
                            required
                            placeholder="Ex. Intégration mobile"
                        />
                    </BackField>

                    <fieldset class="flex flex-col gap-2.5">
                        <legend class="back-label mb-1">Abilities</legend>
                        <label
                            v-for="ability in API_KEY_ABILITIES"
                            :key="ability"
                            class="flex items-start gap-2.5 text-sm text-[var(--color-ink)]"
                            :for="`ability-${ability}`"
                        >
                            <input
                                :id="`ability-${ability}`"
                                :name="`abilities[]`"
                                type="checkbox"
                                :value="ability"
                                v-model="form.abilities"
                                class="back-checkbox mt-0.5"
                            />
                            <span class="flex flex-col">
                                <span class="font-mono text-xs font-medium">
                                    {{ ability }}
                                </span>
                                <span class="text-xs text-[var(--color-muted)]">
                                    {{ abilityDescriptions[ability] }}
                                </span>
                            </span>
                        </label>
                        <p v-if="form.errors.abilities" class="back-error">
                            {{ form.errors.abilities }}
                        </p>
                    </fieldset>

                    <BackField
                        label="Expiration"
                        field-id="expires_at"
                        :error="form.errors.expires_at"
                        help="Optionnel : la clé sera rejetée automatiquement après cette date."
                    >
                        <BackInput
                            id="expires_at"
                            name="expires_at"
                            type="date"
                            v-model="form.expires_at"
                        />
                    </BackField>

                    <BackStatus tone="warning">
                        Chaque clé donne accès aux endpoints
                        <code class="font-mono">/api/v1</code> selon ses
                        abilities. Stockez son secret dans un gestionnaire de
                        secrets : il ne sera jamais réaffiché après la création.
                    </BackStatus>

                    <div class="flex items-center justify-end gap-3">
                        <BackButton variant="secondary" @click="closeForm">
                            Annuler
                        </BackButton>
                        <BackButton type="submit" :loading="form.processing">
                            Créer la clé
                        </BackButton>
                    </div>
                </form>
            </BackCard>

            <!-- Etat vide -->
            <div
                v-if="!hasKeys"
                class="back-card flex flex-col items-center gap-3 px-6 py-16 text-center"
            >
                <p class="text-base font-semibold text-[var(--color-ink)]">
                    Aucune clé API
                </p>
                <p class="max-w-sm text-sm text-[var(--color-muted)]">
                    Créez votre première clé pour consommer les endpoints
                    sécurisés du module Gateway.
                </p>
                <BackButton class="mt-2" @click="openForm">
                    Créer une clé API
                </BackButton>
            </div>

            <!-- Tableau -->
            <div v-else class="back-card overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">
                        Clés d'API : nom, préfixe, abilities, dernier usage,
                        statut et actions
                    </caption>
                    <thead>
                        <tr
                            class="border-b border-[rgba(23,23,28,0.09)] text-xs font-medium tracking-wide text-[var(--color-muted)] uppercase"
                        >
                            <th scope="col" class="px-6 py-3">Clé</th>
                            <th scope="col" class="px-6 py-3">Abilities</th>
                            <th scope="col" class="px-6 py-3">Dernier usage</th>
                            <th scope="col" class="px-6 py-3">Statut</th>
                            <th scope="col" class="px-6 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="key in props.keys"
                            :key="key.id"
                            class="border-b border-[rgba(23,23,28,0.06)] last:border-b-0"
                        >
                            <td class="px-6 py-4">
                                <p class="font-medium text-[var(--color-ink)]">
                                    {{ key.name }}
                                </p>
                                <p
                                    class="mt-0.5 font-mono text-xs text-[var(--color-muted)]"
                                >
                                    {{ key.prefix }}…
                                </p>
                                <p
                                    class="mt-0.5 text-xs text-[var(--color-muted)]"
                                >
                                    Créée le
                                    {{ formatCreatedAt(key.createdAt) }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="ability in key.abilities"
                                        :key="ability"
                                        class="back-chip"
                                    >
                                        {{ ability }}
                                    </span>
                                    <span
                                        v-if="key.abilities.length === 0"
                                        class="text-[var(--color-muted)]"
                                    >
                                        —
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[var(--color-ink)]">
                                {{ formatLastUsed(key.lastUsedAt) }}
                            </td>
                            <td class="px-6 py-4">
                                <BackBadge
                                    :variant="statusVariants[apiKeyStatus(key)]"
                                >
                                    {{ statusLabels[apiKeyStatus(key)] }}
                                </BackBadge>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-3"
                                >
                                    <BackButton
                                        v-if="apiKeyStatus(key) === 'active'"
                                        variant="danger"
                                        :loading="revokingId === key.id"
                                        :disabled="
                                            revokingId !== null &&
                                            revokingId !== key.id
                                        "
                                        @click="onRevoke(key)"
                                    >
                                        Révoquer
                                    </BackButton>
                                    <span
                                        v-else
                                        class="text-xs text-[var(--color-muted)]"
                                    >
                                        —
                                    </span>
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
                aria-labelledby="gateway-secret-title"
                aria-describedby="gateway-secret-description"
            >
                <div class="flex flex-col gap-4 px-6 py-6">
                    <h2
                        id="gateway-secret-title"
                        class="text-lg font-semibold tracking-tight text-[var(--color-ink)]"
                    >
                        Copiez ce secret maintenant
                    </h2>
                    <p
                        id="gateway-secret-description"
                        class="text-sm text-[var(--color-muted)]"
                    >
                        Il ne sera plus jamais affiché : seul son hash est
                        conservé côté serveur. Rangez-le immédiatement dans
                        votre gestionnaire de secrets.
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
