<script setup lang="ts">
// Formulaire de creation / edition d'un connecteur : identite (nom, type,
// url de base) puis champs de credentials ADAPTES au type choisi (mot de
// passe d'application WordPress, cle/secret Caren et Erpen, JSON libre
// Custom). Les credentials ne sont jamais rapatries : en edition, les
// champs vident signifient « conserver l'existant » (placeholders bulles).
// Aucun import hors arbre back/.
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { BackConnectorsFormPageProps, ConnectorType } from '@/types';
import { index, store, update } from '@/routes/admin/connectors';
import BackLayout from '../../layouts/BackLayout.vue';
import BackButton from '../../components/BackButton.vue';
import BackCard from '../../components/BackCard.vue';
import BackField from '../../components/BackField.vue';
import BackInput from '../../components/BackInput.vue';
import BackStatus from '../../components/BackStatus.vue';

const props = defineProps<BackConnectorsFormPageProps>();

/* ------------------------------------------------------------------ */
/* Etat edite                                                          */
/* ------------------------------------------------------------------ */

const existing = computed(() => props.connector ?? null);
const isEdit = computed(() => existing.value !== null);

const typeOptions: Array<{
    value: ConnectorType;
    label: string;
    description: string;
}> = [
    {
        value: 'wordpress',
        label: 'WordPress',
        description: 'API REST native : mot de passe d’application.',
    },
    {
        value: 'caren',
        label: 'Caren',
        description: 'Clé API et secret fournis par la console Caren.',
    },
    {
        value: 'erpen',
        label: 'Erpen',
        description: 'Clé API et secret fournis par la console Erpen.',
    },
    {
        value: 'custom',
        label: 'Custom',
        description: 'Service libre : objet JSON de credentials.',
    },
];

/**
 * Donnees du formulaire. Les champs de credentials ne partent PAS tels
 * quels : la soumission les transforme en objet `credentials` dont la
 * forme depend du type (voir collectCredentials).
 */
type ConnectorFormData = {
    name: string;
    type: ConnectorType;
    base_url: string;
    username: string;
    password: string;
    api_key: string;
    secret: string;
    credentials_json: string;
};

function toFormData(value: typeof existing.value): ConnectorFormData {
    return {
        name: value?.name ?? '',
        type: value?.type ?? 'wordpress',
        base_url: value?.baseUrl ?? '',
        /* Les secrets ne sont jamais pre-remplis : le serveur ne les
           renvoie pas, des champs vides = conserver l'existant. */
        username: '',
        password: '',
        api_key: '',
        secret: '',
        credentials_json: '',
    };
}

const form = useForm<ConnectorFormData>(toFormData(existing.value));

/* Navigation creation -> edition (meme composant reutilise par Inertia) :
   on resynchronise le formulaire quand la page cible change. */
watch(
    () => props.connector?.id,
    () => {
        const next = toFormData(existing.value);
        form.name = next.name;
        form.type = next.type;
        form.base_url = next.base_url;
        form.username = '';
        form.password = '';
        form.api_key = '';
        form.secret = '';
        form.credentials_json = '';
        form.clearErrors();
    },
);

/* Changement de type : les erreurs de credentials ne s'appliquent plus
   et les champs secrets repassent masques. */
watch(
    () => form.type,
    () => {
        showPassword.value = false;
        showApiKey.value = false;
        showSecret.value = false;
        form.clearErrors(
            'username',
            'password',
            'api_key',
            'secret',
            'credentials_json',
        );
    },
);

/* ------------------------------------------------------------------ */
/* Affichage / masquage des champs secrets                              */
/* ------------------------------------------------------------------ */

const showPassword = ref(false);
const showApiKey = ref(false);
const showSecret = ref(false);

/* ------------------------------------------------------------------ */
/* Aides contextuelles                                                  */
/* ------------------------------------------------------------------ */

/* Les erreurs de validation imbriquees (ex. `credentials.username`)
   sortent des cles du formulaire local : lecture via un objet plat de
   chaines, comme le renvoie le sac d'erreurs Laravel. */
const serverErrors = computed(
    () => form.errors as Partial<Record<string, string>>,
);

/** Erreur serveur sur un champ credentials (cle imbriquee ou plate). */
function credentialError(field: string): string | undefined {
    return (
        serverErrors.value[`credentials.${field}`] ?? serverErrors.value[field]
    );
}

const keepHint = 'Laissez vide pour conserver les credentials actuels.';

const usernameHelp = computed(() =>
    isEdit.value
        ? keepHint
        : 'Identifiant utilisé pour authentifier l’API REST du site.',
);

const passwordHelp = computed(() =>
    isEdit.value
        ? keepHint
        : 'Généré dans WordPress : Utilisateurs → Profil → Mots de passe d’application.',
);

const apiKeyHelp = computed(() =>
    isEdit.value ? keepHint : 'Clé fournie par la console du service.',
);

const secretHelp = computed(() =>
    isEdit.value ? keepHint : 'Secret associé à la clé API.',
);

const jsonHelp = computed(() =>
    isEdit.value
        ? 'Laissez vide pour conserver les credentials actuels, ou collez un nouvel objet JSON pour les remplacer.'
        : 'Objet JSON libre transmis tel quel au connecteur.',
);

const jsonPlaceholder = computed(() =>
    isEdit.value ? '••••••••' : '{ "token": "..." }',
);

/* ------------------------------------------------------------------ */
/* Collecte des credentials : valide et faconne la charge utile         */
/* ------------------------------------------------------------------ */

function isPlainObject(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/** Valide la saisie JSON libre (Custom) et renvoie l'objet, ou null. */
function parseCustomJson(raw: string): Record<string, unknown> | null {
    let parsed: unknown;

    try {
        parsed = JSON.parse(raw);
    } catch {
        form.setError(
            'credentials_json',
            'JSON invalide : vérifiez la syntaxe (guillemets, virgules).',
        );
        return null;
    }

    if (!isPlainObject(parsed)) {
        form.setError(
            'credentials_json',
            'Saisissez un objet JSON, par exemple { "token": "..." }.',
        );
        return null;
    }

    return parsed;
}

type CredentialsOutcome =
    | { action: 'send'; credentials: Record<string, unknown> }
    | { action: 'omit' }
    | { action: 'invalid' };

/**
 * Construit l'objet `credentials` envoye au backend, selon le type :
 * - creation : tous les champs du type sont requis (erreur inline sinon) ;
 * - edition : champs vides = { action: 'omit' } (conserver l'existant),
 *   champs renseignes = remplacement, champ par champ.
 * Retourne { action: 'invalid' } si la validation client echoue.
 */
function collectCredentials(): CredentialsOutcome {
    if (form.type === 'custom') {
        const raw = form.credentials_json.trim();

        if (raw === '') {
            if (isEdit.value) {
                return { action: 'omit' };
            }

            form.setError(
                'credentials_json',
                'Saisissez les credentials au format JSON.',
            );
            return { action: 'invalid' };
        }

        const parsed = parseCustomJson(form.credentials_json);

        return parsed === null
            ? { action: 'invalid' }
            : { action: 'send', credentials: parsed };
    }

    const username = form.username.trim();
    const password = form.password;
    const apiKey = form.api_key.trim();
    const secret = form.secret;

    if (form.type === 'wordpress') {
        if (isEdit.value && username === '' && password === '') {
            return { action: 'omit' };
        }

        if (!isEdit.value && (username === '' || password === '')) {
            if (username === '') {
                form.setError('username', 'Le nom d’utilisateur est requis.');
            }

            if (password === '') {
                form.setError(
                    'password',
                    'Le mot de passe d’application est requis.',
                );
            }

            return { action: 'invalid' };
        }

        const credentials: Record<string, unknown> = {};

        if (username !== '') {
            credentials.username = username;
        }

        if (password !== '') {
            credentials.password = password;
        }

        return { action: 'send', credentials };
    }

    /* caren / erpen : meme contrat cle + secret. */
    if (isEdit.value && apiKey === '' && secret === '') {
        return { action: 'omit' };
    }

    if (!isEdit.value && (apiKey === '' || secret === '')) {
        if (apiKey === '') {
            form.setError('api_key', 'La clé API est requise.');
        }

        if (secret === '') {
            form.setError('secret', 'Le secret est requis.');
        }

        return { action: 'invalid' };
    }

    const credentials: Record<string, unknown> = {};

    if (apiKey !== '') {
        credentials.api_key = apiKey;
    }

    if (secret !== '') {
        credentials.secret = secret;
    }

    return { action: 'send', credentials };
}

/* ------------------------------------------------------------------ */
/* Soumission                                                           */
/* ------------------------------------------------------------------ */

function onSubmit(): void {
    const outcome = collectCredentials();

    if (outcome.action === 'invalid') {
        return;
    }

    form.transform((data) => {
        const payload: Record<string, unknown> = {
            name: data.name,
            type: data.type,
            base_url: data.base_url,
        };

        /* En edition, credentials omis = conserver l'existant. */
        if (outcome.action === 'send') {
            payload.credentials = outcome.credentials;
        }

        return payload;
    });

    if (isEdit.value && existing.value !== null) {
        void form.patch(update({ connector: existing.value.id }).url);
        return;
    }

    void form.post(store.url());
}
</script>

<template>
    <BackLayout>
        <Head
            :title="isEdit ? 'Modifier le connecteur' : 'Nouveau connecteur'"
        />

        <section class="flex flex-col gap-6">
            <header class="flex flex-col gap-1">
                <p class="text-sm text-[var(--color-muted)]">
                    <a :href="index.url()" class="back-link"> Connecteurs </a>
                    <span class="mx-1">/</span>
                    <span>{{
                        isEdit ? existing?.name : 'Nouveau connecteur'
                    }}</span>
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight text-[var(--color-ink)]"
                >
                    {{
                        isEdit ? 'Modifier le connecteur' : 'Nouveau connecteur'
                    }}
                </h1>
            </header>

            <form class="flex flex-col gap-6" @submit.prevent="onSubmit">
                <!-- Identite -->
                <BackCard
                    title="Identité"
                    description="Nom affiché dans le back-office, type d'intégration et adresse du service."
                >
                    <div class="flex flex-col gap-5">
                        <BackField
                            label="Nom"
                            field-id="name"
                            :error="form.errors.name"
                            help="Ex. Blog vitrine, Facturation Erpen."
                        >
                            <BackInput
                                id="name"
                                name="name"
                                v-model="form.name"
                                required
                                placeholder="Ex. Blog vitrine"
                            />
                        </BackField>

                        <BackField
                            label="Type"
                            field-id="type"
                            :error="form.errors.type"
                            help="Les champs de credentials s'adaptent au type choisi."
                        >
                            <select
                                id="type"
                                name="type"
                                v-model="form.type"
                                class="back-input back-select"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <p class="back-help mt-1">
                                {{
                                    typeOptions.find(
                                        (option) => option.value === form.type,
                                    )?.description
                                }}
                            </p>
                        </BackField>

                        <BackField
                            label="URL de base"
                            field-id="base_url"
                            :error="form.errors.base_url"
                            help="Le test de connexion pingue cette adresse."
                        >
                            <BackInput
                                id="base_url"
                                name="base_url"
                                type="url"
                                v-model="form.base_url"
                                required
                                placeholder="https://exemple.com"
                            />
                        </BackField>
                    </div>
                </BackCard>

                <!-- Credentials : champs adaptes au type -->
                <BackCard
                    title="Credentials"
                    :description="
                        isEdit
                            ? 'Les credentials actuels ne sont jamais réaffichés.'
                            : 'Champs adaptés au type de connecteur.'
                    "
                >
                    <div class="flex flex-col gap-5">
                        <BackStatus>
                            Les credentials sont chiffrés au repos et jamais
                            réaffichés : le serveur ne les renvoie jamais au
                            navigateur.

                            <template v-if="isEdit">
                                Laissez les champs vides pour conserver les
                                credentials actuels, ou saisissez de nouvelles
                                valeurs pour les remplacer.
                            </template>
                        </BackStatus>

                        <!-- WordPress : nom d'utilisateur + mot de passe
                             d'application -->
                        <template v-if="form.type === 'wordpress'">
                            <BackField
                                label="Nom d'utilisateur"
                                field-id="username"
                                :error="credentialError('username')"
                                :help="usernameHelp"
                            >
                                <BackInput
                                    id="username"
                                    name="username"
                                    v-model="form.username"
                                    :required="!isEdit"
                                    autocomplete="off"
                                    :placeholder="
                                        isEdit ? '••••••••' : 'Ex. admin'
                                    "
                                />
                            </BackField>

                            <BackField
                                label="Mot de passe d'application"
                                field-id="password"
                                :error="credentialError('password')"
                                :help="passwordHelp"
                            >
                                <div class="relative">
                                    <BackInput
                                        id="password"
                                        name="password"
                                        v-model="form.password"
                                        :type="
                                            showPassword ? 'text' : 'password'
                                        "
                                        class="back-input--secret"
                                        :required="!isEdit"
                                        autocomplete="new-password"
                                        :placeholder="
                                            isEdit ? '••••••••' : undefined
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="back-secret-toggle"
                                        :aria-pressed="showPassword"
                                        :aria-label="
                                            showPassword
                                                ? 'Masquer le mot de passe'
                                                : 'Afficher le mot de passe'
                                        "
                                        @click="showPassword = !showPassword"
                                    >
                                        {{
                                            showPassword
                                                ? 'Masquer'
                                                : 'Afficher'
                                        }}
                                    </button>
                                </div>
                            </BackField>
                        </template>

                        <!-- Caren / Erpen : cle API + secret -->
                        <template
                            v-else-if="
                                form.type === 'caren' || form.type === 'erpen'
                            "
                        >
                            <BackField
                                label="Clé API"
                                field-id="api_key"
                                :error="credentialError('api_key')"
                                :help="apiKeyHelp"
                            >
                                <div class="relative">
                                    <BackInput
                                        id="api_key"
                                        name="api_key"
                                        v-model="form.api_key"
                                        :type="showApiKey ? 'text' : 'password'"
                                        class="back-input--secret"
                                        :required="!isEdit"
                                        autocomplete="off"
                                        :placeholder="
                                            isEdit ? '••••••••' : undefined
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="back-secret-toggle"
                                        :aria-pressed="showApiKey"
                                        :aria-label="
                                            showApiKey
                                                ? 'Masquer la clé API'
                                                : 'Afficher la clé API'
                                        "
                                        @click="showApiKey = !showApiKey"
                                    >
                                        {{
                                            showApiKey ? 'Masquer' : 'Afficher'
                                        }}
                                    </button>
                                </div>
                            </BackField>

                            <BackField
                                label="Secret"
                                field-id="secret"
                                :error="credentialError('secret')"
                                :help="secretHelp"
                            >
                                <div class="relative">
                                    <BackInput
                                        id="secret"
                                        name="secret"
                                        v-model="form.secret"
                                        :type="showSecret ? 'text' : 'password'"
                                        class="back-input--secret"
                                        :required="!isEdit"
                                        autocomplete="new-password"
                                        :placeholder="
                                            isEdit ? '••••••••' : undefined
                                        "
                                    />
                                    <button
                                        type="button"
                                        class="back-secret-toggle"
                                        :aria-pressed="showSecret"
                                        :aria-label="
                                            showSecret
                                                ? 'Masquer le secret'
                                                : 'Afficher le secret'
                                        "
                                        @click="showSecret = !showSecret"
                                    >
                                        {{
                                            showSecret ? 'Masquer' : 'Afficher'
                                        }}
                                    </button>
                                </div>
                            </BackField>
                        </template>

                        <!-- Custom : JSON libre valide cote client -->
                        <template v-else>
                            <BackField
                                label="Credentials (JSON)"
                                field-id="credentials_json"
                                :error="
                                    serverErrors.credentials ??
                                    serverErrors.credentials_json
                                "
                                :help="jsonHelp"
                            >
                                <textarea
                                    id="credentials_json"
                                    name="credentials_json"
                                    v-model="form.credentials_json"
                                    class="back-input back-textarea"
                                    spellcheck="false"
                                    :placeholder="jsonPlaceholder"
                                />
                            </BackField>
                        </template>
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
                                : 'Créer le connecteur'
                        }}
                    </BackButton>
                </div>
            </form>
        </section>
    </BackLayout>
</template>
